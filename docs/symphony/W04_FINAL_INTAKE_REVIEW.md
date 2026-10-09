# W04 final browser / TEST recovery independent intake

**PASS_BOUNDED_INTAKE.** 비작성자가 원본 Git 결과와 parent의 로컬 인수본, 공개 증거 해시 및 판정 범위를 읽기 전용으로 대조했다. 브라우저·DB·설치·복구를 재실행하지 않았고 공식 Validation/원격 통합/전체 제품 PASS가 아니다. 제품/다른 Request/DB/env/service/Git 변경은 없으며 이 보고서만 작성했다.

## 고정 출처와 인수

두 결과의 제품 대상은 `1052e3fb4bc4cccabb51b8c538116c78655f345b`, tree `f18fa2056a031353c889785d768f87824e3083f5`다.

| 결과 | 원본 child commit | parent local commit | 독립 파일 대조 |
| --- | --- | --- | --- |
| browser | `5ce63b5ac9e3eeb96a2a2ab74e522fbcc1112d84` | `d7d018dab9642a926026a6a97b228af671318a4e` | 184/184 경로·바이트 동일 |
| TEST recovery | `738c2fa4b65af2470f99c6b23c957de5604b784e` | `0906083ec41f43e968397bfefee32c8243bf9908` | 26/26 경로·바이트 동일 |

1052는 네 커밋 모두의 ancestor다. 원본 child 객체는 common Git에서 읽을 수 있고 parent HEAD의 ancestor는 아니다. cherry-pick한 두 local commit은 parent HEAD에 도달 가능하다. 이 대조는 원격 push/CI/receipt를 증명하지 않는다.

| 고정 입력 | SHA-256 |
| --- | --- |
| [W04_BROWSER_FINAL.md](W04_BROWSER_FINAL.md) | `8d83ce954e3be510d6c6c27ea7edd139f45f530e617d4517c12ef0f66d4d1197` |
| browser [sha256-manifest.json](evidence/W04_FINAL_BROWSER/sha256-manifest.json) | `39123992d7e85f3aa0f89b2f5587f0be5989beaf2f558e798d09e4db4516f9bb` |
| [W04_TEST_BASELINE_RECOVERY_2.md](W04_TEST_BASELINE_RECOVERY_2.md) | `3fbd2a376b301804c5830c05e45139b005d11c91332484d73745373552178c77` |
| recovery [source-manifest.json](w04-test-recovery-2/evidence/source-manifest.json) | `5f0d0e05b15e2bf1702b9049e0b5d70a592cccc783dfc109a3fb753d98b0a7b7` |

## 실제 증거 대조와 제한

