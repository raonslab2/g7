# W04 native install attempt3 독립 인수

**CHANGES_REQUIRED 유지.** 비작성자가 고정 Git source/helper/public evidence를 읽기 전용으로 대조했다. 실제 설치본 HTML/HTTP/재시작/환경 복구 증거는 있지만, 공개 package 초기 migrate와 고정 MySQL smoke는 실패가 남는다. 본 검토는 SQL·PHPUnit·HTTP·복구를 재실행하지 않았고 공식 Validation/전체 제품 PASS가 아니다. 실행 중인 브라우저의 APP/TEST/env/service/process 및 제품/Git/다른 Request는 변경하지 않았다. 이 보고서만 작성했다.

## 출처와 실제 해시 대조

원본 공식 Request는 `req_caef46f3473043048b5b4bc2ec41eaea`, 제품 SHA는 **`fa5523175ac494cfbd13bbf89bf06b3ec91835a6`**, tree는 **`fa685339b030ee4efe46b63dc8d98c0e2f7d4f0c`**다. helper checkpoint HEAD를 제품 SHA로 대신하지 않는다.

Evidence commits `94c3f75b41ef44ca9456c742216382fc089a2f45`와 `0eb9e68f11e4e25f46fdfa1ed600b3c851951d14`는 읽을 수 있으며 모두 fa552317의 자손이다. 제품→최종 delivery는 **261개 파일**, 전부 `docs/symphony/w04-native-final/**` 및 `W04_NATIVE_INSTALL_FINAL.md`다. parent/remote publication 완료를 증명하지 않는다. `intake/source.json`의 **2,266개 해시를 실제 fixed Git blob과 독립 대조해 2,266/2,266 일치**, `intake/readonly-review.json` helper pins도 **30/30 실제 파일과 일치**했다.

| 고정 입력 | SHA-256 |
| --- | --- |
| `W04_NATIVE_INSTALL_FINAL.md` | `e8a8b57ec3d13fedea0355ded7467b5f6571c343773e8d78d9ff0d99a9788693` |
| `intake/source.json` | `0474e0ef1495882f284e8002d7e88d3d09eff05a85c5aed0fb46b101ec987fab` |
| `intake/readonly-review.json` | `cba5bafee83153b4581ffbb07ecded49cd0f040337ed30802bb7e0e7a1ce7def` |
| `regressions/progress.json` | `571766804bd75bd1e9ea3c38a1080752581c232138ac94919966d786e0837339` |
| `release/result.json` | `7dc156131cd6c6f68090e684b4b4985fa0753ab84befe5122611ba38b88ab51d` |

Evidence 경로는 원본 Request의 `docs/symphony/w04-native-final/evidence/` 아래다.

## 실행 증거와 판정 범위

다음은 원본 verifier의 실제 실행 결과와 helper/ledger의 일치를 확인한 것이며 이 intake의 새 runtime 실행이 아니다.

- 빈 TEST 0테이블에서 native 설치 후 **112테이블/1,587행**. 최종 경로 성공 native 명령은 **19개**: commands ledger 성공14 + installer-only migrate1 + resume 성공4. metadata 거부1, 초기 실패와 resume를 보존했으며 한 번의 무조정 공개 recipe PASS가 아니다.
- 네 module의 실제 vendor_mode=bundled DB 재조회, missing/corrupt bundle 설치 거부, 원본 bundle 복원. silent Composer fallback은 관찰되지 않았다.
- 실제 설치본 ProductService/HTMLPurifier4.19.0 origin에서 합성 상품2개 ko/en HTML create/update·악성 markup 제거 PASS. 직접 상품 SQL write0.
- 실제 installed kernel **37 HTTP/로그인5**, routes31 및 installed Service/Listener/role/menu 확인. server price24,000, owner replay/status/private support를 확인했고 orders/payments/notification logs0이다.
- 자체 TEST 서버 **20 HTTP/3 process starts**. A→B 재시작 직후 후속 HTTP 전에 전체 inventory 동일. own env 실제 제거→거부→bytes/0600 복원 후 C HTTP 전에 전체 동일. parent preview18871은 건드리지 않았다.
- native migration/recovery 정상 inner exit0, fault inner **exit1 유지**. 양쪽 **112테이블/1,763행** 전체 전후 digest `f2aaeb72925291d8bf7f9e0220b5dce0081fa8cc4eb6584e87a34f73ab48fc04` 동일. native catch/finally control flow는 유지했으나 adapter byte-verbatim=false다. 복구 PASS를 고장 처리 exit0로 바꾸지 않는다.
- `runtime-bootstrap.php`는 native 생성 autoload cache의 installed vendor paths를 먼저 require하고 실제 core kernel을 boot하는 검증 entry다. 수동 route/provider/auth 등록은 없다. 이 custom entry 결과를 미변경 public/index.php 전체의 독립 검증과 동일시하지 않는다.

