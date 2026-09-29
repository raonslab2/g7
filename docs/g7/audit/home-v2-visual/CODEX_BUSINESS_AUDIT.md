# Home V2 business offer / proof / CTA audit

- Audit: B — business truth, offer clarity, conversion path
- Request: `req_5082dbcf0833480f91bbe9c6049065b1`
- Date: 2026-09-29 (Asia/Seoul)
- Mode: read-only product/runtime/DB audit; audit-document write only
- Method: expert heuristic review, not observed-user conversion research

## Executive judgment

The public home now tells a credible, bounded story: RAON sells implementation of a real AI workflow inside an existing business system, starts with one recurring task, and expands only after verification. The three offers are understandable, RAON Hub demonstrates the relevant agent workflow better than a generic CMS-installation claim, and MOBILE_STOCK is presented inside its approved paper/example/read-only boundary.

The surface is not conversion-ready because consultation is intentionally closed and there is no approved private alternative. That is an honest launch blocker, not a reason to turn Q&A into a lead channel. While intake remains closed, the smallest useful follow-up is to strengthen the case-to-offer bridge and make every detail-page CTA describe guidance/status before the click. A separate static SEO fallback defect should be corrected so the crawler surface does not expose both open and closed hero actions.

## Baseline and boundary verification

| Item | Expected | Observed | Verdict |
| --- | --- | --- | --- |
| Request worktree source | `664c36ba40ed8bf6723d7776ab08fa1ac8a8720e` | Exact HEAD; detached audit worktree, initially clean | confirmed |
| Runtime source | same SHA | `/home/mrdev/git/g7` HEAD exact; `main...origin/main`, clean | confirmed |
| Product module | `raonslab-product` 0.5.0 | source and active runtime manifests both 0.5.0 | confirmed |
| Public surface | `http://203.245.29.156:58770/` | HTTP 200; standard desktop Chrome 1440×900 and mobile Chrome 390×844 rendered | confirmed |
| Native cases Page | id 8 / version 2 | public API `GET /api/modules/sirsoft-page/pages/cases` returned `id=8`, `slug=cases`, `current_version=2`; public page reflects both current cases | confirmed |
| Consultation | closed | config API returned `intake_enabled=false`; human browser showed “접수 준비 중” / “Intake not open yet,” no form fields, and no PII collection | confirmed |
| Concurrent surface work | none expected | the only RUNNING V2 requests were this read-only Audit B and `req_21813c0a771d41a083bdfb6b7e3521ae`, titled read-only Audit A; no implementation/deployment request or build/update/migration process was found | confirmed at audit time |

No MOBILE_STOCK checkout, build, test, or new audit was performed. This review only used the already-bound 0.5.0 provenance and `PASS_PUBLIC` decision.

Temporary screenshots and DOM captures were used for inspection outside the repository and are not audit deliverables.

## Readiness

| Dimension | Judgment | Reason |
| --- | --- | --- |
| `VISUAL_READINESS` | **READY_WITH_FIX_NEXT** | The normal human surface is coherent at desktop and 390px, the first screen identifies the offer, and product proof starts immediately after it. Audit A owns deeper visual judgment. The SEO/static representation still exposes both hero action variants and an empty consultation panel. |
| `PROOF_READINESS` | **READY_WITH_DECLARED_LIMITS** | RAON Hub and MOBILE_STOCK claims match the evidence available to this audit, and unverified/customer-scale limits are visible. The remaining weakness is interpretation: MOBILE_STOCK is not explicitly translated into the bounded implementation/verification capability it proves. |
| `CONVERSION_READINESS` | **KNOWN_LAUNCH_BLOCKER** | Private consultation is closed, no approved alternative contact exists, and Q&A is correctly public. The site can educate and qualify, but cannot presently accept a private business lead. |

## Time-to-understanding heuristic

These are expert judgments about likely comprehension, not measured conversion results.

