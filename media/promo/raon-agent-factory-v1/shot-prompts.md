# RAON Agent Factory 홍보영상 V1 — 생성형 장면 프롬프트

## 현재 상태

- 이 제작 환경에는 **외부 영상 생성 서비스가 연결되어 있지 않다.** 그래서 생성형 영상은 만들지 않았고, 만들었다고 주장하지 않는다.
- V1 렌더의 해당 구간(S1 · S2 · S7, 그리고 S4a 배경)은 **코드로 그린 추상 모션 그래픽**(`render/promo.html`)이 대신 채운다. 텍스트·워드마크는 생성 영상이 아니라 이 합성기가 그 위에 얹는다.
- 생성 서비스를 연결하면 아래 프롬프트로 **텍스트 없는 배경 플레이트**만 생성해 해당 구간 배경으로 교체한다. 실제 제품 UI 를 생성형으로 흉내내지 않는다(실제 UI 구간은 캡처만 쓴다).
- 기준 프레임: [`reference-frames/`](reference-frames/) — 현재 V1 의 해당 시각 프레임. 구도·여백·텍스트 위치를 이 프레임에 맞춘다.

## 공통 스타일 (모든 샷에 붙인다)

```
Style: premium enterprise technology film, dark-first, calm and trustworthy.
Palette: charcoal #0a0e13, graphite #131a22, deep navy #0f1b2d, slate #c9d2dd,
muted blue accent #7fa6d6, muted green accent #74c19e. Low saturation, soft contrast.
Lighting: soft, diffused, subtle volumetric haze, no lens flares.
Motion: slow, steady, deliberate; no fast cuts, no shake.
Composition: leave clean negative space for overlaid Korean typography (see per-shot safe area).
Output: no text, no letters, no numbers, no logos, no UI that imitates a real product, no people, no faces, no hands.
```

```
Negative: neon, cyberpunk, glowing circuits overload, heavy glow, bloom, rainbow gradients, holograms of humans,
robots with faces, humanoid androids, brand logos, readable text, watermarks, stock-market tickers, currency symbols,
fire, explosions, glitch effects, chromatic aberration, fisheye.
```

## 샷별 프롬프트

### G1 — 문제 제시 배경 (S1, 0.0–7.0초, 7초)

- 목적: “AI는 빠르게 답하지만, 일이 끝났는지는 흩어져 있다”는 정서. 정돈되지 않은 조각들.
- 화면비: 16:9 1920×1080 / 9:16 1080×1920 각각 생성. 텍스트 안전 영역: 16:9 좌측 하단 1/2, 9:16 중앙.
- 기준 프레임: `reference-frames/16x9-t03.0.jpg`, `reference-frames/9x16-t03.0.jpg`
- 프롬프트:

```
Slow drifting macro shot through a dark graphite void filled with dozens of small,
semi-transparent rounded rectangular panels floating at different depths, like scattered message cards
and log fragments, each panel blank with only abstract soft grey bars (no readable text).
Shallow depth of field, panels in the foreground softly out of focus, a faint deep-navy haze.
Camera slowly dollies forward; panels drift apart and gradually lose focus by the end,
leaving calm empty space. Mood: quiet uncertainty, unresolved. [공통 스타일]
```

### G2 — Agent Factory 오프닝 (S2, 7.0–16.0초, 9초)

- 목적: “공장” 은유 — 차분하고 정밀한 조립 라인 위로 작은 작업 단위가 질서 있게 흐른다. 사람·로봇 팔 없음.
- 텍스트 안전 영역: 화면 중앙 가로 60% (워드마크 + 카피 2줄이 올라간다).
- 기준 프레임: `reference-frames/16x9-t12.0.jpg`, `reference-frames/9x16-t12.0.jpg`
- 프롬프트:

```
Wide, symmetrical, top-down-tilted view of an abstract, minimal factory floor rendered as thin parallel light lanes
on a matte graphite surface, extending into soft darkness. Small glowing points in muted green and muted blue travel
steadily along the lanes in both directions at different speeds, occasionally merging at quiet junction nodes,
like work items moving through an orderly production line. Very slow crane-up camera move revealing the order of the system.
Center of frame stays darker and uncluttered for overlaid typography. Precise, architectural, calm. [공통 스타일]
```

### G3 — 오케스트레이션 전환 배경 (S4a 뒤, 33.0–40.5초, 7.5초, 선택)

- 목적: 구조 도식(AI_GCS → AgentOpt → CODEX / CLAUDE → Evidence → Git) 뒤에 깔리는 은은한 배경. **도식 자체는 합성기가 그린다** — 생성 영상이 노드나 글자를 만들지 않는다.
- 텍스트 안전 영역: 화면 전체(도식이 덮는다). 대비 매우 낮게.
- 기준 프레임: `reference-frames/16x9-t37.0.jpg`
- 프롬프트:

```
Extremely subtle abstract background: two gentle streams of soft light (one muted blue, one muted green) flowing
left to right in parallel through deep navy darkness, briefly diverging into two branches and rejoining further right,
like two coordinated workers converging on a shared result. Very low contrast, slow, mostly dark, no hard shapes. [공통 스타일]
```

### G4 — 엔딩 배경 (S7, 80.0–88.0초, 8초)

- 목적: 정돈된 결말. 조각들이 하나의 선으로 정렬되고 고요해진다.
- 텍스트 안전 영역: 중앙 가로 70% × 세로 60% (워드마크 + 엔딩 카피 3줄).
- 기준 프레임: `reference-frames/16x9-t86.0.jpg`, `reference-frames/9x16-t86.0.jpg`
- 프롬프트:

```
Calm closing plate: from soft darkness, a field of faint points slowly aligns into a few clean horizontal lines of light
in muted green and slate, then holds still. Gentle slow push-in, deep navy to charcoal vignette, generous empty center.
Feeling: resolved, verified, trustworthy. [공통 스타일]
```

## 교체 절차 (렌더러 연결 후)

1. 각 샷을 16:9 / 9:16 으로 생성(24 또는 30fps, 최소 1080p, 길이는 위 초 + 앞뒤 0.5초 핸들).
2. `assets/generated/G{n}-{16x9|9x16}.mp4` 로 저장(대용량이면 저장소 밖, 출처·생성 도구·프롬프트·시드를 `assets/generated/README.md` 에 기록).
3. `render/promo.html` 해당 장면 build 함수에서 추상 레이어 대신 `<video>` 를 배경으로 두고, `__render(t)` 에서 `video.currentTime = t - 장면시작` 으로 프레임을 맞춘다(결정적 렌더 유지).
4. 텍스트 레이어·타이밍·자막은 바꾸지 않는다. `render/build.sh` 재실행.
5. 생성 결과에 글자·로고·인물·실제 제품 UI 유사물이 섞였으면 폐기하고 재생성한다.