- Browser manifest는 자기 자신을 제외한 183개 파일을 포함한다. 실제 크기·SHA-256은 **183/183 일치**, PNG는 106개다. 실행 전후 required runtime 4,363개 및 실제 HTTP 자산 11개 일치는 원본 verifier의 source-binding 증거다. 설치되지 않은 optional sirsoft-basic 463개와 broad `source_all_equal=false`를 삭제하거나 전체 일치로 바꾸지 않는다.
- 390/1440 Chromium emulation의 고객→관리자→고객, 응답 유실/메모리 quota 복구, 지원·목록 geometry는 원본 비작성자 브라우저 실행 결과다. 공개 체크 34, 여정 6단계, 지원 12, recovery 10, geometry/navigation 4, access/decline 8은 서로 다른 집계 단위이며 합산 제품 테스트 수로 만들지 않는다. true contention/DB 원자성/재기동/첨부/optional localStorage 및 전체 14개 계약의 미실행 부분은 NOT_RUN이다.
- **Native 관리자 상품/옵션 생성은 두 너비 모두 BLOCKED**다. 기존 24개 카테고리가 inactive여서 native category tree가 비었다. 당시 검증자는 category/policy 준비를 자신의 검증 범위 밖으로 해석해 저장 상품 0개로 중단했다. 부모의 이번 후속 범위에는 새 자체 category/policy UI 생성이 명시적으로 포함된다. API fixture 생성과 그 상품의 UI 편집/여행 등록 PASS를 UI 신규 상품 생성 PASS로 대체하지 않는다.
- 초기 OCR 마스킹 실패는 공개 sanitized 실패 기록에 보존됐다. 제외된 `native-answer-1440.png`는 현재 트리/manifest에 없다. 늦게 표시된 이메일 PNG 원본이나 민감 문자열은 읽거나 출력하지 않았다. 최종 OCR 106/106은 durable 원본 결과를 읽은 것이며 본 검토의 독립 OCR/전체 pixel screening 재실행은 아니다. 초기 OCR exit143와 helper 수정 후 기존 frame 미재캡처 제한도 유지한다.
- 공개 browser text/manifest와 recovery 26개 경로에 대해 이메일, Bearer, private-key, raw INSERT VALUES, UUID 패턴을 값 출력 없이 검사했으며 발견 0이다. private env/options/dump/contact 원문은 읽지 않았다. 이 제한된 텍스트 검사는 모든 PNG의 개인정보 부재를 새로 보증하지 않는다.
- Recovery helper manifest **8/8 실제 해시 일치**. 공개 original/current/final inventory의 전체 native JSON digest를 독립 재계산했다: 원본 **55/104** `ded72a53ad82a159b88e50a6560625488bb569a55f5f5ffa109cd45ae52d056e`; 복구 전 **55/419** `f9ba89d77dac19888cfe5982292c782d08b640453493ceaacea923dc1456dfe1`; 최종 **55/104** 원본 digest와 동일. 테이블/각 행 수·hash/DDL/object inventory JSON 전체도 원본=최종이다. raw SQL dump는 읽지 않았다.
- 첫 launcher autoload 실패는 DB/wipe/import 전 실패로 보존됐고, 제한된 resume 뒤 native wipe 1회·import 1회와 final equality/release가 기록됐다. original snapshot 불변·private backup 보존·TEST release는 원본 실행 metadata다. fresh install 명령/회귀 PHPUnit은 0회다. 이전 128/639는 NOT_PROVEN을 유지하며, 이 결과는 **PASS_BASELINE_RECOVERY_ONLY**이다. 현재 live DB/연결/프로세스 재확인은 수행하지 않았다.

## 남은 native 상품 생성의 준비 경로

다음은 source-only 계약 설명이다. 실제 준비/저장/브라우저 재검증은 lead 또는 새 고정 SHA 검증자가 격리 lab에서 수행해야 한다. 기존 24개 카테고리·기본 정책·운영 DB를 수정할 필요가 없다.

1. `/admin/ecommerce/categories`에서 **새 합성 root category**를 만든다. native POST `/api/modules/sirsoft-ecommerce/admin/categories`는 `src/routes/api.php:1041`, `CreateCategoryRequest.php:52`의 다국어 name, 고유 소문자 slug, `parent_id:null`, `is_active:true` 계약을 따른다. native CategoryController/CategoryService가 tree/path/sort를 처리한다. 관리자 tree는 active만 반환하므로 기존 inactive 행을 활성화하는 우회가 필요 없다.
2. 정책이 필요하면 `/admin/ecommerce/shipping-policies/create` → native POST `.../admin/shipping-policies` (`src/routes/api.php:672`)로 새 정책을 만든다. `is_default:false`, `is_active:true`, KR country, 기존 허용 `pickup`, `charge_policy:free`, fee 0, 외부 endpoint/api_config 없음을 사용한다. `ShippingPolicyService.php:98`은 새 정책이 default일 때만 기존 default를 해제하므로 false는 기존 기본 정책을 보존한다. 서버가 상점 기본 통화를 강제한다. 이는 무료 native 카탈로그 정책이며 여행 예약 확정/배송 완료/외부 연계로 표시하지 않는다.
3. **배송유형 참조 선행조건:** `StoreShippingPolicyRequest.php:50`은 ShippingTypeRepository의 실제 코드 목록을 허용값으로 쓴다. `module.php:getSeeders():1106`에는 ShippingTypeSeeder가 없지만 module DatabaseSeeder에는 있다. 따라서 native install만으로 pickup이 존재한다고 단정할 수 없다. 격리 새 lab의 배송유형 참조가 비었다면 native `ShippingTypeSeeder` FQCN을 지원 `db:seed --class` 경로로 실행하는 준비가 가능하다. 이 seeder는 sync뿐 아니라 `cleanupStale`도 하므로 기존 custom 배송유형이 있는 DB에는 무조건 실행하지 않는다. defaults의 pickup은 active이고 digital은 inactive다. 새 코드 `travel`을 발명하거나 직접 테이블에 넣지 않는다.
4. `/admin/ecommerce/products/create` → native POST `.../admin/products` (`src/routes/api.php:884`)에서 새 category/own policy를 선택하고 새 product_code, 다국어 name, native list/selling price, stock/status/tax, 최소 1개 native option을 저장한다. `StoreProductRequest.php:55`가 category 1..5, 가격·재고·상태·option 계약을 검증한다. 생성 후 반환 product/option ID를 기존 여행 관리자 metadata/출발일 화면에 연결한다. fixture API 준비가 아니라 이 실제 관리자 생성 동선을 390/1440에서 끝까지 재검증해야 BLOCKED가 닫힌다.

