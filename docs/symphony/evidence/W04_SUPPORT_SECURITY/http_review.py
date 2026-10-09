#!/usr/bin/env python3
"""Nonauthor APP-only HTTP checks. Secrets/bodies remain in memory; no cookies.

Run: python3 -I http_review.py ACCESS_JSON OUTPUT_JSON
Only this request's fresh actors are used. No cache reset, sleep, DB, or auth bypass.
"""
import collections
import datetime
import hashlib
import json
import os
import stat
import sys
import time
import urllib.error
import urllib.request

SHA = '598a89fff702d51c1405f1a5952d95ab1d2651f4'
ACCESS, OUT = sys.argv[1:]
assert stat.S_IMODE(os.stat(ACCESS).st_mode) == 0o600
assert stat.S_IMODE(os.stat(os.path.dirname(ACCESS)).st_mode) == 0o700
assert not os.path.islink(ACCESS)
A = json.load(open(ACCESS))
assert A['source_sha'] == SHA and A['db'] == 'req81_travel_lab'
assert A['base_url'] == 'http://127.0.0.1:18871'
assert datetime.datetime.fromisoformat(A['expires_at'].replace('Z', '+00:00')) > datetime.datetime.now(datetime.timezone.utc)
TL = '/api/modules/raonslab-travel_lab'
BOARD = '/api/modules/sirsoft-board'
SLUG = 'travel-lab-questions'
RUN = 'W04S-' + datetime.datetime.now(datetime.timezone.utc).strftime('%H%M%S')
tokens, fixtures, records, checks = {}, [], [], []
start = time.monotonic()
opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))


def save():
    obj = {'source_sha': SHA, 'tree': '9e00273bdf18d6a713343755aac54f44a9b032b4',
           'run': RUN, 'scope': 'actual loopback HTTP; fresh distinct actors; API only',
           'elapsed_seconds': round(time.monotonic() - start, 3), 'checks': checks,
           'requests': records, 'created_private_questions': len(fixtures),
           'counts': dict(collections.Counter(x['result'] for x in checks))}
    with open(OUT, 'w') as f:
        json.dump(obj, f, indent=2)


def check(name, ok, **facts):
    checks.append({'name': name, 'result': 'PASS' if ok else 'FAIL', **facts})
    print(checks[-1]['result'], name, flush=True)
    save()


def req(method, path, role=None, body=None, label=None):
    h = {'Accept': 'application/json', 'Accept-Language': 'en'}
    if role:
        h['Authorization'] = 'Bearer ' + tokens[role]
    data = None
    if body is not None:
        data = json.dumps(body).encode()
        h['Content-Type'] = 'application/json'
    r = urllib.request.Request(A['base_url'] + path, data=data, headers=h, method=method)
    t0 = time.monotonic()
    try:
        with opener.open(r, timeout=30) as x:
            b, status, headers = x.read(), x.status, x.headers
    except urllib.error.HTTPError as x:
        b, status, headers = x.read(), x.code, x.headers
    try:
        obj = json.loads(b or b'null')
    except ValueError:
        obj = {}
    facts = {k.lower(): headers.get(k) for k in ('X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After', 'X-RateLimit-Reset') if headers.get(k) is not None}
    records.append({'n': len(records) + 1, 'method': method, 'endpoint': label or path,
                    'actor': role or 'anonymous', 'status': status,
                    'start_s': round(t0 - start, 4), 'end_s': round(time.monotonic() - start, 4),
                    'headers': facts})
    return status, obj, facts


def rows(obj):
    d = obj.get('data', {})
    return d.get('data', []) if isinstance(d, dict) else d


def login(role):
    st, j, _ = req('POST', '/api/auth/login', body={k: A[role][k] for k in ('email', 'password')}, label='native_login/' + role)
    token = (j.get('data') or {}).get('token')
    check('native_login_' + role, st == 200 and isinstance(token, str), http=st, token_issued=isinstance(token, str))
    if not token:
        raise RuntimeError('native login unavailable')
    tokens[role] = token


