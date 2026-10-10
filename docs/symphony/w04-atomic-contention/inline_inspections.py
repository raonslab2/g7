#!/usr/bin/env python3
"""W04 atomic contention — VERBATIM copies of three read-only inline snippets that were executed as `python3 -I -` (stdin),
NOT through run.sh, and therefore have no run-log.tsv line. Delivered so the phases t2-audit-inspect (17:46:15),
t3-post-cleanup-inspect (17:51:04) and t9-final-state (17:51:26) are reproducible. Logic is unchanged; only wrapped in
functions selected by argv[3]. All three are read-only (GETs + guarded named SELECTs) except Ctx.record writing the phase file.

Usage: python3 -I inline_inspections.py <private-dir> <out-dir> audit|policy|final
"""
import collections, json, sys
sys.path.insert(0, __import__('os').path.dirname(__import__('os').path.abspath(__file__)))
from c_common import Ctx, EC, data  # noqa: E402

PRIV, OUT, WHICH = sys.argv[1:4]


def audit():
    C = Ctx(PRIV, OUT, 't2-audit-inspect')
    S = json.load(open(C.out_dir + '/t2-state.json'))
    q1 = S['questions'][0]
    rows = C.dbq('own_activity', users=[C.uid['other_member']], min_id=S['floors']['g7_activity_logs'], marker=S['edited_title'], body_marker=S['body_marker'])['activity']
    cnt = collections.Counter((r['action'], r['loggable_type']) for r in rows)
    touch_q1 = [r for r in rows if r['loggable_id'] == q1]
    own_race = set(S.get('race_questions', []))
    d = {'other_member_activity_since_floor': dict((f'{a}|{t}', n) for (a, t), n in cnt.items()), 'other_rows_on_member_q1': len(touch_q1),
         'other_post_rows_outside_own_create_race': len([r for r in rows if r['loggable_type'] == 'Post' and r['loggable_id'] not in own_race]),
         'any_contains_body_marker': any(r['contains_body_marker'] for r in rows), 'any_contains_edited_title': any(r['contains_marker'] for r in rows)}
    C.record('audit re-inspection (same SQL, own rows only): other_member rows since floor are its own create_race post creations; none touch member q1',
             'PASS' if d['other_rows_on_member_q1'] == 0 and d['other_post_rows_outside_own_create_race'] == 0 and not d['any_contains_body_marker'] and not d['any_contains_edited_title'] else 'FAIL', d)


def policy():
    C = Ctx(PRIV, OUT, 't3-post-cleanup-inspect')
    S = json.load(open(C.out_dir + '/t3-state.json'))
    st, j, _ = C.req('GET', f"{EC}/admin/products/{S['product']}", role='admin')
    d = data(j) or {}
    g = C.req('GET', f"{EC}/admin/shipping-policies/{S['policy']}", role='admin')
    det = {'product_get': st, 'product_shipping_policy_id': d.get('shipping_policy_id'), 'product_display_status': d.get('display_status'), 'policy_get_after_delete': g[0]}
    C.record('post-cleanup: own product shipping-policy reference after native delete of own policy (observation, ecommerce scope)', 'OBSERVED', det)


def final():
    C = Ctx(PRIV, OUT, 't9-final-state')
    fl = C.dbq('floors')
    toks = C.dbq('tokens', users=list(C.uid.values()), min_id=0)['tokens']
    keys = C.cache(['travel-lab-support-public:', 'travel-lab-support-questions:', 'travel-lab-support-create:', 'travel-lab-workflow:', 'travel-lab-submit:', 'travel-lab-cart:', 'travel-lab-cancel:', 'travel-lab-admin:'], ['member', 'other_member', 'admin'])
    d = {'innodb_deadlocks_global': fl['innodb_deadlocks_global'], 'own_tokens': [{'id': t['id'], 'owner_role': [r for r, u in C.uid.items() if u == t['tokenable_id']][0], 'name': t['name']} for t in toks],
         'surviving_own_admission_lock_rows': sum(v['admission_lock_rows'] for v in keys.values()), 'own_counter_rows_present': sum(v['counter_rows'] for v in keys.values())}
    C.record('final state: own tokens (handoff only), no surviving own admission locks, deadlock counter', 'PASS' if d['surviving_own_admission_lock_rows'] == 0 and len(toks) == 3 else 'FAIL', d)


{'audit': audit, 'policy': policy, 'final': final}[WHICH]()
