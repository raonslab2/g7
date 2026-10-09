"""외부 앱에 접근하지 않고 검증 기록과 전체 요구 매트릭스를 묶는다."""
from pathlib import Path
import hashlib,json,datetime,subprocess,yaml
ROOT=Path.cwd();D=ROOT/'docs/symphony/evidence/W04_BROWSER_RECHECK';SHA='598a89fff702d51c1405f1a5952d95ab1d2651f4'
def load(n):return json.loads((D/(n+'.json')).read_text())
def save(n,d): (D/(n+'.json')).write_text(json.dumps(d,ensure_ascii=False,indent=2)+'\n')
contract=yaml.safe_load((ROOT/'tests/scenarios/travel-lab.yaml').read_text())
# 전체 효과를 그대로 유지하며 직접 실행한 부분만 PASS로 판정한다.
effect_pass={
'TR-CATALOG-001':set(contract['scenarios'][0]['effects']),
'TR-CART-001':{'owner_cart_persistence','departure_option_mapping','canonical_cart_calculation'},
'TR-INQUIRY-001':{'server_calculation_snapshot','test_inquiry_persistence','actor_audit_event'},
'TR-IDEMPOTENCY-001':{'same_inquiry_id','same_amount','no_duplicate_item_or_audit_event'},
'TR-IDEMPOTENCY-002':{'conflict_409'},
'TR-CONCURRENCY-001':set(),
'TR-ADMIN-001':set(next(s['effects'] for s in contract['scenarios'] if s['id']=='TR-ADMIN-001')),
'TR-RELEASE-001':{'terminal_transition_guard','durable_owner_result'},
'TR-ACCESS-001':{'owner_scope_404_or_permission_403_or_auth_401','no_state_change'},
'TR-SUPPORT-001':{'native_board_or_page_persistence','private_owner_admin_scope','published_help_only'},
'TR-RESTART-001':set(),'TR-MIGRATION-001':set(),
'TR-UI-001':{'actual_g7_spa','working_auth_and_toasts','clear_test_only_transaction','visible_loading_empty_error_retry','no_horizontal_overflow'},
'TR-REGRESSION-001':set()}
refs={
'TR-CATALOG-001':['public-results.json','catalogue.json'],
'TR-CART-001':['journey.json','access-decline.json'],
'TR-INQUIRY-001':['journey.json','owner-audit.json'],
'TR-IDEMPOTENCY-001':['recovery.json','owner-audit.json'],
'TR-IDEMPOTENCY-002':['access-decline.json'],
'TR-CONCURRENCY-001':[],
'TR-ADMIN-001':['journey.json','owner-audit.json'],
'TR-RELEASE-001':['journey.json','decline-followup.json','owner-audit.json','cleanup.json'],
'TR-ACCESS-001':['access-decline.json','support.json','owner-audit.json'],
'TR-SUPPORT-001':['support.json'],
'TR-RESTART-001':['journey.json','support.json','touch-persistence.json'],
'TR-MIGRATION-001':[],
'TR-UI-001':['public-results.json','journey.json','support.json','touch-persistence.json'],
'TR-REGRESSION-001':[]}
notes={
'TR-CART-001':'비여행 상품 제외 및 order/payment DB 행 감사는 미실행. 실제 cart add/update/remove와 재로그인 지속성은 실행.',
'TR-INQUIRY-001':'API의 계산·불변 item 스냅샷/감사 이벤트와 cart 소모 확인. 독립 SQL로 once 예약/transaction 원자성 및 외부 발송 테이블을 감사하지 않음.',
'TR-IDEMPOTENCY-001':'실제 upstream201 후 응답만 유실시켜 동일 key/body200/동일ID/금액, 감사 이벤트 중복 없음. 예약량 변화의 독립 SQL 감사 미실행.',
'TR-IDEMPOTENCY-002':'사용한 key의 변경 contact409 확인. 즉시 전후 inquiry/cart/inventory 전체 스냅샷 비교는 미실행.',
'TR-CONCURRENCY-001':'다른 검증자의 별도 actor/독립 DB 검증 범위. 이 요청에서는 독립 연결 last-seat 경합을 실행하지 않음.',
'TR-RELEASE-001':'수락 후 취소200/반복취소200/이벤트4개 유지, 거절 후 전이409, 정리 후 전부 reserved0. release once와 commerce stock 불변의 독립 전후 SQL 감사 미실행.',
'TR-ACCESS-001':'타인 cart PATCH/DELETE404, 문의 read/cancel404, 질문 UI/PATCH404; guest401/admin member403. 자체 첨부 없음: attachment 접근 미실행.',
'TR-SUPPORT-001':'native notice/FAQ/question/answer 실제201/200, PATCH422 입력 유지와 취소0PATCH. 발송 DB/worker는 읽거나 변경하지 않음.',
'TR-RESTART-001':'실제 logout/login/reload는 PASS. parent 서비스 stop/start는 금지되어 process-restart 결합 시나리오는 NOT_RUN.',
'TR-MIGRATION-001':'parent schema/seed/lifecycle 수정 금지. 마이그레이션/rollback/seed 반복 미실행.',
'TR-UI-001':'실제 양폭 critical journey 완료. 390px /travel/requests scrollWidth412>390 재현: FAIL. 1440px 관찰 화면 overflow 없음.',
'TR-REGRESSION-001':'npm ci와 검증 스크립트 문법/증거 점검만 실행. 제품 전체 build/typecheck/commerce/board/core 테스트는 이 브라우저 검증에서 NOT_RUN; hosted CI0runs, canonical Validation BLOCKED.'}
rows=[]
for s in contract['scenarios']:
 r=dict(s);r['evidence']=refs[s['id']];r['note']=notes.get(s['id'],'실제 UI 및 native API 응답으로 확인.');r['widths']={}
 for w in (390,1440):
  effects={e:('PASS' if e in effect_pass[s['id']] else 'NOT_RUN') for e in s['effects']}
  if s['id']=='TR-UI-001' and w==390:effects['no_horizontal_overflow']='FAIL'
  status='FAIL' if 'FAIL' in effects.values() else 'NOT_RUN' if 'NOT_RUN' in effects.values() else 'PASS'
  r['widths'][str(w)]={'status':status,'effects':effects}
 rows.append(r)