| 최종 PHPUnit 명령 | tests/assertions | 실제 결과 |
| --- | ---: | --- |
| LiveMysqlTest | **1/8** | **PHPUnit1·runner1, FAIL** |
| TravelSupportApiTest | 7/90 | PASS |
| TravelSupportProvisionerTest | 5/48 | PHPUnit0, **runner1 복원 scanner BLOCKED 보존**; 후속 별도 process의55/104 equality/handles0로 해소 |
| TravelSupportNotificationTest | 2/15 | PASS |
| SecretPostCommentAccessTest | 8/17 | PASS |
| CartQuantityAndPurchaseLimitTest | 15/39 | PASS |
| UserAuthControllerTest | 63/186 | PASS |
| Installation | 2/29 | PASS, 실제 IDV migration2개뿐 |
| InstallerContextTest | 8/8 | PASS |

Partial/다른 Request/이전 실행 수를 합산하지 않았다. frontend build/typecheck는 의존성 미준비로 NOT_RUN, CI/공식 Validation/installer wizard 전체도 NOT_RUN. 기존128/639는 NOT_PROVEN이다. 초기 cache/template 연결 purge/helper closure/cleanup/parse 실패 및 일부 초기 log 덮어쓰기 제한은 원본 report에 남는다.

## 원본55/104 복원

`original/before.json`, `original/after.json`, `release/inventory.json`을 독립 읽어 전체 JSON 동일과 **55테이블/104행**을 확인했다. native JSON encoding으로 full digest를 재계산해 **`ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`** 일치. row/NULL/중복/DDL/object inventory를 포함한다. 세 파일 실제 SHA-256도 모두 `4d48b2f40cad00e234aa8bd32b0a2ead19f5bae81555d0565ce8601dfedbdc16`이다.

원본 release는 **2026-10-09 17:53:26 UTC**이며 TEST connections0, own native/HTTP processes0, port18874 closed, foreign handles/unreadable0을 기록했다. observer는 별도 종료 확인했다. 이는 원본 실행 metadata이고 현재 DB/process 재측정이 아니다. private original/safety backup은 보존됐고 과거 BLOCKED 이력과 현재 live marker를 구분한다. raw dump/options/env/private diagnostics/contact/token 값은 읽지 않았다. 복원 성공이 설치 gate 실패를 취소하지 않는다.

## 필요한 수정과 고정 소스

**1. 공개 첫 설치의 cache bootstrap.** `scripts/travel-lab/setup.php:77`은 normal database-cache 환경으로 migrate를 시작한다. `app/Providers/CoreServiceProvider.php:533–537`의 compatibility cache `has()`가 cache table 생성 전에 부팅 중 호출돼 빈 schema에서 실패한다. 기존 InstallerContext의 schema readiness 판정만으로 이 cache read가 없어지지 않는다.

