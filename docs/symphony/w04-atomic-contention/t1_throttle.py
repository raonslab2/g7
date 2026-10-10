#!/usr/bin/env python3
"""W04 atomic contention (nonauthor, fixed fa552317) — live native DatabaseStore throttle + admission lock.

Usage: python3 -I t1_throttle.py <private-dir> <out-dir> <mode> [args]
  identity_sequential            member: same-IP actor/guest separation, then sequential 600 x 200 + 601st 429 in one own window
  burst <role> <lanes> <total>   fresh own window: concurrent real HTTP burst; admitted must be <= 600, 429 after exhaustion, no reset
  admission <role>               own-counter-row FOR UPDATE barrier: native lock wait (block 3 -> 503) and 25 s headroom (-> 503)
  wait_fresh <role> [prefix]     waits (bounded 75 s) until the own counter row is absent/expired; never deletes it

Rules: same real loopback IP, no forwarded headers, no cache seed/reset/clear, no clock/config/quota change. DB reads only for
the own actors' exact rate-limiter keys (db_guard cache_keys). A window overrun is NOT_RUN, never PASS.
"""
import json, os, subprocess, sys, threading, time
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from c_common import HERE, TL, Ctx, check, msg  # noqa: E402

PRIV, OUT, MODE = sys.argv[1:4]
ARGS = sys.argv[4:]
C = Ctx(PRIV, OUT, 't1-' + MODE + ('-' + '-'.join(ARGS) if ARGS else '') + '-' + time.strftime('%H%M%S', time.gmtime()))
PUB = TL + '/support/faqs'
PFX = 'travel-lab-support-public:'


def own_counter(role, prefix=PFX):
    return C.cache([prefix], [role])[(prefix, C.uid[role])]


def wait_fresh(role, prefix=PFX, limit=75):
    t0 = time.time()
    while time.time() - t0 < limit:
        k = own_counter(role, prefix)
        if k['counter_rows'] == 0 or k['counter_expired']:
            return {'waited_s': round(time.time() - t0, 1), 'row': k}
        time.sleep(min(5, max(1, (k['counter_expires_in_s'] or 1) + 1)))
    raise AssertionError(f'own {role} window did not expire within {limit}s (no reset performed)')


def lanes_burst(role, n, lanes, path=PUB, deadline=None):
    out, idx, lk = [], [0], threading.Lock()

    def lane(li):
        while True:
            with lk:
                if idx[0] >= n or (deadline is not None and time.time() >= deadline):
                    return
                idx[0] += 1
            try:
                st, j, m = C.req('GET', path, role=role)
            except Exception as e:  # noqa: BLE001 - recorded, not hidden
                st, j, m = 0, {'error': type(e).__name__}, {'t0': time.time(), 't1': time.time(), 'remaining': None, 'limit': None, 'retry_after': None}
            with lk:
                out.append({'lane': li, 'status': st, 'remaining': m.get('remaining'), 'limit': m.get('limit'), 'retry_after': m.get('retry_after'),
                            't0': m['t0'], 't1': m['t1'], 'message': msg(j) if st not in (200,) else None})
    ts = [threading.Thread(target=lane, args=(i,)) for i in range(lanes)]
    for t in ts:
        t.start()
    for t in ts:
        t.join()
    return sorted(out, key=lambda x: x['t0'])


if MODE == 'wait_fresh':
    role = ARGS[0]
    prefix = ARGS[1] if len(ARGS) > 1 else PFX
    C.step(f'wait for natural expiry of own {role} {prefix} window (no reset)', lambda: ('PASS', wait_fresh(role, prefix)))

