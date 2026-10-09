#!/usr/bin/env python3
"""W04 final security phase 2: private question budgets, ownership/audit, containment and native auth lifecycle.

Usage: python3 -I p2_questions.py <private-dir> <out-dir> <mode> [state.json]
  modes: budget | semantics | auth | cleanup
State (own question IDs, run marker, floors) is kept in the private out-dir only.
"""
import json, os, secrets, sys, time, zlib
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from w04f_common import BD, Ctx, TL, check, data, msg, rows  # noqa: E402

PRIV, OUT, MODE = sys.argv[1:4]
C = Ctx(PRIV, OUT, 'p2-' + MODE + '-' + time.strftime('%H%M%S', time.gmtime()))
STATE = os.path.join(OUT, 'p2-state.json')
S = json.load(open(STATE)) if os.path.exists(STATE) else {}
Q = TL + '/support/questions'
SLUG = 'travel-lab-questions'


def save():
    with open(STATE, 'w') as f:
        json.dump(S, f)
    os.chmod(STATE, 0o600)


if MODE == 'budget':
    S.update({'run': 'W04F-' + time.strftime('%H%M%S', time.gmtime()), 'floors': C.dbq('floors')['max_ids'], 'questions': []})
    S['body_marker'] = 'body-' + secrets.token_hex(6)
    save()

    def s_budget():
        first = C.req('GET', Q, role='member')
        check(first[0] == 200 and first[2]['limit'] == '120' and first[2]['remaining'] == '119', f'member question window not fresh {first[0]} {first[2]}')
        creates = []
        for i in range(1, 12):
            st, j, m = C.req('POST', Q, role='member', body={'title': f"{S['run']} Q{i:02d} synthetic", 'content': f"{S['run']} {S['body_marker']} synthetic question body {i}"})
            creates.append({'n': i, 'status': st, 'limit': m['limit'], 'remaining': m['remaining'], 'id': (data(j) or {}).get('id') if st == 201 else None, 'message': msg(j) if st != 201 else None})
        S['questions'] = [c['id'] for c in creates if c['id']]
        save()
        reads = []
        for _ in range(108):  # 1 read + 11 POST (incl. rejected 11th) + 108 reads = 120
            st, _, m = C.req('GET', Q, role='member')
            reads.append((st, m['remaining']))
        r121 = C.req('GET', Q, role='member')
        p_after = C.req('POST', Q, role='member', body={'title': f"{S['run']} after exhaustion", 'content': 'must not persist synthetic'})
        other = C.req('GET', Q, role='other_member')
        pub = C.req('GET', TL + '/support/faqs', role='member')
        elapsed = r121[2]['t1'] - first[2]['t0']
        posts = C.dbq('posts', users=[C.uid['member']], min_id=S['floors']['g7_board_posts'] + 1)['posts']
        d = {'first_read': [first[0], first[2]['remaining']], 'creates': creates, 'reads_13_120_statuses': sorted({s for s, _ in reads}),
             'remaining_after_read_120': reads[-1][1], 'request_121': {'status': r121[0], 'limit': r121[2]['limit'], 'remaining': r121[2]['remaining'], 'retry_after': r121[2]['retry_after']},
             'create_after_aggregate_exhaustion': {'status': p_after[0], 'limit': p_after[2]['limit'], 'remaining': p_after[2]['remaining']},
             'other_member_question_read': [other[0], other[2]['limit'], other[2]['remaining']], 'member_public_read': [pub[0], pub[2]['limit'], pub[2]['remaining']],
             'elapsed_first_to_121_s': round(elapsed, 3), 'sql_new_member_posts': len(posts), 'sql_new_member_posts_secret': sorted({int(p['is_secret']) for p in posts})}
        check(elapsed < 60, 'window exceeded; not claimed')
        check([c['status'] for c in creates[:10]] == [201] * 10, 'first ten creates not 201')
        check(creates[10]['status'] == 429 and creates[10]['limit'] == '10' and creates[10]['remaining'] == '0', 'eleventh create not 429 limit10')
        check(all(s == 200 for s, _ in reads) and reads[-1][1] == '0', 'reads 13..120 or remaining0 after 120')
        check(r121[0] == 429 and r121[2]['limit'] == '120' and r121[2]['remaining'] == '0', 'request 121 not 429 limit120')
        check(p_after[0] == 429 and p_after[2]['limit'] == '120', 'create after aggregate exhaustion not 429 limit120')
        check(other[0] == 200 and other[2]['remaining'] == '119' and pub[0] == 200 and pub[2]['limit'] == '600', 'actor/prefix separation')
        check(len(posts) == 10 and d['sql_new_member_posts_secret'] == [1], 'exactly ten secret posts persisted (rejected writes did not persist)')
        return 'PASS', d
    C.step('member question budgets: create 10/11 and aggregate 120/121 counting the rejected write; actor and public prefixes separate', s_budget)

