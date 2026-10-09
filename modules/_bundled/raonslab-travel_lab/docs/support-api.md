# Support API 레퍼런스

> **소유**: module `raonslab-travel_lab` · 설계: [support.md](support.md)
> 응답 예시는 `tests/Feature` 와 같은 부트스트랩으로 실제 호출해 관측한 값이다 (locale `en`, 날짜·이름은 테스트 팩토리 값). 모듈 등록 통합 후 `php artisan api:docgen` 으로 `docs/api/` 에 재생성할 수 있다.

## TL;DR

```text
1. prefix: /api/modules/raonslab-travel_lab/support · 라우트명 prefix: api.modules.raonslab-travel_lab.support.
2. 공지/FAQ: 비로그인 열람 (optional.sanctum). 1:1 문의: Authorization: Bearer {sanctum token} 필수
3. 성공 봉투: {"success": true, "message": "...", "data": ...}  · 도메인 오류: {"success": false, "message": "..."}
4. 401(미인증)·422(검증) 은 코어 예외 처리기의 봉투를 그대로 쓴다 (success 키 없음, 아래 참조)
5. 타인 문의는 403 이 아니라 404 — 존재 여부를 숨긴다
```

## 공통 봉투

| 상황 | 상태 | 본문 |
| --- | --- | --- |
| 성공 | 200/201 | `{"success": true, "message": <번역문>, "data": <object>}` (ResponseHelper::success) |
| 도메인 오류 | 404/503 | `{"success": false, "message": <번역문>}` (ResponseHelper::error, `errors` 없음) |
| 미인증 | 401 | `{"message": "Authentication required."}` (코어 인증 예외 처리) |
| 검증 실패 | 422 | `{"message": "...", "errors": {"field": ["..."]}}` (FormRequest 기본) |
| 과다 요청 | 429 | 코어 throttle 응답 |

목록 `data` 구조: `{"data": [<list item>], "meta": {"current_page", "last_page", "per_page", "total"}}`

### 목록 항목 필드

| 필드 | 타입 | 설명 |
| --- | --- | --- |
| id | integer | 게시글 ID (`board_posts.id`) |
| channel | string | `notices` \| `faqs` \| `questions` |
| title | string | 제목 |
| author_name | string | 작성자 표시명 (공지/FAQ 는 `Travel Lab`) |
| is_notice | boolean | 상단 고정 공지 여부 (목록 정렬 1순위) |
| is_secret | boolean | 비밀글 여부 (문의는 항상 true) |
| is_mine | boolean | 문의를 요청 사용자가 작성했는지 (공지/FAQ 는 false) |
| answers_count | integer | 게시판 댓글 수 집계값 (상세에서는 실제 반환 답변 수) |
| created_at / updated_at | string | 사용자 타임존 `Y-m-d H:i:s` |

상세는 위 필드에 `content`(string, 평문)를 더한다. 목록은 본문을 싣지 않는다.

---

### GET /api/modules/raonslab-travel_lab/support/notices

- **라우트명**: `api.modules.raonslab-travel_lab.support.notices.index`
- **컨트롤러**: `SupportController@notices` · **미들웨어**: `api`, `optional.sanctum`, `throttle:600,1`

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| page | query | integer | 아니오 | 1–10000 | 페이지 |
| per_page | query | integer | 아니오 | 1–50 (기본 15) | 페이지당 건수 |

```http
GET /api/modules/raonslab-travel_lab/support/notices?per_page=2 HTTP/1.1
Accept: application/json
```

```json
{
  "success": true,
  "message": "Notices loaded.",
  "data": {
    "data": [
      {"id": 1, "channel": "notices", "title": "[LAB] 여행 연구소 테스트 운영 안내", "author_name": "Travel Lab",
       "is_notice": true, "is_secret": false, "is_mine": false, "answers_count": 0,
       "created_at": "2026-10-09 17:55:05", "updated_at": "2026-10-09 17:55:05"},
      {"id": 3, "channel": "notices", "title": "[LAB] 테스트 데이터 정리 일정 안내", "author_name": "Travel Lab",
       "is_notice": false, "is_secret": false, "is_mine": false, "answers_count": 0,
       "created_at": "2026-10-09 17:55:05", "updated_at": "2026-10-09 17:55:05"}
    ],
    "meta": {"current_page": 1, "last_page": 2, "per_page": 2, "total": 3}
  }
}
```

게시판 미준비(프로비저닝 전·설정 불일치) 시 `503 {"success": false, "message": "The support boards are not ready yet."}`

### GET /api/modules/raonslab-travel_lab/support/notices/{id}

- **라우트명**: `api.modules.raonslab-travel_lab.support.notices.show` · `{id}` 숫자만
- 다른 채널의 글·비밀글·삭제/블라인드 글은 `404 {"success": false, "message": "Post not found."}`

```json
{
  "success": true,
  "message": "Post loaded.",
  "data": {"id": 1, "channel": "notices", "title": "[LAB] 여행 연구소 테스트 운영 안내", "author_name": "Travel Lab",
           "is_notice": true, "is_secret": false, "is_mine": false, "answers_count": 0,
           "created_at": "2026-10-09 17:55:05", "updated_at": "2026-10-09 17:55:05",
           "content": "여행 연구소는 여행 상품 화면과 문의 흐름을 검증하기 위한 테스트 공간입니다.\n표시되는 일정·인원·금액은 모두 예시이며 실제 예약이나 결제로 이어지지 않습니다."}
}
```

### GET /api/modules/raonslab-travel_lab/support/faqs · GET …/support/faqs/{id}

