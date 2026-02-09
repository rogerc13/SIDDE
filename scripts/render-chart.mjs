import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import puppeteer from 'puppeteer';

function getArg(name, fallback = null) {
  const idx = process.argv.indexOf(name);
  if (idx === -1) return fallback;
  const val = process.argv[idx + 1];
  return val ?? fallback;
}

const htmlPath = getArg('--html');
const outPath = getArg('--out');
const selector = getArg('--selector', '#capture');
const width = Number(getArg('--width', '1400'));
const height = Number(getArg('--height', '800'));
const timeoutMs = Number(getArg('--timeoutMs', '45000'));

if (!htmlPath || !outPath) {
  console.error('Missing required args: --html and --out');
  process.exit(2);
}

const absHtml = path.resolve(htmlPath);
const absOut = path.resolve(outPath);

const fileUrl = `file:///${absHtml.replace(/\\/g, '/')}`;

const browser = await puppeteer.launch({
  headless: 'new',
  args: [
    '--disable-dev-shm-usage',
  ],
});

try {
  const page = await browser.newPage();
  await page.setViewport({ width, height, deviceScaleFactor: 1.5 });

  await page.goto(fileUrl, { waitUntil: 'networkidle0', timeout: timeoutMs });

  // Wait until the chart signals readiness (or an error is captured).
  await page.waitForFunction('window.__CHART_READY === true || window.__CHART_ERROR', { timeout: timeoutMs });

  const chartError = await page.evaluate(() => window.__CHART_ERROR);
  if (chartError) {
    throw new Error(`Chart render error: ${chartError}`);
  }

  const el = await page.$(selector);
  if (!el) {
    throw new Error(`Selector not found: ${selector}`);
  }

  await fs.promises.mkdir(path.dirname(absOut), { recursive: true });
  await el.screenshot({ path: absOut, type: 'png' });
} finally {
  await browser.close();
}
