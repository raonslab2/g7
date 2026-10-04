// AI_GCS V2 실제 배포 Web 빌드(web f6dd9a92)를 로컬에서 띄우고, /api/v1 응답만
// 홍보용 안전 데모 데이터로 대체해 캡처한다. 운영 API·DB·자격증명에는 접근하지 않는다.
// 데모 데이터의 구조와 수치는 2026-10-04 실제 검증된 CODEX+CLAUDE 위임 실행
// (ai_gcs_v2 docs/control-plane-20261004/evidence/closure.json)을 따른다. ID 는 데모 값이다.
import http from "node:http";
import fs from "node:fs";
import path from "node:path";
import { chromium } from "playwright";

const WEB = path.resolve(process.argv[2] ?? "web");
const OUT = path.resolve(process.argv[3] ?? "captures");
fs.mkdirSync(OUT, { recursive: true });

const server = http.createServer((req, res) => {
  const url = new URL(req.url, "http://x");
  let file = path.join(WEB, url.pathname);
  if (!file.startsWith(WEB) || !fs.existsSync(file) || fs.statSync(file).isDirectory()) file = path.join(WEB, "index.html");
  const type = file.endsWith(".js") ? "text/javascript" : file.endsWith(".css") ? "text/css" : "text/html";
  res.writeHead(200, { "content-type": type });
  fs.createReadStream(file).pipe(res);
});
await new Promise((r) => server.listen(0, "127.0.0.1", r));
const BASE = `http://127.0.0.1:${server.address().port}`;

// ── 데모 시각: 2026-10-04 15:20 KST ──
const NOW = Date.parse("2026-10-04T06:20:00Z");
const at = (min) => new Date(NOW - min * 60_000).toISOString();
const SHA = (seed) => (seed.repeat(40)).slice(0, 40);

const projects = [
  { project_id: "g7", name: "G7 · RAON Hub", description: "RAON Agent Factory 공개 사이트", available: true, default_branch: "main", lifecycle: "ACTIVE", providers: ["CODEX", "CLAUDE"] },
  { project_id: "ai-gcs", name: "AI_GCS V2", description: "모바일 우선 작업 지시 Web", available: true, default_branch: "main", lifecycle: "ACTIVE", providers: ["CODEX", "CLAUDE"] },
  { project_id: "mobile-stock", name: "MOBILE_STOCK", description: "모의투자 앱 Symphony", available: true, default_branch: "main", lifecycle: "ACTIVE", providers: ["CODEX", "CLAUDE"] },
];
const providers = [
  { provider: "CODEX", profiles: [{ profile: "default", label: "CODEX", available: true }] },
  { provider: "CLAUDE", profiles: [{ profile: "default", label: "CLAUDE", available: true }] },
];

const PARENT_PROMPT = [
  "협업 진행 화면을 실제 Provider로 검증해줘.",
  "API 계약은 CODEX, 모바일 UX는 CLAUDE가 검토하고,",
  "서로의 결과를 교차 검토한 뒤 같은 대화에서 최종 결과를 정리해줘.",
].join(" ");

const child = (key, role, provider, state, extra = {}) => ({
  child_request_id: `req_demo_${key}`, task_key: key, role, attempt: 1, provider, profile: "default",
  state, dependencies: [], evidence_available: state === "COMPLETED", validation_summary: "", result_summary: "", blocker: "", ...extra,
});

