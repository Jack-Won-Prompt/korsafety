// YESWILL catalog scraper -> local JSON + downloaded images.
// Node 22+ (built-in fetch). Resumable: existing image files are skipped.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const IMG_DIR = path.join(ROOT, 'public', 'shop', 'img');
const DATA_DIR = path.join(ROOT, 'database', 'seeders', 'data');
fs.mkdirSync(IMG_DIR, { recursive: true });
fs.mkdirSync(DATA_DIR, { recursive: true });

const BASE = 'https://yeswill.kr';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';

// The 9 real top-level product categories (clean storefront nav).
const TOP_CATS = [
  { no: 42,  name: '안전화',            slug: 'safety-shoes' },
  { no: 676, name: '워크웨어',          slug: 'workwear' },
  { no: 679, name: '안전용품',          slug: 'safety-gear' },
  { no: 128, name: '안전대·안전벨트',   slug: 'harness' },
  { no: 163, name: '안전시설물',        slug: 'facilities' },
  { no: 839, name: '도로안전용품',      slug: 'road-safety' },
  { no: 800, name: '소방·구급안전용품', slug: 'fire-rescue' },
  { no: 854, name: '클린&세이프',       slug: 'clean-safe' },
  { no: 956, name: '시즌상품',          slug: 'seasonal' },
];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

async function fetchText(url, tries = 4) {
  for (let i = 0; i < tries; i++) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': UA, 'Referer': BASE } });
      if (res.ok) return await res.text();
      if (res.status === 404) return null;
    } catch (e) { /* retry */ }
    await sleep(600 * (i + 1));
  }
  return null;
}

function abs(u) {
  if (!u) return null;
  u = u.trim();
  if (u.startsWith('//')) return 'https:' + u;
  if (u.startsWith('/')) return BASE + u;
  if (u.startsWith('http')) return u.replace(/^http:/, 'https:');
  return BASE + '/' + u;
}

// ---- Phase A: crawl category listings ----
function parseListing(html) {
  const nos = new Set();
  const re = /detail\.html\?product_no=(\d+)/g;
  let m;
  while ((m = re.exec(html))) nos.add(parseInt(m[1], 10));
  return [...nos];
}

async function crawlCategory(cat) {
  const found = new Set();
  let prevSig = '';
  for (let page = 1; page <= 80; page++) {
    const html = await fetchText(`${BASE}/product/list.html?cate_no=${cat.no}&page=${page}`);
    if (!html) break;
    const nos = parseListing(html);
    if (nos.length === 0) break;
    const sig = nos.slice().sort((a, b) => a - b).join(',');
    if (sig === prevSig) break; // last page repeated -> stop
    prevSig = sig;
    nos.forEach((n) => found.add(n));
    process.stdout.write(`\r  [${cat.slug}] page ${page} -> ${found.size} products   `);
    await sleep(120);
  }
  process.stdout.write('\n');
  return [...found];
}

// ---- Phase B: parse product detail ----
function meta(html, prop) {
  const re = new RegExp(`<meta[^>]+property=["']${prop}["'][^>]+content=["']([^"']*)["']`, 'i');
  const m = html.match(re);
  return m ? m[1] : null;
}
function decodeEntities(s) {
  if (!s) return s;
  return s.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>')
          .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&nbsp;/g, ' ').trim();
}

