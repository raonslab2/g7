# W04 ActionDispatcher live global 독립 검토

**PASS_BOUNDED_SOURCE.** 비작성자가 두 evaluator 읽기 변경과 새 실제-state 회귀 테스트를 읽었다. public state getter 계약과 맞으며 새 P1/P2 source 결함은 발견하지 않았다. 제품·DB/env/service/Git/build 변경 및 테스트/runtime 실행은 없었다. 이 보고서만 작성했으며 공식 Validation·전체 인증·브라우저 PASS가 아니다.

## 고정 입력과 변경

parent HEAD `e4aaa96f0845c25b65fa83b924004f0a68331461`의 ActionDispatcher baseline SHA-256 **`bfa67b164cb19a245cde7b904630e8a6689ec4ad3f30b6b55cd7f17492d48e8b`** → reviewed working file **`bb57838bde3849631f31c6b0e0947bbef84bb885548749f5fa7ee1008b524f68`**.

`resources/js/core/template-engine/ActionDispatcher.ts:7152,7183`의 single/복합 expression 분기에서 각각 `G7Core?.state?.get()?._global`을 **`G7Core?.state?.get()`**로 바꿨다. 나머지 parser/cache/computed/local/row/state writer/auth 처리와 public signature는 변경하지 않았다.

새 테스트 `resources/js/core/template-engine/__tests__/ActionDispatcher.liveGlobalExpressions.test.ts` SHA-256 **`e975330094fa2878239ef752e5c9f432532068016584ce71fdb8476ac0411353`**.

| 실제 읽은 native 계약 | SHA-256 |
| --- | --- |
| `resources/js/core/template-engine/G7CoreGlobals.ts` | `b6b67e14b9de7baf46fe2abf1ad8aba1c90e2598fb2ea4b6c23f6ba4b6548616` |
| `resources/js/core/TemplateApp.ts` | `bc578f3fd5437b3634d630057a105a1696d9648130496f1737a5ee5b46599ebb` |
| `docs/frontend/g7core-api.md` | `e1e32464159767d3d758ef1e0954bdf17d063907c3d789ac5b65066aaccac5ab` |
| `docs/extension/changelog-rules.md` | `e3a76e7f2985f49b9540b7af25de05ac995a9f1276baa702d38b5cf419788def` |

## native 의미와 경계

- `initializeG7CoreGlobals`는 `initStateAPI`를 호출한다. 실제 getter는 `window.__templateApp.getGlobalState()` 또는 `{}`를 반환한다. 반환 내용 자체가 global이며 `_global` wrapper가 아니다. 문서도 `Record<string,any>` 반환을 선언한다.
- `TemplateApp.ts:3399`는 global의 shallow snapshot을 반환하고 setGlobalState는 새 top-level 객체를 저장한다. 새 테스트는 captured `_global` 뒤 새로운 departure 객체를 저장하므로 **진짜 stale capture**가 남는다. 동일 객체를 공유해 우연히 PASS하는 fixture가 아니다.
- 두 분기 모두 native latest record를 `_global`에만 주입한다. spread된 기존 context의 `_local`/row와 기존 computed 처리는 보존된다. false는 object 안의 값이므로 latest record truthiness guard에 의해 누락되지 않는다. 유효 native empty `{}`도 truthy여서 삭제된 global key를 captured context로 복원하지 않는다.
- API가 없는 경우 effective context를 바꾸지 않는 기존 fallback을 유지한다. missing dataContext 조기 반환도 기존 동작이다. 고장난 getter/유효 계약 밖 scalar를 새로 정상화하는 범위는 아니다.
- 전역 값 읽기만 바뀌며 native authentication·Bearer resolver·auth_mode·permission·server price 검증을 우회하는 코드가 없다. 그러나 `_global`를 참조하는 action body는 의도대로 최신 사용자 편집 값으로 바뀌므로 fixed browser/admin payload 재검증은 필요하다.

## 테스트의 실효성과 한계

새 파일에는 **8개 unit case**가 있다: latest false, 복합 interpolation+row, number/object type, 삭제된 key, 같은 sequence의 setState→API, API unavailable single/complex 두 경우, local/row와 live global 공존.

실제 TemplateApp 저장소, native initializeG7CoreGlobals 및 ActionDispatcher createHandler/sequence/apiCall을 사용하고 `render:false`로 render 완료를 기다리지 않는다. 최종 fetch body를 확인하므로 getter 코드 문자열이나 mock attribute capture를 그대로 검사하는 mirror test가 아니다.

`ApiClient`와 fetch 응답은 mock이고 auth_mode=none이다. 따라서 이 파일을 실제 Sanctum/auth/body 서버 계약 검증으로 표현하지 않는다. local/row 케이스는 해당 context 보존을 확인하지만 모든 local/computed/event/auth cross product를 증명하지 않는다. 기존 targeted regression 결과는 author 실행으로 분리하며 본 reviewer는 11-file suite를 반복하지 않았다.

### 작성자 실행 증거 인수

