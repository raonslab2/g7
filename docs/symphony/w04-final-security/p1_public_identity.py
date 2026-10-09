#!/usr/bin/env python3
"""W04 final security phase 1: public support throttle identity on one real client IP + exact 600/601 boundary.

Usage: python3 -I p1_public_identity.py <private-dir> <out-dir> <parent-root> [atomicity]
- Same real loopback IP for every actor; no forwarded-IP headers, no cache seeding/reset, no clock/config change.
- Reads the parent's native file-cache counters READ-ONLY for the exact rate-limiter keys (integer values only).
- Never exhausts the shared anonymous IP bucket (other reviewers share 127.0.0.1).
"""
import hashlib, os, sys, threading, time
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from w04f_common import Ctx, TL, check, msg  # noqa: E402

PRIV, OUT, PARENT = sys.argv[1:4]
MODE = sys.argv[4] if len(sys.argv) > 4 else 'identity'
C = Ctx(PRIV, OUT, 'p1-' + MODE + '-' + time.strftime('%H%M%S', time.gmtime()))
PUB = TL + '/support/faqs'
CACHE = os.path.join(PARENT, 'storage/framework/cache/data')


def cache_counter(key):
    """Native FileStore path = sha1(key) split 2/2; payload = 10-digit expiry + value. Read only."""
    h = hashlib.sha1(key.encode()).hexdigest()
    p = os.path.join(CACHE, h[:2], h[2:4], h)
    if not os.path.isfile(p):
        return {'key_kind': key.split(':')[0], 'present': False}
    raw = open(p, 'rb').read()
    val = raw[10:].decode('ascii', 'replace').strip()
    if val.startswith('i:') and val.endswith(';'):  # PHP serialized integer
        val = val[2:-1]
    return {'key_kind': key.split(':')[0], 'present': True, 'expires_unix': int(raw[:10]), 'value': int(val) if val.lstrip('-').isdigit() else 'non-integer',
            'mtime_utc': time.strftime('%H:%M:%S', time.gmtime(os.path.getmtime(p)))}


def user_key(prefix, uid):
    return prefix + hashlib.sha1(str(uid).encode()).hexdigest()


def ip_key(prefix):
    return prefix + hashlib.sha1('|127.0.0.1'.encode()).hexdigest()


def burst(role, n, lanes):
    """n public reads by role over `lanes` concurrent sequential lanes; returns list of (status, remaining, t0, t1)."""
    out, idx, lk = [], [0], threading.Lock()

    def lane():
        while True:
            with lk:
                if idx[0] >= n:
                    return
                idx[0] += 1
            st, _, m = C.req('GET', PUB, role=role)
            with lk:
                out.append((st, m['remaining'], m['limit'], m['t0'], m['t1']))
    ts = [threading.Thread(target=lane) for _ in range(lanes)]
    for t in ts:
        t.start()
    for t in ts:
        t.join()
    return sorted(out, key=lambda x: x[3])