save('required-matrix',{'sourceSHA':SHA,'contract_sha256':hashlib.sha256((ROOT/'tests/scenarios/travel-lab.yaml').read_bytes()).hexdigest(),'contract':contract,'metadata_note':'계약 input_source_sha6853는 원본 메타데이터 그대로 보존; 실제 검증 HEAD는598. 부분 PASS를 전체 계약 PASS로 승격하지 않음.','scenarios':rows,'officialPASS':False})
timings=[]
for f in sorted(D.glob('*.json')):
 d=json.loads(f.read_text())
 if 'run' in d and d['run'].startswith('W04R') and 'harness' not in f.name and 'initial' not in f.name:
  seconds=d.get('elapsed_seconds');method='process elapsed_seconds'
  if seconds is None:seconds=round(f.stat().st_mtime-int(d['run'][4:])/1000,3);method='마지막 원본 JSON mtime - run timestamp 근사; 정확한 process duration 아님'
  timings.append({'file':f.name,'seconds':seconds,'measurement':method,'exit_code':'실행 완료0; step FAIL은 JSON에 보존. 초기 지원 검증 프로세스 종료는 별도 BLOCKED.'})
save('timings',{'sourceSHA':SHA,'commands':[{'command':'git fetch origin feat/g7-travel-lab-c7ae42d1; git checkout --detach '+SHA,'exit_code':0},{'command':'npm ci --ignore-scripts --no-audit --no-fund','exit_code':0,'approx_seconds':9},{'command':'node tests/W04_RECHECK/<script>.mjs','results':timings}],'public_commands':load('public-results')['commands'],'public_total_seconds':110.368,'note':'중간 실패/재시도는 별도 harness JSON; 단계 수/cleanup 행을 테스트 총수로 합산하지 않음.'})
old=Path('/home/ubuntu/.agentopt-v2/workspaces/req_52b83af0b2d1425094bb4b9e24edd166/tests/W03_RECHECK')
reuse=[]
for n in ('common','admin-recheck','catalogue-recheck','journey-recheck','recovery-recheck','support-recheck'):
 p=old/(n+'.mjs');reuse.append({'prior_source':'tests/W03_RECHECK/'+p.name,'prior_sha256':hashlib.sha256(p.read_bytes()).hexdigest(),'current':'tests/W04_RECHECK/'+p.name,'current_sha256':hashlib.sha256((ROOT/'tests/W04_RECHECK'/p.name).read_bytes()).hexdigest()})
