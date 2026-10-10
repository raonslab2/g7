# RAON 여행사이트 사용자 검토본 — 2026-10-10

실행 중인 **G7 Travel Lab**에서 여행 메인 → 지역·테마·기획전 → 검색 → 상품 상세 → 출발일·인원 → 장바구니 → 시험 상담 접수 → 관리자 처리 → 내 상담 상태 조회를 확인할 수 있습니다. 공지·FAQ·비공개 문의도 실제 G7 API와 격리 DB를 사용합니다. 합성 상품·가격·출발일을 쓰며, 거래 화면의 안내대로 실제 예약·결제·외부 발송은 연결하지 않습니다.

## 화면과 접속

현재 확인한 개발 주소는 **http://127.0.0.1:18871/travel** 입니다. 이 주소는 개발 호스트의 loopback이며 외부 공개 미리보기가 아닙니다. 기존 개발 호스트에 SSH 접근 권한이 있는 검토자는 다음 터널로 자신의 브라우저에서 확인할 수 있습니다. `DEV_HOST`는 이미 접근 가능한 개발 호스트 주소로 바꿉니다. 새 계정이나 공개 포트를 만들지 않습니다.

```bash
ssh -N -L 18872:127.0.0.1:18871 DEV_HOST
# 검토자 브라우저: http://127.0.0.1:18872/travel
```

기존 호스트 접근권이 없으면 아래 Git 실행 패키지를 **독립 개발 머신과 전용 MySQL/MariaDB 인스턴스**에서 실행합니다. [설치·실행 절차](../../deploy/travel-lab/README.md), PHP 8.2+/Node 20.19+ 또는 22.12+, 잠긴 Composer/npm 의존성을 사용합니다. 패키지가 고정 이름 `req81_travel_lab`, `req81_travel_lab_test`, `req81_travel`을 쓰므로 현재 검증 호스트나 다른 사람이 사용하는 같은 DB 서버에서 새 checkout의 `setup.php`를 실행하면 안 됩니다. 기존 개발 서비스의 계정 비밀번호를 변경할 수 있습니다.

로그인은 전용 설치가 생성한 `admin@travel-lab.example.invalid`와 그 checkout의 무시된 `.env` 안 `INSTALLER_ADMIN_PASSWORD`를 로컬에서만 확인합니다. 비밀번호·환경 파일을 Git, 보고서, 화면에 공유하지 않습니다. 기존 호스트의 로그인 정보는 기존 환경 소유자가 보유한 값이며 이 패키지에 공개하지 않습니다. 새 설치의 공지·FAQ·합성 상품은 공식 provisioning을 사용합니다. 기획전은 기본 0개이며, 실행 안내의 명시적 campaign provisioning 또는 native Page 관리로 두 슬롯을 게시합니다.

이번 Request에서 다시 촬영한 실제 화면입니다. 이미지가 보이지 않으면 링크를 엽니다.

| 화면 | 모바일 390px | PC 1440px |
|---|---|---|
| 여행 메인 | [390](scope-correction-ui/home-390.png) | [1440](scope-correction-ui/home-1440.png) |
| 상품 상세·출발 선택 | [390](scope-correction-ui/product-390.png) | [1440](scope-correction-ui/product-1440.png) |
| 고객센터 | [390](scope-correction-ui/help-390.png) | [1440](scope-correction-ui/help-1440.png) |
| 실제 빈 검색 | [390](scope-correction-ui/empty-search-390.png) | [1440](scope-correction-ui/empty-search-1440.png) |
| 통신 차단 해제 후 실제 API 재시도 | [390](scope-correction-ui/catalog-recovered-390.png) | [1440](scope-correction-ui/catalog-recovered-1440.png) |
| 시험접수·관리자 처리·재로그인 조회(인수한 독립 검증) | [390](../../tests/W04_RETRY_BROWSER/evidence/relogin-lifecycle-transaction-1-390.png) | [1440](../../tests/W04_RETRY_BROWSER/evidence/relogin-lifecycle-transaction-1-1440.png) |

## 직접 확인하는 흐름

1. `/travel`에서 지역 또는 테마를 선택하고 `/travel/search`에서 검색·가격·날짜 필터를 바꿉니다. 상품을 열었다가 뒤로 가면 검색 조건을 확인할 수 있습니다. 없는 검색어의 빈 결과와 재시도 안내도 확인합니다.
2. 상품 상세에서 실제 가용 출발일과 인원을 선택하고 장바구니로 이동합니다. 장바구니의 인원을 바꾸면 서버 계산 금액이 바뀝니다. 시험 접수 안내에 동의하여 접수합니다. 성공 화면은 실제 여행 예약 확정이 아닙니다.
3. `/travel/requests`에서 접수와 처리 상태를 조회합니다. 관리자는 `/admin/travel-lab/inquiries`에서 상세를 열고 허용된 상태로 처리합니다. 사용자로 재로그인하여 상태를 다시 확인합니다.
4. `/travel/help`에서 공지·FAQ를 조회하고, 로그인 후 비공개 문의를 작성합니다. 관리자 답변은 작성자에게만 조회됩니다. 관리자 여행 메뉴에서 출발일·정원·기획전을 관리하며 상품·옵션·가격은 G7 native 커머스 화면을 사용합니다.

