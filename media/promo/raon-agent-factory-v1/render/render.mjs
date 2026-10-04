// promo.html 을 프레임 단위로 캡처해 ffmpeg 로 인코딩한다.
// 사용:
//   node render.mjs stills <h|v> <outDir> <t1,t2,...>        # 기준 프레임(JPEG)
//   node render.mjs video  <h|v> <out.mp4> [fps] [from] [to]  # 무음 clean 영상 (자막·오디오는 build.sh 에서 합성)
// 환경변수 FFMPEG 로 ffmpeg 경로 지정(기본: PATH 의 ffmpeg). 저장소 루트를 정적 서버 루트로 쓴다(Pretendard 폰트 경로).
import http from "node:http";
import fs from "node:fs";
import path from "node:path";
import { spawn } from "node:child_process";
import { fileURLToPath } from "node:url";

// PLAYWRIGHT_MODULE: 저장소 밖에 설치된 playwright 패키지 경로(예: …/node_modules/playwright/index.mjs). 기본은 일반 해석.
const { chromium } = await import(process.env.PLAYWRIGHT_MODULE ?? "playwright");
const here = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(here, "../../../..");
const [mode, orient = "h", out, a4, a5, a6] = process.argv.slice(2);
const W = orient === "v" ? 1080 : 1920, H = orient === "v" ? 1920 : 1080;
const TYPES = { ".html": "text/html", ".js": "text/javascript", ".json": "application/json", ".webp": "image/webp", ".woff2": "font/woff2", ".css": "text/css" };

const server = http.createServer((req, res) => {
  const p = path.join(ROOT, decodeURIComponent(new URL(req.url, "http://x").pathname));
  if (!p.startsWith(ROOT) || !fs.existsSync(p) || fs.statSync(p).isDirectory()) { res.writeHead(404); return res.end(); }
  res.writeHead(200, { "content-type": TYPES[path.extname(p)] ?? "application/octet-stream" });
  fs.createReadStream(p).pipe(res);
});
await new Promise((r) => server.listen(0, "127.0.0.1", r));
const url = `http://127.0.0.1:${server.address().port}/media/promo/raon-agent-factory-v1/render/promo.html?o=${orient}`;

const browser = await chromium.launch();
const page = await browser.newPage({ viewport: { width: W, height: H }, deviceScaleFactor: 1 });
const errors = [];
page.on("pageerror", (e) => errors.push(String(e)));
page.on("requestfailed", (r) => errors.push("requestfailed " + r.url()));
page.on("response", (r) => { if (r.status() >= 400) errors.push(`${r.status()} ${r.url()}`); });
await page.goto(url, { waitUntil: "load" });
await page.evaluate(() => window.__ready);
if (errors.length) { console.error(errors.join("\n")); process.exit(2); }

const frame = async (t, opts) => { await page.evaluate((x) => window.__render(x), t); return page.screenshot({ type: "jpeg", quality: 94, ...opts }); };

if (mode === "stills") {
  fs.mkdirSync(out, { recursive: true });
  for (const t of a4.split(",").map(Number)) {
    await frame(t, { path: path.join(out, `${orient === "v" ? "9x16" : "16x9"}-t${t.toFixed(1).padStart(4, "0")}.jpg` ) });
  }
} else if (mode === "video") {
  const fps = Number(a4 ?? 30), from = Number(a5 ?? 0), to = Number(a6 ?? 88);
  const ff = spawn(process.env.FFMPEG ?? "ffmpeg", ["-loglevel", "error", "-y", "-f", "image2pipe", "-framerate", String(fps), "-c:v", "mjpeg", "-i", "-",
    "-c:v", "libx264", "-preset", "slow", "-crf", "17", "-pix_fmt", "yuv420p", "-r", String(fps), "-movflags", "+faststart", out], { stdio: ["pipe", "inherit", "inherit"] });
  const n = Math.round((to - from) * fps);
  const t0 = Date.now();
  for (let i = 0; i < n; i++) {
    const buf = await frame(from + i / fps);
    if (!ff.stdin.write(buf)) await new Promise((r) => ff.stdin.once("drain", r));
    if (i % 300 === 0) console.log(`${orient} frame ${i}/${n} ${((Date.now() - t0) / 1000).toFixed(0)}s`);
  }
  ff.stdin.end();
  await new Promise((r, j) => ff.on("close", (c) => (c === 0 ? r() : j(new Error("ffmpeg exit " + c)))));
}
if (errors.length) { console.error(errors.join("\n")); process.exitCode = 2; }
await browser.close();
server.close();