function parentDetail(phase) {
  const running = phase === "parallel";
  const children = running
    ? [
        child("backend-review", "API 계약 검토", "CODEX", "RUNNING"),
        child("ux-review", "모바일 UX 검토 · 구현", "CLAUDE", "RUNNING"),
        child("cross-api", "교차 검토 · API", "CODEX", "QUEUED", { dependencies: ["ux-review"] }),
        child("cross-ux", "교차 검토 · UX", "CLAUDE", "QUEUED", { dependencies: ["backend-review"] }),
      ]
    : [
        child("backend-review", "API 계약 검토", "CODEX", "COMPLETED", { result_summary: "상태·응답 계약 위험 없음, 파일 근거 제출", validation_summary: "읽기 전용 검토 · 근거 파일 확인" }),
        child("ux-review", "모바일 UX 검토 · 구현", "CLAUDE", "COMPLETED", { result_summary: "증거 문서 작성, 원격 브랜치 push", validation_summary: "집중 테스트 6 PASS · 빌드 PASS" }),
        child("cross-api", "교차 검토 · API", "CODEX", "COMPLETED", { dependencies: ["ux-review"], result_summary: "UX 변경의 API 영향 없음", validation_summary: "독립 작업공간에서 재확인",
          review_target: { commit_sha: "c4338cb89d4bc15c8827f58f697fbc67307bd4df", scope: "협업 진행 화면", completion_criteria: "회귀 없음", test_evidence: "unit 6 PASS" } }),
        child("cross-ux", "교차 검토 · UX", "CLAUDE", "COMPLETED", { dependencies: ["backend-review"], result_summary: "모바일 접힘·대기 문구 확인", validation_summary: "390·360·1440 화면 확인" }),
      ];
  const done = phase === "done";
  return {
    request_id: "req_demo_parent", project_id: "ai-gcs", provider: "CODEX", profile: "default",
    title: "협업 진행 화면 실제 Provider 검증", title_source: "user",
    prompt: PARENT_PROMPT, original_prompt: PARENT_PROMPT, native_session_id: "demo-session",
    state: done ? "COMPLETED" : "WAITING_CHILDREN",
    status: done ? { execution_started_at: at(38) } : { waiting_reason: "delegation_results", execution_started_at: at(9) },
    created_at: done ? at(40) : at(10), updated_at: done ? at(2) : at(1),
    collaboration: { is_child: false, waiting: !done, children },
    result: done
      ? { text: "같은 총괄 대화에서 네 위임 결과를 모아 최종 결과를 정리했습니다.\n\n- API 계약 검토(CODEX): 위험 없음\n- 모바일 UX 검토·구현(CLAUDE): 집중 테스트 6 PASS, 빌드 PASS, 증거 문서 commit·push\n- 교차 검토 2건: 회귀 없음\n\nProvider 완료와 별개로, 서버가 관측한 커밋·원격 ref·운영 반영을 결과물에서 확인하세요." }
      : { text: "API 계약 검토는 CODEX, 모바일 UX 검토는 CLAUDE에 위임했습니다. 결과가 도착하면 이 대화에서 이어집니다." },
    evidence: done ? {
      available: true,
      source_revision: "6323c07ad26cb5da557c0c60c274861d76ee0331", head_revision: "f6dd9a92bc06562ab9dba310206497715256976e",
      changed_files: [
        { path: "src/collaboration-view.ts", status: "modified" },
        { path: "src/styles.css", status: "modified" },
        { path: "tests/collaboration.spec.ts", status: "added" },
      ],
      commands: [
        { name: "unit", command: "npm test -- collaboration-view", status: "PASS", detail: "6 passed" },
        { name: "build", command: "npm run build", status: "PASS" },
        { name: "browser", command: "npx playwright test collaboration", status: "PASS" },
      ],
      commits: [{ sha: "30954e6f50aa4e8282613cd375412083ac5d19c0", subject: "Show durable delegation progress beside the parent conversation" }],
      git_delivery: { status: "REMOTE_REF_VERIFIED", main_ref_verified: true, current_branch: "main", remote_readback_refs_at_head: ["refs/heads/main"] },
      runtime_delivery: {
        state: "CURRENT_VERIFIED", request_id: "req_demo_parent",
        backend_sha: "794ec7826819b510ab0a5232075e41e8b293b73f", web_sha: "f6dd9a92bc06562ab9dba310206497715256976e",
        release: "794ec782-f6dd9a92", completed_at: at(3), verification_basis: "self_deploy_verified_receipt_matches_current_runtime",
      },
    } : undefined,
  };
}

function parentEvents(phase) {
  const e = [];
  let s = 0;
  const push = (event_type, payload, min) => e.push({ sequence: ++s, event_type, payload, created_at: at(min) });
  const base = phase === "done" ? 40 : 10;
  push("request.accepted", { state: "ACCEPTED" }, base);
  push("workspace.prepared", {}, base - 0.5);
  push("provider.binding", { provider: "CODEX" }, base - 0.7);
  push("request.state", { state: "RUNNING" }, base - 1);
  push("provider.message", { text: "역할을 나눠 위임합니다. API 계약은 CODEX, 모바일 UX는 CLAUDE." }, base - 2);
  push("request.state", { state: "WAITING_CHILDREN" }, base - 3);
  if (phase === "done") {
    push("request.state", { state: "RUNNING" }, 6);
    push("provider.result", { state: "COMPLETED", result: parentDetail("done").result }, 2);
  }
  return e;
}