save('provenance',{'sourceSHA':SHA,'oldRequest':'req_52b83af0b2d1425094bb4b9e24edd166','oldStatus':'INTERRUPTED','oldPartialCounts':{'PASS':32,'FAIL':6,'BLOCKED':4,'NOT_RUN':4},'oldPartialIsFinal':False,'selected_read_only_sources':reuse,'prior_PNGs_copied':0,'old_scripts_executed':False,'old_workspace_writes':False,'post_execution_harness_corrections':['catalogue priorBoundaryEvidence basename corrected in script only; original catalogue.json remains immutable and catalogue-input-harness.json proves prior own native201.','access-decline script now asserts native 진행 어려움; original locale assertion FAIL preserved and decline-followup closes actual owner state.','localStorage quotaHits0 original PASS marker preserved but final fallback verdict NOT_RUN; future script corrected.','Literal synthetic test phone values changed to runtime-only generator before Git publication; actual executed JSON/screens remain unchanged.','common/source guard allows evidence-only descendant commit; actual executed product HEAD fixed598.','guest redirect screenshots initially captured blank navigation frame; recaptured after native login input visible, both visually inspected.'],'native_subagents':1,'peak_native_agents':2,'official_children':0,'app_http_workers':4,'canonicalValidation':'BLOCKED','hostedCI':'NOT_RUN:0runs','public_readonly_review':{'script_sha256':'434f4c47eaae0bd9dd0732c0bc64e582e8a32d9539c931f939b413d3e7dc0b53','result':'34uniquePASS,30manifest hashes,served primary assets match; P3 hero1440 reason wording corrected'}})
save('summary',{'sourceSHA':SHA,'tree':'9e00273bdf18d6a713343755aac54f44a9b032b4','tested_actual_HEAD':SHA,'request':'req_7e41235d7eeb44a0b4efe254bc678748','overall':'FAIL','critical_native_journeys':{'390':'PASS with requests overflowFAIL','1440':'PASS'},'product_findings':[{'width':390,'route':'/travel/requests','viewport':390,'initialScrollWidth':412,'finalScrollWidth':424,'status':'FAIL','evidence':['journey.json','my-requests-390.png','overflow-reproducer.json','final-requests-overflow-390.png']}],'blocked':['six unretained initial native-login tokens deletion unproven','prior actors25carts/tokens inaccessible to fresh actors','canonical Validation unavailable'],'not_run':['four individually selected native editor datasource previews','optional localStorage quota fallback: actual quotaHits0','private attachment access: no owned attachment','independent SQL last-seat concurrency and transactional stock/order/payment/dispatch table assertions','preview restart/migrations/full product regression','prior10category final state requery','hostedCI0runs'],'cleanup':{'counts':load('cleanup-counts')['counts'],'CANCELLED':22,'DECLINED':2,'products_hidden_unpublished':15,'departures_inactive_reserved0':17,'native_posts_softdeleted':18,'policies_inactive':4,'categories_inactive':4,'member_cart_items':0,'other_member_cart_items':0,'recorded_tokens_verified_revoked':84+(load('overflow-token-count')['verifiedRevoked'] if (D/'overflow-token-count.json').exists() else 0),'unretained_tokens_unproven':6,'all_requests_CANCELLED':False},'screenshots':len(list(D.glob('*.png'))),'officialPASS':False,'push_merge_deploy':False,'implementation_changed':False})
print('전체14시나리오/효과 보존, provenance/summary/timings 생성')
