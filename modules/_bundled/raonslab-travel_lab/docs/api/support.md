# Support API 레퍼런스

> **소유**: module `raonslab-travel_lab` · **생성**: `php artisan api:docgen` (native 골격·부분 실측 + 사람이 작성한 소스 계약). @generated 블록은 재생성 시 갱신되며, 사람이 작성한 설명은 보존됩니다.

---

## 계약과 증거 구분

- 모듈 전체 native `api:docgen` 생성 대상은 31개 라우트이며, 자동 실측은 11건이며 트랜잭션 후 rollback한 POST도 포함한다. 나머지 20건의 기본 프로브 생략은 전체 기능 성공 판정이 아니다.
- `@probed` 표시는 lead가 수집한 실제 응답으로, 기본 자동 프로브와 별도 쓰기 응답 캡처를 포함한다. 출력의 `...`는 생성기의 축약 표시이며 실제 API 필드가 아니다.
- 실측하지 못한 응답의 표·예시는 Controllers/FormRequests/Resources와 계약 테스트에서 작성한 **합성 계약 예시**로 표시했다. 전체 31개 라이브 PASS나 독립 Validation을 주장하지 않는다.
- 재생성은 사람이 채운 설명·예시를 보존하는 native 경로를 사용한다. 설치된 API와 고정 검증 SHA의 일치·재검증은 별도 증거로 판단한다.

---


