# Travel Lab 문서

## 문서 목차

<!-- @generated:stats START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
**훅 수**: 0 · **구독 훅 수**: 11 · **라우트 수**: 0 · **모델 수**: 5 · **테이블 수**: 5 · **마이그레이션 수**: 2 · **레이아웃 수**: 5 · **핸들러 수**: 0
<!-- @generated:stats END -->

<!-- @generated:doc-toc START — ext:docgen 이 갱신. 이 블록 안은 직접 수정하지 않는다 -->
| 문서 | 내용 |
|---|---|
| [architecture.md](architecture.md) | 설계 의도·계층 지도·디렉토리 맵 |
| [extension-points.md](extension-points.md) | 발행/구독 훅·미들웨어·채널·스케줄 |
| [data-model.md](data-model.md) | 모델·소유 테이블·마이그레이션·Enum |
| [settings.md](settings.md) | 설정 스키마·권한·메뉴·라우트·의존 관계 |
| [frontend.md](frontend.md) | 레이아웃·액션 핸들러·전역 진입점·에셋 |
| [editor-spec.md](editor-spec.md) | 레이아웃 편집기에 선언한 팔레트·컨트롤·샘플 데이터 |
| [api/](api/README.md) | API 레퍼런스 |
| [../AGENTS.md](../AGENTS.md) | 에이전트·확장개발자 진입점 |
| [../README.md](../README.md) | 사람(도입검토자·운영자) 진입점 |
<!-- @generated:doc-toc END -->

<!-- @intent START -->
생성 표는 native 정적 스캐너의 실측입니다. 분할 라우트 `catalog.php/workflow.php/support.php`를 require하는 구조 때문에 정적 route 집계가 0으로 보일 수 있습니다. 이를 API 부재나 실행 검증으로 해석하지 않습니다. [실제 카탈로그 API 레퍼런스](api/README.md)는 격리 HTTP inventory의 카탈로그 12개 엔드포인트를 별도로 기록합니다. 전체 workflow/support 계약은 아래 수동 문서와 독립 검증 증거를 함께 봅니다.
<!-- @intent END -->

| 문서 | 내용 |
| --- | --- |
| [domain.md](domain.md) | 상품·옵션·가격·가시성·정원·스키마 계약 |
| [domain-api.md](domain-api.md) | 카탈로그/관리 API 계약 |
| [support.md](support.md), [support-api.md](support-api.md) | native 지원 게시판과 권한/API |
| [domain-evidence.md](domain-evidence.md), [domain-delivery.md](domain-delivery.md) | 최초 도메인 근거·원본 전달 범위 |
| [tests/README.md](../tests/README.md), [WORKFLOW_EVIDENCE.md](../tests/WORKFLOW_EVIDENCE.md) | 테스트·native 계산/워크플로 증거 |
