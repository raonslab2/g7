# 여행 워크플로 테스트 근거

기준 소스: `6853f40d58acbf53a2f29cbb9dd422cc439047a9`. 이 문서는 이 Request 안에서 실행한 개발 테스트의 결과다. 독립 Validation 또는 MySQL 병렬 잠금 검증을 의미하지 않는다.

## 실행 결과

```bash
php vendor/bin/phpunit modules/_bundled/raonslab-travel_lab/tests/Feature/TravelWorkflowTest.php
# OK (48 tests, 373 assertions)
# Time: 01:19.331, Memory: 82.50 MB

vendor/bin/pint modules/_bundled/raonslab-travel_lab/tests --test
# passed
```

현재 테스트 총 48개 실행 케이스를 마지막 전체 실행으로 검증했다. 시나리오 매니페스트의 17개 flow 및 52개 effect와 테스트 메서드의 마커를 대조하여 누락이 없음을 확인했다.

검증한 주요 결과: 실제 이커머스 담기·수량 변경·선택 삭제 및 현재 가격 계산, 일반 상품 제외, Bearer 토큰 인증, 소유권과 관리자 권한/본인 스코프, 같은 키 재전송/변경 충돌, 정원 경계, 상태 25개 조합, 정원 반환의 재전송 안전성, 스냅샷 쓰기 실패 후 전체 롤백 및 동일 키 재시도, 두 번째 정원 반환 실패 후 첫 반환/상태/메모 롤백, 불가 항목 정리, 비지원 추가옵션 및 중복 옵션 거절, 금액/수량 주입·중복 ID·헤더/본문 키 충돌 거절, 불변 스냅샷 리소스 직렬화.

## 격리와 한계

- `WorkflowTestCase`는 앱 bootstrap 이전부터 DB 연결을 SQLite `:memory:`로 고정한다. 실제 운영 DB에 접속하지 않는다. settings 디스크는 기존 Commerce ModuleTestCase가 격리하며, 환경 변수와 테스트 autoloader는 teardown에서 복원한다.
- Commerce `CartService`, 계산기, repositories, Sanctum 인증은 실제 구현을 사용한다. Commerce 가격·카트 기능의 mock은 없다.
- 아직 이 Request에 도메인 담당자의 소스가 없으므로 **테스트 전용 모델 4개 및 enum 1개**와 계약 테이블 4개를 사용했다. 실제 도메인 클래스·마이그레이션이 합쳐지면 bootstrap은 해당 실제 구현을 우선 사용한다. 이 fixture는 운영 provider/autoload에 등록하지 않는다. 합본 이후 실제 도메인으로 재검증해야 한다.
- MySQL의 테이블별 인덱스 이름을 SQLite의 전역 이름으로 변환하는 테스트 전용 grammar를 사용한다. 여행 문의와 무관한 구매적립 generated-column의 MySQL 전용 마이그레이션 1개는 제외하며 다른 실제 코어·커머스 마이그레이션을 실행한다.
- SQLite는 `SELECT FOR UPDATE`를 제공하지 않는다. 테스트는 원자성·순차 정원 경계·멱등성과 상태 전이를 검증한다. 동시 중복 키 및 실제 MySQL 잠금 경합 검증은 **NOT_RUN**이며 별도의 합본 검증이 필요하다.
- 실제 주문·결제 생성이 없음을 확인했고 외부 결제, 이메일/SMS 또는 공급자 연동을 실행하지 않았다.

## 내부 검토와 전달 대상

소스를 구현하지 않은 네이티브 서브에이전트가 고정된 staged source diff를 검토했다. 최종 검토 대상 SHA256 (`git diff --cached -- modules/_bundled/raonslab-travel_lab/src`): `6a5481bb299948aad24a73750861e740bb157a2270890359c662f6b9f212fc04`. 최종 차단 사항 없음. 오래된 카트 정리, 쓰기 이후 롤백, 관리자 소유자 스코프의 초기 발견 사항을 수정·테스트했다. 이는 내부 검토이며 공식 독립 Validation이 아니다.

실제로 생성한 네이티브 서브에이전트는 2개다(커머스 계약 조사·내부 검토 1개, 테스트 구현·실행 1개). 리드를 포함한 최대 동시 활성 에이전트는 3개였다. 공식 자식 Request는 생성하지 않았다.

리드가 최종 전체 테스트를 직접 재실행한 결과: `OK (48 tests, 373 assertions)`, `Time: 01:20.522, Memory: 82.50 MB`. src와 tests 대상 Pint `--test` PASS, 17개 flow/52개 effect 마커 대조 PASS, staged diff whitespace 검사 PASS.

아래는 이 스코프의 정확한 변경 경로다. 모두 신규 파일이며 기준 소스의 다른 경로는 변경하지 않았다.

```text
modules/_bundled/raonslab-travel_lab/docs/api/workflow.md
modules/_bundled/raonslab-travel_lab/src/Http/Controllers/Admin/InquiryController.php
modules/_bundled/raonslab-travel_lab/src/Http/Controllers/Api/CartController.php
modules/_bundled/raonslab-travel_lab/src/Http/Controllers/Api/InquiryController.php
modules/_bundled/raonslab-travel_lab/src/Http/Requests/Workflow/AddTravelCartRequest.php
modules/_bundled/raonslab-travel_lab/src/Http/Requests/Workflow/ListInquiriesRequest.php
modules/_bundled/raonslab-travel_lab/src/Http/Requests/Workflow/SubmitInquiryRequest.php
modules/_bundled/raonslab-travel_lab/src/Http/Requests/Workflow/UpdateInquiryRequest.php
modules/_bundled/raonslab-travel_lab/src/Http/Requests/Workflow/UpdateTravelCartRequest.php
modules/_bundled/raonslab-travel_lab/src/Http/Requests/Workflow/WorkflowRequest.php
modules/_bundled/raonslab-travel_lab/src/Http/Resources/AdminInquiryCollection.php
modules/_bundled/raonslab-travel_lab/src/Http/Resources/AdminInquiryResource.php
modules/_bundled/raonslab-travel_lab/src/Http/Resources/InquiryCollection.php
modules/_bundled/raonslab-travel_lab/src/Http/Resources/InquiryItemResource.php
modules/_bundled/raonslab-travel_lab/src/Http/Resources/InquiryResource.php
modules/_bundled/raonslab-travel_lab/src/Http/Resources/TravelCartResource.php
modules/_bundled/raonslab-travel_lab/src/Repositories/Contracts/WorkflowCartRepositoryInterface.php
modules/_bundled/raonslab-travel_lab/src/Repositories/Contracts/WorkflowInquiryRepositoryInterface.php
modules/_bundled/raonslab-travel_lab/src/Repositories/WorkflowCartRepository.php
modules/_bundled/raonslab-travel_lab/src/Repositories/WorkflowInquiryRepository.php
modules/_bundled/raonslab-travel_lab/src/Services/InquiryService.php
modules/_bundled/raonslab-travel_lab/src/Services/TravelCartService.php
modules/_bundled/raonslab-travel_lab/src/routes/workflow.php
modules/_bundled/raonslab-travel_lab/tests/Feature/TravelWorkflowTest.php
modules/_bundled/raonslab-travel_lab/tests/Fixtures/DomainContract.php
modules/_bundled/raonslab-travel_lab/tests/Fixtures/WorkflowSqliteGrammar.php
modules/_bundled/raonslab-travel_lab/tests/WORKFLOW_EVIDENCE.md
modules/_bundled/raonslab-travel_lab/tests/WorkflowTestCase.php
modules/_bundled/raonslab-travel_lab/tests/scenarios/workflow.yaml
```