실행 패키지 재현은 별도 설치 안내를 따릅니다. 위 단계는 사용자가 확인할 기능이며 현재 Request에서 모든 쓰기 동선을 새로 실행했다는 주장이 아닙니다. 마지막 독립 검증에서 실행된 실제 DB/UI 결과를 동일한 제품 소스로 인수했습니다.

## 공개 벤치마킹과 RAON 설계

[롯데관광 공개 메인](https://www.lottetour.com/welcome)의 10월 9일 관찰 기록을 재사용했고 10월 10일 메인만 읽기 전용으로 재확인했습니다. 고객 소스·Figma·DB 인수와 110개 고객 화면 납품은 이 작업의 선행조건이나 완료조건이 아닙니다. 공개에서 볼 수 없는 관리자·예약 내부 처리는 RAON 실증 계약입니다.

| 공개 참고 지점 | RAON 자체 구현·설계 |
|---|---|
| 메인 여행 유형·지역 메뉴, 검색, 시작 가격 상품 카드 | 밝은 바탕·청록 내비게이션, 국내 합성 지역·테마, 자체 SVG, 서버 가격·가용 출발 카드 |
| 기획전 진입과 지역·유형 탐색 | native Page 두 슬롯, 게시 상태 확인, 연결 상품 필터와 실제 빈 결과 |
| 상품 요약·일정·날짜/가격 진입 | 자체 일정 문구, 실제 출발 옵션·인원·가용 정원·서버 계산 |
| 고객센터·공지·FAQ·문의 진입 | G7 Board 재사용, 작성자 비공개 문의, 관리자 처리·재조회 |
| 예약 확인 진입 | 명시적 시험 상담 접수·처리 상태, idempotency·권한·경쟁·영속성 검증 |

원본 로고·사진·문구·소스는 복제하지 않았습니다. 운영 사이트의 제출·예약·결제·회원가입·부하 시험도 하지 않았습니다. 상세 근거와 관찰 한계는 [REFERENCE.md](REFERENCE.md)에 있습니다.

## 인수한 고정 버전과 판정

검토 제품 source는 `5783e6ba124061bdfae639cdaf9b1c14a83cdf03`, 당시 tree는 `361f9a142572f8a6c0c28326c461a233a92aec4e` 입니다. 기존 PR #2 head `888f6b2d052b3b189fb1ff322b1e117e281e8e4d`까지 변경은 문서 5개뿐입니다. 이번 인수도 제품 소스를 바꾸지 않습니다. 설치본 4,507개 파일과 served core/travel/admin 자산 5개가 동일함을 새로 확인했습니다.

| 항목 | 판정·근거 |
|---|---|
| Page Retry 후 남은 오류 | 복구 PASS, 390/1440 실제 native 200 후 reload 없이 렌더링; 실패 재시도는 오류 유지. [독립 원본](W04_RETRY_BROWSER_CLOSURE.md) |
| Page readonly/self 범위·실제 빈 기획전 | PASS, readonly 쓰기 차단·self 타인403·0 matching 상품 화면 확인. 같은 원본 |
| 핵심 거래·관리자·비공개 문의 | 실제 UI/API PASS 범위 인수. 원본의 간헐 메뉴 실패는 아래에 분리 |
| 가격·인원·타인 접근·중복·정원 경쟁 | 설치 API 14행 계약·4개 지속 MySQL 연결 barrier PASS. 실제 엔진 MariaDB 10.11.14. [독립 원본](W04_FINAL_CONTRACT_PERSISTENCE.md) |
| 시험접수/문의답변/기획전 재시작 영속 | own service stop/start·새 로그인·row digest PASS. 같은 원본 |
| migration·복원 | 전체 백업 복원 및 primary import 실패→fallback PASS; down/up 자체가 이벤트를 보존하는 것은 아님. TEST 55테이블/104행 원상복구 |
| 이번 checkout의 domain 회귀 | 175 tests / 2,936 assertions PASS, 86.783초. SQLite source suite이며 MySQL 또는 공식 Validation과 구분 |
| hosted CI | NOT_RUN: G7에 Actions workflow 없음, 마지막 관찰 check run 0. 면제하거나 PASS로 바꾸지 않음 |
| 공식 Validation | NOT_RUN: verified receipt 미회수. 독립 Child 보고서가 공식 Validation을 대신하지 않음 |
| main 통합·운영 반영 | HOLD: 필수 미실행 게이트, 운영 자동 배포 여부 UNKNOWN. 기존 PR #2에 비충돌 인계만 연결하며 운영 배포하지 않음 |

원본 두 독립 증거 커밋 `3f16c7d53b5fba5e56a525f3d5be7e89cb960322`와 `e4c736fff897aae3545cf32126f7da12a2a9f824`를 ancestry-preserving merge로 인수했습니다. 이전 실패·하네스 실패·절차 한계·NOT_RUN 기록은 삭제하거나 PASS로 재작성하지 않았습니다.

## 조율·잔여 항목

정식 `agentopt_control catalog/status`로 현재 work receipt REGISTERED 및 Provider 실행을 확인했습니다. 원 지시 `work-20261009-g7-symphony-max-child-c7ae42d1` receipt도 확인했습니다. [범위 정정 지시](https://github.com/raonslab2/ai_gcs_v2/blob/main/work/orders/work-20261010-travel-benchmark-scope-correction-4f82b7d9.md) Git blob `b2ed450c05e0a776120f02e6cbb1d4dca281833d`와 [복구 지시](https://github.com/raonslab2/ai_gcs_v2/blob/main/work/orders/work-20261010-g7-travel-final-gate-recovery-9d7c41e2.md) blob `3b8e3f596f713aac08723e55941b38456e27e294`의 원격 발행도 확인했습니다. 복구 지시 receipt는 반환된 최근 50개에 **NOT_OBSERVED**이며 미등록이라고 단정하지 않습니다. Git 발행·작업지시함 수집·Request 등록·Provider 실행은 별개 상태입니다. 상태 도구는 현재 Request child만 조회하므로 원 부모와 과거 두 Child 상태는 조회하지 못했습니다. 정식 `GET /api/v1/requests/req_81ac33cac94046b9a2249cd14c0d00ba`는 HTTP 401(trusted proxy identity required)입니다. 인증정보·플랫폼 DB·설정을 읽어 우회하지 않았습니다.

따라서 활성 총괄 부재를 주장하지 않습니다. 동일 파일/DB를 수정하는 두 번째 구현 총괄이나 Child를 만들지 않고, 이 Request는 원본 회수·추가 read-only 검증·비충돌 Git 인계만 수행합니다. 기존 정식 추가지시 경로에서 활성 총괄에게 이 범위 정정과 두 검증 커밋을 연결해야 합니다. 그 연결은 이 Request에서 이용 가능한 공식 도구로 실행할 수 없어 한 번 인계합니다. 고객자료 수령이나 새 고객 프로젝트 등록은 필요하지 않습니다.

잔여 제품 관찰: 과거 1440 관리자 ActionMenu가 긴 context에서 한 번 열리지 않았고 fresh context 진단 5/5는 PASS입니다. 이번 추가 검증은 같은 context에서 pointer→상세→뒤로가기→keyboard→상세→뒤로가기→pointer 단계를 분리하여 1440 12/12, 390 6/6 PASS, 재현 0입니다. public 탐색·빈 검색·실제 통신 차단 해제 후 Retry·뒤로가기도 10/10 PASS입니다. [추가 UI 증거](scope-correction-ui/README.md)에 범위를 기록하고 과거 간헐 실패를 삭제하거나 전체 메뉴 PASS로 승격하지 않습니다. 현재 재현·수정 근거가 없으므로 공용 메뉴를 추측으로 수정하지 않았습니다. 서버 권한 문제는 관찰되지 않았습니다. native Page editor 전체·served SEO·engine lock graph 등 변경 없는 미검증 조합은 원본의 NOT_RUN 범위를 유지합니다.

첫 검토본 목표 **10월 11일 23:59 KST**, 사용자 검토 목표 **10월 12일**을 유지합니다. 지금은 동작하는 검토본과 화면을 인계하며, 공식 연결·Validation·hosted CI·운영 반영을 완료했다고 보고하지 않습니다. 승인된 외부 개발 미리보기가 없으므로 외부 검토에는 기존 SSH 접근 또는 승인된 비운영 호스팅 연결이 필요합니다.

## Spring 트랙

[Spring PR #1](https://github.com/raonslab2/raon-spring-starter/pull/1)의 병합 `1d63477f859cc3d0e4c55a0dced7f32b3e493052`와 PR 고정 head `fef595bed8424d9ea33b6546e3618576f5cd3628` CI SUCCESS를 GitHub에서 확인했습니다. 공통 `raon-common`과 여행 `travel-reference`/실행 `reference-app` 분리를 유지합니다. G7에서 Spring 소스·DB·서비스를 수정하지 않습니다. 같은 자체 공개 벤치마킹 범위입니다.

Spring 검토자는 [고정 버전 실행 안내](https://github.com/raonslab2/raon-spring-starter/blob/1d63477f859cc3d0e4c55a0dced7f32b3e493052/README.md)에 따라 JDK21/Maven wrapper로 `clean verify` 후 실행 JAR를 기동합니다. 기본 주소 `http://127.0.0.1:8080`, file-H2 dev 또는 별도 PostgreSQL16.15 프로필을 사용합니다. 이 Request는 해당 실행을 새로 검증하지 않았습니다. 공개 prerelease는 과거 checkpoint이므로 최종 merge 버전이라고 표시하지 않습니다. Spring 공식 Validation/운영 배포는 별도 상태입니다.
