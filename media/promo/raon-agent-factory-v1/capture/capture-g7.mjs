// G7 / RAON Hub 공개 사이트를 읽기 전용(GET)으로 캡처한다. 로그인·폼 제출·쓰기 없음.
import fs from "node:fs";
import { chromium } from "playwright";
const BASE = process.env.G7_PUBLIC_URL;
const OUT = process.argv[2] ?? "captures-g7";
fs.mkdirSync(OUT, { recursive: true });
const b = await chromium.launch();
const log = [];
for (const [k, vp, dpr] of [["1440x900", { width: 1440, height: 900 }, 1.5], ["390x844", { width: 390, height: 844 }, 3]]) {
  const ctx = await b.newContext({ viewport: vp, deviceScaleFactor: dpr, isMobile: vp.width < 500, locale: "ko-KR", colorScheme: "dark", userAgent: vp.width < 500 ? "Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Mobile Safari/537.36" : "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36" });
  await ctx.addInitScript(() => localStorage.setItem("g7_locale", "ko"));
  const page = await ctx.newPage();
  const methods = new Set();
  page.on("request", (r) => methods.add(r.method()));
  await page.goto(BASE, { waitUntil: "networkidle", timeout: 60000 });
  await page.waitForTimeout(3000);
  await page.screenshot({ path: `${OUT}/g7-home-hero-${k}.png` });
  await page.screenshot({ path: `${OUT}/g7-home-full-${k}.png`, fullPage: true });
  for (const [name, re] of [["products", /실제로 동작하는 자체 제품/], ["process", /작게 합의하고/]]) {
    const loc = page.getByText(re).first();
    if (await loc.count()) { await loc.evaluate((el) => el.scrollIntoView({ block: "start" })); await page.evaluate(() => scrollBy(0, -90)); await page.waitForTimeout(600); await page.screenshot({ path: `${OUT}/g7-${name}-${k}.png` }); }
  }
  log.push({ viewport: k, title: await page.title(), methods: [...methods] });
  await ctx.close();
}
await b.close();
fs.writeFileSync(`${OUT}/capture-log.json`, JSON.stringify(log, null, 2));
console.log(JSON.stringify(log));
