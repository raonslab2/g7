#!/usr/bin/env python3
"""Native audit assertion correction; no new product writes or fixture creation."""
import ast
from pathlib import Path
import urllib.parse

helper = Path(__file__).with_name('http_review.py')
module = ast.parse(helper.read_text())
module.body = [x for x in module.body if not isinstance(x, ast.Try)]
exec(compile(module, str(helper), 'exec'))
original = json.loads(Path(__file__).with_name('http-results.json').read_text())
original_run = original['run']
try:
    login('admin')
    params = urllib.parse.urlencode({'user_id': A['member']['user_id'], 'loggable_type': 'Modules\\Sirsoft\\Board\\Models\\Post', 'action': 'post.update', 'per_page': 100})
    st, j, _ = req('GET', '/api/admin/activity-logs?' + params, 'admin', label='own_native_update_audit_query')
    edits = [x for x in rows(j) if original_run in x.get('localized_description', '')]
    title = [c for x in edits for c in x.get('changes', []) if c.get('field') == 'title']
    check('native_update_audit_exact_persistence', st == 200 and len(edits) == len(title) == 1 and title[0].get('old') == original_run + ' private synthetic' and title[0].get('new') == original_run + ' edited title',
          http=st, matching_update_rows=len(edits), title_change_rows=len(title),
          old_title_matches=len(title) == 1 and title[0].get('old') == original_run + ' private synthetic',
          new_title_matches=len(title) == 1 and title[0].get('new') == original_run + ' edited title',
          source_contract='Native Post.activityLogFields omits content; initial content-field assertion was overbroad and remains in original evidence')
    # Own notification inboxes are read through the handoff tokens (never revoked here).
    for role in ('member', 'other_member'):
        tokens[role] = A[role]['bearer_token']
        st, j, _ = req('GET', '/api/user/notifications?per_page=100', role, label='own_notification_inbox/' + role)
        check('own_native_notification_inbox_' + role, st == 200 and len(rows(j)) == 0, http=st, returned_notifications=len(rows(j)) if st == 200 else None,
              limitation='Inbox observation only; external delivery/queue and whole DB row counts not attested')
        tokens.pop(role)
finally:
    if 'admin' in tokens:
        st, _, _ = req('POST', '/api/auth/logout', 'admin', label='cleanup_audit_followup_token')
        check('cleanup_audit_followup_token', st == 200, http=st)
    save()