| Time | Nontechnical business buyer | CTO / SI lead |
| --- | --- | --- |
| 5 seconds | Understands that RAON builds business-specific AI agents around one recurring task in an existing system. “RAON Hub” versus “RAON Agent Factory” adds slight brand interpretation cost, but the sold work is still legible. | Understands that the offer is not a foundation model: it is scoped integration, execution, verification, and operation inside an existing system. |
| 15 seconds | Sees two things RAON built itself. RAON Hub reads as the relevant agent case; MOBILE_STOCK can momentarily look like a second line of business, but “모의투자 · 자체 제품 · 예시 데이터” and the no-live-order statements prevent a stock-service claim. | Sees a request → progress → result → same-request follow-up workflow, extension-based integration, and a paper-trading boundary. Neither case is presented as a customer delivery. |
| 30 seconds | Can distinguish one-workflow validation, custom build, and operation/improvement, including what the buyer supplies and receives. The page does not look like a generic website package or AI-model sale after the “RAON이 맡는 일” section. | Can entrust a bounded API/extension integration with permission design, an observable request/result surface, automated/on-screen checks, change approval, and re-verification. SLA, scale, and long-term external operation remain explicitly outside proof. |
| 60 seconds | The honest next action is to review cases, process, and consultation guidance and prepare the three requested facts. There is no private submission path while intake is closed. | Can continue technical qualification through cases, service, engineering principles, and access policy. Procurement/intake cannot start on this surface yet. |

## Offer clarity

| Offer | Input shown | Output shown | Customer preparation shown | Likely misunderstanding and judgment |
| --- | --- | --- | --- | --- |
| One-workflow validation | one recurring task and its current process | one working flow plus verification report | task owner and agreed success criteria | Clear that this is a bounded pilot, not a full transformation. The detail page adds scope and permission boundaries. **KEEP.** |
| Custom Agent build | the flow confirmed in the pilot | system integration plus request/progress/result UI | API or extension points and access details | Clear that RAON builds an operational workflow around the customer's system. “Agent” is not sold as a model; the permission/approval language controls that misunderstanding. **KEEP.** |
| Operation / improvement | operating records and failures | rule changes and re-verification | agreed operating scope and a change approver | Clear as post-build change work. The service detail correctly withholds SLA, zero-downtime, and long-term stability promises. **KEEP.** |

The sequence is strongest on `/page/service`, where the three stages are explicitly numbered. The home table remains concise without losing input/output/preparation.

## Proof and claim truth

### RAON Hub

The useful proof is not “we installed a CMS.” The public surface states that RAON reused G7 membership, boards, search, and permissions; added the product and AI workspace as two extension modules; kept request, progress, result, and follow-up on the same request; and made zero G7 core changes. That supports the offer to add a permissioned, observable agent workflow to an existing system.

The public case also says what the evidence does not support: external-customer long-term operation and large commercial load. No customer count, delivery count, revenue, ROI, or scale claim appears.

### MOBILE_STOCK

The approved public scope is preserved:

- in-house mobile paper-trading product;
- example data;
- new account through first simulated KRW buy and holding update;
- local paper broker only, live trading off;
- optional KIS connection limited to read-only market data, with no order path.

The public pages do not infer AI analysis, live trading, real KIS quote verification, investment performance/advice, customer delivery, deployed-runtime verification, full browser E2E, or frontend typecheck. This matches evidence commit `7063bed01e287bbaeaa5b7e3f8c555b39b19ec70` and independent review `req_82fc148ab10a4d4195d6f08eb7ad7aa1` (`PASS_PUBLIC`, limited scope).

MOBILE_STOCK should remain supporting proof of tightly scoped product implementation, hard external-action boundaries, and verification discipline. It must not be presented as proof that MOBILE_STOCK itself has AI analysis or as proof of live financial-system operation.

## CTA and hierarchy inventory

