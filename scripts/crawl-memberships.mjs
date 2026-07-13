// Listing-only crawl to build product -> categories membership (fast, no images).
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
const __dirname = path.dirname(fileURLToPath(import.meta.url));
const OUT = path.join(__dirname, '..', 'database', 'seeders', 'data', 'memberships.json');
const BASE = 'https://yeswill.kr';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0 Safari/537.36';

const TOP_CATS = [
  { no: 42,  slug: 'safety-shoes' }, { no: 676, slug: 'workwear' },
  { no: 679, slug: 'safety-gear' },  { no: 128, slug: 'harness' },
  { no: 163, slug: 'facilities' },   { no: 839, slug: 'road-safety' },
  { no: 800, slug: 'fire-rescue' },  { no: 854, slug: 'clean-safe' },
  { no: 956, slug: 'seasonal' },
];
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function get(url, t = 4) {
  for (let i = 0; i < t; i++) {
    try { const r = await fetch(url, { headers: { 'User-Agent': UA, Referer: BASE } }); if (r.ok) return await r.text(); if (r.status === 404) return null; } catch {}
    await sleep(500 * (i + 1));
  }
  return null;
}
function nos(html) {
  const s = new Set(); let m; const re = /detail\.html\?product_no=(\d+)/g;
  while ((m = re.exec(html))) s.add(parseInt(m[1], 10));
  return [...s];
}
const memberships = {};
for (const c of TOP_CATS) {
  const found = new Set(); let prev = '';
  for (let page = 1; page <= 90; page++) {
    const html = await get(`${BASE}/product/list.html?cate_no=${c.no}&page=${page}`);
    if (!html) break;
    const list = nos(html);
    if (!list.length) break;
    const sig = list.slice().sort((a, b) => a - b).join(',');
    if (sig === prev) break;
    prev = sig; list.forEach((n) => found.add(n));
    process.stdout.write(`\r  [${c.slug}] page ${page} -> ${found.size}    `);
    await sleep(100);
  }
  memberships[c.slug] = [...found];
  process.stdout.write(`\n  ${c.slug}: ${found.size}\n`);
}
fs.writeFileSync(OUT, JSON.stringify(memberships, null, 1));
console.log('WROTE memberships.json');
