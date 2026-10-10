# G7 Travel Lab

## 소개

<!-- @generated:badges START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
<p align="center">
  <img src="https://img.shields.io/badge/version-0.1.3-0066FF?style=flat-square" alt="version 0.1.3">
  <img src="https://img.shields.io/badge/type-%EB%AA%A8%EB%93%88-555555?style=flat-square" alt="type 모듈">
  <img src="https://img.shields.io/badge/%EA%B7%B8%EB%88%84%EB%B3%B4%EB%93%9C7-%3E%3D7.0.12-1F883D?style=flat-square" alt="그누보드7 &gt;=7.0.12">
  <img src="https://img.shields.io/badge/license-MIT-8250DF?style=flat-square" alt="license MIT">
  <img src="https://img.shields.io/badge/requires-sirsoft--board-BF8700?style=flat-square" alt="requires sirsoft-board">
  <img src="https://img.shields.io/badge/requires-sirsoft--ecommerce-BF8700?style=flat-square" alt="requires sirsoft-ecommerce">
  <img src="https://img.shields.io/badge/requires-sirsoft--page-BF8700?style=flat-square" alt="requires sirsoft-page">
</p>
<!-- @generated:badges END -->

<!-- @intent START -->
RAON 자체 여행 상품 실증을 위한 G7 모듈입니다. 합성 상품을 탐색하고 인원별 서버 가격을 확인해 시험 문의를 접수합니다. 롯데관광 공개 정보 구조는 참고 동선이며 고객 사진·로고·문구·비공개 자료를 복제하지 않습니다. 실제 예약이나 결제를 연결하지 않습니다.
<!-- @intent END -->

## 주요 기능

<!-- @intent START -->
지역/테마/날짜/가격 검색과 상세·출발일, native 장바구니, 중복 키 재시도, 시험 문의/관리자 처리/사용자 상태 재조회, 공지·FAQ·비공개 문의를 제공합니다. 상품·옵션 가격은 이커머스 관리자에서, 여행 메타·출발일과 모의 정원은 여행 카탈로그 관리자에서 관리합니다. 실제 실행/독립 검증 상태는 저장소 `docs/symphony/WAVE_STATUS.md`와 W03 보고서에 별도 기록합니다.
<!-- @intent END -->

## 동작 방식

<!-- @intent START -->
여행 탐색 → 날짜·인원 선택 → 장바구니 → 시험 접수 → 관리자 검토/시험 수락 또는 거절 → 본인 상태 재조회입니다. 서버가 상품/옵션 가격과 가용 인원을 다시 확인합니다. 접수는 `TEST_INQUIRY`, 수락은 `TEST_ACCEPTED`이며 어느 상태도 실예약 확정이 아닙니다. 인원 확보와 취소/거절에 따른 반환은 같은 DB 트랜잭션에서 처리합니다.
<!-- @intent END -->

## 요구 사항

<!-- @generated:requirements START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 항목 | 값 |
|---|---|
| 그누보드7 코어 | `>=7.0.12` |
| PHP | `^8.2` |
| 의존 모듈 | `sirsoft-board` `>=1.1.2` |
| 의존 모듈 | `sirsoft-ecommerce` `>=1.2.1` |
| 의존 모듈 | `sirsoft-page` `>=1.1.2` |
<!-- @generated:requirements END -->

## 설치

아래 생성 명령은 격리 환경에서만 사용합니다. 재현 패키지의 [설치 안내](../../../deploy/travel-lab/README.md)와 [독립 fresh 설치 계획](../../../docs/symphony/W04_FRESH_INSTALL_RECIPE.md)의 DB/환경 가드를 먼저 적용하십시오. 운영 환경에 그대로 실행하지 않습니다.

<!-- @generated:install START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
```bash
# 번들 설치 (코어에 동봉된 소스에서 설치)
php artisan module:install raonslab-travel_lab

# 활성화
php artisan module:activate raonslab-travel_lab

# 업데이트 (번들 소스 기준 강제 반영)
php artisan module:update raonslab-travel_lab --force
```
<!-- @generated:install END -->

## 관리자 설정

<!-- @generated:settings-summary START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
_별도의 관리자 설정 항목이 없습니다._
<!-- @generated:settings-summary END -->

<!-- @intent START -->
여행 카탈로그·시험 접수·여행 고객지원 메뉴는 native 권한에 따라 노출됩니다. 사업 시간대는 자체 실증 결정 `Asia/Seoul`이며 전역 앱 시간대를 바꾸지 않습니다. 지원 게시판 provisioning은 격리 LAB 표식과 명시 허용이 모두 필요합니다. [설정/권한 계약](docs/settings.md)을 확인하십시오.
<!-- @intent END -->

## 사용 방법