elif MODE == 'identity_sequential':
    st = {}

    def s_identity():
        pre = {r: own_counter(r) for r in ('member', 'other_member', 'admin')}
        check(all(v['counter_rows'] == 0 or v['counter_expired'] for v in pre.values()), f'own windows not fresh: {pre}')
        g0 = C.req('GET', PUB)
        seq = []
        for _ in range(40):
            s, _, m = C.req('GET', PUB, role='member')
            seq.append((s, m['remaining'], m['t0'], m['t1']))
        st['seq'] = seq
        g1 = C.req('GET', PUB)
        o1 = C.req('GET', PUB, role='other_member')
        a1 = C.req('GET', PUB, role='admin')
        db = {r: own_counter(r) for r in ('member', 'other_member', 'admin')}
        rem = [int(r) for _, r, _, _ in seq]
        gd = int(g0[2]['remaining']) - int(g1[2]['remaining'])
        d = {'pre_db_rows_fresh': True, 'guest_before': [g0[0], g0[2]['limit'], g0[2]['remaining']], 'member_40': [sorted({s for s, *_ in seq}), rem[0], rem[-1]],
             'member_strictly_by_1': all(b == a - 1 for a, b in zip(rem, rem[1:])), 'guest_after': [g1[0], g1[2]['remaining'], gd],
             'other_first': [o1[0], o1[2]['limit'], o1[2]['remaining']], 'admin_first': [a1[0], a1[2]['limit'], a1[2]['remaining']],
             'db_counters': {r: {k: v[k] for k in ('counter', 'counter_expires_in_s', 'timer_rows', 'admission_lock_rows', 'cache_prefix_length')} for r, v in db.items()}}
        check(all(s == 200 for s, *_ in seq) and rem[0] == 599 and rem[-1] == 560 and d['member_strictly_by_1'], 'member window not fresh/exact')
        check(o1[2]['remaining'] == '599' and a1[2]['remaining'] == '599', 'other/admin not separate fresh counters')
        check(gd < 40, f'guest IP bucket absorbed member reads {gd}')
        check(db['member']['counter'] == 40 and db['other_member']['counter'] == 1 and db['admin']['counter'] == 1, 'DatabaseStore counters != header accounting')
        check(all(v['admission_lock_rows'] == 0 for v in db.values()), 'surviving own admission lock row')
        d['guest_delta_note'] = 'exact 1: no other loopback traffic between the two guest reads' if gd == 1 else 'other loopback clients share the anonymous IP bucket'
        return 'PASS', d
    C.step('same-IP member/other/admin separate per-user DatabaseStore counters; guest IP bucket not consumed by member reads', s_identity)

    def s_sequential():
        seq = st['seq']
        t_first = seq[0][2]
        rest = []
        for _ in range(560):
            s, _, m = C.req('GET', PUB, role='member')
            rest.append((s, m['remaining'], m['t0'], m['t1']))
        s601, j601, m601 = C.req('GET', PUB, role='member')
        db = own_counter('member')
        allr = seq + rest
        elapsed_600 = allr[-1][3] - t_first
        elapsed_601 = m601['t1'] - t_first
        rem = [int(x[1]) for x in allr]
        lat = sorted(x[3] - x[2] for x in allr)
        d = {'requests_1_600': len(allr), 'statuses_1_600': sorted({x[0] for x in allr}), 'remaining_first_last': [rem[0], rem[-1]],
             'strictly_decreasing_by_1': all(b == a - 1 for a, b in zip(rem, rem[1:])), 'elapsed_first_to_600th_s': round(elapsed_600, 3),
             'latency_ms_p50_p95_max': [round(lat[len(lat) // 2] * 1000, 1), round(lat[int(len(lat) * .95)] * 1000, 1), round(lat[-1] * 1000, 1)],
             'request_601': {'status': s601, 'limit': m601['limit'], 'remaining': m601['remaining'], 'retry_after': m601['retry_after'], 'message': msg(j601)},
             'elapsed_first_to_601_s': round(elapsed_601, 3), 'db_counter_after': {k: db[k] for k in ('counter', 'counter_expires_in_s', 'timer_rows', 'admission_lock_rows')}}
        if elapsed_601 >= 59 or s601 == 200 and (db['counter_rows'] == 0 or db['counter'] < 600):
            return 'NOT_RUN', {**d, 'reason': 'sequential 601 requests did not finish inside one own 60 s window on this runtime; boundary not claimed, no reset/retry forcing'}
        check(all(x[0] == 200 for x in allr) and d['strictly_decreasing_by_1'] and rem[-1] == 0, 'requests 1..600 not exact 200 sequence')
        check(s601 == 429 and m601['limit'] == '600' and m601['remaining'] == '0', 'request 601 not 429 limit 600 remaining 0')
        check(db['counter'] == 600 and db['admission_lock_rows'] == 0, 'DB counter != 600 or surviving lock')
        return 'PASS', d
    C.step('member sequential 600 x 200 then 601st 429 inside one fresh own 60 s window; DatabaseStore counter exactly 600; no surviving lock', s_sequential)

elif MODE == 'burst':
    role, lanes, total = ARGS[0], int(ARGS[1]), int(ARGS[2])

    def s_burst():
        pre = own_counter(role)
        check(pre['counter_rows'] == 0 or pre['counter_expired'], f'own {role} window not fresh (no reset performed): {pre}')
        dl0 = C.deadlocks()
        first = C.req('GET', PUB, role=role)
        check(first[0] == 200 and first[2]['remaining'] == '599', f'first read not fresh 599: {first[0]} {first[2]["remaining"]}')
        t_first = first[2]['t0']
        # Cross-actor control: a different own actor reads the UNthrottled public catalog once per second during the burst.
        ctl_role = 'member' if role != 'member' else 'admin'
        base = [C.req('GET', TL + '/catalog', role=ctl_role)[2]['ms'] for _ in range(0)]
        ctl, stop = [], [False]

        def sampler():
            while not stop[0]:
                st_, _, m_ = C.req('GET', TL + '/catalog', role=ctl_role, timeout=60)
                ctl.append((round(m_['t0'] - t_first, 1), st_, m_['ms']))
                time.sleep(1)
        th = threading.Thread(target=sampler)
        th.start()
        b = lanes_burst(role, total - 1, lanes, deadline=t_first + 58)
        stop[0] = True
        th.join(timeout=70)
        db = own_counter(role)
        dl1 = C.deadlocks()
        sc = {}
        for x in b:
            sc[str(x['status'])] = sc.get(str(x['status']), 0) + 1
        admitted = 1 + sc.get('200', 0)
        t429 = [x for x in b if x['status'] == 429]
        first429_t0 = min((x['t0'] for x in t429), default=None)
        # Sound ordering criterion: a request SENT after a 429 response was fully RECEIVED is decided later, so it must not be admitted.
        first429_done = min((x['t1'] for x in t429), default=None)
        after429_200 = [x for x in b if x['status'] == 200 and first429_done is not None and x['t0'] > first429_done]
        last_t1 = max(x['t1'] for x in b)
        win_end_est = t_first + 59
        rem = [int(x['remaining']) for x in b if x['status'] == 200 and x['remaining'] is not None]
        bad429 = [x for x in t429 if x['limit'] != '600' or x['remaining'] != '0']
        lat = sorted(x['t1'] - x['t0'] for x in b)
        d = {'role': role, 'lanes': lanes, 'http_calls': total, 'status_counts_incl_first': {**sc, '200': admitted}, 'admitted_200_total': admitted,
             'busy_503': sc.get('503', 0), 'errors_or_other': {k: v for k, v in sc.items() if k not in ('200', '429', '503')},
             'elapsed_first_to_last_response_s': round(last_t1 - t_first, 3), 'first_429_at_s': round(first429_t0 - t_first, 3) if first429_t0 else None,
             'admitted_sent_after_a_429_completed': len(after429_200), 'min_remaining_header_200': min(rem) if rem else None,
             'distinct_remaining_values_200': len(set(rem)), 'malformed_429': len(bad429), 'retry_after_values': sorted({x['retry_after'] for x in t429})[:5],
             'latency_ms_p50_p95_max': [round(lat[len(lat) // 2] * 1000, 1), round(lat[int(len(lat) * .95)] * 1000, 1), round(lat[-1] * 1000, 1)],
             'db_counter_after': {k: db[k] for k in ('counter', 'counter_expires_in_s', 'timer_rows', 'admission_lock_rows')},
             'innodb_deadlocks_global_before_after': [dl0, dl1], 'issued_calls': 1 + len(b),
             'cross_actor_unthrottled_catalog_ms': {'role': ctl_role, 'samples': len(ctl), 'p50': sorted(x[2] for x in ctl)[len(ctl) // 2] if ctl else None,
                                                    'max': max((x[2] for x in ctl), default=None), 'non_200': sum(1 for x in ctl if x[1] != 200)}}
        if first429_t0 is None:
            return 'NOT_RUN', {**d, 'reason': 'budget of 600 not reached inside one own window at this runtime throughput (no 429 observed); upper-bound property not exercised at 600, no reset/forcing'}
        if last_t1 >= win_end_est:
            return 'NOT_RUN', {**d, 'reason': 'burst did not complete inside one own window; no claim, no reset'}
        check(admitted <= 600, f'OVER-ADMISSION {admitted} > 600')
        check(sc.get('429', 0) > 0 and not bad429, '429 missing or malformed after exhaustion')
        check(not after429_200, 'a request was admitted after exhaustion inside the same window (reset/lost increment)')
        nonbusy = total - sc.get('503', 0) - sum(v for k, v in sc.items() if k not in ('200', '429', '503'))
        check(nonbusy < 600 or admitted == 600, f'budget not fully usable: admitted {admitted} with {nonbusy} non-busy calls')
        check(db['counter'] == admitted and db['admission_lock_rows'] == 0, f'DB counter {db["counter"]} != admitted {admitted} or surviving lock')
        return 'PASS', d
    C.step(f'{role} concurrent {lanes}-lane burst of {total} real HTTP calls in one fresh own window: admitted <= 600 (exact), correct 429, no reset; DB counter == admitted', s_burst)

elif MODE == 'admission':
    role = ARGS[0]
    prefix = PFX

    def barrier_run(hold_s, extra_calls):
        p = subprocess.Popen(['php', os.path.join(HERE, 'db_guard.php'), PRIV, 'barrier', json.dumps({'kind': 'cache', 'limit_prefix': prefix, 'users': [C.uid[role]], 'min_waiters': 1, 'sustain_s': hold_s, 'max_hold_s': hold_s + 2})],
                             stdin=subprocess.PIPE, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
        locked = json.loads(p.stdout.readline() or '{}')
        check(locked.get('event') == 'LOCKED', f'barrier not locked {p.stderr.read()[:200] if locked == {} else locked}')
        out = [None] * (1 + extra_calls)
        mid = {}

        def run(i):
            out[i] = C.req('GET', PUB, role=role, timeout=90)
        ta = threading.Thread(target=run, args=(0,))
        ta.start()
        time.sleep(0.6)
        mid['lock_rows_while_A_blocked'] = own_counter(role)['admission_lock_rows']
        tb = [threading.Thread(target=run, args=(i,)) for i in range(1, 1 + extra_calls)]
        for t in tb:
            t.start()
            time.sleep(0.05)
        p.stdin.write('OBSERVE\n'); p.stdin.flush()
        obs = json.loads(p.stdout.readline() or '{}')
        for t in tb:
            t.join(timeout=30)
        mid['B_done_before_release'] = [o is not None for o in out[1:]]
        mid['A_pending_at_release'] = out[0] is None
        p.stdin.write('RELEASE\n'); p.stdin.flush()
        rel = json.loads(p.stdout.readline() or '{}')
        p.wait(timeout=30)
        ta.join(timeout=90)
        ra = rel.get('at', 0)
        res = [{'status': o[0], 'message': msg(o[1]), 'retry_after': o[2]['retry_after'], 'limit': o[2]['limit'], 'sent_before_release_s': round(ra - o[2]['t0'], 3),
                'completed_after_release_s': round(o[2]['t1'] - ra, 3), 'duration_s': round(o[2]['t1'] - o[2]['t0'], 3)} for o in out]
        return {'barrier_connection': locked.get('barrier_connection_id'), 'held_for_s': obs.get('held_for_s'), 'waiting_ids': obs.get('distinct_waiting_connection_ids'),
                'first_detection': (obs.get('first_detection') or {}).get('at_s'), **mid, 'responses': res, 'after': {k: v for k, v in own_counter(role).items() if k in ('counter', 'admission_lock_rows', 'counter_expires_in_s')}}

    def s_wait():
        first = C.req('GET', PUB, role=role)
        check(first[0] == 200, f'seed read {first[0]}')
        c0 = own_counter(role)['counter']
        r = barrier_run(5.0, 2)
        a, bs = r['responses'][0], r['responses'][1:]
        r['counter_before'] = c0
        check(r['A_pending_at_release'] and a['status'] == 200 and a['sent_before_release_s'] >= 4.5, f'A not blocked then admitted {a}')
        check(r['lock_rows_while_A_blocked'] == 1, 'admission lock row not held while A blocked inside the native increment')
        check(all(b['status'] == 503 and b['retry_after'] == '1' and 2.8 <= b['duration_s'] <= 6 for b in bs) and all(r['B_done_before_release']), f'B not 503 after ~3 s {bs}')
        check(r['after']['admission_lock_rows'] == 0 and r['after']['counter'] == c0 + 1, 'counter/lock after: busy requests must not count; lock released')
        return 'PASS', r
    C.step(f'{role} admission lock wait: own counter row held 5 s -> holder A waits then 200; same-actor B/C get native block(3) 503 Retry-After 1 without counting; no surviving lock', s_wait)

    def s_headroom():
        c0 = own_counter(role)['counter']
        r = barrier_run(26.5, 0)
        a = r['responses'][0]
        r['counter_before'] = c0
        check(r['A_pending_at_release'] and a['status'] == 503 and a['retry_after'] == '1' and a['duration_s'] >= 25, f'headroom not enforced {a}')
        check(r['after']['admission_lock_rows'] == 0, 'surviving lock after headroom rejection')
        r['counter_note'] = 'the native hit was recorded before the headroom check, so the rejected request consumed one unit (bounded, author-documented)'
        return 'PASS', r
    C.step(f'{role} admission headroom: native increment blocked 26.5 s -> request rejected 503 (>=25 s), lock released, controller not run', s_headroom)
C.flush()
