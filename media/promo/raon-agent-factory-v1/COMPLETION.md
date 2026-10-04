# 기존 제작 패키지 이어서 완료

원본 FAILED Request의 제작 패키지를 재사용한다. 기존 62개 파일은 덮어쓰거나 폐기하지 않았으며, 원본별 SHA-256은 `evidence/original-inventory.json`에 보존한다. 원본 전체 백업과 내부 식별자가 포함된 캡처·기준 프레임은 기존 workspace 및 서버의 비공개 제작 보관 위치에 유지한다.

## 재현

FFmpeg 6.1.1(libx264/libass), ffprobe, Python 3, Playwright 1.55.1/Chromium, Noto Sans CJK KR와 저장소 동봉 Pretendard를 사용한다. 외부 유료 생성 서비스는 사용하지 않는다.

```bash
PLAYWRIGHT_MODULE=/path/to/playwright/index.mjs \
FFMPEG=/usr/bin/ffmpeg FFPROBE=/usr/bin/ffprobe \
bash media/promo/raon-agent-factory-v1/render/build-completion.sh /path/outside-git
```

완료판은 `promo-completion.html`과 `assets-safe/`를 사용한다. 원본 렌더 소스는 보존했다. `sanitize_assets.py`는 원본이 있는 보관 환경에서 OCR 기반 마스킹 사본을 만들고, `mask_known_identifiers.py`는 관측 화면의 알려진 식별자 영역을 추가로 가린다. 공개 Git에는 원본 캡처를 포함하지 않는다. 마스킹은 표시값을 숨기며 실행 상태를 변경하지 않는다.

88초, 30fps, 16:9와 9:16 각각 별도 레이아웃이다. 한국어 자막 18큐와 기존 내레이션 원고를 재사용한다. 영상 오디오는 FFmpeg로 합성한 음악이다. 음성 내레이션은 녹음하지 않았다. AI_GCS 화면은 실제 제품 빌드에 안전 데모 데이터를 넣은 캡처이며 라이브 실행 촬영이 아니다. 장면 라벨에 데모임을 표시한다. 외부 생성형 영상은 사용하지 않았다.

사이트 Page/DB/운영 설정을 수정하지 않는다. 최종 MP4는 Git에 넣지 않고 GitHub Release 초안의 asset으로 보관한다. 원래 Request의 FAILED 상태를 COMPLETED로 조작하거나 새 총괄 Request를 만들지 않는다.

완료판 기준 프레임은 `reference-frames-safe/`에 있다. 기존 문서의 `assets/`, `reference-frames/` 링크는 보존된 원본 workspace용이며, Git에서 재현할 때는 위 완료판 빌드를 사용한다.

검증 재현:

```bash
PLAYWRIGHT_MODULE=/path/to/playwright/index.mjs \
PLAYBACK_BROWSER=/path/to/chrome-with-mp4-support \
python3 media/promo/raon-agent-factory-v1/render/verify.py /path/outside-git
```

기본 Playwright 1.55 헤드리스 셸은 이 환경에서 H.264/AAC 재생을 지원하지 않았다. 프레임 렌더에는 이를 사용하고, 실제 재생 검증에는 기존 설치 Google Chrome for Testing 151.0.7922.34(설치 빌드 1234) 바이너리를 사용한다. 영상 코덱을 지원하는 브라우저가 필요하다.

## 최종 검증

- 두 최종 MP4 모두 88.000초, 30fps, H.264/yuv420p. 가로 1920×1080, 세로 1080×1920. AAC 48kHz 스테레오 음악, peak -13.2dB.
- FFmpeg 전체 영상·오디오 디코딩 오류 없음.
- 실제 브라우저 1440×900 / 390×844에서 재생 시간이 증가했고, 25/43/56/64/77/86초 구간 이동·디코딩 성공. 음성 내레이션은 없음.
- 원천 마스킹 이미지 21개 OCR와 최종 영상에서 추출한 장면별 26개 프레임 OCR에서 대상 식별자 패턴 미검출. 26개 최종 프레임을 시각 확인하여 자막·주요 제목 잘림 없음. 모든 5,280프레임을 사람이 개별 검토한 것은 아니며, 전체 디코딩과 고정 캡처/합성기 검사 및 장면별 샘플 검토를 수행했다.
- 원본 62개 파일 SHA-256 모두 변경 없음. 비밀정보 패턴 정적 검사에서 credential 패턴 미검출.
- 증거: `video-probe.json`, `playback.json`, `asset-ocr.json`, `frame-ocr.json`, `contact-*.jpg`, `SHA256SUMS`.

Release 초안 태그: `promo-raon-agent-factory-v1`. 가로·세로 자막판과 clean판, 썸네일, SHA256SUMS를 보관한다. 공개 배포와 사이트 삽입은 수행하지 않았다.
