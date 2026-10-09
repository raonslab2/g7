"""원본14개 시나리오/효과/축을 보존한다. 과거 PASS 값은 복사하지 않는다."""
import pathlib,json,hashlib
root=pathlib.Path(__file__).resolve().parents[2];out=root/'docs/symphony/evidence/W04_NATIVE_ADMIN'
prior=json.loads((root/'docs/symphony/evidence/W04_FINAL_BROWSER/required-matrix.json').read_text());contract=prior['contract'];h=hashlib.sha256((root/'tests/scenarios/travel-lab.yaml').read_bytes()).hexdigest();assert h==prior['contract_sha256']
# PASS는 이 Request가 수행한 bounded native API/browser 관찰에만 적용한다.
def effect(status,note,evidence):return {'status':status,'scope':note,'evidence':evidence}
passed={
'TR-CATALOG-001':['public.json','catalogue.json','journey-initial-harness.json','journey-1440.json'],
'TR-CART-001':['journey-initial-harness.json','journey-1440.json','access-decline.json'],
'TR-INQUIRY-001':['journey-initial-harness.json','journey-1440.json','recovery.json','actor-effects.json'],
'TR-IDEMPOTENCY-001':['recovery.json'],
'TR-IDEMPOTENCY-002':['recovery.json','quota.json'],
'TR-ADMIN-001':['journey-initial-harness.json','journey-1440.json','actor-effects.json'],
'TR-RELEASE-001':['access-decline.json','actor-effects.json','cleanup.json'],
'TR-ACCESS-001':['access-decline.json','support.json','owner-recovery.json'],
'TR-SUPPORT-001':['support.json'],
'TR-UI-001':['public.json','mobile-inspection-final.json','admin.json','catalogue.json','journey-1440.json']}
missing={
'TR-CART-001':{'nontravel_cart_excluded','no_order_or_payment_rows'},
'TR-INQUIRY-001':{'no_external_dispatch'},
'TR-ACCESS-001':{'no_private_attachment_exposure'},
'TR-SUPPORT-001':{'no_mail_sms_notifications','published_help_only'},
'TR-RELEASE-001':{'no_commerce_stock_mutation'}}
scenarios=[]
for s in contract['scenarios']:
 effects={}
 for e in s['effects']:
  if s['id'] not in passed or e in missing.get(s['id'],set()):effects[e]=effect('NOT_RUN','Independent SQL/external dispatch, full native CI, restart, migration or attachments not executed as applicable',[])
  else:
   note='Own native UI/API observations at390/1440; no independent SQL/transaction barrier proof'
   if s['id']=='TR-IDEMPOTENCY-002' and e=='inquiry_and_cart_and_inventory_unchanged':note='1440 native API before/after hashes include inquiry/events/items/cart/departures;390 inquiry/alloc measured, cart effect NOT_RUN'
   if s['id']=='TR-INQUIRY-001' and e=='consumed_cart_removed_transactionally':note='Consumed cart absent via native UI/API; transaction isolation/SQL barrier NOT_RUN'
   if s['id']=='TR-UI-001' and e=='visible_loading_empty_error_retry':note='Public catalogue both widths loading/empty/real abort error/native retry; not every customer/admin screen crossed with every state'
   effects[e]=effect('PASS',note,passed[s['id']])
 full='NOT_RUN' if any(x['status']=='NOT_RUN' for x in effects.values()) else 'PASS'
 if s['id'] in ['TR-UI-001','TR-IDEMPOTENCY-002']:full='NOT_RUN'
 scenarios.append({**s,'effects':effects,'bounded_result':'PASS' if s['id'] in passed else 'NOT_RUN','full_contract_result':full})
axes={}
notes={
'publication':{'published':'nativeUI publish/read','unpublished':'cleanup nativeAPI public404'},
'departure':{'active_future':'bothwidth actualcart/intake','inactive':'settled nativeUItoggle then cart409/intake409/restore samebody201','past':'admin nativeUI rejects422; persisted past cart/intake NOT_RUN','invalid_date':'admin sameKST/returnbefore/invalidcalendar422; persisted invalid cart/intake NOT_RUN'},
'price_input':{'omitted':'nativeUI body no clientprice, server13000','forged_amount':'controlled own nativeUI outgoing POST tamper422','changed_option_price_since_cart':'nativeadminUI14500 cart recompute + newintake; accepted26000 immutable'},
'idempotency':{'new':'native201','same_key_same_payload':'committed upstream201 beforeabort, retry200 sameID/items/amount/events/capacity','same_key_changed_payload':'409; fullAPIeffects1440'},
'identity':{'owner':'own UIrows','other_user':'native foreigncart/inquiry/question404','guest':'nativecart401'},
'network_state':{'loading':'held actualGET, no fake response','empty':'actualemptyquery/cart','error':'actualGETaborted','retry_after_unknown_submission_result':'upstreamCOMMITTED201 and actualabort then samekey native retry'},
'persistence':{'same_process':'native reload','relogin':'native owner logout/loginstatus and account pending isolation'}}
for name,values in contract['axes'].items():
 axes[name]={}
 for value in values:
  status='NOT_RUN';note='Full cross-product not executed'
  if value in notes.get(name,{}):note=notes[name][value];status='PASS'
  if name=='departure' and value in ['past','invalid_date']:status='NOT_RUN'
  if name=='travelers':status='PASS';note='Nativecart/inquiry ordinary valid; cartPATCH0/-1/1.5/999 guards, not final-submit everyquantity cross-product'
  if name=='inventory' and value=='sufficient':status='PASS';note='Native own capacity4/reserved/API snapshots'
  axes[name][value]=effect(status,note,[])
report={'sourceSHA':'fa5523175ac494cfbd13bbf89bf06b3ec91835a6','sourceTree':'fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c','contract_sha256':h,'contract':contract,'metadata_note':'Contract input_source_sha6853 is original metadata only. Actual source under test fa552; prior PASS values not reused. APP browser only; no TEST or SQL access.','scenarios':scenarios,'axes':axes,'cross_product_execution':[{'policy':p,'status':'NOT_RUN','boundedEvidence':list(passed),'note':'Complete Cartesian crossing not claimed'}for p in contract['cross_product_policy']],'independent_NOT_RUN':['SQL barriers/races/independent connections','restart/env-loss/config rotation','migration/seed/rollback','full native CI/build/typecheck','attachments and all editor data-source previews','backend counter-store outage and admission lock503 injection','persisted past/invalid-date cart+intake','admin UUID→numeric DB actor mapping'],'officialPASS':False,'canonicalValidation':'NOT_RUN; native internal review is not canonical Validation','knownObservations':[{'status':'FAIL','case':'Rapid native status selection then immediate Save sent stale is_active:true at bothwidths','evidence':'inactive-ui-toggle-witness.json','settledFollowup':'inactive.json PASS after bounded1s actualglobal/UIsettle; no source fix'}, {'status':'FAIL','case':'PC pointer RowActionMenu did not remain open in initial journey; keyboard Enter worked','evidence':'journey-1440-menu-harness.json','settledFollowup':'journey-1440.json PASS using actual keyboardEnter then detailclick'}],'full_contract_result':'NOT_RUN','bounds':'NativeCREATE and bounded transaction/recovery bothwidths; limitations retained'}
(out/'required-matrix.json').write_text(json.dumps(report,ensure_ascii=False,indent=2)+'\n');print('Preserved14 scenarios,9 axes,5 cross-product policies; full contract NOT_RUN')
