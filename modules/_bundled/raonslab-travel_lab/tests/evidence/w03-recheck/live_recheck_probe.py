#!/usr/bin/env python3
"""W03 nonauthor security recheck: live native HTTP probe against the isolated preview.

Run with `python3 -I live_recheck_probe.py <access.json> <parent-root> <out.json>`.

- Uses only the synthetic member, other_member and admin from the private access file.
- Every fixture is created through authorized native/admin HTTP APIs and is owned by this run
  (SKU prefix W03R-...).
- Real request overlap is shown by row_lock_barrier.php. It holds a lock on one owned row while two
  HTTP clients wait, and reports their distinct blocked DB connection IDs.
- No order, payment, supplier, mail/SMS or external search call is made.
- Output is scrubbed of tokens, passwords and emails.
"""
import base64, datetime, hashlib, json, os, subprocess, sys, threading, time, urllib.error, urllib.request, zlib
from zoneinfo import ZoneInfo

ACCESS, PARENT, OUT = sys.argv[1], sys.argv[2], sys.argv[3]
A = json.load(open(ACCESS))
BASE = A['base_url']
HERE = os.path.dirname(os.path.abspath(__file__))
RUN = 'W03R-' + datetime.datetime.now(datetime.timezone.utc).strftime('%H%M%S')
TL, EC = '/api/modules/raonslab-travel_lab', '/api/modules/sirsoft-ecommerce'
SECRETS = [v for r in ('member', 'other_member', 'admin') for k, v in A[r].items() if k != 'user_id' and isinstance(v, str)]
results, fixture, races = [], {}, []
USERS = f"{A['member']['user_id']},{A['other_member']['user_id']}"


def scrub(x):
    s = json.dumps(x, ensure_ascii=False, default=str)
    for v in SECRETS:
        s = s.replace(v, '[private]')
    return json.loads(s)


def flush():
    with open(OUT, 'w') as f:
        json.dump(scrub({'source_sha_expected': A['source_sha'], 'run': RUN, 'base_url': BASE, 'fixture': fixture,
                         'races': races, 'results': results,
                         'counts': {s: sum(1 for r in results if r['status'] == s) for s in ('PASS', 'FAIL', 'OBSERVED', 'BLOCKED', 'NOT_RUN')}}),
                  f, ensure_ascii=False, indent=1)
    os.chmod(OUT, 0o600)


def req(method, path, role='admin', body=None, headers=None, raw=None, ctype=None):
    h = {'Accept': 'application/json', 'Accept-Language': 'en'}
    if role:
        h['Authorization'] = 'Bearer ' + A[role]['bearer_token']
    data = None
    if raw is not None:
        data, h['Content-Type'] = raw, ctype
    elif body is not None:
        data, h['Content-Type'] = json.dumps(body).encode(), 'application/json'
    h.update(headers or {})
    r = urllib.request.Request(BASE + path, data=data, headers=h, method=method)
    t0 = time.time()
    try:
        with urllib.request.urlopen(r, timeout=90) as x:
            b, st, hd = x.read(), x.status, dict(x.headers)
    except urllib.error.HTTPError as e:
        b, st, hd = e.read(), e.code, dict(e.headers)
    try:
        j = json.loads(b or b'null')
    except ValueError:
        j = {'_non_json_bytes': len(b), '_sha256': hashlib.sha256(b).hexdigest()}
    return st, j, {'t0': t0, 't1': time.time(), 'ratelimit_limit': hd.get('X-RateLimit-Limit'), 'ratelimit_remaining': hd.get('X-RateLimit-Remaining')}


def raw_get(url):
    try:
        with urllib.request.urlopen(urllib.request.Request(url), timeout=30) as x:
            b = x.read()
            return x.status, x.headers.get('Content-Type'), hashlib.sha256(b).hexdigest()
    except urllib.error.HTTPError as e:
        return e.code, e.headers.get('Content-Type'), None


SKIP = False


def step(name, fn):
    if SKIP and not name.startswith('cleanup'):
        return {}
    try:
        status, detail = fn()
    except AssertionError as e:
        status, detail = 'FAIL', {'assert': str(e)[:1500]}
    except Exception as e:  # noqa: BLE001 - evidence must record the failure, not abort the probe
        status, detail = 'FAIL', {'error': type(e).__name__ + ': ' + str(e)[:1500]}
    results.append({'name': name, 'status': status, 'detail': detail})
    print(status, name, flush=True)
    flush()
    return detail


def check(cond, msg):
    if not cond:
        raise AssertionError(msg)


def msg(j):
    return (j or {}).get('message') if isinstance(j, dict) else None


def png():
    raw = b'\x00\xff\x00\x00'
    def chunk(t, d):
        return len(d).to_bytes(4, 'big') + t + d + zlib.crc32(t + d).to_bytes(4, 'big')
    return b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', (1).to_bytes(4, 'big') * 2 + b'\x08\x02\x00\x00\x00') + chunk(b'IDAT', zlib.compress(raw)) + chunk(b'IEND', b'')


def multipart(fields, fname, content):
    bd = 'w03r' + hashlib.md5(RUN.encode()).hexdigest()
    out = b''
    for k, v in fields.items():
        out += f'--{bd}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode()
    out += f'--{bd}\r\nContent-Disposition: form-data; name="file"; filename="{fname}"\r\nContent-Type: image/png\r\n\r\n'.encode() + content + f'\r\n--{bd}--\r\n'.encode()
    return out, 'multipart/form-data; boundary=' + bd


