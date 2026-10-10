#!/usr/bin/env python3
"""Package this review's observations without promoting partial coverage to PASS.

No APP access, fixture changes, Git publication, or credentials are used here.
Run after browser cleanup/audit, then privacy scan and final manifest.
"""
import hashlib, json, pathlib, subprocess

ROOT = pathlib.Path(__file__).resolve().parents[2]
OUT = ROOT / 'docs/symphony/evidence/W04_FINAL_BROWSER'
SHA = '1052e3fb4bc4cccabb51b8c538116c78655f345b'
def read(name): return json.loads((OUT / name).read_text())
def write(name, value): (OUT / name).write_text(json.dumps(value, ensure_ascii=False, indent=2) + '\n')
def digest(p): return hashlib.sha256(p.read_bytes()).hexdigest()

# Required contract is kept verbatim; observation layer alone is updated.
matrix = read('required-matrix.json')
prior = json.loads((ROOT / 'docs/symphony/evidence/W04_BROWSER_RECHECK/required-matrix.json').read_text())
assert matrix['contract'] == prior['contract']
assert len(matrix['contract']['scenarios']) == 14 and len(matrix['contract']['axes']) == 9
mapping = {
 'TR-CATALOG-001': (True, [], ['public-results.json', 'public-final-detail-soldout.json'], 'All five catalog effects observed in actual guest SPA/HTTP at both widths;17 unique checks per width.'),
 'TR-CART-001': (False, ['owner_cart_persistence', 'departure_option_mapping', 'canonical_cart_calculation'], ['journey.json', 'access-decline.json'], 'Native UI add/increase/decrease/remove/empty and owner HTTP persistence observed. Nontravel exclusion and order/payment SQL NOT_RUN.'),
 'TR-INQUIRY-001': (False, ['server_calculation_snapshot', 'test_inquiry_persistence', 'actor_audit_event'], ['journey.json', 'owner-audit.json', 'actor-audit.json'], 'Actual201, persisted item price/quantity and calculation snapshot/event readback observed. Immutability after source item change, independent reservation/transaction atomicity, external dispatch SQL NOT_RUN.'),
 'TR-IDEMPOTENCY-001': (False, ['same_inquiry_id'], ['recovery.json', 'recovery-1440-final.json', 'owner-audit.json'], 'Real committed201 response aborted; immediate/reload retry returned200 same ID/key/body. API event readback has no duplicate events. Explicit upstream-vs-retry amount comparison, item cardinality, independent SQL reservation invariance NOT_RUN; event-only evidence does not complete the combined item/audit effect.'),
 'TR-IDEMPOTENCY-002': (False, ['conflict_409'], ['access-decline.json'], 'Used key with changed contact body returned409 both widths. Immediate full inquiry/cart/inventory before/after audit NOT_RUN.'),
 'TR-CONCURRENCY-001': (False, [], [], 'Independent DB last-seat race belongs to separate reviewers; own SQL and all four effects NOT_RUN.'),
 'TR-ADMIN-001': (True, [], ['journey.json', 'owner-audit.json', 'actor-audit.json'], 'Native allowed UNDER_REVIEW/TEST_ACCEPTED, admin note, customer relogin/reload and exact event-state readback; explicit test-only labels.'),
 'TR-RELEASE-001': (False, ['terminal_transition_guard', 'durable_owner_result'], ['journey.json', 'access-decline.json', 'mobile-decline-final.json', 'cleanup.json', 'owner-audit.json'], 'Accepted requests cancel, declined requests terminal409, repeated cancel200 adds0 events, final own reserved0 via native API. Release-once and commerce-stock invariance SQL NOT_RUN.'),
 'TR-ACCESS-001': (False, ['owner_scope_404_or_permission_403_or_auth_401'], ['access-decline.json', 'support.json', 'owner-audit.json'], 'Foreign cart PATCH/DELETE and inquiry GET/cancel404, private question UI/PATCH404, guest401/login redirects/member-admin403. Cart quantity unchanged; full entrypoint cross product and private attachment NOT_RUN.'),
 'TR-SUPPORT-001': (False, ['native_board_or_page_persistence', 'private_owner_admin_scope', 'published_help_only'], ['support.json'], 'Native notice/FAQ201/edit200/public read, own private question201/edit422 retained input/cancel0PATCH/save200/answer201/reload, foreign404. Dispatch/mail/SMS SQL NOT_RUN.'),
 'TR-RESTART-001': (False, [], ['journey.json', 'recovery-390-owner-final.json', 'recovery-1440-final.json', 'support.json'], 'Relogin persistence observed only. Required preview stop/start forbidden, so all restart effects NOT_RUN; no production operations.'),
 'TR-MIGRATION-001': (False, [], [], 'Schema/seed/rollback mutation outside scope and forbidden; all migration effects NOT_RUN.'),
 'TR-UI-001': (False, ['actual_g7_spa', 'no_horizontal_overflow', 'working_auth_and_toasts', 'clear_test_only_transaction'], ['journey.json', 'public-results.json', 'public-final-detail-soldout.json', 'mobile-inspection-final.json', 'support.json', 'recovery-390-owner-final.json', 'recovery-1440-final.json'], 'Bounded native journey and MOBILE-01 PASS. Customer catalog loading/empty/error/retry observed; every customer/admin screen x all network states NOT_RUN. Whole scenario remains NOT_RUN.'),
 'TR-REGRESSION-001': (False, ['independent_review_and_postintegration_results_separate'], ['commands.json', 'source-binding.json'], 'Own browser/source evidence only. Full core/commerce/board installation/regression/build/typecheck, hosted/native CI and official Validation NOT_RUN; author/other Request counts not inherited.')
}
for row in matrix['scenarios']:
    full, effects, evidence, note = mapping[row['id']]
    row['evidence'], row['note'] = evidence, note
    effects = row['effects'] if full else effects
    for width in ('390', '1440'):
        row['widths'][width] = {'status': 'PASS' if full else 'NOT_RUN',
                               'effects': {e: 'PASS' if e in effects else 'NOT_RUN' for e in row['effects']}}