elif MODE == 'semantics':
    q1 = S['questions'][0]
    run, body = S['run'], S['body_marker']
    act_floor = S['floors']['g7_activity_logs']

    def s_owner_edit():
        before = C.req('GET', f'{Q}/{q1}', role='member')
        check(before[2]['remaining'] == '119', f'member question window not fresh {before[2]}')
        new_title, new_body = f'{run} Q01 edited synthetic', f'{run} {body} edited synthetic body'
        p = C.req('PATCH', f'{Q}/{q1}', role='member', body={'title': new_title, 'content': new_body})
        after = C.req('GET', f'{Q}/{q1}', role='member')
        a = data(after[1]) or {}
        S['edited_title'] = new_title
        save()
        check(p[0] == 200 and after[0] == 200, (p[0], msg(p[1]), after[0]))
        check(a.get('title') == new_title and a.get('content') == new_body and a.get('is_mine') is True and a.get('is_secret') is True, 'owner requery mismatch')
        return 'PASS', {'patch': p[0], 'requery': after[0], 'title_equal': True, 'content_equal': True, 'is_mine': a.get('is_mine'), 'is_secret': a.get('is_secret')}
    C.step('owner native edit persists exact title/body; is_mine/is_secret', s_owner_edit)

    def s_foreign():
        g = C.req('GET', f'{Q}/{q1}', role='other_member')
        p = C.req('PATCH', f'{Q}/{q1}', role='other_member', body={'title': f'{run} foreign overwrite synthetic', 'content': 'foreign synthetic body text'})
        lst = C.req('GET', Q + '?per_page=50', role='other_member')
        ids = {int(r.get('id')) for r in rows(lst[1]) if isinstance(r, dict) and r.get('id') is not None}
        anon = C.req('GET', f'{Q}/{q1}')
        anon_l = C.req('GET', Q)
        own = C.req('GET', f'{Q}/{q1}', role='member')
        check(g[0] == 404 and p[0] == 404 and lst[0] == 200 and not (ids & set(S['questions'])) and anon[0] == 401 and anon_l[0] == 401, (g[0], p[0], lst[0], anon[0], anon_l[0]))
        check((data(own[1]) or {}).get('title') == S['edited_title'], 'foreign PATCH changed owner question')
        return 'PASS', {'foreign_show': g[0], 'foreign_patch': p[0], 'foreign_list': lst[0], 'foreign_list_contains_member_questions': 0, 'anonymous_show': anon[0], 'anonymous_list': anon_l[0], 'owner_title_unchanged_by_foreign': True}
    C.step('foreign member show/patch 404, foreign list contains 0 own questions, anonymous 401', s_foreign)

    def s_admin_answer():
        a = C.req('GET', f'{Q}/{q1}', role='admin')
        c = C.req('POST', f'{BD}/admin/board/{SLUG}/posts/{q1}/comments', role='admin', body={'content': f'{run} synthetic admin answer'})
        own = C.req('GET', f'{Q}/{q1}', role='member')
        o = data(own[1]) or {}
        answers = o.get('answers') or []
        S['comment_id'] = (data(c[1]) or {}).get('id') if c[0] in (200, 201) else None
        save()
        check(a[0] == 200 and c[0] in (200, 201) and own[0] == 200 and int(o.get('answers_count') or 0) >= 1 and any(f'{run} synthetic admin answer' == x.get('content') for x in answers), (a[0], c[0], msg(c[1]), own[0]))
        return 'PASS', {'admin_show': a[0], 'native_admin_comment': c[0], 'owner_sees_answers_count': o.get('answers_count'), 'answer_author_flag': [x.get('is_author') for x in answers]}
    C.step('admin reads and answers through native board admin comment; owner sees the answer', s_admin_answer)

    def s_audit():
        st, j, _ = C.req('GET', f'/api/admin/activity-logs?user_id={C.uid["member"]}&per_page=100', role='admin')
        entries = rows(j)
        blob = [json.dumps(e, ensure_ascii=False) for e in entries]
        upd = [b for b in blob if 'post.update' in b and S['edited_title'] in b]
        sql = C.dbq('own_activity', users=[C.uid['member'], C.uid['other_member']], min_id=act_floor, marker=S['edited_title'], body_marker=body)['activity']
        updates = [r for r in sql if r['action'] and 'update' in r['action'] and r['loggable_id'] == q1]
        creates = [r for r in sql if r['action'] and 'create' in r['action']]
        d = {'api_status': st, 'api_entries': len(entries), 'api_post_update_with_new_title': len(upd), 'api_body_marker_in_update_entries': sum(body in b for b in upd),
             'sql_rows_since_floor': len(sql), 'sql_actions': sorted({r['action'] for r in sql if r['action']}), 'sql_update_rows_for_q1': updates,
             'sql_create_rows': len(creates), 'sql_any_row_contains_body_marker': any(r['contains_body_marker'] for r in sql),
             'other_member_rows': sum(r['user_id'] == C.uid['other_member'] for r in sql)}
        check(st == 200 and len(upd) >= 1 and d['api_body_marker_in_update_entries'] == 0, 'native activity API update entry / body exclusion')
        check(len(updates) == 1 and updates[0]['changes_mentions_title'] and not updates[0]['changes_mentions_content'] and not updates[0]['contains_body_marker'], 'SQL update row contract')
        check(d['other_member_rows'] == 0, 'foreign PATCH produced an audit row')
        return 'PASS', d
    C.step('native activity audit: exactly one post.update for owner edit with title metadata, body excluded; foreign attempts not audited', s_audit)

    def s_search():
        marker = S['edited_title']
        out = {}
        for role in (None, 'member', 'other_member', 'admin'):
            st, j, _ = C.req('GET', '/api/search?q=' + run.replace('-', '+') + '&type=all', role=role)
            st2, j2, _ = C.req('GET', '/api/search?q=' + 'synthetic+question+body', role=role)
            out[role or 'guest'] = {'status': st, 'marker_hits': json.dumps(j, ensure_ascii=False).count(run), 'status_body_query': st2, 'body_marker_hits': json.dumps(j2, ensure_ascii=False).count(body)}
        board = C.req('GET', f'{BD}/boards/{SLUG}/posts', role='other_member')
        board_g = C.req('GET', f'{BD}/boards/{SLUG}/posts')
        out['native_board_list_other'] = board[0]
        out['native_board_list_guest'] = board_g[0]
        out['native_board_list_other_contains_run'] = json.dumps(board[1], ensure_ascii=False).count(run)
        check(all(v['marker_hits'] == 0 and v['body_marker_hits'] == 0 for k, v in out.items() if isinstance(v, dict)), out)
        check(out['native_board_list_other_contains_run'] == 0, 'native board list exposed questions')
        return 'PASS', {**out, 'scope': 'current APP database search API only; external engines/scout:import NOT_RUN (W03-05(a) remains CONTAINED, not CLOSED)'}
    C.step('native current-APP search and board list expose no own private question title/body to guest/member/other/admin', s_search)

    def s_attach():
        board = C.dbq('board', slug=SLUG)['board']
        def chunk(t, d):
            return len(d).to_bytes(4, 'big') + t + d + zlib.crc32(t + d).to_bytes(4, 'big')
        png = b'\x89PNG\r\n\x1a\n' + chunk(b'IHDR', (1).to_bytes(4, 'big') * 2 + b'\x08\x02\x00\x00\x00') + chunk(b'IDAT', zlib.compress(b'\x00\xff\x00\x00')) + chunk(b'IEND', b'')
        bd = 'w04f' + secrets.token_hex(4)
        raw = (f'--{bd}\r\nContent-Disposition: form-data; name="file"; filename="w04f.png"\r\nContent-Type: image/png\r\n\r\n').encode() + png + f'\r\n--{bd}--\r\n'.encode()
        up = C.req('POST', f'{BD}/boards/{SLUG}/attachments', role='member', raw=raw, ctype='multipart/form-data; boundary=' + bd)
        after = C.dbq('floors')['max_ids']['g7_board_attachments']
        check(board.get('use_file_upload') in (0, '0', False) and up[0] in (403, 404, 422) and after == S['floors']['g7_board_attachments'], (board, up[0], after))
        return 'PASS', {'board_settings': board, 'member_native_upload': up[0], 'upload_message': msg(up[1]), 'attachments_rows_unchanged': True,
                        'foreign_attachment_access': 'NOT_RUN: native member path denies owned attachment creation, so no realistic owned file exists; nonexistent-ID 404 is not used as proof'}
    C.step('isolated support board attachments disabled: native member upload denied, no attachment row', s_attach)