try:
    for role in ('member', 'other_member', 'admin'):
        login(role)
    st, _, _ = req('GET', TL + '/support/questions', label='anonymous_questions')
    check('anonymous_questions_auth', st == 401, http=st)
    # Forty public and sixty own-question reads precede any creation.
    p = [req('GET', TL + '/support/' + ('notices' if n % 2 else 'faqs'), 'member')[0] for n in range(40)]
    q = [req('GET', TL + '/support/questions', 'member') for _ in range(60)]
    check('substantial_reads_before_create', p == [200] * 40 and all(x[0] == 200 for x in q), public_reads=40, question_reads=60,
          first_question_headers=q[0][2], last_question_headers=q[-1][2])
    statuses = []
    for n in range(11):
        st, j, hd = req('POST', TL + '/support/questions', 'member', {'title': RUN + ' private synthetic', 'content': RUN + ' synthetic content'}, label='question_create')
        statuses.append(st)
        if st == 201:
            fixtures.append(('member', j['data']['id']))
    check('ten_creates_eleventh_429', statuses == [201] * 10 + [429], statuses=statuses, last_headers=hd)
    st, j, hd = req('POST', TL + '/support/questions', 'other_member', {'title': RUN + ' second actor', 'content': RUN + ' isolated synthetic content'}, label='second_actor_create')
    if st == 201:
        fixtures.append(('other_member', j['data']['id']))
    check('second_actor_create_unaffected', st == 201, http=st, headers=hd)
    if not fixtures:
        raise RuntimeError('no owned fixture available')
    role, qid = fixtures[0]
    st, j, _ = req('GET', TL + f'/support/questions/{qid}', role, label='owner_question_detail')
    check('private_owner_read', st == 200 and j['data']['is_secret'] and j['data']['is_mine'], http=st)
    st, _, _ = req('PATCH', TL + f'/support/questions/{qid}', role, {'title': RUN + ' edited title', 'content': RUN + ' edited body'}, label='owner_question_edit')
    r, j, _ = req('GET', TL + f'/support/questions/{qid}', role, label='owner_question_requery')
    check('native_private_edit_persisted', st == 200 and r == 200 and j['data']['title'] == RUN + ' edited title' and j['data']['content'] == RUN + ' edited body', patch_http=st, requery_http=r)
    s, _, _ = req('GET', TL + f'/support/questions/{qid}', 'other_member', label='foreign_question_detail')
    e, _, _ = req('PATCH', TL + f'/support/questions/{qid}', 'other_member', {'title': 'foreign attempt', 'content': 'foreign attempt'}, label='foreign_question_edit')
    l, j, _ = req('GET', TL + '/support/questions', 'other_member', label='foreign_question_list')
    included = any(x['id'] == qid for x in rows(j))
    check('foreign_private_404_and_list_exclusion', s == e == 404 and l == 200 and not included, detail_http=s, edit_http=e, list_http=l, contains_owned_question=included)
    st, _, _ = req('GET', TL + f'/support/questions/{qid}', 'admin', label='admin_private_read')
    check('admin_private_read', st == 200, http=st)
    st, _, _ = req('POST', BOARD + f'/admin/board/{SLUG}/posts/{qid}/comments', 'admin', {'content': RUN + ' synthetic answer'}, label='native_admin_answer')
    r, j, _ = req('GET', TL + f'/support/questions/{qid}', role, label='owner_answer_read')
    check('native_admin_answer_owner_read', st == 201 and r == 200 and any(x['content'] == RUN + ' synthetic answer' for x in j.get('data', {}).get('answers', [])), answer_http=st, owner_http=r)
    # Current token only is revoked; the supplied handoff token is never deleted.
    st, _, _ = req('POST', '/api/auth/logout', role, label='native_logout_member')
    revoked, _, _ = req('GET', TL + '/cart', role, label='revoked_token_cart')
    check('native_logout_revokes_issued_token', st == 200 and revoked == 401, logout_http=st, old_token_http=revoked)
    tokens.pop(role)
    login(role)
    st, j, _ = req('GET', TL + f'/support/questions/{qid}', role, label='relogin_private_persistence')
    check('relogin_private_persistence', st == 200 and j['data']['title'] == RUN + ' edited title', http=st)
    # Continue the same aggregate bucket; all own read/write attempts above count.
    consumed = sum(x['actor'] == 'member' and x['endpoint'] not in ('native_login/member', 'native_logout_member', 'revoked_token_cart') and (x['endpoint'].startswith(TL + '/support/questions') or x['endpoint'] in ('question_create', 'owner_question_detail', 'owner_question_edit', 'owner_question_requery', 'owner_answer_read', 'relogin_private_persistence')) for x in records)
    extra = [req('GET', TL + '/support/questions', 'member', label='question_budget_fill') for _ in range(max(0, 120 - consumed))]
    st, _, hd = req('GET', TL + '/support/questions', 'member', label='question_request_121')
    r, _, _ = req('GET', TL + '/support/questions', 'other_member', label='second_actor_read_after_121')
    check('question_121st_429', all(x[0] == 200 for x in extra) and st == 429 and hd.get('x-ratelimit-limit') == '120' and r == 200,
          own_prior_attempts=consumed, fill_attempts=len(extra), http=st, headers=hd, other_actor_http=r)
    st, _, hd = req('POST', TL + '/support/questions', 'member', {'title': 'aggregate exhausted', 'content': 'aggregate exhausted'}, label='aggregate_exhausted_create')
    check('aggregate_question_exhaustion_blocks_create', st == 429 and hd.get('x-ratelimit-limit') == '120', http=st, headers=hd)
    st, _, _ = req('GET', TL + '/support/notices', 'member', label='public_after_question_exhaustion')
    check('public_unaffected_by_question_exhaustion', st == 200, http=st)
    # Admin has not used the prefixed public bucket: exact 600 then 601 without sleeps/reset.
    t0 = time.monotonic()
    pub = [req('GET', TL + '/support/faqs', 'admin', label='public_budget_fill') for _ in range(600)]
    st, _, hd = req('GET', TL + '/support/faqs', 'admin', label='public_request_601')
    r, _, _ = req('GET', TL + '/support/questions', 'admin', label='question_after_public_601')
    o, _, _ = req('GET', TL + '/support/faqs', 'other_member', label='other_public_after_601')
    elapsed = time.monotonic() - t0
    check('public_601st_429', all(x[0] == 200 for x in pub) and st == 429 and hd.get('x-ratelimit-limit') == '600' and r == o == 200 and elapsed < 60,
          accepted_reads=sum(x[0] == 200 for x in pub), http=st, headers=hd, question_http=r, other_actor_http=o, elapsed_seconds=round(elapsed, 3), first_headers=pub[0][2], last_accepted_headers=pub[-1][2])