# Value-level examples never assert a complete cross product.
observed = {
 ('publication','published'): ('PASS',['public-results.json','journey.json'],'Actual public catalog, detail, inquiry on own published products.'),
 ('publication','unpublished'): ('NOT_RUN',['cleanup.json'],'Native API cleanup requery unpublished/public404, not new both-width UI discovery after cleanup.'),
 ('departure','active_future'): ('PASS',['journey.json'],'Own future departures selected and submitted; valid path only.'),
 ('departure','inactive'): ('NOT_RUN',['cleanup.json'],'Own final inactive/reserved0 native API readback; cart/intake rejection cross product not exercised.'),
 ('departure','invalid_date'): ('NOT_RUN',['access-decline.json','catalogue-followup.json'],'Own fabricated nonexistent departure/date422 both widths; real reverse-return4221440. Not all cart/add/update/intake invalid-date combinations.'),
 ('travelers','valid'): ('PASS',['journey.json','access-decline.json'],'Native quantities1/2/3 and server calculation.'),
 ('travelers','zero'): ('PASS',['access-decline.json'],'Own cart PATCH quantity0 returned422; final inquiry combinations NOT_RUN.'),
 ('travelers','negative'): ('PASS',['access-decline.json'],'Own cart PATCH quantity-1 returned422; final inquiry combinations NOT_RUN.'),
 ('travelers','above_available'): ('PASS',['access-decline.json'],'Own cart PATCH quantity999 returned422; final inquiry combinations NOT_RUN.'),
 ('travelers','non_integer'): ('PASS',['access-decline.json'],'Own cart PATCH quantity1.5 returned422; final inquiry combinations NOT_RUN.'),
 ('price_input','omitted'): ('PASS',['journey.json'],'Native inquiry body omitted client price;201 canonical server unit13000.'),
 ('price_input','forged_amount'): ('PASS',['access-decline.json'],'Controlled own native-dispatched HTTP price/total/amount injection real422 then native retry201.'),
 ('idempotency','new'): ('PASS',['journey.json'],'Actual native new-key201 both widths.'),
 ('idempotency','same_key_same_payload'): ('PASS',['recovery.json','recovery-1440-final.json'],'Upstream committed201 response loss, same key/body immediate and reload retries200 same ID.'),
 ('idempotency','same_key_changed_payload'): ('PASS',['access-decline.json'],'Used key changed body409 both widths.'),
 ('inventory','sufficient'): ('PASS',['journey.json'],'Native available departure submits201; SQL reservation atomicity NOT_RUN.'),
 ('inventory','commerce_stock_reduced'): ('NOT_RUN',['catalogue-followup.json','departure-final.json','public-final-detail-soldout.json'],'Own second option stock0 showed soldout and disabled add, but after-cart stock-change final-submit cross product NOT_RUN.'),
 ('identity','owner'): ('PASS',['journey.json','support.json'],'Own cart/inquiry/question UI and relogin readback.'),
 ('identity','other_user'): ('PASS',['access-decline.json','support.json'],'Foreign cart/inquiry/question404 probes and account-change isolation; all entrypoint permutations NOT_RUN.'),
 ('identity','guest'): ('PASS',['owner-audit.json','access-decline.json'],'Native protected customer login redirects and admin/cart401; all guest write permutations NOT_RUN.'),
 ('network_state','loading'): ('PASS',['public-results.json'],'Actual held catalog GET and visible loading. All admin/customer screens NOT_RUN.'),
 ('network_state','empty'): ('PASS',['public-results.json','access-decline.json'],'Empty search/reset and native cart-remove empty. All screens NOT_RUN.'),
 ('network_state','error'): ('PASS',['public-results.json'],'Controlled catalog transport abort/visible error/actual retry200; all screens NOT_RUN.'),
 ('network_state','retry_after_unknown_submission_result'): ('PASS',['recovery.json','recovery-1440-final.json'],'Real committed201 response aborted and same-ID200 recovery; memory fallback quotaHits3.'),
 ('persistence','same_process'): ('PASS',['journey.json','support.json','owner-audit.json'],'Reload/native API durable inquiry and question readback.'),
 ('persistence','relogin'): ('PASS',['journey.json','recovery-390-owner-final.json','recovery-1440-final.json'],'Native logout/relogin kept saved inquiry/status while pending body/contact cleared.'),
}
for axis, values in matrix['axes'].items():
    for value, widths in values.items():
        status, evidence, note = observed.get((axis,value), ('NOT_RUN', [], 'No execution of this value; no inherited result.'))
        for width in ('390','1440'):
            widths[width] = {'status':status,'evidence':evidence,'note':note,'coverage':'bounded value observation' if status=='PASS' else 'incomplete/not executed', 'full_cross_product':'NOT_RUN'}
