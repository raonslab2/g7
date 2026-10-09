#!/usr/bin/env python3
"""Bounded followup: preserve first failures, recover own IDs through audit API.

Loads helper definitions only (not the first-run request body). No sleeps/resets.
"""
import ast
import concurrent.futures
from pathlib import Path
import urllib.parse

helper = Path(__file__).with_name('http_review.py')
module = ast.parse(helper.read_text())
module.body = [x for x in module.body if not isinstance(x, ast.Try)]
exec(compile(module, str(helper), 'exec'))
original = json.loads(Path(__file__).with_name('http-results.json').read_text())
original_run = original['run']
created_cart, inquiry = None, None

try:
    for role in ('member', 'other_member', 'admin'):
        login(role)
    # Only own fresh actors' native board records; raw resource fields are never emitted.
    ids, updates = [], []
    for role in ('member', 'other_member'):
        params = urllib.parse.urlencode({'user_id': A[role]['user_id'], 'loggable_type': 'Modules\\Sirsoft\\Board\\Models\\Post', 'per_page': 100})
        st, j, _ = req('GET', '/api/admin/activity-logs?' + params, 'admin', label='own_native_activity_query/' + role)
        own = rows(j) if st == 200 else []
        creates = [x for x in own if x.get('action') == 'post.create' and original_run in x.get('localized_description', '')]
        # Fresh actors have only this Request's eleven questions; validate description/run.
        ids.extend((role, int(x['loggable_id'])) for x in creates)
        edit = [x for x in own if x.get('action') == 'post.update' and original_run in x.get('localized_description', '')]
        updates.extend(edit)
        check('native_create_audit_' + role, st == 200 and len(creates) == (10 if role == 'member' else 1), http=st, matching_create_rows=len(creates))
        if role == 'member':
            fields = sorted({c.get('field', '') for x in edit for c in (x.get('changes') or [])})
            check('native_update_audit_persistence', st == 200 and len(edit) == 1 and {'title', 'content'} <= set(fields), http=st, matching_update_rows=len(edit), changed_fields=fields)
    counts = []
    for role, qid in ids:
        st, _, _ = req('GET', TL + f'/support/questions/{qid}', role, label='deleted_own_support_requery')
        counts.append(st)
    remaining = 0
    list_statuses = []
    for role in ('member', 'other_member'):
        st, j, _ = req('GET', TL + '/support/questions', role, label='cleanup_own_question_list/' + role)
        list_statuses.append(st)
        remaining += sum(original_run in x.get('title', '') for x in rows(j))
    check('cleanup_native_support_requery', len(ids) == 11 and counts == [404] * 11 and remaining == 0 and list_statuses == [200, 200],
          recovered_own_ids=[i for _, i in ids], detail_statuses=counts, remaining_private_questions_in_own_lists=remaining, own_list_statuses=list_statuses,
          original_native_board_403='permission gate prevented first owner requery; not evidence of eleven surviving posts')
    # Same-IP public identity check. No artificial client IP or limiter reset.
    s1, _, h1 = req('GET', TL + '/support/faqs', 'member', label='public_member_first')
    s2, _, h2 = req('GET', TL + '/support/faqs', 'other_member', label='public_other_first')
    s3, _, h3 = req('GET', TL + '/support/faqs', 'admin', label='public_admin_first')
    check('public_actor_bucket_isolation', s1 == s2 == s3 == 200 and h1.get('x-ratelimit-remaining') == h2.get('x-ratelimit-remaining') == h3.get('x-ratelimit-remaining'),
          member_headers=h1, other_headers=h2, admin_headers=h3, same_real_client_ip=True)
    # Fresh window observed by remaining599, then accounting includes all three above.
    t0 = time.monotonic()
    with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
        pub = list(pool.map(lambda _: req('GET', TL + '/support/faqs', 'member', label='public_four_worker_budget_fill'), range(597)))
    st, _, hd = req('GET', TL + '/support/faqs', 'member', label='public_attempt_601_followup')
    other, _, oh = req('GET', TL + '/support/faqs', 'other_member', label='other_public_after_601_followup')
    q, _, _ = req('GET', TL + '/support/questions', 'member', label='question_after_public_limit_followup')
    elapsed = time.monotonic() - t0
    check('public_exact_window_601_boundary', h1.get('x-ratelimit-remaining') == '599' and all(x[0] == 200 for x in pub) and st == 429 and elapsed < 60,
          initial_reads=3, batch_reads=len(pub), accepted_batch_reads=sum(x[0] == 200 for x in pub), boundary_http=st, boundary_headers=hd,
          other_actor_http=other, other_actor_headers=oh, question_http=q, elapsed_seconds=round(elapsed, 3), workers=4,
          atomicity='bounded outcome only; not proof of atomic file-store increments')
    # Contract regression on an own cart/inquiry, reading an existing public date only.
    st, j, _ = req('GET', TL + '/catalog', label='public_catalog_read')
    products = rows(j)
    dep = next(d for p in products for d in p.get('departures', []) if d.get('available', 0) >= 1)
    statuses = []
    for body in ({'departure_id': dep['id'], 'quantity': 1, 'unit_price': 1},
                 {'departure_id': dep['id'], 'quantity': 0}, {'departure_id': dep['id'], 'quantity': 1.5},
                 {'departure_id': dep['id'], 'quantity': 1, 'user_id': A['other_member']['user_id']}):
        statuses.append(req('POST', TL + '/cart', 'member', body, label='cart_tamper_reject')[0])
    check('current_cart_money_quantity_owner_tamper', statuses == [422] * 4, statuses=statuses)
    st, _, _ = req('POST', TL + '/cart', 'member', {'departure_id': dep['id'], 'quantity': 1}, label='own_cart_create')
    r, j, _ = req('GET', TL + '/cart', 'member', label='own_cart_read')
    cart = next(x for x in j['data']['items'] if int(x.get('departure_id', 0)) == dep['id'])
    created_cart = cart['id']
    check('native_cart_fixture', st in (200, 201) and r == 200, create_http=st, read_http=r)
    body = {'cart_ids': [created_cart], 'contact': {'name': os.urandom(12).hex()}, 'idempotency_key': RUN + '-contract'}
    tamper = req('POST', TL + '/inquiries', 'member', {**body, 'total_amount': 1}, label='inquiry_client_money_rejected')[0]
    st, j, _ = req('POST', TL + '/inquiries', 'member', body, label='own_inquiry_create')
    if st == 201:
        inquiry = j['data']['id']
    check('inquiry_money_reject_and_server_recompute', tamper == 422 and st == 201 and float(j['data']['total_amount']) == float(dep['unit_price']), tamper_http=tamper, create_http=st,
          authoritative_amount=j.get('data', {}).get('total_amount'), native_catalog_unit_price=dep['unit_price'])
    if inquiry:
        foreign = req('GET', TL + f'/inquiries/{inquiry}', 'other_member', label='foreign_inquiry_detail')[0]
        cancel = req('POST', TL + f'/inquiries/{inquiry}/cancel', 'other_member', label='foreign_inquiry_cancel')[0]
        admin_list = req('GET', TL + '/admin/inquiries', 'other_member', label='member_admin_inquiry_list')[0]
        admin_edit = req('PATCH', TL + f'/admin/inquiries/{inquiry}', 'other_member', {'status': 'UNDER_REVIEW'}, label='member_admin_inquiry_edit')[0]
        money = req('PATCH', TL + f'/admin/inquiries/{inquiry}', 'admin', {'status': 'UNDER_REVIEW', 'total_amount': 1}, label='admin_inquiry_money_tamper')[0]
        check('current_inquiry_permissions_and_money', foreign == cancel == 404 and admin_list == admin_edit == 403 and money == 422,
              foreign_read_http=foreign, foreign_cancel_http=cancel, member_admin_list_http=admin_list, member_admin_edit_http=admin_edit, admin_money_http=money)
        replay, j, _ = req('POST', TL + '/inquiries', 'member', body, label='own_inquiry_exact_replay')
        changed = req('POST', TL + '/inquiries', 'member', {**body, 'contact': {'name': os.urandom(12).hex()}}, label='own_inquiry_changed_payload')[0]
        check('current_idempotent_replay_contract', replay == 200 and j['data']['id'] == inquiry and changed == 409, exact_http=replay, changed_http=changed)