elif MODE == 'auth':
    def login(role):
        st, j, m = C.req('POST', '/api/auth/login', body={'email': C.A[role]['email'], 'password': C.A[role]['password']})
        tok = (data(j) or {}).get('token') if st == 200 else None
        C.add_secret(tok)
        return st, tok

    def s_lifecycle():
        floor = C.dbq('floors')['max_ids']['g7_personal_access_tokens']
        st1, t1 = login('member')
        a1 = C.req('GET', TL + '/cart', token=t1)
        o1 = C.req('GET', f"{Q}/{S['questions'][0]}", token=t1)
        lo = C.req('POST', '/api/auth/logout', token=t1)
        a2 = C.req('GET', TL + '/cart', token=t1)
        p2 = C.req('GET', TL + '/support/faqs', token=t1)
        st2, t2 = login('member')
        o2 = C.req('GET', f"{Q}/{S['questions'][0]}", token=t2)
        lo2 = C.req('POST', '/api/auth/logout', token=t2)
        a3 = C.req('GET', TL + '/cart', token=t2)
        hand = C.req('GET', TL + '/cart', role='member')
        toks = C.dbq('tokens', users=[C.uid['member']], min_id=floor + 1)['tokens']
        d = {'login1': st1, 'cart_with_t1': a1[0], 'question_with_t1': o1[0], 'logout1': lo[0], 'cart_after_logout1': a2[0], 'public_with_revoked_t1': p2[0],
             'relogin': st2, 'edited_question_persisted_after_relogin': (data(o2[1]) or {}).get('title') == S.get('edited_title'), 'logout2': lo2[0], 'cart_after_logout2': a3[0],
             'handoff_token_still_valid': hand[0], 'own_issued_tokens_remaining_in_db': len(toks)}
        check(st1 == 200 and a1[0] == 200 and o1[0] == 200 and lo[0] == 200 and a2[0] == 401 and p2[0] == 401 and st2 == 200 and d['edited_question_persisted_after_relogin'], d)
        check(lo2[0] == 200 and a3[0] == 401 and hand[0] == 200 and len(toks) == 0, d)
        return 'PASS', d
    C.step('native login -> logout 401 -> relogin persists edited state -> logout 401; handoff token retained; own tokens absent', s_lifecycle)

    def s_optional_semantics():
        floor = C.dbq('floors')['max_ids']['g7_personal_access_tokens']
        started = C.dbq('floors')['at_utc']
        g0 = C.req('GET', TL + '/support/faqs')
        bad = C.req('GET', TL + '/support/faqs', token='999999|' + secrets.token_hex(20))
        st, t3 = login('other_member')
        tid = C.dbq('tokens', users=[C.uid['other_member']], min_id=floor + 1)['tokens'][0]['id']
        v = C.req('GET', TL + '/support/faqs', token=t3)
        exp = C.db('own-token-expire', {'users': [C.uid['other_member']], 'token_id': tid, 'min_id': floor + 1, 'created_after': started[:10] + ' 00:00:00'})
        e_pub = C.req('GET', TL + '/support/faqs', token=t3)
        g1 = C.req('GET', TL + '/support/faqs')
        e_q = C.req('GET', Q, token=t3)
        dele = C.db('own-token-delete', {'users': [C.uid['other_member']], 'token_id': tid, 'min_id': floor + 1, 'created_after': started[:10] + ' 00:00:00'})
        gone = C.req('GET', TL + '/support/faqs', token=t3)
        left = C.dbq('tokens', users=[C.uid['other_member']], min_id=floor + 1)['tokens']
        d = {'guest': [g0[0], g0[2]['remaining']], 'invalid_token_public': [bad[0], msg(bad[1]) is not None, (bad[1] or {}).get('success') if isinstance(bad[1], dict) else None],
             'valid_login_public': [st, v[0], v[2]['remaining']], 'expire_own_token_rows': exp['rows'],
             'expired_token_public': [e_pub[0], e_pub[2]['remaining']], 'guest_after_expired': [g1[0], g1[2]['remaining']],
             'expired_token_question': e_q[0], 'delete_own_token_rows': dele['rows'], 'deleted_token_public': gone[0], 'own_tokens_left': len(left)}
        # Expired token is treated as guest: it consumes the IP bucket (strictly between guest reads), not a per-user counter.
        check(g0[0] == 200 and bad[0] == 401 and st == 200 and v[0] == 200 and exp['rows'] == 1, d)
        check(e_pub[0] == 200 and int(g0[2]['remaining']) > int(e_pub[2]['remaining']) > int(g1[2]['remaining']), d)
        check(e_q[0] == 401 and dele['rows'] == 1 and gone[0] == 401 and not left, d)
        return 'PASS', d
    C.step('optional auth semantics: guest IP bucket, invalid token 401, expired own token = guest (IP bucket) and private 401; own token removed', s_optional_semantics)