matrix['cross_product_execution'] = [{'policy':p,'status':'NOT_RUN','note':'Observed subsets above; complete permutation coverage not executed.'} for p in matrix['contract']['cross_product_policy']]
matrix['bounded_browser_result']='PASS'
matrix['full_contract_result']='NOT_RUN'
matrix['cart_intake_stage_boundaries']={'cart':'Native UI creation/update/removal plus owner HTTP validation gates observed; nontravel/order/payment DB exclusions NOT_RUN.', 'intake':'Native real201/recovery200/conflict409/server422 observed; transactional/concurrency/outbound SQL effects NOT_RUN.'}
write('required-matrix.json', matrix)

public = {}
for name in ('public-results.json','public-final-detail-soldout.json'):
    for r in read(name)['scenarios']: public[(r['width'],r['name'])]=r
assert len(public)==34 and all(r['status']=='PASS' for r in public.values())
recovery={}
for name in ('recovery.json','recovery-390-owner-final.json','recovery-1440-final.json'):
    for r in read(name)['results']:
        if r.get('width') and (r['name'].startswith('recovery ') or r['name'].startswith('owner UUID')): recovery[(r['width'],r['name'])]=r
assert len(recovery)==10 and all(r['status']=='PASS' for r in recovery.values())
cleanup=read('cleanup-counts.json')
assert cleanup['allRequestsClosed'] and cleanup['recordedIssuedTokensRequeried']==55
assert all(r['status']=='PASS' for r in read('cleanup.json')['results'])
assert all(r['status']=='PASS' for r in read('owner-audit.json')['results'])
assert all(r['status']=='PASS' for r in read('actor-audit.json')['results'])
write('execution-summary.json', {'sourceSHA':SHA, 'browserResult':'PASS within stated scope', 'fullContract':'NOT_RUN',
      'uniqueBoundedChecks':{'public':len(public),'ordinaryJourneySteps':6,'support':12,'recoveryModesIncludingOwnerIsolation':len(recovery),'mobileGeometryAndNavigation':4,'accessDecline':8},
      'fixtureAndCleanupRowsExcludedFromUniqueCounts':True,'cleanup':cleanup,
      'historical598':'FAIL immutable original;1052 observation does not rewrite prior negatives',
      'nativeHelpers':1,'peakNativeAgents':2,'officialChildRequests':0,'publication':0,'ownSQL':'NOT_RUN','canonicalValidation':'NOT_RUN','CI':'NOT_RUN'})

provenance=read('provenance.json')
for f in provenance['files']: f['final_sha256']=digest(ROOT/f['path'])
known={f['path'] for f in provenance['files']}
for p in sorted((ROOT/'tests/W04_FINAL').glob('*')):
    if p.is_file() and str(p.relative_to(ROOT)) not in known:
        provenance['files'].append({'path':str(p.relative_to(ROOT)),'final_sha256':digest(p),'origin':'New independent1052 review helper or source adaptation; no prior screenshot reuse'})
write('provenance.json', provenance)
timings=[]
for p in sorted(OUT.glob('*.json')):
    d=json.loads(p.read_text())
    if isinstance(d,dict) and ('elapsed_seconds' in d or 'durationSeconds' in d):
        timings.append({'evidence':p.name,'seconds':d.get('elapsed_seconds',d.get('durationSeconds')),'method':'Process-recorded elapsed; includes own fixture/UI waits; no exact sum as sequential wall time claim'})
write('timings.json', {'sourceSHA':SHA,'records':timings,'interruptedProcesses':'Elapsed incomplete, exact end-to-end duration NOT_RUN; command failures preserved separately'})
print('PASS:14 scenarios/full effects/9 axes preserved;34 public/10 recovery bounded unique checks;cleanup/audit verified')