작성자 `docs/symphony/W04_ADMIN_INTERACTION_DIAGNOSIS.md` SHA-256 `4635fc0fa2e880120656a6cde17aa5d0b02887907f0dad7ac6ad2102cbca882a` 및 실제 JSON을 읽고 수를 대조했다. 아래 결과는 **작성자 실행**이며 reviewer 재실행·독립 runtime PASS가 아니다.

| 실제 JSON (`tests/W04_ADMIN_DIAGNOSTIC/`) | 확인한 결과 | SHA-256 |
| --- | --- | --- |
| `core-fail-first.json` | 1 file /8 tests:2 PASS,6 FAIL, success=false | `36736600ef80b095092757883697aca26de1b9d42733f4f3315cab10e7076bf2` |
| `core-focused-pass.json` | 1 file /8 PASS,0 FAIL, success=true | `db93f362e0cdcfe152c45cf6b66d802e3c0069dd68b16ce12c99ba87467fbc6b` |
| `core-regression.json` | 9 files /507 tests:505 PASS,2 FAIL, success=false | `a0b6dc1325c974ba1e466ff9ec9eaaaa8e850f4ab6ee10480eb2a5ae78c9bd8c` |
| `core-regression-final.json` | 11 files /546 PASS,0 FAIL,0 pending, success=true | `d25d0aedba741bd0ca9eab8a05ed92cb342c33bd66f089114fe54ac3a854e577` |

Final JSON의 실제 `testResults`는 작성자 명령의 11개 파일과 일치하며, 각 `assertionResults`를 합친 **546개가 전부 passed**다. `numTotalTestSuites=149`는 nested describe 집계이며 파일 수로 보고하지 않는다. Final546에는 focused8이 포함되므로 별도 unique test로 합산하지 않는다. 초기507의 두 실패는 보존한다. native getter shape 실패 및 그 실패 뒤 cleanup 누락에 따른 cascade라는 원인 설명은 작성자의 진단이다.

기존 `ActionDispatcher.test.ts:681` diff도 읽었다: getter fixture의 `{_global:{shopBase:'/store'}}`를 native `{shopBase:'/store'}`로 바꾸고 주석만 고쳤다. 기존 navigation 기대 assertion은 변경하지 않았다. reviewed SHA-256 `9a04112ade42c939518183c756ec90979210510cc0249c7573bb531882f47730`. 새 test와 production source의 위 pinned hashes도 인수 후 다시 같음을 확인했다.

Lead가 별도로 완료했다고 전달한 production build의 실제 `public/build/core/template-engine.min.js` bytes SHA-256은 `b8cf27fab58e108b1509c379a5f4d0860a21a90bc591bb34b48a94d6e56cb76d`와 일치했다. reviewer는 build command/served HTTP/설치본/runtime 재실행을 하지 않았다. 이 byte 확인을 독립 배포·runtime PASS로 확대하지 않는다.

최초 인수 시점에는 manifest가 없어 입력 미제공으로 기록했으나, 작성자의 freeze 통지 후 `tests/W04_ADMIN_DIAGNOSTIC/manifest.json` SHA-256 `248ea402dde6936cfff5a8d44ffe2c8f582747998bec8e16f6c149f4a05f7c36`을 추가 인수했다. **4 source/report pins +10 public artifact hashes/byte counts가 전부 일치**했고 tracked 두 파일 diff hash `b38b0a0894a4af6859c6603e9524b3ffe8c31bf906680bda79021f3a30f7fcc0`도 직접 계산해 일치했다. 새 untracked regression test는 별도 source_files binding으로 포함된다. 미제공 관찰은 이 후속 입력으로 해소됐으며, manifest는 working-tree binding이므로 fixed Git SHA/served runtime proof를 대신하지 않는다. Manifest의 fixture cleanup·토큰 revoke·baseline 브라우저 주장은 작성자 진술이고 이 source 검토가 독립 재실행하지 않았다.

## 버전·호환성 권고

`docs/extension/changelog-rules.md:235–271` 및 루트 guide는 public extension 표면/새 API/minor·major core 변경을 실제 소비자 최소 버전 재검토 trigger로 둔다. 이번 변경은 private expression evaluator가 **기존 public G7Core.state 반환 계약을 올바르게 소비하도록 복구**하는 내부 bugfix다. getter signature, Abstract/Hook/Contract, module route/상태/인증 public API는 그대로다.

따라서 모든 번들/무관한 RAON 사업 모듈 manifest 최소 버전을 일괄 상향할 근거는 없다. lead가 현재 unreleased core patch/engine 내부 이력에 Fixed 사유와 재현·검증 SHA를 기록하고 official core production build를 고정해 전달하는 것이 적합하다. `template-engine/CHANGELOG.md`는 engine 내부 버전이 G7 release와 독립임을 명시한다. 실제 patch 번호/공통 metadata/build 결정은 lead가 소유한다. Travel 기능이 수정 core를 배포 전제조건으로 요구한다고 선언할 경우 그 영향 범위만 명시하며 existing business source를 임의 변경하지 않는다.

이 source review로 runtime/통합/CI gate를 닫지 않는다. 작성자 fail-first/after suite와 새 bundle/fixed SHA에서 실제 관리자 edit→submit 및 고객/auth 회귀를 별도 인수해야 한다.
