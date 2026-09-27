# GNUBOARD7 제품 아키텍처

## 경계

```text
Browser
  -> dedicated nginx 127.0.0.1:18770
  -> dedicated PHP 8.3 FPM socket
  -> GNUBOARD7 Laravel/React
       -> MySQL g7_product (g7_product user)
       -> database session/cache/queue
       -> G7-owned storage/logs/backups

G7 AI UI
  -> G7 AI Application Service
  -> G7 AI_GCS V2 API Adapter
  -> AI_GCS V2 Request API
  -> AgentOpt V2 canonical Request/provider lane
```

G7은 AI_GCS/AgentOpt DB, SQLite, filesystem, provider session을 읽지 않는다. AI 상태와 이벤트의 source of truth는 AI_GCS V2 API 응답과 persisted event cursor다.

## 배포 격리

| 자원 | G7 전용 값 |
|---|---|
| repository | `/home/mrdev/git/g7` |
| origin/upstream | raonslab2 fork / official gnuboard |
| HTTP | loopback `127.0.0.1:18770` |
| FPM socket | `/run/g7-product-fpm/php-fpm.sock` |
| DB/database user | `g7_product` / `g7_product@127.0.0.1` |
| env | repository-local ignored `.env` |
| session/cache/queue | G7 DB tables와 고유 cookie/cache prefix |
| logs | `storage/logs`, 전용 nginx/FPM/application 로그 |
| backup | `/var/backups/g7-product` |
| services | `g7-product-*` namespace |

전역 nginx 설정, 기존 reverse tunnel, production 포트, 다른 서비스 unit, 시스템 기본 PHP를 변경하지 않는다. 새 PHP 8.3의 배포판 기본 FPM unit도 비활성화하고 G7 전용 pool만 실행한다.

## 코드 계층

1. G7 core: 공식 stable과 동기화하는 영역. 직접 변경을 최소화한다.
2. 공식 bundled extensions: upstream source는 `_bundled`, runtime은 lifecycle로 생성한 활성 디렉터리다.
3. 제품 extension: 제품 브랜드/UX와 AI 기능을 공식 Module/Plugin/Template/Hook에 둔다.
4. deployment layer: `deploy/`, `.env.product.example`, `scripts/g7-backup.sh`.
5. evidence/docs: `docs/g7`과 ignored runtime evidence 원본.

제품 기능은 Module → Plugin → Template → Hook/Event → Adapter/config 순으로 공식 확장점을 우선한다. 불가피한 core patch는 별도 inventory, upstream 충돌 분석, rollback을 남기기 전에는 허용하지 않는다.

## 주요 런타임 흐름

### Native web

Nginx는 public entry만 노출하고 임의 PHP 실행과 dotfile 접근을 차단한다. PHP-FPM은 `mrdev` pool로 application을 실행한다. DB session은 브라우저 세션을 재시작 뒤에도 유지하고, database queue worker가 notification/hook job을 처리한다. scheduler timer는 매분 공식 schedule을 실행한다.

### Extension lifecycle

`_bundled` 또는 정식 package → `_pending` 검증 → official lifecycle install/update → active directory → migration/permission/menu/layout sync의 흐름을 따른다. 활성 복사본은 직접 수정하지 않는다.

### AI adapter

Adapter는 다음 포트만 제공한다.

- submit canonical request
- request status/detail
- cursor 기반 event/SSE와 reconnect
- terminal result
- same-request follow-up, contract에 존재할 때 resume
- provider availability read-only
- 공식 지원 범위의 attachment

G7 사용자와 AI request owner는 명시적 mapping으로 저장한다. service credential은 server-side env/secret store에만 두고 browser bundle이나 DB evidence에 포함하지 않는다. G7은 fake percent/ETA를 만들지 않는다.

## 데이터와 복구

- migration은 공식 migration과 extension migration만 사용한다.
- backup은 transaction-consistent DB dump, `.env`, `storage/app`, source SHA, migration status를 담는다.
- archive와 payload 모두 SHA-256을 기록한다.
- 복구는 새 격리 DB에 dump를 복원하고 persistent files를 되돌린 뒤 동일 SHA의 source로 기동하여 검증한다.
- core/extension update 전에는 공식 backup/rollback과 운영 backup을 함께 사용한다.

## 보안 원칙

- credential commit 및 frontend secret 주입 금지
- G7 DB 계정은 G7 DB에만 권한 부여
- loopback bind가 기본이며 외부 공개는 별도 승인된 reverse proxy/TLS 뒤에서만 수행
- user/admin/API 권한은 G7 RBAC middleware로 재검증
- AI project 권한은 Adapter에서 G7 identity mapping과 API authorization을 모두 검증
- 사용자 화면에는 provider shell/internal secret/raw trace를 기본 노출하지 않음