elif MODE == 'create_race':
    import threading

    def s_create_race():
        first = C.req('GET', Q, role='other_member')
        check(first[2]['remaining'] == '119', f'other question window not fresh {first[2]}')
        floor = C.dbq('floors')['max_ids']['g7_board_posts']
        out, lk, idx = [], threading.Lock(), [0]

        def lane():
            while True:
                with lk:
                    if idx[0] >= 20:
                        return
                    idx[0] += 1
                    n = idx[0]
                st, j, m = C.req('POST', Q, role='other_member', body={'title': f"{S['run']} R{n:02d} concurrent synthetic", 'content': f"{S['run']} concurrent synthetic body {n}"})
                with lk:
                    out.append({'status': st, 'limit': m['limit'], 'remaining': m['remaining'], 'id': (data(j) or {}).get('id') if st == 201 else None, 't0': m['t0'], 't1': m['t1']})
        ts = [threading.Thread(target=lane) for _ in range(8)]
        for t in ts:
            t.start()
        for t in ts:
            t.join()
        created = [o['id'] for o in out if o['id']]
        S.setdefault('race_questions', []).extend(created)
        save()
        posts = C.dbq('posts', users=[C.uid['other_member']], min_id=floor + 1)['posts']
        d = {'lanes': 8, 'requests': len(out), 'status_counts': {str(k): sum(1 for o in out if o['status'] == k) for k in sorted({o['status'] for o in out})},
             'elapsed_s': round(max(o['t1'] for o in out) - min(o['t0'] for o in out), 3), 'sql_new_other_posts': len(posts), 'created_ids': created}
        if len(created) > 10 or len(posts) > 10:
            return 'FAIL', {**d, 'finding': 'concurrent requests exceeded the 10/min create budget: native FileStore increments are not atomic'}
        check(len(created) == 10 and len(posts) == 10, d)
        return 'PASS', {**d, 'note': 'bounded single run; not a proof of atomicity'}
    C.step('bounded concurrent create burst (20 POST, 8 lanes) against the 10/min create budget on native FileStore', s_create_race)