### GET /api/modules/raonslab-travel_lab/support/faqs
<!-- @generated:start:api.modules.raonslab-travel_lab.support.faqs.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.faqs.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@faqs`
- **인증/권한**: `optional.sanctum` (선택적 인증: 회원/비회원 모두 접근)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| page | query | integer | 아니오 | min 1, max 10000 | 조회할 페이지 번호 (1부터 시작) |
| per_page | query | integer | 아니오 | min 1, max 50 | 페이지당 항목 수 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/support/faqs?page=1&per_page=1 HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}   (optional.sanctum: 비회원은 헤더 생략 가능)
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `7` | 기본 키 (내부 식별자) |
| channel | string | `faqs` | 고객지원 채널: notices, faqs, questions |
| title | string | `문의하면 메일이나 문자로 연락이 오나요?` | 제목 |
| author_name | string | `Travel Lab` | 게시글 작성자 표시명; 계정 ID·이메일과 별개 |
| is_notice | boolean | `false` | notice 여부 |
| is_secret | boolean | `false` | secret 여부 |
| is_mine | boolean | `false` | mine 여부 |
| answers_count | integer | `0` | answers 개수 (집계) |
| created_at | string | `2026-10-09 18:43:21` | 생성 일시 |
| updated_at | string | `2026-10-09 18:43:21` | 최종 수정 일시 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "자주 묻는 질문을 불러왔습니다.",
    "data": {
        "data": [
            {
                "id": 7,
                "channel": "faqs",
                "title": "문의하면 메일이나 문자로 연락이 오나요?",
                "author_name": "Travel Lab",
                "is_notice": false,
                "is_secret": "{MASKED}",
                "is_mine": false,
                "answers_count": 0,
                "created_at": "2026-10-09 18:43:21",
                "updated_at": "2026-10-09 18:43:21"
            },
            {
                "id": 6,
                "channel": "faqs",
                "title": "1:1 문의는 누가 볼 수 있나요?",
                "author_name": "Travel Lab",
                "is_notice": false,
                "is_secret": "{MASKED}",
                "is_mine": false,
                "answers_count": 0,
                "created_at": "2026-10-09 18:43:21",
                "updated_at": "2026-10-09 18:43:21"
            },
            "... (총 4건 중 2건 표시)"
        ],
        "meta": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 25,
            "total": 4
        }
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** 로그인 없이 공개 FAQ의 게시·비밀 아님 행을 조회한다. 본문은 상세에서 읽는다. page 기본 1, per_page 기본 15이며 목록 페이지 정보는 pagination이 아닌 meta에 있다.


### GET /api/modules/raonslab-travel_lab/support/faqs/{id}
<!-- @generated:start:api.modules.raonslab-travel_lab.support.faqs.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.faqs.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@faq`
- **인증/권한**: `optional.sanctum` (선택적 인증: 회원/비회원 모두 접근)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| id | path | integer | 예 | 숫자 ID | 대상 리소스의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/support/faqs/{id} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}   (optional.sanctum: 비회원은 헤더 생략 가능)
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id | integer | native sirsoft-board 게시글 ID |
| channel | string | notices, faqs 또는 questions; 요청 채널과 일치 |
| title / author_name | string | 제목 / 작성자 표시명; 계정 ID나 이메일은 제공하지 않음 |
| is_notice | boolean | 상단 고정 공지 여부 |
| is_secret | boolean | 비밀글 여부. questions는 서버가 true 강제 |
| is_mine | boolean | 현재 사용자 작성 문의이면 true; 공개 채널은 false |
| answers_count | integer | 목록은 댓글 수, 문의 상세는 실제 반환 답변 수 |
| created_at / updated_at | string/null | 사용자 시간대 Y-m-d H:i:s |
| content | string | 상세/등록/수정에만 제공되는 평문 본문 |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "게시글을 불러왔습니다.",
  "data": {
    "id": 8,
    "channel": "faqs",
    "title": "[LAB] 합성 안내",
    "author_name": "Travel Lab",
    "is_notice": false,
    "is_secret": false,
    "is_mine": false,
    "answers_count": 0,
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "content": "합성 데이터이며 실제 예약·결제·발송이 없습니다."
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** 요청 FAQ 채널에 속한 공개 게시글만 상세 반환한다. 다른 채널·비밀글·삭제/블라인드 글은 404. 고객지원 전용 native 게시판을 재사용한다.


### GET /api/modules/raonslab-travel_lab/support/notices
<!-- @generated:start:api.modules.raonslab-travel_lab.support.notices.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.notices.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@notices`
- **인증/권한**: `optional.sanctum` (선택적 인증: 회원/비회원 모두 접근)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| page | query | integer | 아니오 | min 1, max 10000 | 조회할 페이지 번호 (1부터 시작) |
| per_page | query | integer | 아니오 | min 1, max 50 | 페이지당 항목 수 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/support/notices?page=1&per_page=1 HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}   (optional.sanctum: 비회원은 헤더 생략 가능)
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `1` | 기본 키 (내부 식별자) |
| channel | string | `notices` | 고객지원 채널: notices, faqs, questions |
| title | string | `[LAB] 여행 연구소 테스트 운영 안내` | 제목 |
| author_name | string | `Travel Lab` | 게시글 작성자 표시명; 계정 ID·이메일과 별개 |
| is_notice | boolean | `true` | notice 여부 |
| is_secret | boolean | `false` | secret 여부 |
| is_mine | boolean | `false` | mine 여부 |
| answers_count | integer | `0` | answers 개수 (집계) |
| created_at | string | `2026-10-09 18:43:20` | 생성 일시 |
| updated_at | string | `2026-10-09 18:43:20` | 최종 수정 일시 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "공지사항을 불러왔습니다.",
    "data": {
        "data": [
            {
                "id": 1,
                "channel": "notices",
                "title": "[LAB] 여행 연구소 테스트 운영 안내",
                "author_name": "Travel Lab",
                "is_notice": true,
                "is_secret": "{MASKED}",
                "is_mine": false,
                "answers_count": 0,
                "created_at": "2026-10-09 18:43:20",
                "updated_at": "2026-10-09 18:43:20"
            },
            {
                "id": 3,
                "channel": "notices",
                "title": "[LAB] 테스트 데이터 정리 일정 안내",
                "author_name": "Travel Lab",
                "is_notice": false,
                "is_secret": "{MASKED}",
                "is_mine": false,
                "answers_count": 0,
                "created_at": "2026-10-09 18:43:21",
                "updated_at": "2026-10-09 18:43:21"
            },
            "... (총 3건 중 2건 표시)"
        ],
        "meta": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 25,
            "total": 3
        }
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** 로그인 없이 공지를 조회한다. 상단 고정 여부와 게시글 ID 역순으로 정렬하고 본문은 목록에서 제외한다. page 기본 1, per_page 기본 15.


### GET /api/modules/raonslab-travel_lab/support/notices/{id}
<!-- @generated:start:api.modules.raonslab-travel_lab.support.notices.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.notices.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@notice`
- **인증/권한**: `optional.sanctum` (선택적 인증: 회원/비회원 모두 접근)

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| id | path | integer | 예 | 숫자 ID | 대상 리소스의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/support/notices/{id} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}   (optional.sanctum: 비회원은 헤더 생략 가능)
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id | integer | native sirsoft-board 게시글 ID |
| channel | string | notices, faqs 또는 questions; 요청 채널과 일치 |
| title / author_name | string | 제목 / 작성자 표시명; 계정 ID나 이메일은 제공하지 않음 |
| is_notice | boolean | 상단 고정 공지 여부 |
| is_secret | boolean | 비밀글 여부. questions는 서버가 true 강제 |
| is_mine | boolean | 현재 사용자 작성 문의이면 true; 공개 채널은 false |
| answers_count | integer | 목록은 댓글 수, 문의 상세는 실제 반환 답변 수 |
| created_at / updated_at | string/null | 사용자 시간대 Y-m-d H:i:s |
| content | string | 상세/등록/수정에만 제공되는 평문 본문 |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "게시글을 불러왔습니다.",
  "data": {
    "id": 8,
    "channel": "notices",
    "title": "[LAB] 합성 안내",
    "author_name": "Travel Lab",
    "is_notice": true,
    "is_secret": false,
    "is_mine": false,
    "answers_count": 0,
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "content": "합성 데이터이며 실제 예약·결제·발송이 없습니다."
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** 공지 채널의 공개 게시글 상세. 다른 채널·비밀글·삭제/블라인드 글은 404. 관리자의 실제 콘텐츠 작성은 native 게시판 화면에서 한다.


### GET /api/modules/raonslab-travel_lab/support/questions
<!-- @generated:start:api.modules.raonslab-travel_lab.support.questions.index -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.index`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@questions`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| page | query | integer | 아니오 | min 1, max 10000 | 조회할 페이지 번호 (1부터 시작) |
| per_page | query | integer | 아니오 | min 1, max 50 | 페이지당 항목 수 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/support/questions?page=1&per_page=1 HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_목록 응답: `data.data[]` 배열 항목의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `15` | 기본 키 (내부 식별자) |
| channel | string | `questions` | 고객지원 채널: notices, faqs, questions |
| title | string | `W03-1791542300168 question 1440` | 제목 |
| author_name | string | `Synthetic reviewer member` | 게시글 작성자 표시명; 계정 ID·이메일과 별개 |
| is_notice | boolean | `false` | notice 여부 |
| is_secret | boolean | `true` | secret 여부 |
| is_mine | boolean | `false` | mine 여부 |
| answers_count | integer | `0` | answers 개수 (집계) |
| created_at | string | `2026-10-09 19:40:33` | 생성 일시 |
| updated_at | string | `2026-10-09 19:40:33` | 최종 수정 일시 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 200
```

```json
{
    "success": true,
    "message": "문의 목록을 불러왔습니다.",
    "data": {
        "data": [
            {
                "id": 15,
                "channel": "questions",
                "title": "W03-1791542300168 question 1440",
                "author_name": "Synthetic reviewer member",
                "is_notice": false,
                "is_secret": "{MASKED}",
                "is_mine": false,
                "answers_count": 0,
                "created_at": "2026-10-09 19:40:33",
                "updated_at": "2026-10-09 19:40:33"
            },
            {
                "id": 14,
                "channel": "questions",
                "title": "W03-1791542300168 question 390",
                "author_name": "Synthetic reviewer member",
                "is_notice": false,
                "is_secret": "{MASKED}",
                "is_mine": false,
                "answers_count": 0,
                "created_at": "2026-10-09 19:39:17",
                "updated_at": "2026-10-09 19:39:17"
            },
            "... (총 4건 중 2건 표시)"
        ],
        "meta": {
            "current_page": 1,
            "last_page": 1,
            "per_page": 25,
            "total": 4
        }
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** 기본적으로 본인 비공개 문의만 반환한다. 여행 support.read와 해당 문의 게시판 admin.posts.read·admin.posts.read-secret의 관리자 권한을 모두 갖고 유효 스코프가 모두 전체(null)일 때만 전체 목록을 제공한다. 여행 권한만 가진 manager도 본인 목록에 한정된다.


### POST /api/modules/raonslab-travel_lab/support/questions
<!-- @generated:start:api.modules.raonslab-travel_lab.support.questions.store -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.store`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@storeQuestion`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| title | body | string | 예 | min 2, max 200 | 제목 |
| content | body | string | 예 | min 2, max 5000 | 본문 내용 |

**요청 예시**

```http
POST /api/modules/raonslab-travel_lab/support/questions HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "title": "예시 제목",
    "content": "예시 내용입니다."
}
```

**응답 필드** (`data` 내부)

_단건 응답: `data` 객체의 필드._

| 필드 | 타입 | 실측 예시값 | 용도/설명 |
| --- | --- | --- | --- |
| id | integer | `16` | 기본 키 (내부 식별자) |
| channel | string | `questions` | 고객지원 채널: notices, faqs, questions |
| title | string | `실측 예시값` | 제목 |
| author_name | string | `Travel Lab Admin` | 게시글 작성자 표시명; 계정 ID·이메일과 별개 |
| is_notice | boolean | `false` | notice 여부 |
| is_secret | boolean | `true` | secret 여부 |
| is_mine | boolean | `true` | mine 여부 |
| answers_count | integer | `0` | answers 개수 (집계) |
| created_at | string | `2026-10-09 20:42:31` | 생성 일시 |
| updated_at | string | `2026-10-09 20:42:31` | 최종 수정 일시 |
| content | string | `실측 예시값` | 본문 내용 |

**응답 예시**

<!-- @probed -->

```http
HTTP/1.1 201
```

```json
{
    "success": true,
    "message": "문의가 등록되었습니다. (LAB 테스트 접수, 알림은 발송되지 않습니다.)",
    "data": {
        "id": 16,
        "channel": "questions",
        "title": "실측 예시값",
        "author_name": "Travel Lab Admin",
        "is_notice": false,
        "is_secret": "{MASKED}",
        "is_mine": true,
        "answers_count": 0,
        "created_at": "2026-10-09 20:42:31",
        "updated_at": "2026-10-09 20:42:31",
        "content": "실측 예시값"
    }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** native PostService로 평문 비공개 문의를 영속 저장한다. 작성자는 Bearer 사용자이며 is_secret=true·첨부 없음·알림 생략을 서버가 강제한다. title/content 외 필드는 저장에 사용하지 않는다. 시험 문의이며 실제 예약·메일·SMS로 이어지지 않는다.


### GET /api/modules/raonslab-travel_lab/support/questions/{id}
<!-- @generated:start:api.modules.raonslab-travel_lab.support.questions.show -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.show`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@showQuestion`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| id | path | integer | 예 | 숫자 ID | 대상 리소스의 식별자 |

**요청 예시**

```http
GET /api/modules/raonslab-travel_lab/support/questions/{id} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id | integer | native sirsoft-board 게시글 ID |
| channel | string | notices, faqs 또는 questions; 요청 채널과 일치 |
| title / author_name | string | 제목 / 작성자 표시명; 계정 ID나 이메일은 제공하지 않음 |
| is_notice | boolean | 상단 고정 공지 여부 |
| is_secret | boolean | 비밀글 여부. questions는 서버가 true 강제 |
| is_mine | boolean | 현재 사용자 작성 문의이면 true; 공개 채널은 false |
| answers_count | integer | 목록은 댓글 수, 문의 상세는 실제 반환 답변 수 |
| created_at / updated_at | string/null | 사용자 시간대 Y-m-d H:i:s |
| content | string | 상세/등록/수정에만 제공되는 평문 본문 |
| answers | array | 문의 상세에서만 제공; 게시 상태 답변 최대 50개, 작성순 |
| answers[].id | integer | native 게시판 댓글 ID |
| answers[].is_author | boolean | 문의 작성자의 추가 댓글이면 true; 관리자 답변은 false |
| answers[].content | string | 답변 본문; 답변자 계정 정보 제외 |
| answers[].created_at | string/null | 사용자 시간대 작성 일시 |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "문의를 불러왔습니다.",
  "data": {
    "id": 8,
    "channel": "questions",
    "title": "[합성] 시험 문의",
    "author_name": "Synthetic member",
    "is_notice": false,
    "is_secret": true,
    "is_mine": true,
    "answers_count": 1,
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "content": "합성 데이터이며 실제 예약·결제·발송이 없습니다.",
    "answers": [
      {
        "id": 1,
        "is_author": false,
        "content": "합성 관리자 답변입니다.",
        "created_at": "2026-10-09 17:56:06"
      }
    ]
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** 작성자 또는 여행 support.read와 해당 문의 게시판의 admin.posts.read·admin.posts.read-secret 관리자 권한/스코프를 모두 통과한 사용자가 열람한다. 그 외·타인·다른 채널·미존재는 404. 관리자 native 게시판 댓글을 최대 50개 작성순으로 반환하고 답변자 계정 정보는 제외한다.


### PATCH /api/modules/raonslab-travel_lab/support/questions/{id}
<!-- @generated:start:api.modules.raonslab-travel_lab.support.questions.update -->
- **라우트명**: `api.modules.raonslab-travel_lab.support.questions.update`
- **컨트롤러**: `Modules\Raonslab\TravelLab\Http\Controllers\Api\SupportController@updateQuestion`
- **인증/권한**: `auth:sanctum`

**요청 파라미터**

| 이름 | 위치 | 타입 | 필수 | 허용값 | 용도 |
| --- | --- | --- | --- | --- | --- |
| id | path | integer | 예 | 숫자 ID | 대상 리소스의 식별자 |
| title | body | string | content 없으면 필수 | min 2, max 200 | 제목 |
| content | body | string | title 없으면 필수 | min 2, max 5000 | 본문 내용 |

**요청 예시**

```http
PATCH /api/modules/raonslab-travel_lab/support/questions/{id} HTTP/1.1
Host: api.example.com
Accept: application/json
Authorization: Bearer {YOUR_TOKEN}
Content-Type: application/json

{
    "title": "예시 제목",
    "content": "예시 내용입니다."
}
```

**응답 필드** (`data` 내부)

_실측 대신 현재 소스 계약을 기준으로 작성한 필드 표._

| 필드 | 타입 | 용도/설명 |
| --- | --- | --- |
| id | integer | native sirsoft-board 게시글 ID |
| channel | string | notices, faqs 또는 questions; 요청 채널과 일치 |
| title / author_name | string | 제목 / 작성자 표시명; 계정 ID나 이메일은 제공하지 않음 |
| is_notice | boolean | 상단 고정 공지 여부 |
| is_secret | boolean | 비밀글 여부. questions는 서버가 true 강제 |
| is_mine | boolean | 현재 사용자 작성 문의이면 true; 공개 채널은 false |
| answers_count | integer | 목록은 댓글 수, 문의 상세는 실제 반환 답변 수 |
| created_at / updated_at | string/null | 사용자 시간대 Y-m-d H:i:s |
| content | string | 상세/등록/수정에만 제공되는 평문 본문 |

**응답 예시**

_소스·테스트 계약에서 작성한 합성 예시. 이번 생성의 실측 성공을 뜻하지 않습니다._

```http
HTTP/1.1 200
```

```json
{
  "success": true,
  "message": "문의가 수정되었습니다.",
  "data": {
    "id": 8,
    "channel": "questions",
    "title": "[합성] 시험 문의",
    "author_name": "Synthetic member",
    "is_notice": false,
    "is_secret": true,
    "is_mine": true,
    "answers_count": 0,
    "created_at": "2026-10-09 17:55:06",
    "updated_at": "2026-10-09 17:55:06",
    "content": "합성 데이터이며 실제 예약·결제·발송이 없습니다."
  }
}
```

**에러 응답**

| 상태코드 | 의미 | 발생 조건 |
| --- | --- | --- |
| 401 | Unauthenticated | 유효한 Bearer 토큰이 없거나 만료된 경우 |
| 422 | Unprocessable Entity | 요청 파라미터가 검증 규칙을 위반한 경우 (`errors` 에 필드별 메시지) |
| 404 | Not Found | path 파라미터에 해당하는 리소스가 없는 경우 |
| 429 | Too Many Requests | 아래 공통 제한의 호출 허용량을 초과한 경우 |
| 503 | Service Unavailable | 전용 게시판 미준비·보안 설정 불일치, 문의 채널은 mysql-fulltext 외 검색 드라이버도 차단 |

<!-- @generated:end -->

**설명** title 또는 content 중 하나 이상을 수정한다. 작성자 또는 여행 support.read·support.update와 native 게시판 admin.posts.read·admin.posts.read-secret·admin.posts.write 관리자 권한/스코프를 모두 통과해야 한다. 거부는 404. 수정은 PostService.updatePost의 native 훅·활동 로그(post.update) 경로를 사용하고 발송은 차단한다. 이 최신 보안·감사 변경의 독립 재검증은 이 문서 작성 시점에 NOT_RUN이다.


## 공통 게시판·보안 계약과 검증 한계

- 공지/FAQ는 `optional.sanctum`, 비공개 문의는 실제 Bearer `auth:sanctum`이다. 공개 목록은 본문을 제외하고 `data.meta`를 쓰며 `pagination`이 아니다.
- 여행 전용 sirsoft-board 게시판·PostService·native 관리자 댓글을 재사용한다. 문의는 비밀글·평문·첨부 없음·인증 작성자를 서버가 강제한다. 입력의 is_secret/user_id/첨부 ID를 저장에 사용하지 않는다. IP/이메일/비밀번호/계정 ID/내부 감사 정보는 여행 응답에서 제외한다.
- 타인 문의 상세/수정은 관리자 여행 권한뿐 아니라 native 해당 게시판 read/read-secret 및 수정 시 write 권한과 소유자 스코프를 모두 검사한다. 권한 불충족은 존재를 숨긴 404다. 전역 manager 이름만으로 타인 문의를 열람할 수 없다.
- 공개 읽기 600회/분, 문의 공통 120회/분, 문의 생성 추가 10회/분. 429는 코어 throttle 응답이다. 401은 코어 인증 봉투, 422는 기본 FormRequest의 `{message,errors}`이며 성공 봉투와 다르다.
- 문의 채널은 `scout.driver=mysql-fulltext`에서만 준비된다. native `scout:import` 등 외부 색인 경로를 여행 훅 하나로 보장할 수 없어 외부/다른 드라이버에서는 문의 읽기·쓰기를 503으로 닫는다. 이는 기존 비밀글 전체나 외부 검색 시스템을 검증했다는 의미가 아니다.
- 최신 코드의 native 게시판 수정 경유·감사 훅, 게시판 권한 안전 검사, 외부 Scout 제한은 소스 계약을 문서화한 것이다. **이 최신 변경의 독립 재검증은 문서 작성 시점 NOT_RUN**이며 이전 SHA 검증과 구분한다.
- 게시판 알림 설정·여행 알림 필터/등록 시 skip_notification으로 외부 메일/SMS를 막는다. 이 접수는 실제 예약이나 주문·결제가 아니다.

근거: [support 상세 계약](../support-api.md), [SupportPostResource](../../src/Http/Resources/SupportPostResource.php), [SupportController](../../src/Http/Controllers/Api/SupportController.php), [TravelSupportService](../../src/Services/TravelSupportService.php), [TravelSupportProvisioner](../../src/Services/TravelSupportProvisioner.php).