const listItems = () => [
  { ...parentDetail("parallel"), evidence: undefined },
  { request_id: "req_demo_g7", project_id: "g7", provider: "CLAUDE", profile: "default", title: "RAON Hub 홍보영상 V1 제작 패키지", state: "RUNNING", created_at: at(24), updated_at: at(1), status: { execution_started_at: at(23) } },
  { request_id: "req_demo_ms", project_id: "mobile-stock", provider: "CODEX", profile: "default", title: "모의투자 주문 영수증 화면 회귀 검증", state: "COMPLETED", created_at: at(95), updated_at: at(70), status: { execution_started_at: at(94) } },
  { request_id: "req_demo_g7b", project_id: "g7", provider: "CODEX", profile: "default", title: "공개 헤더 브랜드 반응형 확인", state: "COMPLETED", created_at: at(160), updated_at: at(140), status: { execution_started_at: at(159) } },
  { request_id: "req_demo_ai", project_id: "ai-gcs", provider: "CLAUDE", profile: "default", title: "요청 상세 헤더 공간 정리", state: "COMPLETED", created_at: at(230), updated_at: at(200), status: { execution_started_at: at(229) } },
];

const workOrder = {
  work_id: "work-demo-promo-video-v1", format_version: "1", title: "RAON Agent Factory 플랫폼 홍보영상 V1 제작", project_id: "g7",
  body: "## 목표\n실제 동작을 기반으로 60~90초 홍보영상 V1을 제작한다.\n## 범위\n실제 화면 우선, 민감정보 제거\n## 완료조건\n스토리보드·자막·렌더 패키지\n## 검증방법\n프레임 단위 민감정보 확인",
  raw: "", version: 1, content_hash: "d".repeat(64), source_revision: "bce35529a0e040bca6860ff0c988639fd2a0d479", source_repo: "raonslab2/ai_gcs_v2", source_branch: "main",
  source_path: "work/orders/promo-video-v1.md", collected_at: at(5), changed: false,
};

async function wire(page, phase) {
  await page.route(/\/api\/v1\//, async (route) => {
    const url = new URL(route.request().url());
    const p = url.pathname;
    const json = (body, status = 200) => route.fulfill({ status, json: body });
    if (p === "/api/v1/session") return json({ user_id: "demo-user" });
    if (p === "/api/v1/projects") return json({ projects });
    if (p === "/api/v1/providers") return json({ providers });
    if (p === "/api/v1/work-orders") return json({ items: [workOrder], last_success: at(5), error: "", errors: [], refresh_requested: false, interval_seconds: 60,
      source: { source_repo: "raonslab2/ai_gcs_v2", source_branch: "main", source_revision: "bce35529a0e040bca6860ff0c988639fd2a0d479" }, counts: { valid_documents: 1, pending: 1, processed: 0, errors: 0 } });
    if (p.startsWith("/api/v1/work-orders/")) return json(workOrder);
    if (p === "/api/v1/operations/requests") return json({ items: listItems().map(r => ({ ...r, state: r.state === "WAITING_CHILDREN" ? "COMPLETED" : r.state })), next_cursor: null });
    if (p === "/api/v1/requests") {
      const st = url.searchParams.get("state");
      const pid = url.searchParams.get("project_id");
      let items = listItems();
      if (phase === "done") items[0] = { ...parentDetail("done"), evidence: undefined };
      if (pid) items = items.filter(i => i.project_id === pid);
      if (st) items = items.filter(i => st.split(",").includes(i.state));
      return json({ items, next_cursor: null });
    }
    if (/\/references/.test(p)) return json({ items: [], next_cursor: null });
    if (p.endsWith("/events")) {
      const id = decodeURIComponent(p.split("/")[4]);
      const events = id === "req_demo_parent" ? parentEvents(phase) : [];
      return route.fulfill({ status: 200, contentType: "text/event-stream", body: events.map(ev => `id: ${ev.sequence}\ndata: ${JSON.stringify(ev)}\n\n`).join("") });
    }
    if (p.startsWith("/api/v1/requests/")) {
      const id = decodeURIComponent(p.split("/")[4]);
      if (id === "req_demo_parent") return json(parentDetail(phase));
      const item = listItems().find(i => i.request_id === id);
      return item ? json({ ...item, prompt: item.title }) : json({ detail: "not found" }, 404);
    }
    return json({}, 404);
  });
}

const browser = await chromium.launch();
const views = { m: { viewport: { width: 390, height: 844 }, deviceScaleFactor: 3, isMobile: true, hasTouch: true }, d: { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1.5 } };
const results = [];
const RECT_SELECTORS = [".collaboration-child", ".collaboration-progress", ".result-summary-card", ".result-summary", ".result-truth", ".result-truth li", ".commit-row", ".git-delivery-result", ".observation-item", "#request-prompt", "#project-id", "#provider-profile", ".order-item", ".request-row", ".conversation-messages", ".nav-title", "button.primary, .submit-request", ".hub-project, .project-card"];

