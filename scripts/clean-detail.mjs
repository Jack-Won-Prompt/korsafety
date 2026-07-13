// Remove common/shared banner images from product "detail" sets.
// Cafe24 product pages embed the same shipping/return/notice banners on every
// product. We hash every downloaded detail image, treat any hash that appears
// across many products as a shared banner, and strip those from products.json.
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const PUB = path.join(ROOT, 'public');
const DATA = path.join(ROOT, 'database', 'seeders', 'data', 'products.json');
const products = JSON.parse(fs.readFileSync(DATA, 'utf-8'));

const THRESHOLD = 8; // hash seen in >= N products => shared banner, drop it

// hash -> Set(productNo)
const hashProducts = new Map();
const pathHash = new Map();
function hashFile(abs) {
  try { return crypto.createHash('md5').update(fs.readFileSync(abs)).digest('hex') + ':' + fs.statSync(abs).size; }
  catch { return null; }
}

for (const p of products) {
  for (const rel of (p.images.detail || [])) {
    const abs = path.join(PUB, rel.replace(/^\//, ''));
    const h = hashFile(abs);
    if (!h) continue;
    pathHash.set(rel, h);
    if (!hashProducts.has(h)) hashProducts.set(h, new Set());
    hashProducts.get(h).add(p.no);
  }
}

const common = new Set();
for (const [h, set] of hashProducts) if (set.size >= THRESHOLD) common.add(h);

let before = 0, after = 0;
for (const p of products) {
  const det = p.images.detail || [];
  before += det.length;
  p.images.detail = det.filter((rel) => {
    const h = pathHash.get(rel);
    return h && !common.has(h);
  });
  after += p.images.detail.length;
}

const withDetail = products.filter((p) => p.images.detail.length).length;
fs.writeFileSync(DATA, JSON.stringify(products, null, 1));
console.log(`distinct detail hashes: ${hashProducts.size}, common(>=${THRESHOLD}): ${common.size}`);
console.log(`detail image refs: ${before} -> ${after}`);
console.log(`products still having product-specific detail images: ${withDetail}/${products.length}`);