읽기 대상 source pins: ecommerce module `2d1bb15b214660c321c69ea4737d127ef71e18b3ae554d70ce2182d3db0c5505`; ShippingTypeSeeder `c4b1036e0cad5754f3125bd2c62032df957223f202af92ab93c59e4a3cf907dc`; CreateCategoryRequest `598060e08b75fbc1b1e4354219ccf19ecbf17da2d73df15be0c2e29fa6e03b96`; StoreShippingPolicyRequest `a29ea9396b388dbc1b5be03508fe7a34aefe1163015fb4d9a44c6cae15e18c03`; StoreProductRequest `581a492591419af5490df4ee5cc5d1c8e078acd6f758caf2146a9d447cb009f2`; ShippingPolicyService `7a856a32196cda960b1856541322ade846fea04ce135ef501b5675572f80cdb5`.

## 후속 empty-reference package repair source review

Lead 작성 `scripts/travel-lab/extensions.php`의 추가 조건을 비작성자가 읽었다. baseline SHA-256 `f438dc99c1da543c23d901e15376b73c1dbc1be6d5bd219f007ee48d6f9d1ad5` → reviewed SHA-256 `8ccbeb224f38f0835921a7aa55370dde69e9017aa1ed0e7a90ba83ef7d32f8e4`.

- native module/template 명령 성공 이후, 여행 sample/support provisioning 이전에만 `g7_ecommerce_shipping_types`의 COUNT=0이면 정확한 native ShippingTypeSeeder FQCN을 `travelLabLifecycleProcess`로 실행한다. nonzero는 custom code 존재 여부와 무관하게 전부 skip한다. 따라서 비어 있지 않은 참조에 seeder의 `cleanupStale`를 적용하는 확장은 없다. 실패 exit는 provisioning을 중단한다. 다른 ecommerce sample seeder나 코어/API 변경은 없다.
- 기존 `travelLabEnvironment(false)`의 명시적 lab marker/DB allowlist/read-write/local host/user/port/prefix/egress/cache guard와 scoped PDO, native lifecycle config cleanup을 재사용한다. 비동시 bootstrap 전제의 count→seed 조건이며 임의 concurrent 운영자 삽입을 막는 atomic lock을 새로 제공한다고 주장하지 않는다. nonempty이지만 pickup이 없는 custom 참조는 보존되고 정책 검증이 실패할 수 있으므로 fixture가 실제 허용 코드를 확인해야 한다.
- **Lead 실행 사실 정정:** 최신 lead 전달은 own isolated APP의 table이 NONEMPTY였으므로 native seed는 **SKIPPED/preserved**이며 APP 참조 변경은 없었다. exit0를 empty branch seed 성공으로 계산하지 않는다. 본 검토도 branch를 실행하지 않았다. empty branch actual fresh installation/새 UI category-policy-product 생성은 후속 고정 SHA 검증에서 NOT_RUN 상태를 닫아야 한다.
- source 범위에서 배송 참조 준비 누락에 대한 package 대응은 적합하다. helper 내부 준비만 추가돼 새 public core/extension API 또는 signature가 없으므로 이 변경만으로 버전 최소 제약 상향 대상은 없다.

새 P1/P2 인수 결함은 발견하지 않았다. 원본 browser BLOCKED/NOT_RUN, recovery-only 판정 및 최신 제품 변경 후 고정 SHA 재검증 요구는 그대로 열린다.