- **라우트명**: `api.modules.raonslab-travel_lab.support.faqs.index` / `.faqs.show`
- 파라미터·응답은 공지와 같고 `channel` 이 `faqs`, 메시지가 `FAQs loaded.` 다.

---

### GET /api/modules/raonslab-travel_lab/support/questions

- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.index`
- **인증/권한**: `auth:sanctum` (Bearer). `raonslab-travel_lab.support.read` 와 문의 게시판 네이티브 `admin.posts.read`·`admin.posts.read-secret` 을 모두 스코프 제한 없이 가진 관리자만 전체, 그 외(여행 권한만 있는 전역 역할 포함)는 본인 글만
- 파라미터: `page`, `per_page` (공지와 동일)

```http
GET /api/modules/raonslab-travel_lab/support/questions HTTP/1.1
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

```json
{
  "success": true,
  "message": "Questions loaded.",
  "data": {
    "data": [
      {"id": 8, "channel": "questions", "title": "[합성] 11월 출발 인원 문의", "author_name": "류강은",
       "is_notice": false, "is_secret": true, "is_mine": true, "answers_count": 0,
       "created_at": "2026-10-09 17:55:06", "updated_at": "2026-10-09 17:55:06"}
    ],
    "meta": {"current_page": 1, "last_page": 1, "per_page": 15, "total": 1}
  }
}
```

### POST /api/modules/raonslab-travel_lab/support/questions

- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.store`
- **인증/권한**: `auth:sanctum`, `throttle:120,1` + `throttle:10,1`
- 비밀글·작성자·첨부 없음은 서버가 강제한다. 본문의 `is_secret`, `user_id`, `attachment_ids` 등은 무시된다. 알림·메일·문자는 발송되지 않는다.

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| title | body | string | 예 | 2–200자 | 제목 |
| content | body | string | 예 | 2–5000자 | 평문 본문 |

```http
POST /api/modules/raonslab-travel_lab/support/questions HTTP/1.1
Accept: application/json
Content-Type: application/json
Authorization: Bearer {YOUR_TOKEN}

{"title": "[합성] 11월 출발 인원 문의", "content": "합성 테스트 문의입니다. 실제 예약이 아닙니다."}
```

`201`

```json
{
  "success": true,
  "message": "Your question was submitted. (LAB test intake; no notifications are sent.)",
  "data": {"id": 8, "channel": "questions", "title": "[합성] 11월 출발 인원 문의", "author_name": "류강은",
           "is_notice": false, "is_secret": true, "is_mine": true, "answers_count": 0,
           "created_at": "2026-10-09 17:55:06", "updated_at": "2026-10-09 17:55:06",
           "content": "합성 테스트 문의입니다. 실제 예약이 아닙니다."}
}
```

`422`

```json
{"message": "The title field is required. (and 1 more error)",
 "errors": {"title": ["The title field is required."], "content": ["The content field must be at least 2 characters."]}}
```

### GET /api/modules/raonslab-travel_lab/support/questions/{id}

- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.show`
- **인증/권한**: `auth:sanctum`. 작성자 또는 `support.read` + 문의 게시판 네이티브 열람 권한(소유자 스코프 판정 포함)을 모두 가진 관리자. 그 외·존재하지 않음·다른 채널 글은 `404 {"success": false, "message": "Question not found."}`
- `answers[]`: 게시판 관리자 화면에서 단 댓글(게시 상태, 작성순, 최대 50건). 답변자 계정 정보는 싣지 않는다.

| 필드 | 타입 | 설명 |
| --- | --- | --- |
| answers[].id | integer | 댓글 ID |
| answers[].is_author | boolean | 문의 작성자 본인의 추가 댓글이면 true, 운영자 답변이면 false |
| answers[].content | string | 답변 본문 |
| answers[].created_at | string\|null | 작성 일시 |
| answers_count | integer | 반환된 답변 수 |

```json
{
  "success": true,
  "message": "Question loaded.",
  "data": {"id": 8, "channel": "questions", "title": "[합성] 11월 출발 인원 문의", "author_name": "류강은",
           "is_notice": false, "is_secret": true, "is_mine": true, "answers_count": 1,
           "created_at": "2026-10-09 17:55:06", "updated_at": "2026-10-09 17:55:06",
           "content": "합성 테스트 문의입니다. 실제 예약이 아닙니다.",
           "answers": [{"id": 1, "is_author": false, "content": "합성 관리자 답변입니다.", "created_at": "2026-10-09 17:55:06"}]}
}
```

### PATCH /api/modules/raonslab-travel_lab/support/questions/{id}

- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.update`
- **인증/권한**: `auth:sanctum`. 작성자 또는 `support.read`·`support.update` + 문의 게시판 네이티브 `admin.posts.read`·`admin.posts.read-secret`·`admin.posts.write` 를 모두 가진 관리자. 그 외는 404, 원문 불변. 수정은 게시판 `PostService::updatePost()` 경유(활동 로그 `post.update` 기록, 알림 없음)
- 본문: `title`(2–200) 과 `content`(2–5000) 중 하나 이상. 응답 형태는 POST 와 같고 상태 200, 메시지 `Your question was updated.`

## 상태 코드 요약

| 코드 | 언제 |
| --- | --- |
| 200 / 201 | 성공 |
| 401 | 문의 엔드포인트에 Bearer 토큰 없음 |
| 404 | 채널 밖·존재하지 않음·타인 문의 |
| 422 | 입력 검증 실패 (`per_page>50` 포함) |
| 429 | throttle 초과 |
| 503 | 고객지원 게시판 미준비 또는 안전 기준 불일치(게시판 권한·댓글·답글 설정 포함), 문의 채널은 외부 검색 드라이버 구성에서도 503 |