| Surface | Actual destination | Truth before click while closed | Judgment |
| --- | --- | --- | --- |
| Desktop product nav “구축 상담 / 접수 준비 중” | `/#rh-consult` | explicit closed state | KEEP |
| Closed-state home hero | `#rh-case`, `#rh-process` | does not invite a closed form | KEEP |
| Home consultation destination | `#rh-consult` | closed panel says intake waits for privacy/security approval, offers no other contact, and collects no PII; retry only | KEEP |
| `/page/cases` “AI 에이전트 구축 상담” | `/#rh-consult` | label implies an available action; the desktop nav supplies status, but the mobile product nav is intentionally hidden | FIX_NEXT |
| `/page/service` “AI 에이전트 구축 상담” | `/#rh-consult` | same issue | FIX_NEXT |
| `/page/contact` “구축 상담 화면으로” | `/#rh-consult` | surrounding copy says the home area owns status, but the button itself does not | FIX_NEXT |
| `/page/contact` Q&A route | `/board/questions` | explicitly described as public; users are warned not to post PII, credentials, confidential material, quotes, or contracts | KEEP; never recast as private intake |

The information/policy hierarchy is sound: about/service/cases/technology/FAQ/contact are “정보,” while privacy/terms/AI-workspace/open-source/refund are “정책.” Home already orders proof → offer → fit → cases → process → consultation. The weak link is after `/page/cases`: its primary next action jumps directly to a closed consultation rather than continuing through service/process guidance.

## Findings

### BIZ-01

- **ID:** BIZ-01
- **Actual observation:** The hero states business-specific AI-agent build/execution/verification/operation, and the lede narrows it to one recurring task in the customer's existing systems. The fit section says RAON designs the permission, approval, and verification flow regardless of the AI tool.
- **Customer problem or misunderstanding:** A first-time buyer could briefly wonder whether RAON Hub is a website/CMS agency or whether RAON sells an AI model. By 30 seconds the current copy resolves both interpretations.
- **Evidence location:** public `/` hero and `#rh-fit`; `resources/lang/ko.json`; `resources/extensions/home-product.json`.
- **Smallest fix:** none.
- **Contract impact:** none.
- **Verification:** normal human Chrome at 1440×900 and 390×844; Korean SEO text cross-check.
- **Verdict:** **KEEP**

### BIZ-02

- **ID:** BIZ-02
- **Actual observation:** All three offers expose input, deliverable, and customer preparation on home; `/page/service` adds audience, limits, and the numbered pilot → build → operation sequence.
- **Customer problem or misunderstanding:** The buyer can tell what must be supplied and received, and is not led to expect a full-system project, guaranteed integration, price/period/ROI, or SLA.
- **Evidence location:** public `/#rh-services`; public `/page/service`; `docs/home-productization-0.5.0.md` lines 8–16.
- **Smallest fix:** none.
- **Contract impact:** none.
- **Verification:** visible-text and anchor review on the live human and server-rendered surfaces.
- **Verdict:** **KEEP**

### PROOF-01

- **ID:** PROOF-01
- **Actual observation:** RAON Hub is directly connected to the agent offer through request → progress → result → same-request follow-up and extension-based integration. MOBILE_STOCK is introduced as a second working product, but its relevance to the agent-building offer is left for the buyer to infer.
- **Customer problem or misunderstanding:** A nontechnical buyer may read MOBILE_STOCK as an unrelated stock-service promotion; a CTO may understand its engineering value but still not know why it belongs in the agent offer proof set.
- **Evidence location:** public `/#rh-proof`, `/#rh-case`, `/page/cases`; `resources/assets/cases/PROVENANCE.json` lines 22–36.
- **Smallest fix:** add one short “이 사례가 입증하는 구축 역량” line to each home case: RAON Hub = extending an existing system with a permissioned request/result/follow-up workflow; MOBILE_STOCK = implementing one bounded flow and automatically enforcing/verifying a no-live-order boundary. Do not call MOBILE_STOCK an AI-analysis case.
- **Contract impact:** module-owned home Layout Extension and ko/en strings only; no core, route, API, dependency, Page-body duplication, or consultation-state change.
- **Verification:** focused home DOM/copy assertions at 390px and desktop; existing MOBILE_STOCK evidence-binding forbidden-claim checks.
- **Verdict:** **FIX_NEXT**

### PROOF-02

