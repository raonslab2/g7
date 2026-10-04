# RAON Agent Factory 홍보영상 V1 — 제작 패키지

> 대화는 단순하게. 실행은 강력하게. 증거는 명확하게.

AI_GCS V2 → AgentOpt V2 → CODEX / CLAUDE → 관측 증거 → Git 으로 이어지는 **현재 플랫폼의 실제 구조와 실제 화면**으로 만든 88초 기업·기술 홍보영상의 소스·문서·렌더 파이프라인이다.
작업지시서: `work-20261004-raon-platform-promo-video-v1-4d7c2a91`.

## 문서

| 파일 | 내용 |
|---|---|
| [scene-list.md](scene-list.md) | 장면 구성, 소재 구분(실제 UI / 공개 사이트 / 제품 / 도식 / 추상), 역할 표현 대조표 |
| [edit-timeline.md](edit-timeline.md) | 초 단위 편집 타임라인(영상·강조·자막·오디오 트랙) |
| [narration-ko.txt](narration-ko.txt) | 한국어 내레이션 원고(타임코드·읽기 속도) — `render/make_text.py` 생성 |
| [subtitles-ko.srt](subtitles-ko.srt) | 한국어 자막 18큐 — 같은 원천에서 생성 |
| [shot-prompts.md](shot-prompts.md) | 생성형 장면(배경 플레이트) 프롬프트 · 기준 프레임 · 교체 절차 |
| [capture/README.md](capture/README.md) | 실제 화면 캡처 방법·데모 데이터·민감정보 점검 |
| [reference-frames/](reference-frames/) | 장면별 기준 프레임 16:9 ×13, 9:16 ×13 |

## 구조

```
assets/ui/            AI_GCS V2 실제 배포 Web 빌드 캡처(데모 데이터) — webp
assets/web/           G7 / RAON Hub 공개 사이트 캡처(2026-10-04) — webp
assets/mobile-stock/  MOBILE_STOCK 공개 사례 증거 화면 — webp
capture/              캡처 스크립트 + DOM 좌표 매니페스트
render/timeline.json  장면·자막 시각의 단일 원천
render/promo.html     결정적 합성기(window.__render(t)), ?o=h|v
render/render.mjs     프레임 캡처 → ffmpeg 인코딩, 기준 프레임 추출
render/make_text.py   timeline → SRT / ASS / 내레이션 원고
render/build.sh       전체 빌드(무음 clean → 음악 베드 → 자막 번인 → 썸네일 → ffprobe)
```

## 빌드

필요: Node 20+, Playwright 1.55(Chromium), Python 3, libass 포함 ffmpeg/ffprobe, Noto Sans CJK KR(자막 번인).
Pretendard 는 저장소 동봉본(`templates/_bundled/sirsoft-basic/dist/vendor/pretendard`, OFL)을 그대로 참조한다.

```bash
FFMPEG=/path/ffmpeg FFPROBE=/path/ffprobe \
PLAYWRIGHT_MODULE=/path/node_modules/playwright/index.mjs \
media/promo/raon-agent-factory-v1/render/build.sh /tmp/promo-out
```

16:9 · 9:16 각 2,640프레임을 렌더한다(이 호스트 기준 방향당 약 5분).

## 산출물 보관

대용량 MP4 는 이 저장소(main)에 커밋하지 않는다(저장소는 공개이며 Git LFS 가 구성되어 있지 않다).
최종 파일은 GitHub Release **초안(draft)** `promo-raon-agent-factory-v1` 의 asset 으로 보관한다 — 초안은 저장소 협업자만 볼 수 있고 공개 배포가 아니다. 공개 여부는 검토 후 결정한다.

## G7 사이트 연동 제안 (이번 작업에서는 반영하지 않음)

이번 요청은 영상 산출물까지만 완료하고 사이트 반영은 별도 후속으로 남긴다. 같은 날 G7 콘텐츠 복원 작업이 Page/DB 를 다뤘고, 사이트 반영에는 템플릿 컴포넌트 추가가 필요하기 때문이다.

| 항목 | 제안 |
|---|---|
| 위치 | 홈 `raonslab-product` 확장 `home-product.json` 의 “실제 제품 증거” 섹션(`raon_home_proof_cases`) 상단, 또는 히어로 우측 “요청이 결과가 되기까지” 카드 아래 |
| 필요 작업 | `sirsoft-basic` 에 `Video` 기본 컴포넌트가 없다(HTML 태그 직접 사용 금지). 템플릿에 `Video` 컴포넌트를 추가하고 editor-spec 의 palette·groups·nesting·capabilities 4곳을 함께 등록하거나, 확장 JS 가 소유한 전용 컴포넌트로 제공 |
| 자산 | MP4 는 same-origin 자체 제공(외부 CDN 금지 원칙). 확장 `resources/assets/promo/` 또는 공개 자산 디스크. URL 은 `G7Core.asset.module` 로 생성(기존 `data-rh-asset` 패턴과 동일) |
| poster | `RAON_AGENT_FACTORY_PROMO_V1_thumbnail.jpg`(1280×720) — 첫 화면은 항상 poster 로 즉시 표시 |
| autoplay | 자동 재생하지 않는 것을 기본으로 권장(클릭 재생 + 자막 번인판). 자동 재생이 필요하면 `muted` `playsinline` `loop` 에 한해, `prefers-reduced-motion: reduce` 에서는 비활성 |
| 모바일 | 900px 미만은 `preload="none"` + poster + 탭 재생, 세로 화면은 9:16 판, 데이터 절약 모드에서는 poster 와 링크만 |
| 접근성 | 자막 번인판 사용 또는 `<track kind="captions" src="subtitles-ko.vtt">`(SRT 변환), 재생 컨트롤 표시 |
| 검증 | 레이아웃 렌더 테스트(createLayoutTest) + 390/1440 브라우저 확인 + 확장 버전 업·CHANGELOG |

<!-- RESULTS -->
