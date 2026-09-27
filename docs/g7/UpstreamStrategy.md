# GNUBOARD7 upstream 운영 전략

## Remote 정책

| remote | URL | 역할 |
|---|---|---|
| `upstream` | `https://github.com/gnuboard/g7.git` | 공식 source of truth, fetch only로 취급 |
| `origin` | `https://github.com/raonslab2/g7.git` | 제품 fork, 제품 브랜치와 tag push 대상 |

공식 tag를 재작성하지 않는다. stable tag는 반드시 dereference한 commit SHA와 함께 기록한다.

## Branch 정책

- `main`: 최신 검증 stable을 바탕으로 한 제품 통합 브랜치.
- `upstream-sync/<version>`: 새 stable을 fetch하여 diff/test하는 임시 브랜치.
- `feature/<scope>`: 제품 extension, adapter, 배포 기능 단위 브랜치.
- `release/<version>-product.<n>`: broad regression과 rollback 검증을 위한 release candidate.
- 제품 tag는 공식 tag와 충돌하지 않는 `product-<upstream>-<n>` 형식을 사용한다.

현재 fork는 비어 있던 origin에 공식 `7.0.11` commit을 baseline으로 먼저 push한 뒤 제품 변경을 쌓았다.

## Stable update 절차

1. `git fetch --tags upstream` 후 공식 release/tag와 서명을 확인한다.
2. 현재 stable과 새 stable의 commit/file/migration/extension manifest diff를 생성한다.
3. `upstream-sync/<version>`에서 새 tag를 기준으로 제품 commit을 재적용한다.
4. core modification inventory가 0인지, 있다면 각 patch 충돌과 대체 extension point를 검토한다.
5. 새 isolated DB 복제본에서 dependency install, migration dry review, build, focused tests를 수행한다.
6. native browser E2E와 AI adapter contract test를 실행한다.
7. 운영 backup 생성과 restore rehearsal 후 release branch에 병합한다.
8. G7 전용 서비스만 순차 재기동하고 health/session/data를 확인한다.
9. 검증 증거와 source SHA를 Agent.Tools GNUBOARD7 pack에 반영한다.

공식 `main`의 미출시 commit은 참고만 하고 stable 제품 브랜치에 섞지 않는다.

## 충돌 정책

- extension/deployment/docs 충돌은 제품 계층에서 해소한다.
- core 충돌은 upstream 동작을 우선하고 공식 hook/module/plugin/template로 이동 가능한지 먼저 검토한다.
- 자동 병합이 되어도 auth, permission, migration, upload, update, layout engine 영역은 수동 review를 필수로 한다.
- 제품 기능 유지를 위해 광범위한 core rewrite가 필요하면 update를 중지하고 설계 결정을 보고한다.

## Core patch 정책

core patch마다 다음을 `docs/g7`에 기록한다.

- extension으로 해결할 수 없는 이유
- 수정 파일/line과 최소 patch 범위
- 관련 upstream issue/release
- 다음 stable 예상 충돌
- focused/broad regression 증거
- patch 제거와 rollback 절차

감사 시점 제품 의미의 core patch는 없다. 제품 변경은 RAON bundled module, deployment, scripts, environment template, 문서에 한정됐다. `7.0.11`과 현재 `upstream/main`이 동일한 상태에서 merge-tree simulation은 conflict marker 0이었다.

## Rollback

1. 배포 직전 source SHA와 `/var/backups/g7-product` archive를 고정한다.
2. G7 전용 web/queue/FPM만 중지한다.
3. 이전 제품 tag/SHA를 checkout한 별도 release path를 준비한다.
4. archive의 외부/내부 SHA-256을 검증한다.
5. 필요 시 새 DB에 dump를 복원하고 persistent files를 복구한다.
6. migration status, HTTP health, admin/user login, attachment와 AI request 조회를 검증한다.
7. 전용 service path만 이전 release로 전환한다.

기존 AI_GCS V2, AgentOpt V2, 다른 Agent.Tools project binding은 rollback 대상이나 변경 대상이 아니다.
