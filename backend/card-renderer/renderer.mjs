import puppeteer from 'puppeteer';
import fs from 'fs';
import os from 'os';
import path from 'path';
import { fileURLToPath } from 'url';
import { fillTemplate } from './fill-template.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

/**
 * @param {object} options
 * @param {string} options.template — 'fullbg'|'bold'|'soft'|'banner'|'minimal'
 * @param {string} options.imagePath
 * @param {string} [options.outPath]
 * @param {number} [options.width]
 * @param {number} [options.height]
 * @param {string[]} [options.badges]
 * @param {string} [options.title]
 * @param {string} [options.description]
 * @param {string} [options.price]
 * @param {string} [options.priceOld]
 * @param {string} [options.accent]
 * @param {'png'|'jpeg'} [options.outputType]
 * @param {number} [options.jpegQuality] 0-100
 * @param {number} [options.deviceScaleFactor]
 * @param {string} [options.executablePath]
 * @returns {Promise<string>} absolute path to written file
 */
export async function renderCard(options) {
  const {
    template = 'fullbg',
    imagePath,
    badges = [],
    title = '',
    description = '',
    price = '',
    priceOld = '',
    accent = '#1565C0',
    outPath,
    width = 900,
    height = 900,
    outputType = 'jpeg',
    jpegQuality = 92,
    deviceScaleFactor = 2,
    executablePath,
  } = options;

  const w = Math.max(1, Math.floor(width));
  const h = Math.max(1, Math.floor(height));

  const tplPath = path.join(__dirname, 'templates', `${template}.html`);
  if (!fs.existsSync(tplPath)) {
    throw new Error(`Template not found: ${template} (${tplPath})`);
  }

  const html = fillTemplate(tplPath, {
    imagePath,
    width: w,
    height: h,
    badges,
    title,
    description,
    price,
    priceOld,
    accent,
  });

  const launchOpts = {
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage'],
  };
  if (executablePath) {
    launchOpts.executablePath = executablePath;
  }

  const browser = await puppeteer.launch(launchOpts);

  try {
    const page = await browser.newPage();
    await page.setViewport({
      width: w,
      height: h,
      deviceScaleFactor: Math.max(1, Math.min(3, deviceScaleFactor || 2)),
    });

    await page.setContent(html, {
      waitUntil: 'networkidle0',
      timeout: 120000,
    });

    await page.evaluate(() => document.fonts.ready);

    const dest =
      outPath ||
      path.join(
        os.tmpdir(),
        `rf_card_${Date.now()}_${Math.random().toString(36).slice(2)}.${outputType === 'png' ? 'png' : 'jpg'}`
      );

    const dir = path.dirname(dest);
    if (!fs.existsSync(dir)) {
      fs.mkdirSync(dir, { recursive: true });
    }

    const shotType = outputType === 'png' ? 'png' : 'jpeg';
    /** @type {import('puppeteer').ScreenshotOptions} */
    const shot = {
      path: dest,
      type: shotType,
      clip: { x: 0, y: 0, width: w, height: h },
    };
    if (shotType === 'jpeg') {
      shot.quality = Math.max(1, Math.min(100, jpegQuality));
    }

    await page.screenshot(shot);

    return dest;
  } finally {
    await browser.close();
  }
}