if MODE == 'identity':
    st = {}

    def s_baseline():
        g0 = C.req('GET', PUB)
        st['g0'] = g0
        seq = []
        for _ in range(40):
            s, _, m = C.req('GET', PUB, role='member')
            seq.append((s, m['remaining']))
        g1 = C.req('GET', PUB)
        o1 = C.req('GET', PUB, role='other_member')
        st['admin_first'] = a1 = C.req('GET', PUB, role='admin')
        m41 = C.req('GET', PUB, role='member')
        rem = [int(r) for _, r in seq]
        guest_delta = int(g0[2]['remaining']) - int(g1[2]['remaining'])
        detail = {'guest_before': {'status': g0[0], 'limit': g0[2]['limit'], 'remaining': g0[2]['remaining']},
                  'member_40_statuses': sorted({s for s, _ in seq}), 'member_remaining_first_last': [rem[0], rem[-1]],
                  'member_remaining_strictly_decreasing_by_1': all(b == a - 1 for a, b in zip(rem, rem[1:])),
                  'guest_after_member_40': {'status': g1[0], 'remaining': g1[2]['remaining'], 'delta_vs_before': guest_delta},
                  'other_member_first': {'status': o1[0], 'limit': o1[2]['limit'], 'remaining': o1[2]['remaining']},
                  'admin_first': {'status': a1[0], 'limit': a1[2]['limit'], 'remaining': a1[2]['remaining']},
                  'member_41st': {'status': m41[0], 'remaining': m41[2]['remaining']}}
        check(all(s == 200 for s, _ in seq) and g0[0] == g1[0] == o1[0] == a1[0] == m41[0] == 200, 'statuses')
        check(rem[0] == 599 and rem[-1] == 560 and detail['member_remaining_strictly_decreasing_by_1'], 'member window not fresh/exact')
        check(o1[2]['remaining'] == '599' and a1[2]['remaining'] == '599', 'other/admin did not get a fresh own counter after member consumption')
        check(int(m41[2]['remaining']) == 559, 'member counter not continued')
        # Guest IP bucket must not have absorbed the 40 member reads. Other loopback clients may add small noise.
        check(guest_delta < 40, f'guest IP bucket absorbed authenticated reads: delta {guest_delta}')
        detail['guest_delta_note'] = 'exact 1 means no other loopback traffic in between' if guest_delta == 1 else 'other loopback clients (separate reviewers) share this anonymous IP bucket'
        return 'PASS', detail
    C.step('same-IP valid member/other/admin public counters separate after member read consumption; guest IP bucket untouched', s_baseline)

    def s_cache():
        keys = {r: cache_counter(user_key('travel-lab-support-public:', C.uid[r])) for r in ('member', 'other_member', 'admin')}
        keys['anonymous_ip'] = cache_counter(ip_key('travel-lab-support-public:'))
        keys['legacy_unprefixed_member'] = cache_counter(hashlib.sha1(str(C.uid['member']).encode()).hexdigest())
        check(keys['member']['present'] and keys['member']['value'] == 41, f'member file counter {keys["member"]}')
        check(keys['other_member'].get('value') == 1 and keys['admin'].get('value') == 1, 'other/admin file counters')
        check(keys['anonymous_ip']['present'], 'anonymous ip counter absent')
        return 'PASS', {'cache_store': 'native FileStore (storage/framework/cache/data); value read-only, no reset', 'counters': keys}
    C.step('installed native cache backend: per-user signed public keys exist in FileStore with exact counts', s_cache)

    def s_admin_boundary():
        t_first = st['admin_first'][2]['t0']
        b = burst('admin', 599, 2)
        t_600 = max(x[4] for x in b)
        s601, j601, m601 = C.req('GET', PUB, role='admin')
        elapsed_601 = m601['t1'] - t_first
        rems = [int(x[1]) for x in b if x[1] is not None]
        dup = len(rems) - len(set(rems))
        extra = []
        if s601 == 200:  # quantify lost increments, bounded to the same window
            while time.time() - t_first < 58 and len(extra) < 60:
                s, _, m = C.req('GET', PUB, role='admin')
                extra.append((s, m['remaining']))
                if s == 429:
                    break
        detail = {'lanes': 2, 'requests_1_to_600_statuses': sorted({x[0] for x in b} | {st['admin_first'][0]}), 'count_1_to_600': len(b) + 1,
                  'remaining_after_600th_min': min(rems) if rems else None, 'duplicate_remaining_values': dup,
                  'elapsed_first_to_600th_s': round(t_600 - t_first, 3),
                  'request_601': {'status': s601, 'limit': m601['limit'], 'remaining': m601['remaining'], 'retry_after': m601['retry_after'], 'message': msg(j601), 'ms': m601['ms']},
                  'elapsed_first_to_601_complete_s': round(elapsed_601, 3), 'post_601_until_429': extra,
                  'file_counter_after': cache_counter(user_key('travel-lab-support-public:', C.uid['admin']))}
        if elapsed_601 >= 60:
            return 'NOT_RUN', {**detail, 'reason': 'runtime could not finish 601 requests inside one 60s window; boundary not claimed'}
        check(all(x[0] == 200 for x in b), 'a request 2..600 was not 200')
        check(s601 == 429 and m601['limit'] == '600' and m601['remaining'] == '0', 'request 601 not 429 limit600 remaining0')
        check(dup == 0, 'lost/duplicated increments within the 2-lane boundary run')
        return 'PASS', detail
    C.step('admin exact public boundary: 600 accepted then 601st 429 inside one 60s own window', s_admin_boundary)

    def s_after():
        q = C.req('GET', TL + '/support/questions', role='admin')
        m = C.req('GET', PUB, role='member')
        o = C.req('GET', PUB, role='other_member')
        g = C.req('GET', PUB)
        detail = {'admin_questions_after_public_exhaustion': [q[0], q[2]['limit'], q[2]['remaining']], 'member_public': [m[0], m[2]['remaining']],
                  'other_public': [o[0], o[2]['remaining']], 'guest_public': [g[0], g[2]['remaining']]}
        check(q[0] == 200 and q[2]['limit'] == '120' and m[0] == 200 and o[0] == 200 and g[0] == 200, 'separation after admin exhaustion')
        return 'PASS', detail
    C.step('after admin public exhaustion: admin question prefix, member/other actors and guest IP remain available', s_after)