- **ID:** PROOF-02
- **Actual observation:** Both cases are labeled in-house rather than customer deliveries. RAON Hub discloses unverified external-customer operation and scale. MOBILE_STOCK preserves paper/example/read-only boundaries and lists AI analysis, full browser regression, deployment, and typecheck as unverified or excluded.
- **Customer problem or misunderstanding:** Material overclaim risk is controlled; no unsupported customer-scale, live-trading, investment, or AI-analysis inference is required to understand the cases.
- **Evidence location:** public `/` and `/page/cases`; `docs/home-productization-0.5.0.md` lines 50–62; `resources/assets/cases/PROVENANCE.json`.
- **Smallest fix:** none; preserve these disclaimers if copy is shortened.
- **Contract impact:** none.
- **Verification:** public copy matched to the existing provenance and PASS_PUBLIC scope; MOBILE_STOCK repository was not re-audited.
- **Verdict:** **KEEP**

### CTA-01

- **ID:** CTA-01
- **Actual observation:** The standard human browser surface fail-closes correctly: the hero favors case/process, the desktop nav says “접수 준비 중,” and the consultation panel states that no alternate contact is offered and no personal data is collected. Q&A is explicitly public.
- **Customer problem or misunderstanding:** The main home path does not pretend intake is open and does not misroute confidential lead information into Q&A.
- **Evidence location:** public `/`; config API `/api/modules/raonslab-product/consultations/config`; public `/page/contact`; `resources/lang/ko.json` lines 118–120 and 187.
- **Smallest fix:** none.
- **Contract impact:** none.
- **Verification:** live config `intake_enabled=false`; full-page desktop/mobile human-browser render.
- **Verdict:** **KEEP**

### CTA-02

- **ID:** CTA-02
- **Actual observation:** Native detail pages use static CTA labels that sound open before click. `/page/cases` also skips from proof directly to consultation instead of carrying the reader through service/process; on mobile the desktop product-nav status badge is absent by design.
- **Customer problem or misunderstanding:** A qualified buyer can expect a form or private contact after tapping “AI 에이전트 구축 상담,” then discover only at the destination that intake is closed. The intended Home → cases → process → consultation-guidance sequence is not explicit.
- **Evidence location:** live human DOM for `/page/cases`, `/page/service`, `/page/contact`; `docs/home-productization-0.5.0.md` lines 20–33; public Page API confirms cases id 8/version 2.
- **Smallest fix:** update Page-owned next actions through `PageService`: cases primary → “서비스·도입 절차 보기” (`/page/service`), secondary → “상담 접수 상태와 준비사항 보기” (`/page/contact`); service primary → the same contact-guidance label; contact → “홈에서 현재 상담 상태 확인” (`/#rh-consult`). Keep Q&A separately labeled public.
- **Contract impact:** native Page content only, producing new Page versions through `PageService`; no raw SQL, no module copy of Page bodies, no taxonomy change, and no core change.
- **Verification:** Page API id/version increment and public content; 390px/desktop CTA text and destination checks; closed config remains false and no form is shown.
- **Verdict:** **FIX_NEXT**

### CTA-03

- **ID:** CTA-03
- **Actual observation:** The standard human SPA shows only the correct closed hero actions and a populated closed consultation panel. The public SEO/static response (`X-SEO-Cache: HIT`) strips the custom `data-rh-intake-show` attributes, emits both open and closed hero action groups, and leaves `.rh-consult-panel` empty.
- **Customer problem or misunderstanding:** Search crawlers and other static consumers can index an “AI 에이전트 구축 상담” action even though intake is closed, weakening the otherwise honest CTA contract.
- **Evidence location:** public `/` standard-human DOM versus SEO response; `resources/extensions/home-product.json` lines 73–143; `resources/css/main.css` lines 220–224; `docs/info-policy-0.4.1.md` line 31 documents the distinct bot rendering surface.
- **Smallest fix:** make the module's server/static default explicitly closed using renderer-preserved classes/standard props and include a static closed-panel fallback that client JS replaces after config resolution. Do not change the core SEO renderer or activate intake.
- **Contract impact:** module-owned Layout Extension, CSS/JS, ko/en strings, changelog/version only; no core change and no API/dependency impact.
- **Verification:** compare standard human Chrome and Googlebot/SEO HTML with config closed: one closed hero action set, closed status text before click, closed panel copy present, zero form fields.
- **Verdict:** **FIX_NEXT**

