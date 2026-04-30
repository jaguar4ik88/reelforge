import fs from 'fs';
import path from 'path';

function escapeHtml(s) {
  if (s == null) return '';
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/**
 * @param {string} tplPath
 * @param {object} data
 * @param {string} data.imagePath
 * @param {number} data.width
 * @param {number} data.height
 * @param {string[]} [data.badges]
 * @param {string} [data.title]
 * @param {string} [data.description]
 * @param {string} [data.price]
 * @param {string} [data.priceOld]
 * @param {string} [data.accent]
 */
export function fillTemplate(tplPath, data) {
  const {
    imagePath,
    width = 900,
    height = 900,
    badges = [],
    title = '',
    description = '',
    price = '',
    priceOld = '',
    accent = '#1565C0',
  } = data;

  let html = fs.readFileSync(tplPath, 'utf-8');

  let imgTag = '';
  if (imagePath && fs.existsSync(imagePath)) {
    const extRaw = path.extname(imagePath).slice(1).toLowerCase();
    const ext = extRaw.replace('jpg', 'jpeg') || 'jpeg';
    const b64 = fs.readFileSync(imagePath).toString('base64');
    imgTag = `<img class="product-img" src="data:image/${ext};base64,${b64}" alt="">`;
  }

  const b = [...badges, '', '', '', ''].slice(0, 4).map((x) => escapeHtml(x));

  const map = {
    '{{CANVAS_WIDTH}}': String(Math.max(1, Math.floor(width))),
    '{{CANVAS_HEIGHT}}': String(Math.max(1, Math.floor(height))),
    '{{PRODUCT_IMAGE}}': imgTag,
    '{{BADGE_1}}': b[0],
    '{{BADGE_2}}': b[1],
    '{{BADGE_3}}': b[2],
    '{{BADGE_4}}': b[3],
    '{{TITLE}}': escapeHtml(title),
    '{{DESC}}': escapeHtml(description),
    '{{PRICE}}': escapeHtml(price),
    '{{PRICE_OLD}}': escapeHtml(priceOld),
    '{{ACCENT}}': escapeHtml(accent),
  };

  for (const [key, val] of Object.entries(map)) {
    html = html.split(key).join(val ?? '');
  }

  return html;
}