elif MODE == 'boundary':
    ROLE, LANES = sys.argv[5], int(sys.argv[6])

    def s_boundary():
        first = C.req('GET', PUB, role=ROLE)
        if first[2]['remaining'] != '599':
            return 'NOT_RUN', {'reason': 'own window not fresh at start; no reset performed', 'remaining': first[2]['remaining']}
        t_first = first[2]['t0']
        b = burst(ROLE, 599, LANES)
        t_600 = max(x[4] for x in b)
        s601, j601, m601 = C.req('GET', PUB, role=ROLE)
        elapsed = m601['t1'] - t_first
        rems = [int(x[1]) for x in b if x[1] is not None]
        dup = len(rems) - len(set(rems))
        extra = []
        if s601 == 200:
            while time.time() - t_first < 58 and len(extra) < 60:
                s, _, m = C.req('GET', PUB, role=ROLE)
                extra.append((s, m['remaining']))
                if s == 429:
                    break
        lat = sorted(x[4] - x[3] for x in b)
        detail = {'role': ROLE, 'lanes': LANES, 'first': {'status': first[0], 'remaining': first[2]['remaining'], 'limit': first[2]['limit']},
                  'statuses_2_600': sorted({x[0] for x in b}), 'count_1_to_600': len(b) + 1, 'min_remaining_2_600': min(rems) if rems else None,
                  'duplicate_remaining_values': dup, 'elapsed_first_to_600th_s': round(t_600 - t_first, 3),
                  'latency_ms_p50_p95': [round(lat[len(lat) // 2] * 1000, 1), round(lat[int(len(lat) * .95)] * 1000, 1)],
                  'request_601': {'status': s601, 'limit': m601['limit'], 'remaining': m601['remaining'], 'retry_after': m601['retry_after'], 'ms': m601['ms']},
                  'elapsed_first_to_601_complete_s': round(elapsed, 3), 'post_601_until_429': extra,
                  'file_counter_after': cache_counter(user_key('travel-lab-support-public:', C.uid[ROLE]))}
        if elapsed >= 60:
            return 'NOT_RUN', {**detail, 'reason': 'runtime could not finish 601 requests inside one 60s window; boundary not claimed'}
        check(all(x[0] == 200 for x in b), 'a request 2..600 was not 200')
        detail['duplicate_note'] = 'duplicate X-RateLimit-Remaining values alone can come from concurrent reads of one counter; lost increments are measured by accepted requests before the first 429'
        if s601 == 200:
            accepted = 601 + sum(1 for x in extra if x[0] == 200)
            detail['accepted_before_first_429'] = accepted
            detail['lost_increments'] = accepted - 600 if any(x[0] == 429 for x in extra) else None
            return 'OBSERVED', {**detail, 'note': 'concurrent native FileStore read-modify-write lost increments; exact 601 boundary not established in this run'}
        check(s601 == 429 and m601['limit'] == '600' and m601['remaining'] == '0', 'request 601 not 429 limit600 remaining0')
        return 'PASS', detail
    C.step(f'{ROLE} exact public boundary: 600 accepted then 601st 429 inside one 60s own window ({LANES} lanes)', s_boundary)

elif MODE == 'cache_inspect':
    def s_inspect():
        reads = {r: C.req('GET', PUB, role=r) for r in ('member', 'other_member', 'admin')}
        g = C.req('GET', PUB)
        keys = {r: cache_counter(user_key('travel-lab-support-public:', C.uid[r])) for r in ('member', 'other_member', 'admin')}
        keys['anonymous_ip'] = cache_counter(ip_key('travel-lab-support-public:'))
        db = C.dbq('cache_rows')
        hdr = {r: int(v[2]['remaining']) for r, v in reads.items()}
        d = {'headers_remaining': {**hdr, 'guest': int(g[2]['remaining'])}, 'file_counters': keys, 'db_cache': db}
        check(all(keys[r]['present'] and keys[r]['value'] == 600 - hdr[r] for r in hdr), 'per-user FileStore counter does not equal header accounting')
        check(keys['anonymous_ip']['present'] and keys['anonymous_ip']['value'] >= 600 - int(g[2]['remaining']), 'anonymous IP key')
        check(db['travel_lab_rate_rows_in_db_cache'] == 0, 'rate rows in DB cache')
        return 'PASS', d
    C.step('read-only native cache backend inspection: per-user and anonymous-IP public keys in FileStore match headers; DB cache has no travel rate rows', s_inspect)

elif MODE == 'atomicity':
    def s_atomic():
        first = C.req('GET', PUB, role='other_member')
        check(first[2]['remaining'] == '599', f'other_member window not fresh: {first[2]["remaining"]} (no reset performed; rerun after natural expiry)')
        t_first = first[2]['t0']
        b = burst('other_member', 599, 8)
        rems = [int(x[1]) for x in b if x[1] is not None]
        dup = len(rems) - len(set(rems))
        seq = []
        while time.time() - t_first < 58 and len(seq) < 120:
            s, _, m = C.req('GET', PUB, role='other_member')
            seq.append((s, m['remaining'], m['limit']))
            if s == 429:
                break
        first429 = next((600 + i + 1 for i, x in enumerate(seq) if x[0] == 429), None)
        fc = cache_counter(user_key('travel-lab-support-public:', C.uid['other_member']))
        detail = {'lanes': 8, 'server_workers_declared': 4, 'elapsed_600_s': round(max(x[4] for x in b) - t_first, 3), 'statuses_1_600': sorted({x[0] for x in b}),
                  'duplicate_remaining_values_in_concurrent_600': dup, 'distinct_remaining_values': len(set(rems)), 'first_429_request_index': first429,
                  'sequential_after_600': seq[:5] + (['...'] if len(seq) > 5 else []) + seq[-2:] if len(seq) > 7 else seq, 'file_counter_after': fc}
        if first429 is None:
            return 'FAIL', {**detail, 'reason': 'no 429 observed inside the window'}
        if first429 == 601 and dup == 0:
            return 'PASS', {**detail, 'note': 'no lost increment observed in this bounded run; not a proof of FileStore atomicity'}
        return 'OBSERVED', {**detail, 'note': 'concurrent FileStore read-modify-write lost increments: limit enforced late by the number of lost increments'}
    C.step('concurrent 8-lane public burst on native FileStore: increment atomicity bounded observation', s_atomic)
C.flush()