<!-- @intent START -->
설치/활성화만으로 합성 여행 상품을 생성하지 않습니다. 격리 환경에서 명시적으로 `php artisan module:seed raonslab-travel_lab --sample`을 실행하면 고정 SKU 8개와 출발 옵션 24개를 생성합니다. native 상품 채번 시퀀스가 준비되어 있어야 합니다. 재실행은 운영자가 수정한 상품/옵션/메타/출발과 기존 모의 확보 인원을 보존합니다. 기본 출발 기준일은 `config/catalog.php`의 `sample_departure_anchor`(`2026-11-01`)이며 시간이 지나면 공개 목록에서 빠집니다. 새 상품은 native 이커머스 상품/옵션을 만든 뒤 여행 메타데이터에 연결합니다. 사용자 `/travel` 화면은 별도 여행 템플릿을 활성화해 사용합니다. 문의는 로그인한 본인만 조회하며 관리자는 허용 상태로 처리합니다. 공지/FAQ 작성과 비공개 문의 답변은 G7 게시판 관리자 경로를 재사용합니다.
<!-- @intent END -->

## 다른 확장과의 연동

<!-- @generated:integrations START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
**이 확장이 의존하는 확장**

| 확장 | 유형 | 버전 제약 | 번들 |
|---|---|---|---|
| `sirsoft-board` | 모듈 | `>=1.1.2` | ✅ |
| `sirsoft-ecommerce` | 모듈 | `>=1.2.1` | ✅ |
| `sirsoft-page` | 모듈 | `>=1.1.2` | ✅ |

**이 확장에 의존하는 확장** (이 확장을 비활성화하면 함께 영향을 받습니다)

| 확장 | 유형 | 요구 버전 |
|---|---|---|
| `raonslab-travel_lab` | 템플릿 | `>=0.1.3` |
<!-- @generated:integrations END -->

<!-- @intent START -->
sirsoft-ecommerce는 상품·옵션·카트·공식 가격 계산을, sirsoft-board는 지원 게시글/댓글과 native 관리자 처리를 소유합니다. sirsoft-page의 페이지 개념과 G7 템플릿 라우팅을 사용하며 기존 RAON 사업사이트/회원/문의/운영 DB를 변경하지 않습니다. 사용자 템플릿은 같은 API 계약을 소비하는 별도 확장입니다.
<!-- @intent END -->

## 문서

<!-- @generated:docs-index START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 문서 | 내용 | 상태 |
|---|---|---|
| [docs/README.md](docs/README.md) | 문서 통합 목차와 실측 집계 | ✅ |
| [docs/architecture.md](docs/architecture.md) | 설계 의도·계층 지도·디렉토리 맵 | ✅ |
| [docs/extension-points.md](docs/extension-points.md) | 발행/구독 훅·미들웨어·채널·스케줄 | ✅ |
| [docs/data-model.md](docs/data-model.md) | 모델·소유 테이블·마이그레이션·Enum | ✅ |
| [docs/settings.md](docs/settings.md) | 설정 스키마·권한·메뉴·라우트·의존 관계 | ✅ |
| [docs/frontend.md](docs/frontend.md) | 레이아웃·액션 핸들러·전역 진입점·에셋 | ✅ |
| [docs/editor-spec.md](docs/editor-spec.md) | 레이아웃 편집기에 선언한 팔레트·컨트롤·샘플 데이터 | ✅ |
| [docs/api/](docs/api/README.md) | API 레퍼런스 (엔드포인트별 파라미터·응답 필드) | ✅ |
| [CHANGELOG.md](CHANGELOG.md) | 변경 이력 | ✅ |
<!-- @generated:docs-index END -->

<!-- @intent START -->
[도메인 계약](docs/domain.md), [API 계약](docs/domain-api.md), [지원 계약](docs/support.md), [실제 카탈로그 API](docs/api/README.md), [테스트](tests/README.md)에 상세 동작과 제한이 있습니다.
<!-- @intent END -->

## 트러블슈팅

<!-- @intent START -->
빈 목록은 미래 KST 출발·공개/판매·옵션 활성·잔여 인원을 확인합니다. 409는 가격/정원/카트/멱등 키/상태 경합 사유를 확인하고 재조회합니다. 422는 입력 오류 또는 출발 옵션 제거 제한입니다. 429는 Retry-After 이후 동일 접수 키로 재시도합니다. 관리자 메뉴가 없으면 해당 native 권한과 설치 사본을 확인합니다. 여행 상품 삭제는 409로 막으며 게시 해제를 사용합니다. 설치본이 오래되면 격리된 공식 update 경로로 반영하고 로드 파일 해시를 확인합니다.
<!-- @intent END -->

## 변경 이력

[CHANGELOG.md](CHANGELOG.md)

## 라이선스

MIT. 범용 코드·합성 데이터만 포함하며 고객 자산/운영 비밀을 포함하지 않습니다.