function parseDetail(html, no) {
  const name = decodeEntities(meta(html, 'og:title') || '');
  const price = parseInt(meta(html, 'product:price:amount') || '0', 10) || null;
  const sale = parseInt(meta(html, 'product:sale_price:amount') || '0', 10) || null;
  const main = abs(meta(html, 'og:image'));

  // brand from JSON-LD
  let brand = null;
  const bm = html.match(/"brand"\s*:\s*\{[^}]*"name"\s*:\s*"([^"]+)"/i);
  if (bm) brand = decodeEntities(bm[1]);

  const soldout = /상품 품절|일시품절|SOLD\s*OUT/i.test(html);

  // gallery: /web/product/extra/big/...
  const gallery = new Set();
  let g;
  const gre = /\/web\/product\/extra\/big\/[^\s"'<>)]+\.(?:jpg|jpeg|png|gif)/gi;
  while ((g = gre.exec(html))) gallery.add(abs(g[0]));

  // description/detail images: openhost + echosting + big product imgs inside description
  const detail = new Set();
  const dre = /(?:https?:)?\/\/[a-z0-9.-]*(?:openhost\.cafe24\.com|echosting\.cafe24\.com|cafe24img\.com)[^\s"'<>)]+\.(?:jpg|jpeg|png|gif)/gi;
  while ((g = dre.exec(html))) {
    const u = abs(g[0]);
    if (/logo|icon|btn_|banner_|common\//i.test(u)) continue;
    detail.add(u);
  }
  return {
    no, name, brand, price, sale, soldout,
    main, gallery: [...gallery], detail: [...detail],
  };
}

// ---- image download ----
function extOf(u) {
  const m = u.split('?')[0].match(/\.(jpg|jpeg|png|gif)$/i);
  return m ? m[1].toLowerCase() : 'jpg';
}
let dlCount = 0, dlSkip = 0;
async function download(url, destAbs) {
  if (fs.existsSync(destAbs) && fs.statSync(destAbs).size > 0) { dlSkip++; return true; }
  for (let i = 0; i < 3; i++) {
    try {
      const res = await fetch(url, { headers: { 'User-Agent': UA, 'Referer': BASE } });
      if (res.ok) {
        const buf = Buffer.from(await res.arrayBuffer());
        if (buf.length > 0) { fs.writeFileSync(destAbs, buf); dlCount++; return true; }
      } else if (res.status === 404) return false;
    } catch (e) {}
    await sleep(400 * (i + 1));
  }
  return false;
}

async function pool(items, size, worker) {
  const q = items.slice();
  const runners = Array.from({ length: size }, async () => {
    while (q.length) { const it = q.shift(); await worker(it); }
  });
  await Promise.all(runners);
}

async function main() {
  console.log('== Phase A: crawl categories ==');
  const productCat = new Map(); // product_no -> primary slug
  const catProducts = {};       // slug -> [product_no]
  for (const cat of TOP_CATS) {
    const nos = await crawlCategory(cat);
    catProducts[cat.slug] = nos;
    for (const n of nos) if (!productCat.has(n)) productCat.set(n, cat.slug);
  }
  const allNos = [...productCat.keys()];
  console.log(`Total unique products: ${allNos.length}`);

  console.log('== Phase B: product details + images ==');
  const products = [];
  let done = 0;
  await pool(allNos, 6, async (no) => {
    const html = await fetchText(`${BASE}/product/detail.html?product_no=${no}`);
    done++;
    if (!html) { process.stdout.write(`\r  detail ${done}/${allNos.length} (miss ${no})     `); return; }
    const d = parseDetail(html, no);
    if (!d.name) { process.stdout.write(`\r  detail ${done}/${allNos.length}      `); return; }
    d.category = productCat.get(no);

    const dir = path.join(IMG_DIR, String(no));
    fs.mkdirSync(dir, { recursive: true });
    const rel = (f) => `/shop/img/${no}/${f}`;

    const images = { main: null, gallery: [], detail: [] };
    if (d.main) {
      const f = `main.${extOf(d.main)}`;
      if (await download(d.main, path.join(dir, f))) images.main = rel(f);
    }
    let gi = 0;
    for (const u of d.gallery) {
      const f = `g${gi++}.${extOf(u)}`;
      if (await download(u, path.join(dir, f))) images.gallery.push(rel(f));
    }
    let di = 0;
    for (const u of d.detail.slice(0, 30)) {
      const f = `d${di++}.${extOf(u)}`;
      if (await download(u, path.join(dir, f))) images.detail.push(rel(f));
    }
    if (!images.main && images.gallery.length) images.main = images.gallery[0];

    products.push({
      no: d.no, name: d.name, brand: d.brand,
      price: d.price, sale: d.sale, soldout: d.soldout,
      category: d.category, images,
    });
    process.stdout.write(`\r  detail ${done}/${allNos.length} | imgs dl=${dlCount} skip=${dlSkip}    `);
  });
  process.stdout.write('\n');

  products.sort((a, b) => a.no - b.no);
  const categories = TOP_CATS.map((c) => ({
    slug: c.slug, name: c.name,
    count: products.filter((p) => p.category === c.slug).length,
  }));
  fs.writeFileSync(path.join(DATA_DIR, 'products.json'), JSON.stringify(products, null, 1));
  fs.writeFileSync(path.join(DATA_DIR, 'categories.json'), JSON.stringify(categories, null, 1));
  console.log(`\nDONE. products=${products.length} images downloaded=${dlCount} skipped=${dlSkip}`);
  console.log('Written to database/seeders/data/{products,categories}.json');
}
main();
