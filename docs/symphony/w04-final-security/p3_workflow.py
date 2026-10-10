#!/usr/bin/env python3
"""W04 final security phase 3: own commerce/travel fixtures, server-contract/side-effect matrix and sustained HTTP races.

Usage: python3 -I p3_workflow.py <private-dir> <out-dir> <mode>
  modes: fixtures | matrix | races | cleanup
- Fixtures are created only through native admin HTTP APIs (SKU prefix W04F-...); the existing free-KR shipping policy is
  reused read-only. No order/payment/checkout call. No direct writes; barrier = FOR UPDATE read on own rows only.
"""
import datetime, json, os, secrets, subprocess, sys, threading, time
from zoneinfo import ZoneInfo
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from w04f_common import EC, HERE, TL, Ctx, check, data, msg, rows  # noqa: E402

PRIV, OUT, MODE = sys.argv[1:4]
C = Ctx(PRIV, OUT, 'p3-' + MODE + '-' + time.strftime('%H%M%S', time.gmtime()))
STATE = os.path.join(OUT, 'p3-state.json')
S = json.load(open(STATE)) if os.path.exists(STATE) else {}
KST = datetime.datetime.now(ZoneInfo('Asia/Seoul')).date()
day = lambda n: (KST + datetime.timedelta(days=n)).isoformat()
USERS = None


def save():
    with open(STATE, 'w') as f:
        json.dump(S, f)
    os.chmod(STATE, 0o600)


def contact():
    return {'name': 'Synthetic ' + secrets.token_hex(3)}


def opt_payload(code, stock=5, oid=None, adjustment=0):
    o = {'price_adjustment': adjustment, 'option_code': code, 'option_name': {'ko': code, 'en': code},
         'option_values': [{'key': {'ko': '출발', 'en': 'Departure'}, 'value': {'ko': code, 'en': code}}],
         'list_price': 20000, 'selling_price': 12000, 'stock_quantity': stock, 'is_active': True, 'is_default': code.endswith('-1')}
    if oid:
        o['id'] = oid
    return o


def dep_rows(ids):
    return {int(d['id']): d for d in C.dbq('departures', ids=list(ids))['departures']}


def cart_items(role):
    st, j, _ = C.req('GET', TL + '/cart', role=role)
    d = data(j) or {}
    return st, (d.get('items') if isinstance(d, dict) else d) or [], d


def add_cart(role, dep, qty):
    st, j, _ = C.req('POST', TL + '/cart', role=role, body={'departure_id': dep, 'quantity': qty})
    check(st in (200, 201), f'cart add {role} {st} {msg(j)}')
    _, items, _ = cart_items(role)
    return next(int(i['id']) for i in items if int(i.get('departure_id') or 0) == dep)


def submit(role, cart_ids, key, c=None):
    return C.req('POST', TL + '/inquiries', role=role, body={'cart_ids': cart_ids, 'contact': c or contact(), 'idempotency_key': key}, headers={'Idempotency-Key': key})


def deadlocks():
    return C.dbq('floors')['innodb_deadlocks_global']


if MODE == 'fixtures':
    S.update({'run': 'W04F-' + time.strftime('%H%M%S', time.gmtime()), 'floors': C.dbq('floors')['max_ids'], 'counts_before': C.dbq('counts', users=list(C.uid.values()))['counts']})
    save()

    def f_all():
        lst = data(C.req('GET', EC + '/admin/shipping-policies?per_page=50', role='admin')[1])
        pol = lst.get('data', lst) if isinstance(lst, dict) else lst
        free = [p for p in pol if p.get('is_active') and not p.get('is_default') and p.get('country_settings') and all(
            c.get('charge_policy') == 'free' and float(c.get('base_fee') or 0) == 0 and not c.get('extra_fee_enabled') and not c.get('api_endpoint') for c in p['country_settings'])
            and any(c.get('country_code') == 'KR' and c.get('is_active') for c in p['country_settings'])]
        check(free, 'no reusable free-KR policy')
        S['policy'] = free[0]['id']
        st, j, _ = C.req('POST', EC + '/admin/categories', role='admin', body={'name': {'ko': S['run'] + ' 분류', 'en': S['run'] + ' category'}, 'slug': S['run'].lower(), 'is_active': True})
        check(st == 201, f'category {st} {msg(j)}')
        S['category'] = data(j)['id']
        sku = S['run'] + '-T'
        opts = [opt_payload(f'{sku}-{i}') for i in range(1, 9)]
        payload = {'name': {'ko': sku + ' 합성 상품', 'en': sku + ' synthetic'}, 'product_code': sku.replace('-', ''), 'sku': sku, 'category_ids': [S['category']],
                   'list_price': 20000, 'selling_price': 12000, 'stock_quantity': sum(o['stock_quantity'] for o in opts), 'sales_status': 'on_sale', 'display_status': 'visible',
                   'tax_status': 'tax_free', 'shipping_policy_id': S['policy'], 'has_options': True, 'options': opts}
        st, j, _ = C.req('POST', EC + '/admin/products', role='admin', body=payload)
        check(st == 201, f'product {st} {msg(j)} {j.get("errors") if isinstance(j, dict) else None}')
        S['product'] = data(j)['id']
        S['payload'] = payload
        d = data(C.req('GET', f"{EC}/admin/products/{S['product']}", role='admin')[1])
        S['options'] = [o['id'] for o in d['options']]
        st, j, _ = C.req('POST', TL + '/admin/catalog', role='admin', body={'product_id': S['product'], 'region': 'jeju', 'theme': 'nature', 'duration_days': 2,
                                                                           'summary': {'ko': S['run'] + ' 합성 요약', 'en': S['run'] + ' synthetic summary'}, 'published': True})
        check(st in (200, 201), f'catalog register {st} {msg(j)}')
        plan = {'price': 3, 'qty': 4, 'state': 3, 'last': 1, 'key': 3, 'cd': 2, 'low': 2, 'spare': 2}
        S['dep'] = {}
        for (name, cap), oid in zip(plan.items(), S['options']):
            st, j, _ = C.req('POST', f"{TL}/admin/catalog/{S['product']}/departures", role='admin',
                             body={'product_option_id': oid, 'departure_date': day(10), 'return_date': day(11), 'capacity': cap, 'is_active': True})
            check(st in (200, 201), f'departure {name} {st} {msg(j)}')
            S['dep'][name] = data(j)['id']
        save()
        return 'PASS', {'policy_reused_read_only': S['policy'], 'category': S['category'], 'product': S['product'], 'options': S['options'], 'departures': S['dep'],
                        'sql_departures': dep_rows(S['dep'].values())}
    C.step('own fixtures via native admin APIs: category, 8-option product, catalog registration, 8 unique departures', f_all)

