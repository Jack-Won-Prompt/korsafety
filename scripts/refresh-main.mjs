// Fix main product images: the source has TWO og:image tags (default logo first,
// real product photo second). Re-fetch each detail page, grab the real primary
// image (/web/product/big/, non-extra), download it, and update products.json.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const IMG_DIR = path.join(ROOT, 'public', 'shop', 'img');
const DATA = path.join(ROOT, 'database', 'seeders', 'data', 'products.json');
const BASE = 'https://yeswill.kr';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

const products = JSON.parse(fs.readFileSync(DATA, 'utf-8'));
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function fetchText(url, tries = 4) {
  for (let i = 0; i < tries; i++) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': UA, 'Referer': BASE } });
      if (res.ok) return await res.text();
      if (res.status === 404) return null;
    } catch (e) {}
    await sleep(500 * (i + 1));
  }
  return null;
}
function abs(u) {
  if (!u) return null; u = u.trim();
  if (u.startsWith('//')) return 'https:' + u;
  if (u.startsWith('/')) return BASE + u;
  return u.replace(/^http:/, 'https:');
}
function extOf(u) {
  const m = u.split('?')[0].match(/\.(jpg|jpeg|png|gif)$/i);
  return m ? m[1].toLowerCase() : 'jpg';
}
async function download(url, destAbs) {
  for (let i = 0; i < 3; i++) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': UA, 'Referer': BASE } });
      if (res.ok) { const b = Buffer.from(await res.arrayBuffer()); if (b.length > 100) { fs.writeFileSync(destAbs, b); return true; } }
      else if (res.status === 404) return false;
    } catch (e) {}
    await sleep(400 * (i + 1));
  }
  return false;
}

// Real primary photo = /web/product/big/<yyyymm>/<hash>.<ext>  (NOT extra/, NOT small/)
function realMain(html) {
  // Prefer og:image entries that point at /web/product/
  const ogs = [...html.matchAll(/property=["']og:image["'][^>]+content=["']([^"']+)["']/gi)].map((m) => m[1]);
  for (const u of ogs) if (/\/web\/product\/big\//i.test(u)) return abs(u);
  // Fallback: first /web/product/big/ (non-extra) in the page
  const m = html.match(/\/web\/product\/big\/[^\s"'<>)]+\.(?:jpg|jpeg|png|gif)/i);
  if (m) return abs(m[0]);
  return null;
}

async function pool(items, size, worker) {
  const q = items.slice();
  await Promise.all(Array.from({ length: size }, async () => {
    while (q.length) await worker(q.shift());
  }));
}

let done = 0, fixed = 0, nophoto = 0;
await pool(products, 8, async (p) => {
  done++;
  const html = await fetchText(`${BASE}/product/detail.html?product_no=${p.no}`);
  if (html) {
    const url = realMain(html);
    if (url) {
      const dir = path.join(IMG_DIR, String(p.no));
      fs.mkdirSync(dir, { recursive: true });
      const f = `photo.${extOf(url)}`;
      if (await download(url, path.join(dir, f))) {
        p.images.main = `/shop/img/${p.no}/${f}`;
        fixed++;
      }
    } else {
      nophoto++;
      // no real photo on source -> keep placeholder, but prefer a real gallery shot if we have one
      if (p.images.gallery && p.images.gallery.length) p.images.main = p.images.gallery[0];
    }
  }
  if (done % 25 === 0 || done === products.length) {
    process.stdout.write(`\r  ${done}/${products.length}  fixed=${fixed} noPhoto=${nophoto}   `);
  }
});
process.stdout.write('\n');
fs.writeFileSync(DATA, JSON.stringify(products, null, 1));
console.log(`DONE. fixed=${fixed} noPhoto=${nophoto}. products.json updated.`);