elif MODE == 'cleanup':
    def s_cleanup():
        out = []
        for qid in S['questions'] + S.get('race_questions', []):
            st, j, _ = C.req('DELETE', f'{BD}/admin/board/{SLUG}/posts/{qid}', role='admin')
            g = C.req('GET', f'{Q}/{qid}', role='member' if qid in S['questions'] else 'other_member')
            out.append({'id': qid, 'admin_delete': st, 'owner_requery': g[0]})
        lm = C.req('GET', Q + '?per_page=50', role='member')
        lo = C.req('GET', Q + '?per_page=50', role='other_member')
        ids_m = {int(r['id']) for r in rows(lm[1]) if isinstance(r, dict) and 'id' in r}
        posts = C.dbq('posts', users=[C.uid['member'], C.uid['other_member']], min_id=S['floors']['g7_board_posts'] + 1)['posts']
        d = {'deletes': out, 'member_list': lm[0], 'member_list_contains_own_run_questions': len(ids_m & set(S['questions'])), 'other_list': lo[0],
             'sql_run_posts': len(posts), 'sql_run_posts_soft_deleted': sum(int(p['deleted']) for p in posts)}
        check(all(o['admin_delete'] == 200 and o['owner_requery'] == 404 for o in out) and lm[0] == 200 and lo[0] == 200 and d['member_list_contains_own_run_questions'] == 0, d)
        return 'PASS', d
    C.step('own questions deleted via native admin API: owner requery 404, lists 0, native soft-delete retained rows counted', s_cleanup)
C.flush()