elif MODE == 'matrix':
    D, P = S['dep'], S['product']
    m = {}

    def r(name, fn):
        m[name] = C.step(name, fn)

    def m1():
        dep = dep_rows([D['price']])[D['price']]
        cid = add_cart('member', D['price'], 1)
        _, items, cart = cart_items('member')
        it = next(i for i in items if int(i['departure_id']) == D['price'])
        expected = float(dep['product_selling_price']) + float(dep['price_adjustment'] or 0)
        pub = rows(C.req('GET', f'{TL}/catalog/{P}/departures')[1])
        pd = next((x for x in pub if int(x['id']) == D['price']), {})
        S['m_cart_price'] = cid
        save()
        check(float(it['unit_price']) == expected and float(pd.get('unit_price', -1)) == expected, (it.get('unit_price'), pd.get('unit_price'), expected))
        return 'PASS', {'sql_product_price': dep['product_selling_price'], 'sql_adjustment': dep['price_adjustment'], 'expected': expected, 'cart_unit_price': it['unit_price'],
                        'public_catalog_unit_price': pd.get('unit_price'), 'currency': cart.get('currency_code') if isinstance(cart, dict) else None}
    r('M01 server native price: cart/catalog unit = native product price + option adjustment (SQL)', m1)

    def m2():
        prod = data(C.req('GET', f'{EC}/admin/products/{P}', role='admin')[1])
        opt_id = dep_rows([D['price']])[D['price']]['product_option_id']
        opts = [opt_payload(o['option_code'], stock=int(o['stock_quantity']), oid=o['id'], adjustment=(700 if o['id'] == opt_id else float(o.get('price_adjustment') or 0))) for o in prod['options']]
        st, j, _ = C.req('PUT', f'{EC}/admin/products/{P}', role='admin', body={**S['payload'], 'options': opts})
        check(st == 200, f'native option edit {st} {msg(j)}')
        _, items, _ = cart_items('member')
        it = next(i for i in items if int(i['departure_id']) == D['price'])
        dep = dep_rows([D['price']])[D['price']]
        exp = float(dep['product_selling_price']) + float(dep['price_adjustment'])
        check(float(dep['price_adjustment']) == 700 and float(it['unit_price']) == exp == 12700, (dep['price_adjustment'], it['unit_price'], exp))
        return 'PASS', {'native_put': st, 'sql_adjustment_after': dep['price_adjustment'], 'cart_unit_price_after': it['unit_price'], 'expected': exp, 'option_ids_preserved': sorted(o['id'] for o in prod['options']) == sorted(S['options'])}
    r('M02 available native option price change is followed by server cart pricing (no client price)', m2)

    def m3():
        cid = S['m_cart_price']
        st, j, _ = C.req('PATCH', f'{TL}/cart/{cid}', role='member', body={'quantity': 2})
        _, items, _ = cart_items('member')
        it = next(i for i in items if int(i['id']) == cid)
        before = dep_rows([D['price']])[D['price']]
        key = S['run'] + '-m3-' + secrets.token_hex(3)
        c = contact()
        s1, j1, _ = submit('member', [cid], key, c)
        iid = (data(j1) or {}).get('id')
        S['inq_m3'], S['key_m3'], S['contact_m3'] = iid, key, c
        save()
        after = dep_rows([D['price']])[D['price']]
        sql = C.dbq('inquiry_items', ids=[iid], users=[C.uid['member']])['items']
        inq = C.dbq('inquiries', users=[C.uid['member']], min_id=iid)['inquiries'][0]
        check(st == 200 and float(it['line_total']) == 2 * float(it['unit_price']) == 25400 and s1 == 201, (st, it.get('line_total'), s1, msg(j1)))
        check(float(sql[0]['line_total']) == 25400 and int(sql[0]['quantity']) == 2 and float(inq['total_amount']) == 25400 and inq['status'] == 'TEST_INQUIRY', (sql, inq))
        check(int(after['reserved']) == int(before['reserved']) + 2, (before['reserved'], after['reserved']))
        return 'PASS', {'cart_patch': st, 'cart_line_total': it['line_total'], 'submit': s1, 'inquiry_id': iid, 'sql_item': sql[0], 'sql_inquiry': {k: inq[k] for k in ('status', 'total_amount', 'currency_code', 'qty', 'events')},
                        'reserved_before_after': [before['reserved'], after['reserved']]}
    r('M03 quantity x server unit = line/total; M08 reservation increments by exact quantity (SQL)', m3)

    def m4():
        base = {'departure_id': D['qty']}
        s = {k: C.req('POST', TL + '/cart', role='member', body={**base, **v})[0] for k, v in {
            'qty0': {'quantity': 0}, 'qty_neg': {'quantity': -1}, 'qty_frac': {'quantity': 1.5}, 'qty_str': {'quantity': 'abc'}, 'qty_huge': {'quantity': 100000},
            'unit_price': {'quantity': 1, 'unit_price': 1}, 'price': {'quantity': 1, 'price': 0}, 'total_amount': {'quantity': 1, 'total_amount': 1}, 'user_id': {'quantity': 1, 'user_id': C.uid['other_member']}}.items()}
        over = C.req('POST', TL + '/cart', role='member', body={**base, 'quantity': 5})
        ghost = C.req('POST', TL + '/cart', role='member', body={'departure_id': 999999999, 'quantity': 1})[0]
        _, items, _ = cart_items('member')
        check(all(v == 422 for v in s.values()) and over[0] == 409 and ghost in (404, 409, 422) and not any(int(i['departure_id']) == D['qty'] for i in items), (s, over[0], ghost))
        return 'PASS', {'tamper': s, 'over_capacity_qty5_of_cap4': [over[0], msg(over[1])], 'unknown_departure': ghost, 'no_cart_row_created': True}
    r('M04 quantity/money/owner tamper rejected (422), over-capacity 409, nothing written', m4)

    def m5():
        b = {'product_option_id': S['options'][7], 'capacity': 1}
        res = {'kst_today': C.req('POST', f'{TL}/admin/catalog/{P}/departures', role='admin', body={**b, 'departure_date': KST.isoformat(), 'return_date': KST.isoformat()})[0],
               'past': C.req('POST', f'{TL}/admin/catalog/{P}/departures', role='admin', body={**b, 'departure_date': day(-3), 'return_date': day(-2)})[0],
               'return_before': C.req('POST', f'{TL}/admin/catalog/{P}/departures', role='admin', body={**b, 'departure_date': day(5), 'return_date': day(4)})[0],
               'reserved_field': C.req('POST', f'{TL}/admin/catalog/{P}/departures', role='admin', body={**b, 'departure_date': day(5), 'return_date': day(6), 'reserved': 1})[0],
               'unit_price_field': C.req('POST', f'{TL}/admin/catalog/{P}/departures', role='admin', body={**b, 'departure_date': day(5), 'return_date': day(6), 'unit_price': 1})[0],
               'member_forbidden': C.req('POST', f'{TL}/admin/catalog/{P}/departures', role='member', body={**b, 'departure_date': day(5), 'return_date': day(6)})[0]}
        n = len(rows(C.req('GET', f'{TL}/admin/catalog/{P}/departures', role='admin')[1]))
        check(res['kst_today'] == res['past'] == res['return_before'] == res['reserved_field'] == res['unit_price_field'] == 422 and res['member_forbidden'] == 403 and n == 8, (res, n))
        return 'PASS', {**res, 'kst_today': KST.isoformat(), 'departures_still': n}
    r('M05 date/tamper on departure: KST-today/past/return-before/reserved/unit_price 422, member 403', m5)

    def m6():
        cid_o = add_cart('other_member', D['spare'], 1)
        foreign = submit('member', [cid_o], S['run'] + '-m6-' + secrets.token_hex(3))
        money = C.req('POST', TL + '/inquiries', role='member', body={'cart_ids': [cid_o], 'contact': contact(), 'idempotency_key': S['run'] + '-m6b', 'total_amount': 1}, headers={'Idempotency-Key': S['run'] + '-m6b'})
        owner = C.req('POST', TL + '/inquiries', role='member', body={'cart_ids': [cid_o], 'contact': contact(), 'idempotency_key': S['run'] + '-m6c', 'user_id': C.uid['other_member']}, headers={'Idempotency-Key': S['run'] + '-m6c'})
        _, items, _ = cart_items('other_member')
        dep = dep_rows([D['spare']])[D['spare']]
        C.req('DELETE', f'{TL}/cart/{cid_o}', role='other_member')
        check(foreign[0] in (404, 409, 422) and money[0] == 422 and owner[0] == 422 and any(int(i['id']) == cid_o for i in items) and int(dep['reserved']) == 0, (foreign[0], money[0], owner[0]))
        return 'PASS', {'foreign_cart_submit': [foreign[0], msg(foreign[1])], 'client_total_amount': money[0], 'client_user_id': owner[0], 'foreign_cart_kept': True, 'reserved_unchanged': dep['reserved']}
    r('M06 inquiry with foreign cart / client money / owner field rejected; nothing reserved', m6)

    def m7():
        iid = S['inq_m3']
        rp = submit('member', [S['m_cart_price']], S['key_m3'], S['contact_m3'])
        ch = submit('member', [S['m_cart_price']], S['key_m3'], contact())
        fo = [C.req('GET', f'{TL}/inquiries/{iid}', role='other_member')[0], C.req('POST', f'{TL}/inquiries/{iid}/cancel', role='other_member')[0]]
        ma = [C.req('GET', f'{TL}/admin/inquiries', role='member')[0], C.req('PATCH', f'{TL}/admin/inquiries/{iid}', role='member', body={'status': 'UNDER_REVIEW'})[0]]
        t1 = C.req('PATCH', f'{TL}/admin/inquiries/{iid}', role='admin', body={'status': 'UNDER_REVIEW'})
        t2 = C.req('PATCH', f'{TL}/admin/inquiries/{iid}', role='admin', body={'status': 'TEST_ACCEPTED'})
        bad = C.req('PATCH', f'{TL}/admin/inquiries/{iid}', role='admin', body={'status': 'UNDER_REVIEW'})
        tamper = C.req('PATCH', f'{TL}/admin/inquiries/{iid}', role='admin', body={'status': 'TEST_ACCEPTED', 'total_amount': 1})
        inq = C.dbq('inquiries', users=[C.uid['member']], min_id=iid)['inquiries'][0]
        check(rp[0] == 200 and (data(rp[1]) or {}).get('id') == iid and ch[0] == 409, (rp[0], ch[0]))
        check(fo == [404, 404] and ma == [403, 403] and t1[0] == 200 and t2[0] == 200 and bad[0] == 409 and tamper[0] == 422, (fo, ma, t1[0], t2[0], bad[0], tamper[0]))
        check(inq['status'] == 'TEST_ACCEPTED' and float(inq['total_amount']) == 25400, inq)
        return 'PASS', {'same_key_replay': [rp[0], 'same_id'], 'changed_payload_same_key': [ch[0], msg(ch[1])], 'foreign_show_cancel': fo, 'member_admin_list_patch': ma,
                        'admin_transitions': [t1[0], t2[0]], 'invalid_back_transition': [bad[0], msg(bad[1])], 'admin_money_tamper': tamper[0], 'sql_inquiry': {k: inq[k] for k in ('status', 'total_amount', 'events')}}
    r('M07 idempotent replay/409, foreign 404, member admin 403, TEST state transitions and invalid 409 (SQL)', m7)

    def m9():
        D2 = D['state']
        c1 = add_cart('member', D2, 3)
        k = S['run'] + '-m9-' + secrets.token_hex(3)
        s1, j1, _ = submit('member', [c1], k)
        iid = (data(j1) or {}).get('id')
        S['inq_m9'] = iid
        save()
        full = C.req('POST', TL + '/cart', role='other_member', body={'departure_id': D2, 'quantity': 1})
        after = dep_rows([D2])[D2]
        c = C.req('POST', f'{TL}/inquiries/{iid}/cancel', role='member')
        mid = dep_rows([D2])[D2]
        c2 = C.req('POST', f'{TL}/inquiries/{iid}/cancel', role='member')
        end = dep_rows([D2])[D2]
        inq = C.dbq('inquiries', users=[C.uid['member']], min_id=iid)['inquiries'][0]
        check(s1 == 201 and int(after['reserved']) == 3 == int(after['capacity']) and full[0] == 409, (s1, after, full[0]))
        check(c[0] == 200 and c2[0] == 200 and int(mid['reserved']) == 0 == int(end['reserved']) and inq['status'] == 'CANCELLED', (c[0], c2[0], mid['reserved'], end['reserved']))
        return 'PASS', {'submit_full_capacity': s1, 'reserved_after_submit': after['reserved'], 'capacity': after['capacity'], 'other_add_when_full': [full[0], msg(full[1])],
                        'cancel': c[0], 'reserved_after_cancel': mid['reserved'], 'repeat_cancel': c2[0], 'reserved_after_repeat': end['reserved'], 'events': inq['events']}
    r('M09 capacity enforced at full reservation; M10 cancel releases exactly once, repeat cancel no-op, never negative (SQL)', m9)
    save()