async function shot(name, kind, phase, fn, opts = {}) {
  const ctx = await browser.newContext({ ...views[kind], locale: "ko-KR", timezoneId: "Asia/Seoul", colorScheme: "dark" });
  const page = await ctx.newPage();
  const errors = [];
  page.on("pageerror", (e) => errors.push(String(e)));
  await page.clock.install({ time: NOW });
  await wire(page, phase);
  await fn(page);
  await page.waitForTimeout(700);
  // 스크롤 위치에 따라 떠 있는 '맨 아래로' 이동 버튼만 가린다(제품 화면의 다른 요소는 변경하지 않음).
  await page.addStyleTag({ content: ".conversation-jump{visibility:hidden!important}" });
  await page.waitForTimeout(100);
  const file = path.join(OUT, `${name}-${kind === "m" ? "390x844" : "1440x900"}.png`);
  await page.screenshot({ path: file, fullPage: !!opts.full, animations: "disabled", caret: "hide" });
  const rects = await page.evaluate((sels) => {
    const out = {};
    for (const sel of sels) out[sel] = [...document.querySelectorAll(sel)].map((el) => { const r = el.getBoundingClientRect(); return [Math.round(r.x), Math.round(r.y), Math.round(r.width), Math.round(r.height)]; }).filter((r) => r[2] > 0 && r[3] > 0);
    return out;
  }, RECT_SELECTORS);
  results.push({ file: path.basename(file), dpr: views[kind].deviceScaleFactor, rects, errors, text_sample: (await page.evaluate(() => document.body.innerText)).slice(0, 4000) });
  await ctx.close();
}

const go = (q = "") => async (page) => { await page.goto(BASE + "/" + q, { waitUntil: "domcontentloaded" }); await page.waitForTimeout(1200); };
const top = (sel) => async (page) => page.evaluate((s) => { const el = document.querySelector(s); if (el) el.scrollIntoView({ block: "start" }); }, sel);
const openCollab = async (page) => {
  const s = page.locator(".collaboration-progress > summary");
  if (await s.count()) { const open = await page.locator(".collaboration-progress").getAttribute("open"); if (open === null) await s.click(); await page.waitForTimeout(300); await top(".collaboration-progress")(page); }
};
const openWork = async (page) => { await page.locator("#work-details > summary").click(); await page.waitForTimeout(400); };

for (const k of ["m", "d"]) {
  await shot("01-work-list", k, "parallel", go());
  await shot("02-composer", k, "parallel", async (page) => {
    await go()(page);
    await page.getByRole("button", { name: "새 요청" }).first().click();
    await page.waitForTimeout(500);
    await page.locator("#project-id").selectOption("ai-gcs").catch(() => {});
    await page.locator("#provider-profile").selectOption("CODEX::default").catch(() => {});
    await page.locator("#request-prompt").fill(PARENT_PROMPT);
  });
  await shot("03-work-orders", k, "parallel", async (page) => {
    await go()(page);
    await page.getByRole("button", { name: /작업지시함/ }).first().click();
    await page.waitForTimeout(500);
  });
  await shot("04-parent-waiting", k, "parallel", async (page) => { await go("?request_id=req_demo_parent")(page); await openCollab(page); });
  await shot("05-parent-collab-done", k, "done", async (page) => { await go("?request_id=req_demo_parent")(page); await openCollab(page); });
  await shot("06-parent-result", k, "done", async (page) => {
    await go("?request_id=req_demo_parent")(page);
    await openWork(page); await top(".result-summary-card")(page);
  });
  await shot("07-observations", k, "done", async (page) => {
    await go("?request_id=req_demo_parent")(page);
    await openWork(page); await top(".observations")(page);
  });
  await shot("08-project-hub", k, "done", async (page) => {
    await go()(page);
    await page.getByRole("button", { name: "프로젝트" }).first().click();
    await page.waitForTimeout(600);
  });
  await shot("09-statistics", k, "done", async (page) => {
    await go()(page);
    await page.getByRole("button", { name: "운영 통계" }).first().click();
    await page.waitForTimeout(800);
  });
  await shot("10-parent-full", k, "done", async (page) => { await go("?request_id=req_demo_parent")(page); await openCollab(page); await page.evaluate(() => scrollTo(0, 0)); }, { full: true });
}

fs.writeFileSync(path.join(OUT, "capture-log.json"), JSON.stringify(results, null, 2));
await browser.close();
server.close();
console.log(results.map(r => `${r.file} errors=${r.errors.length}`).join("\n"));
