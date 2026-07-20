// 유한킴벌리TST (yktst.co.kr, Cafe24) 전체 상품 스크래퍼 -> JSON + 로컬 이미지.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const IMG_DIR = path.join(ROOT, 'public', 'shop', 'img', 'yk');
const DATA_DIR = path.join(ROOT, 'database', 'seeders', 'data');
fs.mkdirSync(IMG_DIR, { recursive: true });

const BASE = 'https://yktst.co.kr';
const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36';
const CATES = [9,13,25,26,27,28,29,30,31,32,33,34,36,37,39,40,41,42,44,45,46,47,48,50,52,72,73,74,75,76,77,78,79,80,81,82];

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
async function fetchText(url, tries = 4) {
  for (let i = 0; i < tries; i++) {
    try { const r = await fetch(url, { headers: { 'User-Agent': UA, Referer: BASE } }); if (r.ok) return await r.text(); if (r.status === 404) return null; } catch {}
    await sleep(500 * (i + 1));
  }
  return null;
}
function abs(u) { if (!u) return null; u = u.trim(); if (u.startsWith('//')) return 'https:' + u; if (u.startsWith('/')) return BASE + u; return u.replace(/^http:/, 'https:'); }
function meta(html, prop) { const m = html.match(new RegExp(`<meta[^>]+property=["']${prop}["'][^>]+content=["']([^"']*)["']`, 'i')); return m ? m[1] : null; }
function decode(s) { return s ? s.replace(/&amp;/g,'&').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&quot;/g,'"').replace(/&#39;/g,"'").replace(/&nbsp;/g,' ').trim() : s; }
function extOf(u){ const m=u.split('?')[0].match(/\.(jpg|jpeg|png|gif)$/i); return m?m[1].toLowerCase():'jpg'; }

function nos(html){ const s=new Set(); let m; const re=/detail\.html\?product_no=(\d+)/g; while((m=re.exec(html))) s.add(parseInt(m[1],10)); return [...s]; }

async function crawlCat(cate){
  const found=new Set(); let prev='';
  for(let page=1;page<=60;page++){
    const html=await fetchText(`${BASE}/product/list.html?cate_no=${cate}&page=${page}`);
    if(!html) break;
    const list=nos(html); if(!list.length) break;
    const sig=list.slice().sort((a,b)=>a-b).join(','); if(sig===prev) break; prev=sig;
    list.forEach(n=>found.add(n));
    await sleep(100);
  }
  return [...found];
}

let dl=0;
async function download(url,dest){
  if(fs.existsSync(dest)&&fs.statSync(dest).size>100) return true;
  for(let i=0;i<3;i++){ try{ const r=await fetch(url,{headers:{'User-Agent':UA,Referer:BASE}}); if(r.ok){ const b=Buffer.from(await r.arrayBuffer()); if(b.length>100){ fs.writeFileSync(dest,b); dl++; return true; } } else if(r.status===404) return false; }catch{} await sleep(400*(i+1)); }
  return false;
}
async function pool(items,size,worker){ const q=items.slice(); await Promise.all(Array.from({length:size},async()=>{ while(q.length) await worker(q.shift()); })); }

async function main(){
  console.log('== 카테고리 크롤 ==');
  const all=new Set();
  for(const c of CATES){ const ns=await crawlCat(c); ns.forEach(n=>all.add(n)); process.stdout.write(`\r  cate ${c} -> 누적 ${all.size}   `); }
  const list=[...all]; console.log(`\n총 상품: ${list.length}`);

  console.log('== 상세 + 이미지 ==');
  const products=[]; let done=0;
  await pool(list,6,async(no)=>{
    done++;
    const html=await fetchText(`${BASE}/product/detail.html?product_no=${no}`);
    if(!html){ return; }
    let name=decode(meta(html,'og:title')||'');
    name=name.replace(/\s*-\s*유한킴벌리TST\s*$/,'').trim();
    if(!name) return;
    const price=parseInt(meta(html,'product:price:amount')||'0',10)||null;
    // 실제 대표이미지: /web/product/big/ (placeholder 제외)
    const ogs=[...html.matchAll(/property=["']og:image["'][^>]+content=["']([^"']+)["']/gi)].map(m=>m[1]);
    let main=null; for(const u of ogs){ if(/\/web\/product\/big\//i.test(u)){ main=abs(u); break; } }
    if(!main){ const m=html.match(/\/web\/product\/big\/[^\s"'<>)]+\.(?:jpg|jpeg|png|gif)/i); if(m) main=abs(m[0]); }
    const gallery=new Set(); let g; const gre=/\/web\/product\/extra\/big\/[^\s"'<>)]+\.(?:jpg|jpeg|png|gif)/gi; while((g=gre.exec(html))) gallery.add(abs(g[0]));

    const dir=path.join(IMG_DIR,String(no)); fs.mkdirSync(dir,{recursive:true});
    const rel=(f)=>`/shop/img/yk/${no}/${f}`;
    const images={main:null,gallery:[]};
    if(main){ const f=`photo.${extOf(main)}`; if(await download(main,path.join(dir,f))) images.main=rel(f); }
    let gi=0; for(const u of [...gallery]){ const f=`g${gi++}.${extOf(u)}`; if(await download(u,path.join(dir,f))) images.gallery.push(rel(f)); }
    if(!images.main && images.gallery.length) images.main=images.gallery[0];
    if(!images.main) return; // 이미지 없으면 제외

    products.push({ no, name, brand:'유한킴벌리', price, images });
    process.stdout.write(`\r  ${done}/${list.length} | 저장 ${products.length} | 이미지 ${dl}   `);
  });
  process.stdout.write('\n');
  products.sort((a,b)=>a.no-b.no);
  fs.writeFileSync(path.join(DATA_DIR,'yk_products.json'), JSON.stringify(products,null,1));
  console.log(`DONE. products=${products.length} images=${dl}`);
}
main();
