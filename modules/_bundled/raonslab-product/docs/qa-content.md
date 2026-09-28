# RAON Agent Factory Q&A 콘텐츠

> RC 상태: rollback 범위·알림 억제·topology·동시 실행 보강 patch와 focused 검증은 PASS했다.
> fixed candidate 독립 QA와 delivery preflight가 승인되기 전에는 production에서 apply/rollback하지 않는다.

## 확인한 공식 계약

- 런타임 read-only 확인: board ID `3`, slug `questions`, 이름 `질문과 답변`, 활성 상태, 답변글 사용, 최대 depth `5`, `created_at DESC`, desktop `20`/mobile `15`, category `[]`, 기존 게시글 `0`.
- 작성자 후보: 새 계정을 만들지 않고 기존 활성 super 관리자 `RAON Hub 관리자`를 사용한다.
- 저장 계약: `sirsoft-board`의 `board_posts`와 `PostService`; 질문은 `parent_id=null`, 답변은 질문 ID를 `parent_id`로 갖는 depth 1 게시글이다.
- 공개 계약: 공식 board list/detail API와 통합 검색을 그대로 사용한다. 현재 board는 category가 없고 post tag 필드도 없으므로 둘을 임의 생성하지 않는다.

## Evidence pack과 구현 계획

`database/content/agent-factory-qa.v1.json`이 8개 질문·답변, 표시 순서, 답변 근거와 비공개 provenance 정의의 SSoT다. 모든 질문 제목은 공개 화면에서 `[자주 묻는 질문]`으로 표시해 실제 고객 문의로 오인되지 않게 한다. 공개 본문에는 개발용 표기를 넣지 않으며, 합성 운영 안내라는 사실은 각 행의 관리자 전용 `action_logs` provenance에도 기록한다.

적용 명령은 기존 활성 Q&A board와 기존 활성 super 관리자만 사용한다. 질문·답변은 공식 `PostService`로 만들고, 제품 모듈의 provenance 한정 알림 필터가 큐 실행 시에도 생성·삭제·복원 알림을 중단한다. 이 필터는 다른 게시글의 알림에는 관여하지 않는다. provenance key + scenario key + content role로 식별하므로 제목이 바뀌어도 같은 행을 갱신한다. 중복 provenance, 잘못된 marker, 질문 `parent_id/depth`, 답변의 부모/depth가 발견되면 자동 보정하지 않고 쓰기 전에 중단한다.

같은 provenance의 변경 명령은 cache atomic lock으로 직렬화한다. 잠금을 얻지 못한 동시 apply/rollback은 어떤 행도 바꾸지 않고 실패한다.

## 적용과 식별

아래 명령은 사용 형태를 기록한 것이며 현재 RC에서는 실행 금지다. fixed candidate 독립 QA와 preflight 승인 뒤
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

롤백은 트랜잭션 안에서 먼저 provenance 질문과 답변의 실제 답글 트리, 댓글, 첨부와 신고 interaction을 공식 board 모델 계약으로 검사한다. provenance가 소유하지 않은 답글이나 댓글·첨부·신고가 하나라도 있으면 전체 롤백을 중단하며 어떤 행도 삭제하지 않는다. 안전할 때만 provenance 답변부터 질문 순서로 각각 공식 `PostService`를 호출하고 `cascade_replies=false`를 전달한다. 다른 게시판 데이터는 제목이 같아도 건드리지 않는다. 다시 적용하면 같은 provenance 행을 복원하므로 새 중복 행을 만들지 않는다.

## Review gate

- fixture 8건과 근거 경로, 금지 문구 정적 검사
- dry-run 무변경, 첫 적용 16행, 두 번째 적용 신규 0/중복 0
- 질문 8 + 답변 8, published, category null, 답변 parent/depth 계약
- topology 변조와 동시 provenance lock 충돌의 fail-closed 처리
- 외부 답글·댓글·첨부가 있을 때 rollback 0 mutation
- provenance 게시글 delete/restore 알림 0, 일반 게시글 알림 필터 불변
- 공개 목록·상세·통합 검색과 mobile page size 15
- 답변부터 원글까지 provenance 한정 rollback과 재적용

이 PHP/JSON-only fix에서는 f755의 production asset을 그대로 재사용하며 build를 반복하지 않았다. Q&A focused 11 tests/220 assertions, consultation 계약 15 tests/102 assertions와 product Vitest 92/92는 final combined source에서 각각 정확히 1회 PASS했다. broad/core/full/browser/runtime 검증은 이 RC 준비 범위에서 수행하지 않았으며 fixed candidate 독립 QA와 delivery preflight 뒤 별도 gate로 남는다.