except Exception as ex:
    checks.append({'name': 'followup_execution_exception', 'result': 'FAIL', 'exception_type': type(ex).__name__})
    print('FAIL followup_execution_exception', type(ex).__name__, flush=True)
finally:
    if inquiry:
        st, _, _ = req('POST', TL + f'/inquiries/{inquiry}/cancel', 'member', label='own_inquiry_cleanup_cancel')
        r, j, _ = req('GET', TL + f'/inquiries/{inquiry}', 'member', label='own_inquiry_cleanup_requery')
        check('own_inquiry_cleanup', st == r == 200 and j['data']['status'] == 'CANCELLED', cancel_http=st, requery_http=r, terminal_status=j.get('data', {}).get('status'))
    if created_cart:
        st, j, _ = req('GET', TL + '/cart', 'member', label='own_cart_cleanup_requery')
        leftover = any(x['id'] == created_cart for x in j['data']['items'])
        if leftover:
            req('DELETE', TL + f'/cart/{created_cart}', 'member', label='own_cart_cleanup_delete')
            st, j, _ = req('GET', TL + '/cart', 'member', label='own_cart_after_fallback_delete')
            leftover = any(x['id'] == created_cart for x in j['data']['items'])
        check('own_cart_cleanup', st == 200 and not leftover, cart_http=st, created_cart_remaining=leftover)
    for role in list(tokens):
        st, _, _ = req('POST', '/api/auth/logout', role, label='cleanup_followup_token/' + role)
        check('cleanup_followup_token_' + role, st == 200, http=st)
    save()