Attempt3 `empty-migrate.php`는 미완료/진짜0테이블/설치 확장 없음/exclusive TEST 확인 후 첫 migrate process에만 기존 native **CACHE_STORE=array**를 전달했다. 정상 env는 database를 유지하고 이후 실제 DatabaseStore/lock tables를 검증했다. 그러나 대응은 검증 helper 안에만 있으므로 **공개 recipe 원형은 미수정/NOT_PROVEN**이다. Package setup/helper에 제한된 installer-only 준비를 구현하고 migrate 후 normal database cache effective 설정을 재확인해야 한다. normal preview guard를 완화하거나 global cache를 array/file로 바꾸지 않는다. `deploy/travel-lab/README.md:29`도 실제 명령에 맞추고 새 고정 SHA에서 fresh 재검증해야 한다.

**2. 고정 MySQL smoke의 cache fixture.** `scripts/travel-lab/LiveMysqlTest.php:31`은 testing ordinary array로 boot하고 실제 migration/MySQL/native services를 준비하지만 기존 `UsesDatabaseThrottleCache`를 사용하지 않는다. `TravelThrottleRequests.php:27`은 ArrayStore를 의도대로503으로 거부해 cart quantity0의 예상422보다 먼저 실패한다. 정상 installed database runtime HTTP 성공으로 이 실패를 대체하지 않는다.

Smoke setUp에서 기존 native DB throttle-cache trait을 사용해 migration 뒤·HTTP 전에 isolated cache/cache_locks/RateLimiter를 준비해야 한다. 기존 trait은 native cache migrations와 driver/limiter reset을 지원한다. ordinary PHPUnit 전역 array는 유지하고 middleware 삭제/ArrayStore 허용/fixture 외부 비공개 override로 PASS를 만들지 않는다. 새 SHA 실제 MySQL smoke 재검증까지 FAIL은 열린다.

| 영향을 받는 fixed-source 파일 | SHA-256 |
| --- | --- |
| `scripts/travel-lab/setup.php` | `0353c4ffce837ae29eb400fedf508d0441ca6dc45e5a1a76e497e5bc0b70cdce` |
| `scripts/travel-lab/environment.php` | `e2f9b633dd26b9a2d69b06eaa4c25f87f3ddc767747f6517e6948c0e3224ba24` |
| `deploy/travel-lab/README.md` | `517d106c752a8483545063554de2608390be419cde8ed8c324ded6a6df212455` |
| `scripts/travel-lab/LiveMysqlTest.php` | `a2d9c3c75e7652082d336bec56f6c6c786fc821c08fba47d5b60518e49aca05b` |
| `modules/_bundled/raonslab-travel_lab/tests/UsesDatabaseThrottleCache.php` | `ed82ef4de82d0fe648fe8cb67e793b8e884c397b1400b8935dbccd19bca086e6` |
| `modules/_bundled/raonslab-travel_lab/src/Http/Middleware/TravelThrottleRequests.php` | `aab02c6b01254dd62b7973ee5b0fbc68bb0afd052d49171ac19f3bd21e97e92d` |

검토 adapter pins: empty-migrate `39ef0617127b4331d290beeb2dd0608d0b67780bd9a74bdb3c165e1702963c84`; runtime-bootstrap `b34fe6c87f494f1047e1ec94e42920627d747819cb1dc7dcde0b2f46642a6629`.

## 공개 evidence 위생

261 delivery 경로에 env/raw SQL dump/cnf/vendor/storage 파일 없음. 값 출력 없이 전체 text를 검사해 literal Bearer 값/private key/raw INSERT VALUES/UUID 패턴0. email 패턴1건은 `make-env.php:38`의 random run을 쓰는 합성 installer source generator이며 live contact/credential 값 저장이 아니다. 원본 private-value scan은259파일/match0으로 final report/scan 자체를 포함하는 delivery261과 시점이 다르다. 같은 분모의 전수 scan으로 꾸미지 않았다. 원본 비밀값 읽기·새 OCR·runtime 실행은 NOT_RUN이다.

출처와 실행 수, 원본55/104 복원 결합은 확인했다. 공개 첫 migrate와 MySQL smoke의 수정 및 새 고정 SHA 검증이 필요하며, 설치본의 한정 성공을 공개 실행 package 전체 PASS로 보고하지 않는다.
