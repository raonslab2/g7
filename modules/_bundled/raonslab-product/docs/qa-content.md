# RAON Agent Factory Q&A 콘텐츠

> RC 주의: 현재 command는 독립 QA에서 rollback cascade와 delete/restore 알림 P1 blocker가 확인됐다.
> 수정 후보와 독립 재승인 전에는 production에서 apply/rollback하지 않는다.

## 확인한 공식 계약

- 런타임 read-only 확인: board ID `3`, slug `questions`, 이름 `질문과 답변`, 활성 상태, 답변글 사용, 최대 depth `5`, `created_at DESC`, desktop `20`/mobile `15`, category `[]`, 기존 게시글 `0`.
- 작성자 후보: 새 계정을 만들지 않고 기존 활성 super 관리자 `RAON Hub 관리자`를 사용한다.
- 저장 계약: `sirsoft-board`의 `board_posts`와 `PostService`; 질문은 `parent_id=null`, 답변은 질문 ID를 `parent_id`로 갖는 depth 1 게시글이다.
- 공개 계약: 공식 board list/detail API와 통합 검색을 그대로 사용한다. 현재 board는 category가 없고 post tag 필드도 없으므로 둘을 임의 생성하지 않는다.

## Evidence pack과 구현 계획

`database/content/agent-factory-qa.v1.json`이 8개 질문·답변, 표시 순서, 답변 근거와 비공개 provenance 정의의 SSoT다. 공개 본문에는 개발용 표기를 넣지 않으며, 합성 운영 안내라는 사실은 각 행의 관리자 전용 `action_logs` provenance에 기록한다.

적용 명령은 기존 활성 Q&A board와 기존 활성 super 관리자만 사용한다. 질문·답변 쓰기는 공식
`PostService`를 사용하고 provenance key + scenario key + content role로 식별하므로 제목이 바뀌어도
같은 행을 갱신하며, 발견된 중복 provenance는 쓰기 전에 중단한다. 다만 현 RC는 rollback/reapply의
알림을 완전히 억제하지 못하므로 “알림 미발송”을 충족한 것으로 판정하지 않는다.

## 적용과 식별

아래 명령은 사용 형태를 기록한 것이며 현재 RC에서는 실행 금지다. Q&A fix와 독립 QA 승인 뒤
통합 담당이 최신 main과 공식 module lifecycle을 반영한 G7 전용 runtime에서만 수행한다.

```bash
/usr/bin/php8.3 artisan raonslab-product:qa-content --dry-run
/usr/bin/php8.3 artisan raonslab-product:qa-content --force
/usr/bin/php8.3 artisan raonslab-product:qa-content --force
```

첫 실제 적용은 `created=16 duplicates=0`, 두 번째 적용은 `created=0 duplicates=0`이어야 한다. 대상 행은 `action_logs`의 `provenance_key=raonslab.agent_factory.qa.wave1_4`로 식별한다. 특정 기존 super 관리자 UUID를 고정하려면 `--author=<uuid>`를 사용한다.

## 롤백

```bash
/usr/bin/php8.3 artisan raonslab-product:qa-content --rollback --dry-run
/usr/bin/php8.3 artisan raonslab-product:qa-content --rollback --force
```

현 구현은 해당 provenance를 가진 질문 원글을 공식 삭제 흐름으로 처리하지만 `cascade_replies=true`가
그 root 아래 provenance 없는 사용자 답글·댓글·첨부까지 soft-delete할 수 있다. 따라서 다른 게시글
미영향 계약을 아직 충족하지 않으며, rollback은 fix와 독립 QA 전 production에서 실행하지 않는다.

## Review gate

- fixture 8건과 근거 경로, 금지 문구 정적 검사
- dry-run 무변경, 첫 적용 16행, 두 번째 적용 신규 0/중복 0
- 질문 8 + 답변 8, published, category null, 답변 parent/depth 계약
- 공개 목록·상세·통합 검색과 mobile page size 15
- provenance 한정 rollback과 재적용

Production build, broad/full regression과 360/390/412/desktop 브라우저 smoke는 Provider 단계에서 수행하지 않는다. 통합 담당이 통합 뒤 각 1회 수행한다.