elif MODE == 'races':
    D = S['dep']
    S.setdefault('races', [])

    def barrier(label, kind, row_id, calls):
        dl0 = deadlocks()
        p = subprocess.Popen(['php', os.path.join(HERE, 'db_guard.php'), PRIV, 'barrier', json.dumps({'kind': kind, 'id': row_id, 'sku_prefix': S['run'], 'users': [C.uid['member'], C.uid['other_member']], 'sustain_s': 3.3, 'max_hold_s': 25})],
                             stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        locked = json.loads(p.stdout.readline() or '{}')
        if locked.get('event') != 'LOCKED':
            p.kill()
            return {'label': label, 'status': 'BLOCKED', 'stderr': p.stderr.read()[:300]}
        out = [None] * len(calls)

        def run(i, c):
            try:
                out[i] = C.req(*c[0], **c[1])
            except Exception as e:  # noqa: BLE001
                out[i] = (599, {'error': type(e).__name__}, {'t0': 0, 't1': 0, 'ms': 0})
        ts = [threading.Thread(target=run, args=(i, c)) for i, c in enumerate(calls)]
        for t in ts:
            t.start()
            time.sleep(0.05)
        p.stdin.write('OBSERVE\n'); p.stdin.flush()
        obs = json.loads(p.stdout.readline() or '{}')
        pending_at_release = [o is None for o in out]
        p.stdin.write('RELEASE\n'); p.stdin.flush()
        rel = json.loads(p.stdout.readline() or '{}')
        p.wait(timeout=30)
        for t in ts:
            t.join(timeout=120)
        ra = rel.get('at', 0)
        resp = [{'status': o[0], 'message': msg(o[1]), 'id': (data(o[1]) or {}).get('id') if isinstance(data(o[1]), dict) else None,
                 'data_status': (data(o[1]) or {}).get('status') if isinstance(data(o[1]), dict) else None,
                 'sent_before_release_s': round(ra - o[2]['t0'], 3), 'completed_after_release_s': round(o[2]['t1'] - ra, 3)} for o in out]
        fd = obs.get('first_detection') or {}
        overlap = (len(obs.get('distinct_waiting_connection_ids', [])) >= 2 and obs.get('same_waiters_sustained') and obs.get('sustained_s', 0) >= 3
                   and all(pending_at_release) and all(x['sent_before_release_s'] >= 3 and x['completed_after_release_s'] >= 0 for x in resp))
        race = {'label': label, 'lock': {'kind': kind, 'row': row_id, **{k: locked.get(k) for k in ('barrier_connection_id', 'db', 'account', 'server_version', 'isolation', 'table')}},
                'observed': {k: obs.get(k) for k in ('held_for_s', 'data_lock_waits_visible', 'innodb_trx_visible', 'same_waiters_sustained', 'sustained_s', 'distinct_waiting_connection_ids')},
                'first_detection_at_s': fd.get('at_s'), 'first_waiting': fd.get('waiting'), 'last_waiting': (obs.get('last_sample') or {}).get('waiting'),
                'samples_head': (obs.get('samples') or [])[:3], 'responses_pending_at_release': pending_at_release, 'responses': resp,
                'innodb_deadlocks_global_before_after': [dl0, deadlocks()], 'real_server_overlap': bool(overlap)}
        S['races'].append(race)
        save()
        return race

    def call(role, method, path, body=None, headers=None):
        return ((method, path), {'role': role, 'body': body, 'headers': headers})

    def sub_call(role, cid, key, c):
        return call(role, 'POST', TL + '/inquiries', {'cart_ids': [cid], 'contact': c, 'idempotency_key': key}, {'Idempotency-Key': key})

    def race1():
        dep = D['last']
        cm, co = add_cart('member', dep, 1), add_cart('other_member', dep, 1)
        rc = barrier('last seat, two members', 'departure', dep, [sub_call('member', cm, S['run'] + '-r1m', contact()), sub_call('other_member', co, S['run'] + '-r1o', contact())])
        after = dep_rows([dep])[dep]
        st = sorted(x['status'] for x in rc['responses'])
        loser = [x for x in rc['responses'] if x['status'] == 409]
        winners = [x['id'] for x in rc['responses'] if x['status'] == 201]
        S['race_inquiries'] = S.get('race_inquiries', []) + winners
        save()
        rc['invariant'] = {'reserved': after['reserved'], 'capacity': after['capacity'], 'loser_message': loser[0]['message'] if loser else None}
        check(rc['real_server_overlap'], 'no sustained two-connection overlap')
        check(st == [201, 409] and int(after['reserved']) == 1 == int(after['capacity']), (st, after))
        check(rc['innodb_deadlocks_global_before_after'][0] == rc['innodb_deadlocks_global_before_after'][1], 'server deadlock counter moved during race')
        return 'PASS', rc
    C.step('R1 last seat: different members overlap on own departure lock -> one 201 / one 409, reserved = capacity 1', race1)

    def race2():
        dep = D['key']
        cm = add_cart('member', dep, 1)
        key, c = S['run'] + '-r2', contact()
        rc = barrier('same key, same member', 'departure', dep, [sub_call('member', cm, key, c), sub_call('member', cm, key, c)])
        after = dep_rows([dep])[dep]
        ids = {x['id'] for x in rc['responses'] if x['status'] in (200, 201)}
        st = sorted(x['status'] for x in rc['responses'])
        rp = submit('member', [cm], key, c)
        ch = submit('member', [cm], key, contact())
        inqs = C.dbq('inquiries', users=[C.uid['member']], min_id=min(ids) if ids else 0)['inquiries']
        same_key_rows = [i for i in inqs if i['id'] in ids]
        S['race_inquiries'] = S.get('race_inquiries', []) + list(ids)
        save()
        rc['invariant'] = {'statuses': st, 'distinct_ids': len(ids), 'reserved': after['reserved'], 'replay': [rp[0], (data(rp[1]) or {}).get('id') in ids], 'changed_payload': [ch[0], msg(ch[1])],
                           'sql_rows_for_key_ids': len(same_key_rows), 'waiter_note': 'submit serialises on the native user row first; the second same-key request waits on the first request\'s user-row lock (transitive), not directly on the barrier row'}
        check(rc['real_server_overlap'], 'no sustained two-connection overlap')
        check(st == [200, 201] and len(ids) == 1 and int(after['reserved']) == 1 and rp[0] == 200 and ch[0] == 409, rc['invariant'])
        check(rc['innodb_deadlocks_global_before_after'][0] == rc['innodb_deadlocks_global_before_after'][1], 'server deadlock counter moved during race')
        return 'PASS', rc
    C.step('R2 same member same key: overlapping submits -> 201 then 200 same ID, reserved once; changed payload 409', race2)

    def race3():
        dep = D['cd']
        cm = add_cart('member', dep, 1)
        s1, j1, _ = submit('member', [cm], S['run'] + '-r3', contact())
        iid = (data(j1) or {}).get('id')
        check(s1 == 201, f'setup submit {s1}')
        S['race_inquiries'] = S.get('race_inquiries', []) + [iid]
        save()
        before = dep_rows([dep])[dep]
        rc = barrier('owner cancel vs admin decline', 'inquiry', iid, [call('member', 'POST', f'{TL}/inquiries/{iid}/cancel'), call('admin', 'PATCH', f'{TL}/admin/inquiries/{iid}', {'status': 'DECLINED'})])
        after = dep_rows([dep])[dep]
        again = C.req('POST', f'{TL}/inquiries/{iid}/cancel', role='member')
        end = dep_rows([dep])[dep]
        inq = C.dbq('inquiries', users=[C.uid['member']], min_id=iid)['inquiries'][0]
        st = sorted(x['status'] for x in rc['responses'])
        rc['invariant'] = {'statuses': st, 'final_status': inq['status'], 'reserved_before_after_end': [before['reserved'], after['reserved'], end['reserved']], 'repeat_cancel': again[0], 'events': inq['events']}
        check(rc['real_server_overlap'], 'no sustained two-connection overlap')
        check(st == [200, 409] and int(before['reserved']) == 1 and int(after['reserved']) == 0 == int(end['reserved']) and inq['status'] in ('CANCELLED', 'DECLINED'), rc['invariant'])
        check(rc['innodb_deadlocks_global_before_after'][0] == rc['innodb_deadlocks_global_before_after'][1], 'server deadlock counter moved during race')
        return 'PASS', rc
    C.step('R3 owner cancel vs admin decline on own inquiry lock -> exactly one release, never negative', race3)

    def race4():
        dep = D['low']
        cm = add_cart('member', dep, 2)
        rc = barrier('submit 2 vs admin capacity 2->1', 'departure', dep, [sub_call('member', cm, S['run'] + '-r4', contact()),
                     call('admin', 'PUT', f"{TL}/admin/catalog/{S['product']}/departures/{dep}", {'product_option_id': dep_rows([dep])[dep]['product_option_id'], 'departure_date': day(10), 'return_date': day(11), 'capacity': 1, 'is_active': True})])
        after = dep_rows([dep])[dep]
        st = [x['status'] for x in rc['responses']]
        S['race_inquiries'] = S.get('race_inquiries', []) + [x['id'] for x in rc['responses'][:1] if x['status'] == 201]
        save()
        rc['invariant'] = {'submit_status': st[0], 'put_status': st[1], 'put_message': rc['responses'][1]['message'], 'reserved': after['reserved'], 'capacity': after['capacity']}
        check(rc['real_server_overlap'], 'no sustained two-connection overlap')
        check(sorted(st) == [201, 409] or sorted(st) == [200, 409], st)
        check(int(after['reserved']) <= int(after['capacity']), after)
        check(rc['innodb_deadlocks_global_before_after'][0] == rc['innodb_deadlocks_global_before_after'][1], 'server deadlock counter moved during race')
        return 'PASS', rc
    C.step('R4 submit qty2 vs admin capacity 2->1 on own departure lock -> one wins, never below reserved', race4)

elif MODE == 'deadlock_attribution':
    D = S['dep']

    def control():
        samples = []
        t0 = time.time()
        while time.time() - t0 < 45:
            samples.append((round(time.time() - t0, 1), deadlocks()))
            time.sleep(4)
        S['dl_control'] = samples
        save()
        return 'PASS' if samples[0][1] == samples[-1][1] else 'OBSERVED', {'idle_45s_samples': samples, 'note': 'server-wide counter; any DB on this MariaDB server'}
    if os.environ.get('W04F_VARIANT') != 'c':
        C.step('deadlock attribution control: server-wide Innodb_deadlocks during 45s without own traffic', control)

    def rerun(variant='b', mkey=None, okey=None):
        prod = data(C.req('GET', f"{EC}/admin/products/{S['product']}", role='admin')[1])
        code = S['run'] + '-T-' + ('9' if variant == 'b' else '10')
        opts = [opt_payload(o['option_code'], stock=int(o['stock_quantity']), oid=o['id'], adjustment=float(o.get('price_adjustment') or 0)) for o in prod['options']] + [opt_payload(code)]
        st, j, _ = C.req('PUT', f"{EC}/admin/products/{S['product']}", role='admin', body={**S['payload'], 'options': opts})
        check(st == 200, f'add option {st} {msg(j)}')
        new = [o['id'] for o in data(C.req('GET', f"{EC}/admin/products/{S['product']}", role='admin')[1])['options'] if o['option_code'] == code][0]
        st, j, _ = C.req('POST', f"{TL}/admin/catalog/{S['product']}/departures", role='admin', body={'product_option_id': new, 'departure_date': day(10), 'return_date': day(11), 'capacity': 1, 'is_active': True})
        check(st in (200, 201), f'departure {st} {msg(j)}')
        dep = data(j)['id']
        S['dep']['last_' + variant] = dep
        save()
        cm, co = add_cart('member', dep, 1), add_cart('other_member', dep, 1)
        dl_before = deadlocks()
        p = subprocess.Popen(['php', os.path.join(HERE, 'db_guard.php'), PRIV, 'barrier', json.dumps({'kind': 'departure', 'id': dep, 'sku_prefix': S['run'], 'users': [C.uid['member'], C.uid['other_member']], 'sustain_s': 3.3, 'max_hold_s': 25})],
                             stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        locked = json.loads(p.stdout.readline() or '{}')
        out = [None, None]
        calls = [('member', cm, mkey or S['run'] + '-r1b-m'), ('other_member', co, okey or S['run'] + '-r1b-o')]

        def run(i):
            role, cid, key = calls[i]
            out[i] = submit(role, [cid], key)
        ts = [threading.Thread(target=run, args=(i,)) for i in range(2)]
        for t in ts:
            t.start()
            time.sleep(0.05)
        p.stdin.write('OBSERVE\n'); p.stdin.flush()
        obs = json.loads(p.stdout.readline() or '{}')
        dl_hold = deadlocks()
        p.stdin.write('RELEASE\n'); p.stdin.flush()
        rel = json.loads(p.stdout.readline() or '{}')
        p.wait(timeout=30)
        for t in ts:
            t.join(timeout=120)
        dl_after = deadlocks()
        time.sleep(3)
        dl_late = deadlocks()
        after = dep_rows([dep])[dep]
        ra = rel.get('at', 0)
        resp = [{'role': calls[i][0], 'status': o[0], 'message': msg(o[1]), 'id': (data(o[1]) or {}).get('id') if isinstance(data(o[1]), dict) else None,
                 'sent_before_release_s': round(ra - o[2]['t0'], 3), 'completed_after_release_s': round(o[2]['t1'] - ra, 3)} for i, o in enumerate(out)]
        S['race_inquiries'] = S.get('race_inquiries', []) + [x['id'] for x in resp if x['status'] == 201]
        save()
        d = {'variant': variant, 'keys': ['member: ' + ('sorts after all own keys' if variant == 'c' else 'between own keys'), 'other: ' + ('sorts before all own keys' if variant == 'c' else 'after own keys')], 'departure': dep, 'barrier_connection': locked.get('barrier_connection_id'), 'waiting_ids': obs.get('distinct_waiting_connection_ids'), 'sustained_s': obs.get('sustained_s'),
             'responses': resp, 'reserved': after['reserved'], 'capacity': after['capacity'],
             'innodb_deadlocks': {'before_lock': dl_before, 'during_hold': dl_hold, 'after_both_responses': dl_after, 'plus_3s': dl_late}}
        check(sorted(x['status'] for x in resp) == [201, 409] and int(after['reserved']) == 1, d)
        if dl_after > dl_hold == dl_before:
            return 'OBSERVED', {**d, 'attribution': 'counter incremented only in the release-to-response interval of this race (correlated, not engine-level proof)'}
        return 'PASS', {**d, 'attribution': 'no deadlock counted in this rerun'}
    if os.environ.get('W04F_VARIANT') == 'c':
        C.step('R1c last-seat rerun, idempotency keys placed in the same (user_id,key) index gap: predicted one deadlock retried', lambda: rerun('c', 'zz' + S['run'] + '-r1c-m', 'AA' + S['run'] + '-r1c-o'))
    else:
        C.step('R1b last-seat rerun on a fresh own capacity-1 departure with release-window deadlock counter sampling', rerun)

elif MODE == 'effects':
    def e():
        after = C.dbq('counts', users=list(C.uid.values()))['counts']
        before = S['counts_before']
        keys = ['g7_ecommerce_orders', 'g7_ecommerce_order_payments', 'g7_ecommerce_order_options', 'g7_ecommerce_temp_orders', 'g7_jobs', 'g7_failed_jobs', 'g7_notifications', 'g7_mail_send_logs', 'g7_notification_logs']
        diff = {k: {'before': before.get(k), 'after': after.get(k)} for k in keys}
        changed = [k for k in keys if before.get(k) != after.get(k)]
        own_inq = after['g7_travel_lab_inquiries'].get('own', 0) - before['g7_travel_lab_inquiries'].get('own', 0)
        check(not changed, {'changed': changed, 'diff': diff})
        return 'PASS', {'unchanged_native_effect_tables': diff, 'own_travel_inquiries_created': own_inq,
                        'mail_queue_settings_evidence': 'lab environment guard scripts/travel-lab/environment.php refuses to run unless MAIL_MAILER=array and QUEUE_CONNECTION=sync; .env.travel-lab.example sets G7_ENV_PRIORITY=true; parent .env not read'}
    C.step('M11-M13 native orders/payments/temp-orders/order-options and jobs/failed-jobs/notifications/mail/notification logs unchanged (own and global SQL)', e)

elif MODE == 'cleanup':
    def cl():
        D = S['dep']
        out = {}
        st, j, _ = C.req('GET', TL + '/inquiries?per_page=100', role='member')
        for r in rows(j):
            if r.get('status') not in ('CANCELLED', 'DECLINED') and int(r['id']) > S['floors']['g7_travel_lab_inquiries']:
                out[f"cancel_{r['id']}"] = C.req('POST', f"{TL}/inquiries/{r['id']}/cancel", role='member')[0]
        st, j, _ = C.req('GET', TL + '/inquiries?per_page=100', role='other_member')
        for r in rows(j):
            if r.get('status') not in ('CANCELLED', 'DECLINED') and int(r['id']) > S['floors']['g7_travel_lab_inquiries']:
                out[f"cancel_{r['id']}"] = C.req('POST', f"{TL}/inquiries/{r['id']}/cancel", role='other_member')[0]
        for role in ('member', 'other_member'):
            _, items, _ = cart_items(role)
            for i in items:
                if int(i['departure_id']) in D.values():
                    out[f'cart_{role}_{i["id"]}'] = C.req('DELETE', f"{TL}/cart/{i['id']}", role=role)[0]
        deps = dep_rows(D.values())
        for name, did in D.items():
            d = deps[did]
            out[f'deactivate_{name}'] = C.req('PUT', f"{TL}/admin/catalog/{S['product']}/departures/{did}", role='admin',
                                              body={'product_option_id': d['product_option_id'], 'departure_date': str(d['departure_date'])[:10], 'return_date': day(11), 'capacity': max(int(d['capacity']), int(d['reserved']), 1), 'is_active': False})[0]
        out['unpublish'] = C.req('PATCH', f"{TL}/admin/catalog/{S['product']}", role='admin', body={'published': False})[0]
        prod = data(C.req('GET', f"{EC}/admin/products/{S['product']}", role='admin')[1])
        opts = [opt_payload(o['option_code'], stock=int(o['stock_quantity']), oid=o['id'], adjustment=float(o.get('price_adjustment') or 0)) for o in prod['options']]
        out['hide_product'] = C.req('PUT', f"{EC}/admin/products/{S['product']}", role='admin', body={**S['payload'], 'display_status': 'hidden', 'options': opts})[0]
        out['delete_attempt_guarded'] = C.req('DELETE', f"{EC}/admin/products/{S['product']}", role='admin')[0]
        out['category_deactivate'] = C.req('PUT', f"{EC}/admin/categories/{S['category']}", role='admin', body={'name': {'ko': S['run'] + ' 분류', 'en': S['run'] + ' category'}, 'slug': S['run'].lower(), 'is_active': False})[0]
        pub = C.req('GET', f"{TL}/catalog/{S['product']}")[0]
        deps = dep_rows(D.values())
        inqs = C.dbq('inquiries', users=[C.uid['member'], C.uid['other_member']], min_id=S['floors']['g7_travel_lab_inquiries'] + 1)['inquiries']
        _, im, _ = cart_items('member')
        _, io, _ = cart_items('other_member')
        d = {'actions': out, 'public_catalog_after': pub, 'departures_inactive_reserved0': all(not int(x['is_active']) and int(x['reserved']) == 0 for x in deps.values()),
             'own_run_inquiries': len(inqs), 'own_run_inquiries_terminal': all(i['status'] in ('CANCELLED', 'DECLINED') for i in inqs),
             'carts_left_on_run_departures': sum(int(i['departure_id']) in D.values() for i in im + io)}
        check(pub == 404 and d['departures_inactive_reserved0'] and d['own_run_inquiries_terminal'] and d['carts_left_on_run_departures'] == 0, d)
        return 'PASS', d
    C.step('workflow fixture terminal cleanup: own inquiries terminal, carts empty, departures inactive reserved0, product unpublished/hidden', cl)
C.flush()