def current_opts(pid):
    d = req('GET', f'{EC}/admin/products/{pid}')[1]['data']
    return d, [opt_payload(o['option_code'], stock=int(o['stock_quantity']), price=int(float(o['selling_price'])), oid=o['id'], adjustment=float(o.get('price_adjustment') or 0)) for o in d['options']]


def departures():
    st, j, _ = req('GET', f"{TL}/admin/catalog/{fixture['travel_product_id']}/departures")
    rows = (j.get('data') or {}) if isinstance(j, dict) else {}
    rows = rows.get('data', rows) if isinstance(rows, dict) else rows
    return {int(d['id']): d for d in rows}


def barrier_race(label, kind, row_id, calls):
    """Hold a lock on one owned row, launch the calls concurrently, observe the blocked connections, then release."""
    p = subprocess.Popen(['php', os.path.join(HERE, 'row_lock_barrier.php'), PARENT, kind, str(row_id), RUN, USERS],
                         stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    locked = json.loads(p.stdout.readline() or '{}')
    if locked.get('event') != 'LOCKED':
        p.kill()
        return {'label': label, 'status': 'BLOCKED', 'reason': 'barrier lock unavailable', 'stderr': p.stderr.read()[:300]}
    out = [None] * len(calls)
    def run(i, c):
        out[i] = req(*c[0], **c[1])
    threads = [threading.Thread(target=run, args=(i, c)) for i, c in enumerate(calls)]
    for t in threads:
        t.start()
    p.stdin.write('OBSERVE\n'); p.stdin.flush()
    observed = json.loads(p.stdout.readline() or '{}')
    p.stdin.write('RELEASE\n'); p.stdin.flush()
    released = json.loads(p.stdout.readline() or '{}')
    p.wait(timeout=30)
    for t in threads:
        t.join(timeout=120)
    rel = released.get('at', 0)
    responses = [{'status': o[0], 'message': msg(o[1]), 'data_id': ((o[1] or {}).get('data') or {}).get('id') if isinstance((o[1] or {}).get('data'), dict) else None,
                  'data_status': ((o[1] or {}).get('data') or {}).get('status') if isinstance((o[1] or {}).get('data'), dict) else None,
                  'sent_before_release_s': round(rel - o[2]['t0'], 3), 'completed_after_release_s': round(o[2]['t1'] - rel, 3)} for o in out]
    blocked = observed.get('blocked_distinct_connection_ids', [])
    # PASS only if two distinct DB connections were seen waiting on the held row, they were still waiting >=3s later,
    # and both HTTP responses were sent before and completed after the release.
    overlap = (len(blocked) >= 2 and bool(observed.get('still_blocked_after_hold'))
               and all(r['sent_before_release_s'] >= 3 and r['completed_after_release_s'] >= 0 for r in responses))
    race = {'label': label, 'lock_kind': kind, 'lock_row': row_id, 'barrier': {k: locked.get(k) for k in ('barrier_connection_id', 'db', 'account', 'server_version', 'table')},
            'observed': observed, 'released_at': rel, 'responses': responses, 'real_server_overlap': overlap}
    races.append(race)
    flush()
    return race


def opt_payload(code, stock=5, price=12000, oid=None, adjustment=0):
    o = {'price_adjustment': adjustment, 'option_code': code, 'option_name': {'ko': code, 'en': code},
         'option_values': [{'key': {'ko': '출발', 'en': 'Departure'}, 'value': {'ko': code, 'en': code}}],
         'list_price': 20000, 'selling_price': price, 'stock_quantity': stock, 'is_active': True, 'is_default': code.endswith('-1')}
    if oid:
        o['id'] = oid
    return o


# ---------------------------------------------------------------- fixtures (own, via admin APIs)
kst_today = datetime.datetime.now(ZoneInfo('Asia/Seoul')).date()
day = lambda n: (kst_today + datetime.timedelta(days=n)).isoformat()


def f_policy():
    st, j, _ = req('POST', f'{EC}/admin/shipping-policies', body={'name': {'ko': RUN + ' 무배송', 'en': RUN + ' nonshipping'}, 'is_active': True, 'is_default': False,
                   'country_settings': [{'country_code': 'KR', 'shipping_method': 'pickup', 'charge_policy': 'free', 'base_fee': 0, 'extra_fee_enabled': False, 'is_active': True}]})
    note = None
    if st == 201:
        fixture['shipping_policy_id'] = j['data']['id']
    else:
        # The preview APP DB has no shipping_types rows, so no shipping_method value validates.
        # Seeding shared reference data is out of scope, so reuse the existing free-KR travel policy read-only.
        lst = req('GET', f'{EC}/admin/shipping-policies?per_page=50')[1]['data']
        rows = lst.get('data', lst) if isinstance(lst, dict) else lst
        free = [p for p in rows if p.get('is_active') and not p.get('is_default') and p.get('country_settings') and all(
            c.get('charge_policy') == 'free' and float(c.get('base_fee') or 0) == 0 and not c.get('extra_fee_enabled') and not c.get('api_endpoint') for c in p['country_settings'])
            and any(c.get('country_code') == 'KR' and c.get('is_active') for c in p['country_settings'])]
        check(free, f'policy {st} and no reusable free-KR policy')
        fixture['shipping_policy_id'] = free[0]['id']
        note = {'own_policy_create_http': st, 'errors': j.get('errors'), 'blocked_reason': 'shipping_types reference table empty on preview; existing free-KR policy reused read-only',
                'reused_policy_id': free[0]['id']}
    st, j, _ = req('POST', f'{EC}/admin/categories', body={'name': {'ko': RUN + ' 분류', 'en': RUN + ' category'}, 'slug': RUN.lower(), 'is_active': True})
    check(st == 201, f'category {st} {msg(j)} {j.get("errors")}')
    fixture['category_id'] = j['data']['id']
    return ('PASS' if note is None else 'OBSERVED'), {'policy': fixture['shipping_policy_id'], 'category': fixture['category_id'], 'policy_note': note}


def product_payload(kind, options, display='visible'):
    sku = f'{RUN}-{kind}'
    return {'name': {'ko': sku + ' 합성 상품', 'en': sku + ' synthetic'}, 'product_code': sku.replace('-', ''), 'sku': sku, 'category_ids': [fixture['category_id']],
            'list_price': 20000, 'selling_price': 12000, 'stock_quantity': sum(o['stock_quantity'] for o in options), 'sales_status': 'on_sale', 'display_status': display,
            'tax_status': 'tax_free', 'shipping_policy_id': fixture['shipping_policy_id'], 'has_options': True, 'options': options}


def f_products():
    for kind, n, display in (('T', 7, 'visible'), ('O', 2, 'hidden'), ('D', 1, 'hidden')):
        payload = product_payload(kind, [opt_payload(f'{RUN}-{kind}-{i}') for i in range(1, n + 1)], display)
        st, j, _ = req('POST', f'{EC}/admin/products', body=payload)
        check(st == 201, f'product {kind} {st} {msg(j)} {j.get("errors")}')
        st, d, _ = req('GET', f"{EC}/admin/products/{j['data']['id']}")
        fixture[f'product_{kind}'] = {'id': j['data']['id'], 'options': [o['id'] for o in d['data']['options']], 'payload': payload}
    fixture['travel_product_id'] = fixture['product_T']['id']
    st, j, _ = req('POST', f'{TL}/admin/catalog', body={'product_id': fixture['travel_product_id'], 'region': 'jeju', 'theme': 'nature', 'duration_days': 2,
                   'summary': {'ko': RUN + ' 합성 요약', 'en': RUN + ' synthetic summary'}, 'published': True})
    check(st in (200, 201), f'catalog register {st} {msg(j)} {j.get("errors")}')
    return 'PASS', {k: fixture[k]['id'] for k in ('product_T', 'product_O', 'product_D')} | {'catalog_register_http': st}


def f_departures():
    opts = fixture['product_T']['options']
    plan = {'last': (opts[0], 1), 'key': (opts[1], 3), 'race': (opts[2], 2), 'cd': (opts[3], 2), 'last2': (opts[5], 1), 'last3': (opts[6], 1)}
    out = {}
    for name, (oid, cap) in plan.items():
        st, j, _ = req('POST', f"{TL}/admin/catalog/{fixture['travel_product_id']}/departures",
                       body={'product_option_id': oid, 'departure_date': day(10), 'return_date': day(11), 'capacity': cap, 'is_active': True})
        check(st in (200, 201), f'departure {name} {st} {msg(j)} {j.get("errors")}')
        out[name] = j['data']['id']
    fixture['departures'] = out
    fixture['unlinked_option'] = opts[4]  # opts[5], opts[6] carry the last-seat retry departures
    return 'PASS', out


if os.environ.get('W03R_CLEANUP_PREVIOUS'):
    # Recovery for an aborted earlier run: unpublish/deactivate/hide its guarded product, delete only its ordinary products.
    prev = json.load(open(os.environ['W03R_CLEANUP_PREVIOUS']))
    fixture.update(prev['fixture'])
    RUN = prev['run']
    P = fixture['travel_product_id']
    D = {}
    st, j, _ = req('GET', f'{TL}/admin/catalog/{P}/departures')
    D = {str(d['id']): int(d['id']) for d in j['data']}
    OUT = os.environ['W03R_CLEANUP_PREVIOUS'] + '.cleanup.json'
    results.clear()
    for k in ('product_O', 'product_D'):
        if k in fixture:
            results.append({'name': 'delete own ordinary ' + k, 'status': 'PASS', 'detail': req('DELETE', f"{EC}/admin/products/{fixture[k]['id']}")[0]})
    exec_cleanup = True
    SKIP = True
else:
    exec_cleanup = False
if not exec_cleanup:
    step('fixture: own free-KR shipping policy + category via native admin API', f_policy)
    step('fixture: own travel/ordinary products via native admin API + POST /admin/catalog registration', f_products)
    step('fixture: own NEW departures via POST /admin/catalog/{product}/departures', f_departures)
    if 'departures' not in fixture:
        flush(); sys.exit(1)
    D = fixture['departures']
    P = fixture['travel_product_id']


# ---------------------------------------------------------------- tamper / validation (no writes expected)
def t_departure_form():
    base = {'product_option_id': fixture['unlinked_option'], 'return_date': day(3), 'capacity': 1}
    a = req('POST', f'{TL}/admin/catalog/{P}/departures', body={**base, 'departure_date': kst_today.isoformat(), 'return_date': kst_today.isoformat()})[0]
    b = req('POST', f'{TL}/admin/catalog/{P}/departures', body={**base, 'departure_date': day(2), 'reserved': 1})[0]
    c = req('POST', f'{TL}/admin/catalog/{P}/departures', body={**base, 'departure_date': day(2), 'unit_price': 1})[0]
    m = req('POST', f'{TL}/admin/catalog/{P}/departures', role='member', body={**base, 'departure_date': day(2)})[0]
    check((a, b, c, m) == (422, 422, 422, 403), (a, b, c, m))
    check(fixture['unlinked_option'] not in [int(x['product_option_id']) for x in departures().values() if 'product_option_id' in x] , 'unlinked option became linked')
    return 'PASS', {'kst_today_422': a, 'reserved_422': b, 'unit_price_422': c, 'member_403': m, 'kst_today': kst_today.isoformat()}


def t_cart_tamper():
    s = [req('POST', f'{TL}/cart', role='member', body=b)[0] for b in (
        {'departure_id': D['key'], 'quantity': 1, 'unit_price': 1}, {'departure_id': D['key'], 'quantity': 1, 'price': 0},
        {'departure_id': D['key'], 'quantity': 0}, {'departure_id': D['key'], 'quantity': 100}, {'departure_id': D['key'], 'quantity': 1.5},
        {'departure_id': D['key'], 'quantity': 'abc'}, {'departure_id': D['key'], 'quantity': 1, 'user_id': A['other_member']['user_id']})]
    ghost = req('POST', f'{TL}/cart', role='member', body={'departure_id': 999999999, 'quantity': 1})[0]
    over = req('POST', f'{TL}/cart', role='member', body={'departure_id': D['last'], 'quantity': 2})
    st, j, _ = req('GET', f'{TL}/cart', role='member')
    check(all(x == 422 for x in s), s)
    check(ghost in (404, 409, 422) and over[0] == 409, (ghost, over[0]))
    return 'PASS', {'tamper_statuses': s, 'unknown_departure': ghost, 'over_capacity': over[0], 'over_capacity_message': msg(over[1]), 'cart_get': st}


step('tamper: departure form KST-today/reserved/unit_price/member rejected', t_departure_form)
step('tamper: cart price/person/owner/unknown-field rejected (422), over-capacity 409', t_cart_tamper)


# ---------------------------------------------------------------- native lifecycle guards
def g_assets():
    body, ct = multipart({'collection': 'main'}, 'w03r.png', png())
    st, j, _ = req('POST', f'{EC}/admin/products/{P}/images', raw=body, ctype=ct)
    check(st in (200, 201), f'image {st} {msg(j)} {j.get("errors")}')
    st, d, _ = req('GET', f'{EC}/admin/products/{P}')
    imgs = d['data'].get('images') or []
    url = next((i.get('download_url') or i.get('url') or i.get('image_url') for i in imgs if (i.get('download_url') or i.get('url') or i.get('image_url'))), None)
    check(url, 'no image url')
    url = url if url.startswith('http') else BASE + url
    fixture['image'] = {'count': len(imgs), 'url_path': url.replace(BASE, ''), 'get': raw_get(url)}
    q = req('POST', f'{EC}/products/{P}/inquiries', role='member', body={'content': RUN + ' synthetic product question', 'is_secret': True})
    fixture['qna'] = {'store_http': q[0], 'message': msg(q[1]), 'id': ((q[1] or {}).get('data') or {}).get('id') if isinstance((q[1] or {}).get('data'), dict) else None}
    lst = req('GET', f'{EC}/products/{P}/inquiries', role='member')
    fixture['qna']['list_http'] = lst[0]
    fixture['qna']['list_total'] = json.dumps(lst[1]).count(str(fixture['qna']['id'])) if fixture['qna']['id'] else None
    check(fixture['image']['get'][0] == 200, fixture['image'])
    return 'PASS', {'image': fixture['image'], 'qna': fixture['qna']}


def g_delete():
    can = req('GET', f'{EC}/admin/products/{P}/can-delete')
    st, j, _ = req('DELETE', f'{EC}/admin/products/{P}')
    after = req('GET', f'{EC}/admin/products/{P}')
    img = raw_get(BASE + fixture['image']['url_path'])
    lst = req('GET', f'{EC}/products/{P}/inquiries', role='member')
    deps = departures()
    # The preview resolves the admin's saved locale (ko), not Accept-Language; both module strings are the travel reason.
    expected = ('Travel Lab products cannot be deleted while linked to travel records. Unpublish the product instead.', '여행 기록에 연결된 상품은 삭제할 수 없습니다. 여행 게시를 해제해 주세요.')
    check(st == 409 and msg(j) in expected, f'delete {st} {msg(j)}')
    check(after[0] == 200 and len(after[1]['data'].get('images') or []) == fixture['image']['count'], 'product/images changed')
    check(img[0] == 200 and img[2] == fixture['image']['get'][2], f'image asset changed {img}')
    check(set(D.values()) <= set(deps), 'departure lost')
    qna_kept = (json.dumps(lst[1]).count(str(fixture['qna']['id'])) == fixture['qna']['list_total']) if fixture['qna'].get('id') else None
    if fixture['qna'].get('id'):
        check(qna_kept, 'native Q&A thread removed')
    return 'PASS', {'can_delete_probe': {'http': can[0], 'data': can[1].get('data') if isinstance(can[1], dict) else None},
                    'delete_http': st, 'delete_message': msg(j), 'product_after': after[0], 'image_after': img, 'qna_kept': qna_kept, 'departures_kept': True}


def g_option_removal():
    t = fixture['product_T']
    before, opts = current_opts(P)
    linked = departures()[D['last']]['product_option_id']
    bad = {**t['payload'], 'name': {'ko': RUN + ' 저장되면 안 됨', 'en': RUN + ' must not save'}, 'options': [o for o in opts if o['id'] != linked]}
    st, j, _ = req('PUT', f'{EC}/admin/products/{P}', body=bad)
    after = req('GET', f'{EC}/admin/products/{P}')[1]['data']
    check(st == 422 and 'options' in (j.get('errors') or {}), f'{st} {msg(j)} {j.get("errors")}')
    check(after['name'] == before['name'] and sorted(o['id'] for o in after['options']) == sorted(o['id'] for o in before['options']), 'write happened before rejection')
    return 'PASS', {'http': st, 'errors_options': (j.get('errors') or {}).get('options'), 'name_unchanged': True, 'option_ids_unchanged': True}


def g_ordinary_edits():
    t = fixture['product_T']
    _, cur = current_opts(P)
    key_option = departures()[D['key']]['product_option_id']
    opts = [o for o in cur if o['id'] != fixture['unlinked_option']]
    check(len(opts) == len(cur) - 1, 'unlinked option missing')
    for o in opts:
        if o['id'] == key_option:
            o['price_adjustment'] = 500  # native option price = product price + adjustment; the server amount must follow (12500)
    opts.append(opt_payload(f'{RUN}-T-new', stock=3))  # new option
    body = {**t['payload'], 'stock_quantity': sum(o['stock_quantity'] for o in opts), 'options': opts}  # unlinked option 5 removed
    st, j, _ = req('PUT', f'{EC}/admin/products/{P}', body=body)
    check(st == 200, f'travel ordinary edit {st} {msg(j)} {j.get("errors")}')
    d = req('GET', f'{EC}/admin/products/{P}')[1]['data']
    t['options'] = [o['id'] for o in d['options']]
    linked_price = {o['id']: {'selling_price': o.get('selling_price'), 'price_adjustment': o.get('price_adjustment')} for o in d['options']}.get(key_option)
    check(float(linked_price['price_adjustment'] or 0) == 500.0, f'linked option adjustment not saved {linked_price}')
    check(fixture['unlinked_option'] not in t['options'], 'unlinked option not removed')
    o = fixture['product_O']
    _, ocur = current_opts(o['id'])
    oopt = [{**ocur[0], 'selling_price': 11000}]
    st2, j2, _ = req('PUT', f"{EC}/admin/products/{o['id']}", body={**o['payload'], 'selling_price': 11000, 'stock_quantity': 5, 'options': oopt})
    pd = fixture['product_D']
    st3, j3, _ = req('DELETE', f"{EC}/admin/products/{pd['id']}")
    gone = req('GET', f"{EC}/admin/products/{pd['id']}")[0]
    check(st2 == 200 and st3 == 200 and gone == 404, (st2, msg(j2), st3, msg(j3), gone))
    return 'PASS', {'travel_price_edit_new_option_unlinked_removal': st, 'linked_option_price_after': linked_price, 'ordinary_option_removal_price_edit': st2,
                    'ordinary_delete': st3, 'ordinary_delete_message': msg(j3), 'ordinary_after_delete': gone}


step('guard setup: own product image + native product Q&A', g_assets)
step('guard: native admin DELETE travel product -> 409 travel reason before image/Q&A cleanup', g_delete)
step('guard: native admin PUT removing departure-linked option -> 422 reason, no write', g_option_removal)
step('guard: legitimate native price/new-option/unlinked-removal + ordinary product edit/delete allowed', g_ordinary_edits)


# ---------------------------------------------------------------- real HTTP overlap (barrier)
def cart_id(role, dep, qty):
    st, j, _ = req('POST', f'{TL}/cart', role=role, body={'departure_id': dep, 'quantity': qty})
    check(st in (200, 201), f'cart {role} {st} {msg(j)}')
    items = req('GET', f'{TL}/cart', role=role)[1]['data']
    items = items.get('items', items) if isinstance(items, dict) else items
    return next(int(i['id']) for i in items if int(i.get('departure_id') or 0) == dep)


def submit_call(role, cid, key, name='W03R tester'):
    return (('POST', f'{TL}/inquiries'), {'role': role, 'body': {'cart_ids': [cid], 'contact': {'name': name}, 'idempotency_key': key}, 'headers': {'Idempotency-Key': key}})


def c_last_seat():
    # The shared preview has 4 php -S workers and is also used by another Request's browser. If a request queues
    # for a worker it never reaches the DB, so it is not a server overlap. Retry on a fresh capacity-1 departure
    # (max 3 attempts) and record every attempt; invariants are checked on all of them.
    attempts = []
    for n, dkey in enumerate(('last', 'last2', 'last3'), 1):
        c1, c2 = cart_id('member', D[dkey], 1), cart_id('other_member', D[dkey], 1)
        race = barrier_race(f'last seat, two members (attempt {n})', 'departure', D[dkey], [submit_call('member', c1, f'{RUN}-{dkey}-m'), submit_call('other_member', c2, f'{RUN}-{dkey}-o')])
        dep = departures()[D[dkey]]
        codes = sorted(r['status'] for r in race['responses'])
        winner = next(({'role': ('member', 'other_member')[i], 'id': r['data_id']} for i, r in enumerate(race['responses']) if r['status'] == 201), None)
        attempts.append({'departure': D[dkey], 'overlap': race['real_server_overlap'], 'statuses': codes, 'reserved': dep['reserved'], 'capacity': dep['capacity'],
                         'blocked_connection_ids': race['observed'].get('blocked_distinct_connection_ids')})
        check(codes == [201, 409] and int(dep['reserved']) == 1 and int(dep['capacity']) == 1, attempts)
        if race['real_server_overlap']:
            break
    fixture['last_winner'] = winner
    check(attempts[-1]['overlap'], {'no overlap in any attempt': attempts})
    full = req('POST', f'{TL}/cart', role=winner['role'], body={'departure_id': D[dkey], 'quantity': 1})
    return 'PASS', {'attempts': attempts, 'loser_message': next(r['message'] for r in race['responses'] if r['status'] == 409), 'full_capacity_followup': full[0]}


def c_same_key():
    # Retry with a fresh key/cart if a request queued for a php -S worker (no DB-level overlap). Each
    # attempt must still produce exactly one inquiry and exactly one reserved seat.
    time.sleep(61)  # fresh submit bucket: up to 3 attempts x 2 concurrent submits + replay + changed payload
    attempts = []
    for n in range(1, 4):
        cid = cart_id('member', D['key'], 1)
        key = f'{RUN}-same{n}'
        race = barrier_race(f'same idempotency key, same member (attempt {n})', 'user', A['member']['user_id'], [submit_call('member', cid, key), submit_call('member', cid, key)])
        ids = {r['data_id'] for r in race['responses']}
        dep = departures()[D['key']]
        attempts.append({'overlap': race['real_server_overlap'], 'statuses': [r['status'] for r in race['responses']], 'inquiry_ids': sorted(i for i in ids if i), 'reserved': dep['reserved'],
                         'blocked_connection_ids': race['observed'].get('blocked_distinct_connection_ids')})
        check(all(r['status'] in (200, 201) for r in race['responses']) and len(ids) == 1 and None not in ids, attempts)
        check(int(dep['reserved']) == n, attempts)
        if race['real_server_overlap']:
            break
    check(attempts[-1]['overlap'], {'no overlap in any attempt': attempts})
    fixture['same_key'] = {'key': key, 'cart_id': cid, 'inquiry_id': ids.pop(), 'reserved_before': n}
    replay = req(*submit_call('member', cid, key)[0], **submit_call('member', cid, key)[1])
    changed = req(*submit_call('member', cid, key, 'Other Name')[0], **submit_call('member', cid, key, 'Other Name')[1])
    dep2 = departures()[D['key']]
    check(replay[0] in (200, 201) and replay[1]['data']['id'] == fixture['same_key']['inquiry_id'] and changed[0] == 409 and int(dep2['reserved']) == n,
          (replay[0], changed[0], dep2['reserved']))
    check(float(replay[1]['data'].get('total_amount')) == 12500.0, f"server amount {replay[1]['data'].get('total_amount')} != edited native price 12500")
    return 'PASS', {'attempts': attempts, 'exact_replay': replay[0], 'changed_payload_retry': changed[0], 'changed_payload_message': msg(changed[1]),
                    'amount': replay[1]['data'].get('total_amount'), 'reserved_after_replays': dep2['reserved']}


def c_cancel_decline():
    time.sleep(61)  # fresh submit/cancel buckets for retry inquiries
    attempts = []
    k = fixture['same_key']
    target = {'inquiry_id': k['inquiry_id'], 'cart_id': k['cart_id'], 'key': k['key'], 'dep': D['key']}
    for n in range(1, 4):
        iid, dkey = target['inquiry_id'], target['dep']
        before = int(departures()[dkey]['reserved'])
        race = barrier_race(f'member cancel vs admin decline (attempt {n})', 'inquiry', iid, [
            (('POST', f'{TL}/inquiries/{iid}/cancel'), {'role': 'member'}),
            (('PATCH', f'{TL}/admin/inquiries/{iid}'), {'role': 'admin', 'body': {'status': 'DECLINED', 'admin_note': 'W03R synthetic'}})])
        after = int(departures()[dkey]['reserved'])
        codes = sorted(r['status'] for r in race['responses'])
        again = req('POST', f'{TL}/inquiries/{iid}/cancel', role='member')
        replay = req(*submit_call('member', target['cart_id'], target['key'])[0], **submit_call('member', target['cart_id'], target['key'])[1])
        after2 = int(departures()[dkey]['reserved'])
        attempts.append({'inquiry': iid, 'overlap': race['real_server_overlap'], 'statuses': codes, 'reserved_before': before, 'reserved_after': after,
                         'repeat_cancel': again[0], 'replay_after_terminal': replay[0], 'replay_status': (replay[1].get('data') or {}).get('status'), 'reserved_after_replay': after2,
                         'winner': next(('cancel', 'decline')[i] for i, r in enumerate(race['responses']) if r['status'] == 200) if 200 in codes else None,
                         'loser_message': next((r['message'] for r in race['responses'] if r['status'] == 409), None),
                         'blocked_connection_ids': race['observed'].get('blocked_distinct_connection_ids')})
        check(codes == [200, 409] and after == before - 1 and after2 == after and replay[0] in (200, 201) and replay[1]['data']['id'] == iid, attempts)
        if race['real_server_overlap']:
            break
        # fresh member inquiry on the dedicated cancel/decline departure (capacity 2) for the next attempt
        cid = cart_id('member', D['cd'], 1)
        key = f'{RUN}-cd{n}'
        st, j, _ = req(*submit_call('member', cid, key)[0], **submit_call('member', cid, key)[1])
        check(st == 201, f'retry inquiry {st} {msg(j)}')
        target = {'inquiry_id': j['data']['id'], 'cart_id': cid, 'key': key, 'dep': D['cd']}
    check(attempts[-1]['overlap'], {'no overlap in any attempt': attempts})
    return 'PASS', {'attempts': attempts}


def c_capacity():
    attempts = []
    for n, (dkey, qty) in enumerate((('race', 2),), 1):
        cid = cart_id('member', D[dkey], qty)
        body = {'product_option_id': departures()[D[dkey]]['product_option_id'], 'departure_date': day(10), 'return_date': day(11), 'capacity': 1, 'is_active': True}
        race = barrier_race(f'submit qty2 vs admin capacity 2->1 (attempt {n})', 'departure', D[dkey], [
            submit_call('member', cid, f'{RUN}-cap{n}'), (('PUT', f"{TL}/admin/catalog/{P}/departures/{D[dkey]}"), {'role': 'admin', 'body': body})])
        dep = departures()[D[dkey]]
        codes = [r['status'] for r in race['responses']]
        attempts.append({'overlap': race['real_server_overlap'], 'submit': codes[0], 'admin_put': codes[1], 'messages': [r['message'] for r in race['responses']],
                         'capacity': dep['capacity'], 'reserved': dep['reserved'], 'blocked_connection_ids': race['observed'].get('blocked_distinct_connection_ids')})
        check(0 <= int(dep['reserved']) <= int(dep['capacity']) and sorted(codes) in ([200, 409], [201, 409]), attempts)
    # Only one capacity-2 departure is reserved for this race; a non-overlapping attempt is reported, not retried.
    check(attempts[-1]['overlap'], {'no overlap': attempts})
    return 'PASS', {'attempts': attempts}


for name, fn in (('overlap: last seat two members (departure row lock)', c_last_seat), ('overlap: same key same member (user row lock) + replay/409', c_same_key),
                 ('overlap: member cancel vs admin decline release once (inquiry row lock)', c_cancel_decline),
                 ('overlap: admin capacity hold vs submit cannot go below reserved (departure row lock)', c_capacity)):
    if name.startswith('overlap: member cancel') and 'same_key' not in fixture:
        results.append({'name': name, 'status': 'NOT_RUN', 'detail': 'depends on same-key inquiry'}); continue
    step(name, fn)


# ---------------------------------------------------------------- ownership / admin scope
def f_scope():
    w = fixture.get('last_winner') or {}
    if fixture.get('same_key'):
        iid, owner = fixture['same_key']['inquiry_id'], 'member'
    else:
        iid, owner = w.get('id'), w.get('role', 'member')
    foreign = 'other_member' if owner == 'member' else 'member'
    s = {'owner': owner, 'foreign_read': req('GET', f'{TL}/inquiries/{iid}', role=foreign)[0], 'foreign_cancel': req('POST', f'{TL}/inquiries/{iid}/cancel', role=foreign)[0],
         'member_admin_list': req('GET', f'{TL}/admin/inquiries', role=foreign)[0], 'member_admin_patch': req('PATCH', f'{TL}/admin/inquiries/{iid}', role=foreign, body={'status': 'UNDER_REVIEW'})[0],
         'anonymous_cart': req('GET', f'{TL}/cart', role=None)[0], 'admin_money_patch': req('PATCH', f'{TL}/admin/inquiries/{iid}', body={'status': 'UNDER_REVIEW', 'total_amount': 1})[0],
         'owner_read': req('GET', f'{TL}/inquiries/{iid}', role=owner)[0],
         'foreign_list_contains': iid in [int(r.get('id') or 0) for r in ((req('GET', f'{TL}/inquiries', role=foreign)[1].get('data') or {}).get('data') or [])]}
    check(s['foreign_read'] == 404 and s['foreign_cancel'] == 404 and s['member_admin_list'] == 403 and s['member_admin_patch'] == 403 and s['anonymous_cart'] == 401
          and s['admin_money_patch'] == 422 and s['owner_read'] == 200 and not s['foreign_list_contains'], s)
    return 'PASS', s


step('scope: foreign 404, member admin 403, anonymous 401, admin money field 422', f_scope)


# ---------------------------------------------------------------- support privacy (own synthetic question)
def s_support():
    st, j, _ = req('POST', f'{TL}/support/questions', role='member', body={'title': RUN + ' synthetic', 'content': RUN + ' synthetic travel question'})
    if st == 503:
        return 'BLOCKED', {'store': st, 'message': msg(j), 'reason': 'support board not ready on preview'}
    check(st == 201, f'{st} {msg(j)}')
    qid = j['data']['id']
    s = {'store': st, 'foreign_show': req('GET', f'{TL}/support/questions/{qid}', role='other_member')[0],
         'foreign_patch': req('PATCH', f'{TL}/support/questions/{qid}', role='other_member', body={'title': 'x' * 3, 'content': 'xyz'})[0],
         'owner_show': req('GET', f'{TL}/support/questions/{qid}', role='member')[0], 'admin_show': req('GET', f'{TL}/support/questions/{qid}')[0],
         'foreign_list_contains': f'"id": {qid}' in json.dumps(req('GET', f'{TL}/support/questions', role='other_member')[1]),
         'owner_list_contains': f'"id": {qid}' in json.dumps(req('GET', f'{TL}/support/questions', role='member')[1]),
         'anonymous': req('GET', f'{TL}/support/questions', role=None)[0]}
    fixture['support_question_id'] = qid
    check(s['foreign_show'] == 404 and s['foreign_patch'] == 404 and s['owner_show'] == 200 and s['admin_show'] == 200 and not s['foreign_list_contains'] and s['owner_list_contains'] and s['anonymous'] == 401, s)
    return 'PASS', s


step('support: private question owner/admin only, foreign 404 and not listed', s_support)


# ---------------------------------------------------------------- per-actor throttles (end of run; no writes)
def throttle(role, method, path, body, limit):
    codes = []
    for _ in range(limit + 1):
        codes.append(req(method, path, role=role, body=body)[0])
    return codes


def th_all():
    time.sleep(61)  # start every per-actor bucket fresh so earlier legitimate calls do not count
    out = {}
    sub = throttle('other_member', 'POST', f'{TL}/inquiries', {}, 10)
    out['submit_10'] = {'first_10': sorted(set(sub[:10])), '11th': sub[10]}
    out['member_submit_isolated'] = req('POST', f'{TL}/inquiries', role='member', body={})[0]
    out['other_cart_get_after_submit_limit'] = req('GET', f'{TL}/cart', role='other_member')[0]
    cart = throttle('other_member', 'POST', f'{TL}/cart', {}, 60)
    out['cart_60'] = {'first_60': sorted(set(cart[:60])), '61st': cart[60]}
    can = throttle('other_member', 'POST', f'{TL}/inquiries/999999999/cancel', None, 20)
    out['cancel_20'] = {'first_20': sorted(set(can[:20])), '21st': can[20]}
    adm = throttle('admin', 'PATCH', f'{TL}/admin/inquiries/999999999', {'status': 'UNDER_REVIEW'}, 60)
    out['admin_patch_60'] = {'first_60': sorted(set(adm[:60])), '61st': adm[60]}
    out['admin_list_after_patch_limit'] = req('GET', f'{TL}/admin/inquiries')[0]
    n, last = 0, None
    while n < 140:
        st, _, meta = req('GET', f'{TL}/cart', role='other_member')
        n += 1
        if st == 429:
            last = meta
            break
    out['workflow_120'] = {'extra_gets_until_429': n, 'prior_other_member_requests_in_window': 10 + 1 + 1 + 61 + 21, 'limit_header': (last or {}).get('ratelimit_limit')}
    out['member_cart_still_ok'] = req('GET', f'{TL}/cart', role='member')[0]
    ok = (out['submit_10']['11th'] == 429 and 429 not in sub[:10] and out['member_submit_isolated'] == 422 and out['other_cart_get_after_submit_limit'] == 200
          and out['cart_60']['61st'] == 429 and 429 not in cart[:60] and out['cancel_20']['21st'] == 429 and 429 not in can[:20]
          and out['admin_patch_60']['61st'] == 429 and 429 not in adm[:60] and out['admin_list_after_patch_limit'] == 200 and last is not None and out['member_cart_still_ok'] == 200)
    return ('PASS' if ok else 'FAIL'), out


step('throttle: per-actor submit10/cart60/cancel20/admin60/workflow120 (validation-failing probes only)', th_all)


# ---------------------------------------------------------------- cleanup (cancel/unpublish; guarded product never deleted)
def cleanup():
    out = {}
    time.sleep(61)  # let the deliberately exhausted buckets expire before owner cleanup calls
    for role in ('member', 'other_member'):
        st, j, _ = req('GET', f'{TL}/inquiries?per_page=50', role=role)
        rows = (j.get('data') or {}).get('data', []) if isinstance(j.get('data'), dict) else (j.get('data') or [])
        for r in rows:
            if RUN in json.dumps(r) or any(int(i.get('departure_id') or 0) in D.values() for i in r.get('items', [])):
                if r.get('status') in ('TEST_INQUIRY', 'UNDER_REVIEW', 'TEST_ACCEPTED'):
                    out[f"cancel_{r['id']}"] = req('POST', f"{TL}/inquiries/{r['id']}/cancel", role=role)[0]
        items = req('GET', f'{TL}/cart', role=role)[1].get('data')
        items = items.get('items', items) if isinstance(items, dict) else (items or [])
        for i in items:
            if int(i.get('departure_id') or 0) in D.values():
                out[f"cart_{role}_{i['id']}"] = req('DELETE', f"{TL}/cart/{i['id']}", role=role)[0]
    for name, dep in departures().items():
        if dep.get('product_option_id'):
            out[f'deactivate_{name}'] = req('PUT', f'{TL}/admin/catalog/{P}/departures/{name}', body={'product_option_id': dep['product_option_id'],
                                            'departure_date': dep['departure_date'][:10], 'return_date': dep['return_date'][:10], 'capacity': max(int(dep['capacity']), int(dep['reserved']), 1), 'is_active': False})[0]
    out['unpublish'] = req('PATCH', f'{TL}/admin/catalog/{P}', body={'published': False})[0]
    t = fixture['product_T']
    d, opts = current_opts(P)
    out['hide_travel_product'] = req('PUT', f'{EC}/admin/products/{P}', body={**t['payload'], 'display_status': 'hidden', 'stock_quantity': sum(o['stock_quantity'] for o in opts), 'options': opts})[0]
    o = fixture['product_O']
    out['ordinary_kept_hidden'] = req('GET', f"{EC}/admin/products/{o['id']}")[0]
    out['category_inactive'] = req('PUT', f"{EC}/admin/categories/{fixture['category_id']}", body={'name': {'ko': RUN + ' 분류', 'en': RUN + ' category'}, 'slug': RUN.lower(), 'is_active': False})[0]
    deps = departures()
    out['reserved_after'] = {k: v['reserved'] for k, v in deps.items()}
    out['active_after'] = {k: v['is_active'] for k, v in deps.items()}
    cat = req('GET', f'{TL}/catalog?per_page=48', role=None)[1].get('data')
    cat = cat.get('data', cat) if isinstance(cat, dict) else (cat or [])
    out['public_catalog_contains_fixture'] = P in [int(r.get('id') or 0) for r in cat]
    out['public_detail_after'] = req('GET', f'{TL}/catalog/{P}', role=None)[0]
    ok = all(int(v) == 0 for v in out['reserved_after'].values()) and not any(out['active_after'].values()) and out['unpublish'] == 200 and not out['public_catalog_contains_fixture']
    return ('PASS' if ok else 'FAIL'), out


step('cleanup: cancel own inquiries, delete own carts, deactivate own departures, unpublish/hide (no guarded delete)', cleanup)
flush()
print(json.dumps(scrub({'counts': {s: sum(1 for r in results if r['status'] == s) for s in ('PASS', 'FAIL', 'BLOCKED', 'NOT_RUN')}})))