### CONV-01

- **ID:** CONV-01
- **Actual observation:** Private consultation intake is disabled and no approved email, phone, address, hours, waitlist, or other private path is published.
- **Customer problem or misunderstanding:** A ready buyer cannot submit a confidential requirement or start qualification. The site currently educates but does not convert a private lead.
- **Evidence location:** consultation config API; public `/#rh-consult`; public `/page/contact`; `modules/_bundled/raonslab-product/AGENTS.md` fail-closed boundary.
- **Smallest fix:** none within this audit. Keep the honest closed state and guidance. Activation requires its separately approved privacy/security readiness process and is deliberately not recommended here.
- **Contract impact:** none from this audit.
- **Verification:** config remains `intake_enabled=false`; no PII inputs; no invented contact path; Q&A remains public.
- **Verdict:** **KNOWN_LAUNCH_BLOCKER**

## Exactly one minimal change bundle

**Bundle: closed-state proof-to-guidance alignment**

1. Add the two evidence-bounded “what this proves” bridge lines on the module-owned home case presentation (PROOF-01).
2. Revise only the Page-owned next-action labels/order through `PageService` so cases lead to service/process and all consultation links promise status/guidance rather than an open form (CTA-02).
3. Make the module-owned SEO/static fallback closed by default and include the existing no-PII/no-alternate-contact explanation (CTA-03).

This is one copy/state-alignment bundle, not a redesign. It does not add sections, screenshots, contact details, a waitlist, a new lead channel, or an intake form. It leaves taxonomy and policy/detail grouping unchanged. It requires no G7 core change: home/nav/fallback remain in the existing `raonslab-product` Layout Extension and assets; detail copy remains native Page content and must be versioned through `PageService`.

The bundle cannot change `CONVERSION_READINESS` to ready while consultation remains closed. Its purpose is to make the closed journey maximally truthful and useful.

## KEEP list

- Keep the one-task-first positioning and the input/output/customer-preparation structure.
- Keep RAON Hub proof centered on request → progress → result → same-request follow-up, permission boundaries, extension modules, and zero core changes.
- Keep “internal build, not customer delivery” and the explicit external-operation/scale gaps.
- Keep all MOBILE_STOCK paper/example/read-only and excluded-claim boundaries.
- Keep the fail-closed consultation panel and do not invent a private contact route.
- Keep Q&A visibly public and separate from consultation.
- Keep information and policy as separate taxonomy groups, with detailed claims on native Pages rather than duplicating Page bodies in module source.

## Source and evidence references used

- Live public human surface: `http://203.245.29.156:58770/`
- Live detail pages: `/page/service`, `/page/cases`, `/page/contact`, `/page/faq`, `/page/technology`, `/page/ai-workspace-policy`
- Live consultation config: `/api/modules/raonslab-product/consultations/config`
- Live native Page API: `/api/modules/sirsoft-page/pages/cases`
- `modules/_bundled/raonslab-product/module.json`
- `modules/_bundled/raonslab-product/AGENTS.md`
- `modules/_bundled/raonslab-product/resources/extensions/home-product.json`
- `modules/_bundled/raonslab-product/resources/extensions/product-nav.json`
- `modules/_bundled/raonslab-product/resources/css/main.css`
- `modules/_bundled/raonslab-product/resources/lang/ko.json`
- `modules/_bundled/raonslab-product/resources/lang/en.json`
- `modules/_bundled/raonslab-product/docs/home-productization-0.5.0.md`
- `modules/_bundled/raonslab-product/docs/sales-evidence-map.md`
- `modules/_bundled/raonslab-product/docs/info-policy-0.4.1.md`
- `modules/_bundled/raonslab-product/resources/assets/cases/PROVENANCE.json`
- `docs/extension/layout-extensions.md`
- `modules/_bundled/sirsoft-page/AGENTS.md`

## Change confirmation

This audit changed no product source, module source, native Page content, runtime files, database rows, configuration, service state, consultation state, or `main`. It performed no push, deployment, build, migration, dependency install, full test suite, Agent.Tools validation, or platform audit. The only repository change is this audit Markdown file in the request worktree.
