# Home business truth · evidence · conversion audit input

Audit date: 2026-09-28 KST

Scope: public Home and linked public conversion surfaces; audit only, no product/runtime/DB mutation

Exact baseline: `57f75c859fedafd498df804554704c2060913091` = worktree HEAD = `origin/main`; runtime checkout reported the same SHA and both trees were clean. `raonslab-product` is `0.4.2`. Public module JS/CSS SHA-256 matched this baseline.

Public review surface: [HTTP review Home](http://203.245.29.156:58770/) — **review ingress, not a customer-ready production domain**.

## Executive finding

The Home explains a credible delivery method, but it does not yet close a sale. It offers three service stages, yet the only strong proof is one internal RAON Hub build; MOBILE_STOCK has no source or visual evidence in this baseline. The primary consultation CTA ends at a disabled state with no approved alternative contact. The public ingress is HTTP, canonical/OG points to `127.0.0.1`, sitemap points to `localhost`, and legal/contact inputs remain unapproved. Treat this as a truthful review site, not an active customer-acquisition site.

Evidence key used below:

- **H** — [Home copy](../../../modules/_bundled/raonslab-product/resources/lang/ko.json) and [Home layout](../../../modules/_bundled/raonslab-product/resources/extensions/home-product.json)
- **B/S** — [product brief](../../../modules/_bundled/raonslab-product/docs/product-brief.md) / [sales evidence map](../../../modules/_bundled/raonslab-product/docs/sales-evidence-map.md)
- **E/P/A** — [E2E record](../E2E.md) / [productization record](../Productization.md) / [AI adapter boundary](../AIGCSAdapter.md)
- **R/C/I** — [0.3.0 release validation](../../../modules/_bundled/raonslab-product/docs/release-validation-0.3.0.md) / [consultation activation](../../../modules/_bundled/raonslab-product/docs/consultation-activation.md) / [initialization audit](INITIALIZATION_AUDIT_2026-09-28.md)
- **LIVE** — read-only check of Home, public pages and consultation config on 2026-09-28. No broad suite was rerun.

Classification means: **VERIFIED** = direct baseline/public or trusted existing evidence; **PARTIALLY_VERIFIED** = demonstrated internally or on one stack but phrased more broadly; **UNVERIFIED** = no supporting artifact in this baseline; **MARKETING_ONLY** = positioning or causal assertion, not a provable product fact.

## 1. Home claim ledger

| ID | Material Home claim | Class | Evidence / gap | Safe customer-facing wording |
|---|---|---|---|---|
| C01 | Enterprise-specific AI agent from build through execution, verification and operation | PARTIALLY_VERIFIED | E/P/A prove one internal G7 flow, not customer delivery | “기존 시스템에 맞춘 AI 실행 흐름을 실증·구축·개선합니다.” |
| C02 | Existing systems and rules can become real execution flows for recurring development, analysis and operations work | PARTIALLY_VERIFIED | RAON Hub proves one development/productization case; no analysis/operations customer case | “연결 수단과 권한이 확인된 업무부터 실행 흐름을 설계합니다.” |
| C03 | “AI agents that actually work” factory | MARKETING_ONLY | Brand metaphor | Keep only as a short brand line, never as proof. |
| C04 | Input → request execution → tests/screens → recorded improvement is the operating model | PARTIALLY_VERIFIED | A/E prove request, event, result and follow-up; customer lifecycle not proven | “RAON Hub에서는 요청·진행·결과·후속 지시를 기록하고 화면과 테스트로 확인했습니다.” |
| C05 | Conversational AI gives answers, while real work requires scoped execution and confirmation | MARKETING_ONLY | Category framing; named alternatives also have tools/agents | “도구 도입과 별개로, 귀사 시스템의 권한·승인·검증 흐름을 구축해야 합니다.” |
| C06 | Unconnected AI stops at suggestions and creates manual copying | MARKETING_ONLY | Plausible problem hypothesis, no buyer evidence | Ask this as a diagnostic question, not a universal fact. |
| C07 | Without verification criteria, results are hard to operationalize | MARKETING_ONLY | Delivery principle, not measured customer outcome | “상담에서 성공 기준과 미검증 범위를 먼저 정합니다.” |
| C08 | Without execution records, teams repeat work and cannot improve | MARKETING_ONLY | No measured recurrence/rework evidence | “요청·진행·결과 기록을 남겨 재검토 가능한 흐름을 지향합니다.” |
| C09 | Start with one task, expand only from verified results | PARTIALLY_VERIFIED | B/S define the method; internal release process follows it | “첫 제안은 업무 한 개의 범위와 성공 기준을 정하는 실증입니다.” |
| C10 | Single-task pilot includes real-system connection, success criteria, permission design and a verified/unverified report | UNVERIFIED | Defined offer, no completed customer engagement or specimen deliverable | “실증 제안 범위: 연결 가능성 확인, 권한 경계, 성공 기준, 결과 보고.” |
| C11 | Custom build provides integrations, request/progress/result UI, automated and screen checks | PARTIALLY_VERIFIED | RAON Hub demonstrates these internally; not all systems are connectable | “연결 가능한 API·확장 지점이 확인되면 요청·진행·결과 화면과 검증을 구축 범위로 제안합니다.” |
| C12 | Ongoing operation finds failures, updates rules and re-verifies improvements | UNVERIFIED | No long-running external operation evidence or SLA | “운영·개선 범위와 대응 방식은 구축 후 별도 합의합니다.” |
| C13 | Two self-built, verified products; neither is a customer delivery | PARTIALLY_VERIFIED | RAON Hub is evidenced; MOBILE_STOCK is only repeated in B/S/H | “RAON Hub는 내부 구축 사례입니다. MOBILE_STOCK는 원본 증거 확인 후 공개합니다.” |
| C14 | MOBILE_STOCK is a mobile paper-trading product built by several AI Providers | UNVERIFIED | No MOBILE_STOCK source, test result, screenshot or immutable evidence in baseline | Remove from Home until an authoritative source and release record are linked. |
| C15 | MOBILE_STOCK used request → Provider → verification → integration with per-request records | UNVERIFIED | Same gap as C14 | Publish only an evidence-linked, date/version-bounded statement. |
| C16 | MOBILE_STOCK users trade with virtual funds | UNVERIFIED | No runnable/public product artifact in baseline | Do not describe the user flow until product evidence is present. |
| C17 | MOBILE_STOCK request implementations and verification results were confirmed internally | UNVERIFIED | No result artifact in baseline | “검증 기록 확인 대기” internally; omit publicly. |
| C18 | MOBILE_STOCK did not verify brokerage integration, investment performance or external-customer operation | VERIFIED | H/B/S consistently set the disclosure boundary | Keep if the case remains; it does not repair C14–C17. |
| C19 | MOBILE_STOCK’s approach transfers to teams using multiple AI tools | MARKETING_ONLY | Transfer hypothesis, no customer use | “적용 가능성은 고객 환경의 저장소·승인·검증 방식 확인 후 판단합니다.” |
| C20 | RAON Hub runs on GNUBOARD7 7.0.11 with product/AI workspace modules and zero core patches | VERIFIED | P core inventory; baseline source; LIVE generator `7.0.11` | “RAON Hub에서는 G7 코어 수정 없이 두 확장 모듈로 적용했습니다.” |
| C21 | Existing members, boards, search and permissions were preserved/reused | VERIFIED | E J1–J9; P product surface | “한 G7 환경에서 기존 회원·게시판·검색·권한 흐름을 재사용해 확인했습니다.” |
| C22 | Approved users can submit a request, see progress/results and send follow-up instructions on the same request | VERIFIED | E A1–A10; A closed loop | “내부 RAON Hub 검증에서 승인 사용자 요청·진행·결과·동일 요청 후속 지시를 확인했습니다.” |
| C23 | J1–J10, A1–A10, 360/390/412px, follow-up and zero-core-change all passed | VERIFIED | E/P; inherited trusted evidence, not rerun here | Keep on the detail/evidence page with date, version and definitions—not as unexplained Home codes. |
| C24 | Commercial load, long-term stability and external-customer operation remain unverified | VERIFIED | E/P/H explicit limitation; no contrary evidence | Keep prominently. |
| C25 | The RAON Hub pattern can transfer to an existing web service without core changes | PARTIALLY_VERIFIED | Proven only for G7 official extension points | “확장 지점이 있는 기존 시스템은 유사 접근을 검토할 수 있습니다.” |
| C26 | Consultation selects one task and agrees systems, data, permissions and success criteria in writing | UNVERIFIED | Stated method; no completed consultation/deliverable sample | Present as the proposed engagement process, not past performance. |
| C27 | The team builds a working flow and shares automated/screen checks split into pass/fail/unverified | PARTIALLY_VERIFIED | Internal release evidence supports the practice | “내부 사례와 같은 형식의 검증 결과를 제안 산출물에 포함합니다.” |
| C28 | A verified flow is put into business operation and continuously improved from records | UNVERIFIED | No external production case or long-term record | “운영 전환과 개선 주기는 별도 범위·책임 합의가 필요합니다.” |
| C29 | This is not general chat but an observable, verifiable execution flow; all request/execution/check facts remain recorded | PARTIALLY_VERIFIED | Requests/events/results/follow-ups persist; complete execution/check artifact retention is not proven | “요청·상태 이벤트·결과·후속 지시를 같은 요청과 연결해 보여 줍니다.” |
| C30 | Customer public APIs/extensions are used first and core changes minimized | PARTIALLY_VERIFIED | Verified on G7; future-customer policy only | “RAON Hub에서 공식 API·확장 지점을 우선한 방식을 적용했습니다.” |
| C31 | Public, member, approved-user and admin permissions are separated; server enforces access | VERIFIED | A/P and module auth/ownership contracts | “RAON Hub는 회원가입과 AI 실행 승인을 분리하고 요청 소유권을 서버에서 확인합니다.” |
| C32 | Request, progress, result and follow-up stay together and can always be traced | PARTIALLY_VERIFIED | E proves replay/restart; “always” implies unsupported retention/SLA | Replace “언제든” with “검증한 저장·재연결 범위에서”. |
| C33 | Only changes passing automated/browser/mobile checks enter operation | PARTIALLY_VERIFIED | R/E show release gates, but universal “only” is a process guarantee | “공개된 RAON Hub 릴리스는 기록된 검증 게이트를 통과했습니다.” |
| C34 | The team always reports verified, failed and unverified facts without exaggeration | MARKETING_ONLY | Editorial principle, not independently provable conduct | “결과 보고서는 통과·실패·미검증을 구분합니다.” |
| C35 | Online intake opens only after privacy/security approval; current status is shown | VERIFIED | C; LIVE config `intake_enabled=false` and blank approval fields | Keep; add “현재 접수 불가” before the click. |
| C36 | Community sign-up and AI workspace approval are separate | VERIFIED | A/P; public policy and routes | Keep, preferably outside the Hero. |
| C37 | Consultation does not require login | PARTIALLY_VERIFIED | Public route is designed without auth, but intake is currently disabled | “접수가 활성화되면 로그인 없이 신청할 수 있습니다.” |
| C38 | Current consultation view collects no PII and offers no invented alternate contact | VERIFIED | LIVE browser and config; C | Keep in the disabled state, but it confirms there is currently no conversion path. |

**Claim count: 38 total — VERIFIED 10 · PARTIALLY_VERIFIED 13 · UNVERIFIED 8 · MARKETING_ONLY 7.**

## 2. Buyer comprehension by persona

| Persona | 5 seconds | 15 seconds | 30 seconds | 60 seconds / consultation reason |
|---|---|---|---|---|
| SME owner | “Custom AI agent builder” is visible, but no concrete office workflow or outcome | Sees pilot/build/operate, still no price, duration, eligibility or deliverable example | Internal technical cases do not answer “what changes in my business?” | Learns the careful limits, then cannot contact anyone. Consult trigger should be “bring one repetitive task; receive a fit/scope decision,” without ROI promises. |
| CTO / dev lead | Understands integration, permissions and verification | Sees a credible staged engineering engagement | G7 evidence is relevant, but unexplained J/A codes and Provider jargon make proof hard to audit | Will consult to assess API/identity/data boundaries and acceptance evidence; needs architecture/evidence detail one click away. |
| SI / operations lead | Sees build-to-operation language | Recognizes governance and handoff intent | Wants responsibility split, change approval, rollback/backup, monitoring and support boundaries | Will consult only if deliverables and operator/customer responsibilities are explicit; SLA and long-term operation are correctly not promised. |
| AI-curious non-expert | “AI that works, not only chats” is attractive | Abstract system/permission language starts to overload | Three offers are understandable, but proof is technical and cases lack screenshots | Needs one everyday before/after workflow and a plain suitability checklist; consultation should promise scope clarification, not guaranteed automation. |

The mobile Home is technically contained (`390px` viewport, `scrollWidth=390`) but the live document is about `9,226px` tall. It reaches the first case only after substantial process text, so mobile comprehension is a density/priority problem, not an overflow problem.

## 3. Technical language → customer value

| Phrase | Customer value | Home decision |
|---|---|---|
| request worktree | One request gets an isolated change area, reducing cross-request collisions | **MOVE TO DETAIL**; Home: “요청별 격리된 작업 공간” only if needed |
| persisted events | Progress/history survives reconnect and supports review | Keep the value: “진행 이력이 남고 다시 연결해 확인” |
| native session | Execution context can continue across follow-up work | **MOVE TO DETAIL**; Home: “같은 요청에서 후속 지시” |
| Provider | Which AI execution backend handled work | Remove from Home; say “AI 실행 도구” only when buyer choice matters |
| rollback | Failed release can be withheld/recovered using a recorded backup | Home: “실패한 변경은 반영하지 않고 복구 기준을 남김”; mechanics to detail |
| tests | Evidence that agreed behavior still works | Home: “합의한 성공 기준을 자동 검사와 실제 화면으로 확인” |

Also move `J1–J10`, `A1–A10`, `raonslab-product`, `raonslab-ai-workspace`, `AI_GCS`, `AgentOpt`, request IDs, event sequence numbers and shell/runtime names off Home. `GNUBOARD7 7.0.11` belongs in the case detail badge, not the general value proposition.

## 4. Differentiation without competitor fiction

Do not say ChatGPT, Claude, Copilot or agent builders “only answer” or “cannot execute.” Current official material describes connected tools/actions for [ChatGPT](https://openai.com/index/introducing-chatgpt-agent/), remote integrations/actions for [Claude](https://www.anthropic.com/news/integrations), asynchronous code changes and review flows for [GitHub Copilot](https://docs.github.com/en/copilot/how-tos/copilot-on-github/use-copilot-agents/overview), and visual multi-step workflows/evaluation for [agent builders](https://developers.openai.com/api/docs/guides/agent-builder). Their exact capabilities will keep changing.

RAON’s defensible distinction is the **service responsibility around the tool**, bounded by the evidence currently available:

| Dimension | Safe RAON distinction | Evidence status |
|---|---|---|
| Actual execution | Connect a scoped task to an existing system and permission boundary, rather than sell a model chat | Demonstrated internally on G7; customer fit must be assessed |
| Long-running work management | Request ownership, stored state/events/results, reconnect and same-request follow-up | Verified on the RAON Hub closed loop; no uptime/retention SLA |
| Verification/evidence | Define success, run targeted checks and report pass/fail/unverified with source/version | Strong internal release evidence; customer report specimen absent |
| Existing-system connection | Prefer the customer system’s API/extension contract and minimize core edits | Verified on one G7 stack; not a universal integration guarantee |

Positioning sentence: **“도구를 하나 더 판매하는 것이 아니라, 고객 시스템의 권한 안에서 한 업무가 실행되고 검증되도록 연결·구축·증거화하는 서비스.”**

## 5. Case-study truth and visual proof

| Case | Problem | Work actually evidenced | Actual visual proof candidate | Verified result | Transferable customer use |
|---|---|---|---|---|---|
| MOBILE_STOCK | Multiple AI tools can blur ownership and verification | **Not evidenced in this baseline** beyond repeated marketing/docs statements | **None currently eligible**; no source-owned screenshot or immutable result exists here | No publishable verified result | A hypothesis for multi-tool engineering teams only; remove/defer until its own repository, version, tests and sanitized screenshots are authoritative |
| RAON Hub | Add an AI workflow without breaking an existing CMS’s member/board/search/permission surface | Product Home + consultation module + approved-user AI workspace through G7 extension boundaries | Public Home workflow/case rail; public cases page; public community/search/auth preservation screens | E J1–J10, A1–A10, mobile widths, persisted follow-up, zero core patches; commercial load/long-term/customer operation explicitly unverified | Evidence for **one G7 integration pattern**, not proof that every legacy system can be integrated |

Both are internal self-build narratives. Neither is a customer success story; no customer, delivery, savings, revenue, SLA or production-scale outcome may be implied.

### Safe screenshot candidates

| Candidate | Source / suitability | Required crop or masking |
|---|---|---|
| Home Hero + “agent works” flow | LIVE public Home; strong first visual but still illustrative | Crop browser chrome/address/IP; no request IDs/usernames/secrets. Caption “설명 도식,” not execution proof. |
| RAON Hub case evidence rail | LIVE Home `#rh-cases`; shows problem/build/verified/unverified discipline | Crop to RAON Hub card; exclude MOBILE_STOCK until evidenced; no IP/browser chrome. |
| Public case page RAON Hub section | [Public cases](http://203.245.29.156:58770/page/cases); useful because it states internal/not-customer and limits | Crop navigation clutter and HTTP address; preserve limitation text. |
| Public community/search/auth preservation | Existing files referenced by E: `browser-community-390.png`, `browser-search-390.png`, `browser-login-390.png`, `browser-register-390.png` | Use only synthetic/public content. Mask usernames, emails, post IDs, notification counts and any private text; crop IP/address bar. |
| Public Q&A guidance | Public `/board/questions`; demonstrates native G7 content flow | Use only seeded guidance posts; crop usernames, internal IDs, dates if identifying, editor/admin controls and unrelated user content. |

Do **not** publish or newly capture `browser-ai-request-390.png`, admin screenshots, AI_GCS/AgentOpt pages, terminals or private request details. Masking is not enough without a separately approved synthetic proof surface: request IDs, user identity, source paths, shell commands, provider/session names, private prompts/results and infrastructure addresses can remain inferable. No eligible MOBILE_STOCK screenshot exists in the current evidence set.

## 6. CTA and conversion loop

- Primary CTA: visually strong and repeated, but it scrolls to a disabled panel. The click promise (“구축 상담”) and outcome (“준비 중, 대체 연락처 없음”) conflict.
- Secondary CTA: “자체 구현 사례” is the right fallback, but the section is text-heavy and contains one unevidenced case before the stronger RAON Hub case.
- Route/form readiness: config route is live and returns `intake_enabled=false`; the form/API implementation exists but is intentionally fail-closed. No write probe was performed.
- Reasons to click: the Home asks for recurring task, systems/data and success criteria—good qualification prompts—but does not say what the buyer receives after a consultation.
- Competing actions: two stacked site nav systems, Home subnav, community/notice/Q&A/search, login/register and policy menus dilute the sales action. Community membership is visually adjacent but does not grant AI access or create a lead.
- Real intake mismatch: the fixed public ingress is HTTP; canonical/OG emit `127.0.0.1:18770`; sitemap index emits `http://localhost`; no approved production domain/TLS, privacy contact, consent version/copy, retention notice or alternative contact is active.
- Legal/SMTP truth: do not claim privacy compliance, a legal entity/contact, response time or contractual terms. Consultation mail is not used; C records administrator-screen review only. No customer-facing SMTP delivery or SLA is verified.
- Public Q&A is not a safe substitute for consultation: it is public and explicitly forbids personal, credential and contract data.

Conversion verdict: **the public review site can inform and pre-qualify, but cannot currently capture a private business lead.** Until the production/legal gate is complete, change the Hero outcome before the click to “현재 온라인 상담 준비 중” and make the evidence/case path primary. Once approved HTTPS intake is live, restore consultation as primary.

## 7. Content disposition

### KEEP — 5

1. “업무 한 개 실증” as the entry offer.
2. Explicit “internal self-build, not customer delivery” disclosure.
3. Pass/fail/unverified evidence discipline and unverified-limit callouts.
4. Existing-system contract and permission-boundary principles.
5. The three consultation preparation inputs: current task, connected system/data, success criteria.

### REMOVE / COMPRESS — 8

1. Remove MOBILE_STOCK from Home until authoritative proof exists, or reduce it to an evidence-pending internal note off Home.
2. Replace “대화형 AI는 답만 준다” competitor generalization with a customer integration problem.
3. Compress the three problem cards into one before/after statement.
4. Merge the three service cards and five process steps; they repeat scope → build → verify → improve.
5. Remove unexplained J/A codes and module/tool names from Home.
6. Shorten the five technology principles to three buyer assurances: access, evidence, recovery.
7. Remove “언제든,” “모두,” “만” absolutes unless retention/process guarantees are contracted.
8. Hide or demote community/shop/account navigation on the sales path where the template contract permits; it is not the conversion goal.

### VISUALIZE — 5

1. One actual, sanitized request card: task → permission → status → result → evidence.
2. “What you buy” comparison: pilot / build / operate with inputs, deliverables and explicit exclusions.
3. RAON Hub before/after system map: unchanged G7 core + two extension modules.
4. Verification receipt: source version, checks passed/failed/unverified, remaining limits.
5. Consultation-fit checklist: API/owner/data/permission/success criterion ready or unknown.

### MOVE TO DETAIL — 5

1. Worktree/session/provider/event persistence mechanics.
2. J1–J10 and A1–A10 definitions and raw validation commands.
3. GNUBOARD7/module versions and core-diff inventory.
4. Backup/rollback procedure and infrastructure topology.
5. Full policy, retention, ownership and administrator-operation boundaries.

## 8. Recommended seven-section vNext sales flow

1. **Hero / purchase truth** — one-task-to-execution outcome, one evidence visual, state-aware CTA.
2. **What you can buy** — pilot, custom build, operate/improve; inputs, deliverables and exclusions.
3. **Is this your task?** — 3–4 concrete but non-metric workflow patterns and a fit checklist.
4. **How execution is controlled** — connect → permission → run → verify → approve/recover.
5. **Proof** — RAON Hub first, with real public visuals and dated evidence; MOBILE_STOCK only after proof gate.
6. **Why RAON / why not DIY alone** — responsibility for integration, long-running request management, evidence and system fit; no competitor incapability claims.
7. **Consultation + trust footer** — what happens next, what to prepare, current intake state, limitations and approved legal/contact facts.

Preferred Hero concept:

> **반복 업무 하나를, 기존 시스템에서 실제로 실행되는 AI 흐름으로.**
>
> 연결할 데이터와 권한을 먼저 정하고, 요청·진행·결과와 테스트·화면 검증을 함께 남깁니다.
>
> Primary when intake is live: **업무 한 개 상담하기** · Secondary: **검증된 구현 사례 보기**
>
> Current disabled state: make **검증된 구현 사례 보기** primary and label **온라인 상담 준비 상태 확인** before the click.

The right-side visual should be a sanitized RAON Hub request/evidence receipt, not an abstract decorative pipeline. Caption it “내부 RAON Hub 검증 예시” and show an explicit “미검증” row.

## 9. P0 / P1 implementation scope (recommendation only)

### P0 — truth and conversion safety

1. Resolve the CTA state mismatch: either complete approved HTTPS/domain/privacy/contact intake readiness, or demote consultation and declare “currently unavailable” before the click.
2. Remove/defer MOBILE_STOCK material claims until its authoritative repository/version/test/visual evidence is linked.
3. Replace competitor incapability language and qualify universal/absolute technical promises to the demonstrated RAON Hub scope.
4. Repair public identity truth before customer launch: production domain/TLS, canonical/OG/sitemap host, approved operator/privacy/contact/retention facts. Do not invent SMTP or response-time claims.
5. Reconcile public `/page/technology` (“AI 연동 확장은 현재 보류”) with Home’s active AI workspace/closed-loop claims; one approved statement must govern both.

### P1 — evidence-led selling

1. Reorder and compress Home into the seven-section flow; target one idea per mobile screen and materially shorten the current 9,226px path.
2. Add sanitized actual-product visuals and a dated evidence receipt; keep private AI_GCS surfaces excluded.
3. Publish a plain deliverable/specimen outline for pilot/build/operate, including customer responsibilities and exclusions.
4. Put RAON Hub before any secondary case and move technical names/raw check codes to the case detail.
5. Make the live consultation state, next step and expected intake fields explicit; when intake is closed, use a non-PII evidence path rather than pretending Q&A is private intake.

## 10. V1 vs proposed vNext score (1–5)

| Criterion | Current V1 | Proposed vNext | Why |
|---|---:|---:|---|
| 5-second comprehension | 3 | 5 | Category is visible; concrete first purchase/outcome is not |
| Business clarity | 3 | 5 | Three stages exist, but deliverables and qualification are diffuse |
| Differentiation | 3 | 4 | Evidence discipline is promising; competitor framing is overbroad |
| Visual explanation | 4 | 5 | Good diagrams, but mostly illustrative rather than actual proof |
| Product evidence | 2 | 4 | Strong RAON documents, almost no public actual-product proof; MOBILE_STOCK gap |
| CTA focus | 2 | 5 | Primary action currently dead-ends |
| Mobile readability | 3 | 4 | No overflow; excessive length and repeated structures |
| Trust | 3 | 4 | Honest limitations help; HTTP/local metadata/dead CTA and case gap hurt |
| Text density | 2 | 4 | Important evidence is buried in a long page |
| Tech/business balance | 3 | 5 | CTO-friendly detail dominates buyer outcome and next step |

## 11. Q1–Q6, plainly

**Q1. What is being sold?** A scoped service to connect one repeatable business task to an existing system, then build and verify an AI execution flow. It is not a packaged SaaS SKU yet.

**Q2. What can a buyer buy now?** The site describes a one-task pilot, custom build and later operation/improvement. Price, duration, SLA and standard contract are intentionally not established.

**Q3. Who is it for?** Organizations that can name a task, system owner, data/permission boundary and success criterion. The Home is least effective for an SME buyer who only has a general “use AI” goal.

**Q4. Why should they consult?** To determine feasibility, connection/permission gaps, a bounded pilot and acceptance evidence—not to receive a guaranteed ROI or universal integration promise.

**Q5. Why RAON rather than a general AI tool or agent builder?** The defensible answer is implementation responsibility around the tool: existing-system connection, request ownership/continuity, evidence and release discipline. The current evidence proves this internally on RAON Hub, not superiority over named products or external customer success.

**Q6. Is the site ready to convert a real customer?** No. It is ready for review and education, but the private consultation loop is closed and no approved alternative contact exists. HTTP/domain/metadata/legal intake truth must be resolved before calling it a live acquisition funnel.

## Blockers

1. No authoritative MOBILE_STOCK source, version, test record or safe screenshot in this baseline.
2. Consultation intake is disabled and no approved alternate private contact exists.
3. Public review is HTTP; production domain/TLS and canonical/OG/sitemap identity are unresolved.
4. Legal operator/privacy contact/consent/retention facts are not approved; SMTP/customer reply is not verified.
5. Public technology-page wording conflicts with the active-workspace/closed-loop story.