except Exception as ex:
    checks.append({'name': 'execution_exception', 'result': 'FAIL', 'exception_type': type(ex).__name__})
    print('FAIL execution_exception', type(ex).__name__, flush=True)
finally:
    # Native administrative soft-delete on exactly the returned IDs, never broad cleanup.
    cleanup = []
    for role, qid in fixtures:
        st, _, _ = req('DELETE', BOARD + f'/admin/board/{SLUG}/posts/{qid}', 'admin', label='own_question_native_delete')
        r, _, _ = req('GET', BOARD + f'/boards/{SLUG}/posts/{qid}', role, label='own_question_native_requery')
        cleanup.append({'delete_http': st, 'owner_requery_http': r})
    check('own_private_question_cleanup', len(cleanup) == len(fixtures) and all(x['delete_http'] == 200 and x['owner_requery_http'] == 404 for x in cleanup), fixture_count=len(fixtures), outcomes=cleanup,
          remaining_accessible_private_posts=sum(x['owner_requery_http'] != 404 for x in cleanup), soft_deleted_rows_retained=len(cleanup))
    for role in list(tokens):
        st, _, _ = req('POST', '/api/auth/logout', role, label='cleanup_issued_token/' + role)
        check('cleanup_issued_token_' + role, st == 200, http=st)
    save()
