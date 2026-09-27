#!/usr/bin/env node

import { chromium } from 'playwright';

const baseURL = process.env.G7_BROWSER_BASE_URL ?? 'http://127.0.0.1:18770';
const journeys = [
  ['/', 360],
  ['/', 390],
  ['/', 412],
  ['/login', 390],
  ['/register', 390],
  ['/boards', 390],
  ['/board/community', 390],
];

const browser = await chromium.launch({ headless: true });
const results = [];

try {
  for (const [path, width] of journeys) {
    const context = await browser.newContext({ viewport: { width, height: 900 }, colorScheme: 'dark' });
    const page = await context.newPage();
    const failures = [];
    page.on('response', (response) => {
      if (response.status() >= 400) failures.push(`${response.status()} ${response.url()}`);
    });
    const response = await page.goto(`${baseURL}${path}`, { waitUntil: 'networkidle' });
    await page.waitForTimeout(500);
    const metrics = await page.evaluate(() => ({
      clientWidth: document.documentElement.clientWidth,
      scrollWidth: document.documentElement.scrollWidth,
      textLength: document.body.innerText.trim().length,
    }));
    if (!response?.ok()) throw new Error(`${path}: HTTP ${response?.status() ?? 'no response'}`);
    if (metrics.scrollWidth > metrics.clientWidth + 1) throw new Error(`${path}: ${width}px horizontal overflow`);
    if (metrics.textLength === 0) throw new Error(`${path}: empty render`);
    if (failures.length) throw new Error(`${path}: failed resources: ${failures.join(', ')}`);
    results.push({ path, width, status: response.status(), overflow: false });
    await context.close();
  }
} finally {
  await browser.close();
}

console.log(JSON.stringify({ baseURL, journeys: results, result: 'PASS' }, null, 2));
