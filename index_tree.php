<?php
// ==========================================================================
//  ช่องทางเคลม 2569 - แบบต้นไม้ (mazza 5 ต.ค. 69: ขอแบบเดียวกับ ClaimMap index_tree_v3)
//  ราก = ทุกคนที่มารับบริการ / กิ่ง = 5 กลุ่มตามอินโฟกราฟิก / ใบ = 28 ช่อง
//  หน้าการ์ดแบบเดิมสำรองไว้ที่ index_cards.php · เงื่อนไขอยู่ที่ channels.php
// ==========================================================================
require_once __DIR__ . '/channels.php';
require_once __DIR__ . '/auth.php';
auth_require();
$chs = cc_channels();
$ready = count(array_filter($chs, fn($c) => $c['status'] === 'ready'));
$meta = [];
foreach ($chs as $c) $meta[$c['code']] = ['g' => $c['g'], 'name' => $c['name'], 'right' => $c['right'],
    'status' => $c['status'], 'why' => $c['why'], 'cond' => $c['cond'], 'how' => $c['how']];
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ช่องทางเคลม 2569</title>
<style>
:root{
  --bg:#f5f7fa; --surface:#ffffff; --ink:#1b2433; --muted:#5d6a7e; --line:#d5dce6; --root:#0f3d6e; --hit:#fff1a8;
  /* สีกลุ่มตามอินโฟกราฟิก 99 Training: ชมพู / ฟ้า / เทา / แดง / น้ำเงิน */
  --f43:#d9467a; --f43s:#fde8ef; --ecl:#2f8fd6; --ecls:#e3f1fc; --dmis:#6b7280; --dmiss:#eceef1;
  --nat:#c93a3a; --nats:#fbe9e9; --plat:#1d4ed8; --plats:#e3eafc;
  --ok:#2e9d5b; --warn:#b26a00;
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    color-scheme:dark;
    --bg:#10151d; --surface:#18202b; --ink:#e6ebf2; --muted:#9aa6b8; --line:#2c3746; --root:#7fb4ec; --hit:#5c4d0a;
    --f43:#f27aa3; --f43s:#3a1d29; --ecl:#67b3ec; --ecls:#15293b; --dmis:#a3abb8; --dmiss:#252b34;
    --nat:#f08080; --nats:#3a1c1c; --plat:#8aa6f5; --plats:#1b2447;
    --ok:#6fcf97; --warn:#f0b65a;
  }
}
:root[data-theme="dark"]{
  color-scheme:dark;
  --bg:#10151d; --surface:#18202b; --ink:#e6ebf2; --muted:#9aa6b8; --line:#2c3746; --root:#7fb4ec; --hit:#5c4d0a;
  --f43:#f27aa3; --f43s:#3a1d29; --ecl:#67b3ec; --ecls:#15293b; --dmis:#a3abb8; --dmiss:#252b34;
  --nat:#f08080; --nats:#3a1c1c; --plat:#8aa6f5; --plats:#1b2447;
  --ok:#6fcf97; --warn:#f0b65a;
}
*{box-sizing:border-box}
[hidden]{display:none!important}
body{margin:0;background:var(--bg);color:var(--ink);font-family:"Leelawadee UI","IBM Plex Sans Thai",Tahoma,sans-serif;font-size:15px;line-height:1.55;padding:0 16px}
.wrap{max-width:980px;margin:0 auto;padding-block:24px 40px}
.code{font-family:Consolas,"IBM Plex Mono",monospace}
button,select,input{font:inherit;color:inherit}
header h1{margin:0;font-size:clamp(26px,5vw,34px);line-height:1.15;color:var(--root)}
header p{margin:2px 0 0;color:var(--muted)}

/* ช่วงเวลา */
.period{display:flex;flex-wrap:wrap;align-items:center;gap:8px 10px;margin:16px 0 12px}
.seg{display:inline-flex;background:var(--surface);border:1px solid var(--line);border-radius:999px;padding:3px}
.seg button{border:0;background:transparent;color:var(--muted);font-weight:600;font-size:13.5px;padding:5px 14px;border-radius:999px;cursor:pointer}
.seg button[aria-pressed="true"]{background:var(--root);color:var(--surface)}
.period select,.period input{font-size:14px;background:var(--surface);border:1px solid var(--line);border-radius:8px;padding:6px 8px;min-width:0}
.quick{display:flex;flex-wrap:wrap;gap:4px}
.quick button{font-size:12.5px;border:1px solid var(--line);background:var(--surface);border-radius:999px;padding:3px 10px;cursor:pointer}
.quick button[aria-pressed="true"]{border-color:var(--root);color:var(--root);font-weight:600}
.range{width:100%;font-size:13px;color:var(--muted)}
.range b{color:var(--root)}
.range .warn{color:var(--warn)}
.period :is(button,select,input):focus-visible{outline:2px solid var(--root);outline-offset:2px}

/* ตัวเลขหลัก 3 ตัว */
.summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(200px,100%),1fr));gap:10px;margin:8px 0 16px}
.sum{background:var(--surface);border:1px solid var(--line);border-left:4px solid var(--c);border-radius:10px;padding:9px 12px}
.sum b{display:block;font-size:22px;font-variant-numeric:tabular-nums;color:var(--c);line-height:1.2}
.sum span{font-size:12.5px;color:var(--muted)}

/* เครื่องมือ */
.tools{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:16px}
.tools input{flex:1 1 220px;min-width:0;padding:8px 12px;border:1px solid var(--line);border-radius:8px;background:var(--surface)}
.tools button{font-size:14px;padding:8px 12px;border:1px solid var(--line);border-radius:8px;background:var(--surface);cursor:pointer}
.tools button:hover{border-color:var(--root)}
.tools :is(input,button):focus-visible{outline:2px solid var(--root);outline-offset:1px}
#count{font-size:13px;color:var(--muted);width:100%}

/* ราก */
.rootnode{display:flex;gap:14px;align-items:center;flex-wrap:wrap;background:var(--root);color:var(--bg);border-radius:12px;padding:12px 16px}
.rootnode svg{width:30px;height:30px;flex:none}
.rootnode strong{display:block;font-size:17px}
.rootnode small{opacity:.85;font-size:12.5px}
.rootnode .tot{margin-left:auto;text-align:right}
.rootnode .tot b{font-size:26px;font-variant-numeric:tabular-nums;line-height:1.1}
.rootnode .tot span{display:block;font-size:12.5px;opacity:.9;font-variant-numeric:tabular-nums}

/* ต้นไม้ */
ul.tree,ul.tree ul{list-style:none;margin:0;padding:0}
ul.tree{margin-left:22px;border-left:2px solid var(--line)}
ul.tree ul{margin-left:18px;border-left:2px solid var(--c,var(--line))}
ul.tree li{position:relative;padding:10px 0 0 22px}
ul.tree li::before{content:"";position:absolute;left:0;top:28px;width:18px;border-top:2px solid var(--c,var(--line))}
ul.tree>li:last-child,ul.tree ul>li:last-child{background:linear-gradient(var(--bg),var(--bg)) 0 30px/2px 100% no-repeat;margin-left:-2px;padding-left:24px}
ul.tree>li:last-child::before,ul.tree ul>li:last-child::before{left:2px}
details>summary{list-style:none;cursor:pointer}
details>summary::-webkit-details-marker{display:none}
.group>summary{display:flex;gap:10px;align-items:center;flex-wrap:wrap;background:var(--cs);border:1px solid var(--c);border-radius:10px;padding:8px 14px}
.group>summary h2{font-size:16px;margin:0}
.group>summary .en{font-size:12.5px;color:var(--muted)}
.group>summary:focus-visible{outline:2px solid var(--c);outline-offset:2px}
.badge{margin-left:auto;font-size:12.5px;font-weight:700;background:var(--c);color:var(--surface);border-radius:999px;padding:1px 10px;font-variant-numeric:tabular-nums;white-space:nowrap}
.chev{width:10px;height:10px;border-right:2px solid var(--c);border-bottom:2px solid var(--c);transform:rotate(-45deg);transition:transform .15s;flex:none}
details[open]>summary .chev{transform:rotate(45deg)}
.leaf{display:flex;gap:10px;align-items:baseline;flex-wrap:wrap;background:var(--surface);border:1px solid var(--line);border-radius:8px;padding:7px 12px;width:100%;text-align:left;cursor:pointer}
.leaf:hover,.leaf:focus-visible{border-color:var(--c);outline:none}
.leaf .code{color:var(--c);font-weight:700;font-size:12.5px;background:var(--cs);border-radius:5px;padding:1px 7px;white-space:nowrap}
.leaf .d{flex:1 1 200px;min-width:0}
.leaf .rt{display:inline-block;margin-left:6px;font-size:11.5px;color:var(--muted);border:1px solid var(--line);border-radius:999px;padding:0 7px;white-space:nowrap}
.leaf .n{margin-left:auto;font-weight:700;font-variant-numeric:tabular-nums;white-space:nowrap;text-align:right}
.leaf .n small{display:block;font-weight:400;font-size:12px;color:var(--muted)}
.leaf .n.zero{color:var(--muted);font-weight:400}
.leaf .todo{margin-left:auto;font-size:12px;color:var(--muted);border:1px dashed var(--line);border-radius:999px;padding:0 9px;white-space:nowrap}
.leaf .todo.metric{color:var(--warn);border-color:var(--warn)}
.leaf.pending{background:transparent}
.leaf.hit{background:var(--hit)}
li.hide{display:none}
footer{margin-top:28px;font-size:12.5px;color:var(--muted);border-top:1px solid var(--line);padding-top:10px}

/* คำอธิบายเงื่อนไข (ชี้เมาส์) */
#tip{position:fixed;z-index:50;max-width:380px;background:var(--surface);color:var(--ink);border:1px solid var(--line);border-top:4px solid var(--tc,var(--root));border-radius:10px;padding:10px 12px;font-size:12.5px;line-height:1.55;box-shadow:0 14px 32px -12px rgba(0,0,0,.35);pointer-events:none;opacity:0;transition:opacity .12s}
#tip.show{opacity:1}
#tip h4{margin:0 0 6px;font-size:13.5px;color:var(--tc)}
#tip dl,.db dl{margin:0;display:grid;grid-template-columns:auto 1fr;gap:3px 10px}
#tip dt,.db dt{color:var(--muted);white-space:nowrap}
#tip dd,.db dd{margin:0}

/* หน้าต่างรายวัน */
dialog{border:none;border-radius:14px;padding:0;width:min(860px,calc(100vw - 32px));max-height:calc(100vh - 40px);background:var(--surface);color:var(--ink);box-shadow:0 20px 60px rgba(0,0,0,.3)}
dialog::backdrop{background:rgba(20,26,40,.45)}
.dh{padding:12px 18px;color:var(--surface);background:var(--c);display:flex;align-items:flex-start;gap:12px}
.dh b{font-size:18px;display:block}
.dh span{font-size:13px;opacity:.9}
.dh button{margin-left:auto;background:transparent;border:1px solid currentColor;color:inherit;border-radius:8px;padding:3px 10px;cursor:pointer}
.db{padding:14px 18px 18px;overflow:auto;max-height:calc(100vh - 130px)}
.db dl{font-size:14px;margin-bottom:12px}
.big{display:flex;gap:28px;margin:6px 0 10px;flex-wrap:wrap}
.big div{font-size:13px;color:var(--muted)}
.big b{display:block;font-size:24px;color:var(--ink);font-variant-numeric:tabular-nums}
.chart{position:relative;height:190px;border-bottom:1px solid var(--line);display:flex;align-items:flex-end;gap:2px;padding-top:8px}
.chart .bar{flex:1;background:var(--c);border-radius:4px 4px 0 0;min-height:1px}
.chart .bar:hover{opacity:.7}
.axis{display:flex;justify-content:space-between;font-size:12px;color:var(--muted);margin-top:4px}
#ctip{position:fixed;pointer-events:none;background:var(--ink);color:var(--bg);font-size:13px;padding:6px 9px;border-radius:6px;z-index:60;white-space:nowrap}
.tbl-t{margin-top:12px;background:none;border:1px solid var(--line);border-radius:8px;padding:4px 10px;font-size:13px;cursor:pointer;color:var(--muted)}
.tw{overflow-x:auto}
table{border-collapse:collapse;width:100%;margin-top:8px;font-size:14px}
th,td{padding:4px 8px;border-bottom:1px solid var(--line);text-align:right;font-variant-numeric:tabular-nums}
th:first-child,td:first-child{text-align:left}
th{color:var(--muted);font-weight:400}
.pend-box{border:1px dashed var(--line);border-radius:10px;padding:12px 14px;color:var(--muted);font-size:14px}
@media (prefers-reduced-motion:reduce){.chev,#tip{transition:none}}
@media (max-width:520px){ul.tree{margin-left:10px}ul.tree ul{margin-left:8px}ul.tree li{padding-left:16px}ul.tree li::before{width:12px}.rootnode .tot{margin-left:0;text-align:left}}
</style>
</head>
<body>
<div class="wrap">
  <header>
    <h1>ช่องทางเคลม 2569</h1>
    <p>จำนวนผู้มารับบริการแยกตามช่องทางเคลม สปสช. 28 ช่องทาง · เงื่อนไขตามประกาศ สปสช. ปีงบ 2569 · นับแล้ว <?= $ready ?>/28 ช่องทาง</p>
  </header>

  <form class="period" id="period" autocomplete="off">
    <div class="seg" role="group" aria-label="เลือกวิธีกำหนดช่วงเวลา">
      <button type="button" id="modeFy" aria-pressed="false">ปีงบประมาณ</button>
      <button type="button" id="modeRange" aria-pressed="true">ช่วงวันที่</button>
    </div>
    <span id="fyFields" hidden><select id="fy" aria-label="ปีงบประมาณ"></select></span>
    <span id="rangeFields"><input type="date" id="dFrom" aria-label="ตั้งแต่วันที่"> – <input type="date" id="dTo" aria-label="ถึงวันที่"></span>
    <div class="quick" id="quick">
      <button type="button" data-q="today">วันนี้</button><button type="button" data-q="yesterday">เมื่อวาน</button>
      <button type="button" data-q="month">เดือนนี้</button><button type="button" data-q="lastmonth">เดือนที่แล้ว</button>
    </div>
    <div class="range" id="rangeText" aria-live="polite"></div>
  </form>

  <div class="summary">
    <div class="sum" style="--c:var(--root)"><b id="kAll">–</b><span id="kAllS">มารับบริการทั้งหมด</span></div>
    <div class="sum" style="--c:var(--ok)"><b id="kIn">–</b><span id="kInS">เข้าอย่างน้อย 1 ช่องทาง</span></div>
    <div class="sum" style="--c:var(--muted)"><b id="kOut">–</b><span id="kOutS">ไม่เข้าช่องทางใด</span></div>
  </div>

  <div class="tools">
    <input id="q" type="search" placeholder="ค้นหา เช่น UCEP, ฟอกไต, HIV, KTB…" aria-label="ค้นหาช่องทาง">
    <button id="openAll" type="button">กางทั้งหมด</button>
    <button id="closeAll" type="button">หุบทั้งหมด</button>
    <div id="count"></div>
  </div>

  <div class="rootnode" aria-live="polite">
    <svg viewBox="0 0 32 32" aria-hidden="true"><rect x="3" y="9" width="26" height="20" rx="2" fill="currentColor" opacity=".25"/><rect x="10" y="3" width="12" height="10" rx="1.5" fill="currentColor"/><path d="M16 5v6M13 8h6" stroke="var(--root)" stroke-width="2" stroke-linecap="round"/><rect x="13" y="20" width="6" height="9" fill="currentColor"/></svg>
    <div><strong>โรงพยาบาล / หน่วยบริการ</strong><small id="sNote">กำลังโหลด…</small></div>
    <div class="tot"><b id="sVisit">–</b> ครั้ง<span id="sSub">&nbsp;</span></div>
  </div>
  <ul class="tree" id="tree"></ul>

  <footer>โครงช่องทางตามอินโฟกราฟิก "ช่องทางเคลม ปีงบประมาณ 2569" (99 Training) · ชี้เมาส์ที่ช่องทางเพื่อดูเงื่อนไข กดเพื่อดูรายวัน ·
    คนหนึ่งคนอยู่ได้หลายช่องทาง ยอดของกิ่งนับไม่ซ้ำภายในกลุ่ม · ช่องที่ระบุ UC = สิทธิ UCS/WEL</footer>
</div>
<div id="tip" role="tooltip" aria-hidden="true"></div>
<div id="ctip" hidden></div>
<dialog id="dlg"><div class="dh"><div><b id="dTitle"></b><span id="dSub"></span></div><button type="button" id="dClose">ปิด</button></div><div class="db" id="dBody"></div></dialog>

<script>
const GROUPS = <?= json_encode(CC_GROUPS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const CH = <?= json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const fmt = n => Number(n).toLocaleString('th-TH');
const MON = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
const th = s => { const [y,m,d] = s.split('-').map(Number); return `${d} ${MON[m-1]} ${y+543}`; };
const iso = t => { const z = new Date(t.getTime() - t.getTimezoneOffset()*60000); return z.toISOString().slice(0,10); };
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const pct = (a, b) => b ? (a / b * 100).toFixed(1) + '%' : '0%';
const WHY = {ask: 'รอคำตอบงานประกัน', metric: 'ต้องตกลงตัวเลข'};

// ---- ต้นไม้ (สร้างครั้งเดียว ตัวเลขเติมทีหลัง) ----
const tree = document.getElementById('tree');
tree.innerHTML = Object.entries(GROUPS).map(([g, x]) => {
  const items = Object.entries(CH).filter(([, c]) => c.g === g);
  return `<li style="--c:var(--${g});--cs:var(--${g}s)">
    <details class="group" open data-g="${g}">
      <summary><span class="chev" aria-hidden="true"></span><h2>${esc(x.name)}</h2><span class="en">${esc(x.en)} · ${items.length} ช่องทาง</span>
        <span class="badge" id="b-${g}">–</span></summary>
      <ul>${items.map(([code, c]) => `<li data-s="${esc((code + ' ' + c.name + ' ' + c.cond + ' ' + c.how).toLowerCase())}">
        <button type="button" class="leaf ${c.status}" data-ch="${esc(code)}">
          <span class="code">${esc(code)}</span>
          <span class="d">${esc(c.name)}<span class="rt">${esc(c.right)}</span></span>
          ${c.status === 'ready' ? `<span class="n" id="n-${esc(code)}">–</span>`
                                 : `<span class="todo ${c.why}">${WHY[c.why] || 'รอเงื่อนไข'}</span>`}
        </button></li>`).join('')}</ul>
    </details></li>`;
}).join('');

// ---- ช่วงเวลา ----
const $fy = document.getElementById('fy'), $f = document.getElementById('dFrom'), $t = document.getElementById('dTo');
const today = new Date(); today.setHours(12);
const curFy = today.getMonth() >= 9 ? today.getFullYear() + 544 : today.getFullYear() + 543;
for (let y = curFy; y >= 2569; y--) $fy.insertAdjacentHTML('beforeend', `<option value="${y}">ปีงบประมาณ ${y} (1 ต.ค. ${y-1} – 30 ก.ย. ${y})</option>`);
function quick(q){
  const y = today.getFullYear(), m = today.getMonth();
  switch(q){
    case 'today': return [iso(today), iso(today)];
    case 'yesterday': { const a = new Date(today); a.setDate(a.getDate()-1); return [iso(a), iso(a)]; }
    case 'month': return [iso(new Date(y, m, 1, 12)), iso(today)];
    case 'lastmonth': return [iso(new Date(y, m-1, 1, 12)), iso(new Date(y, m, 0, 12))];
  }
}
let st = {mode: 'range', fy: curFy, from: iso(today), to: iso(today), q: 'today'};
try { const s = JSON.parse(localStorage.getItem('cc_tree') || 'null'); if (s && s.from) st = {...st, ...s}; } catch(e) {}
function range(){
  if (st.mode === 'fy') return [`${st.fy - 544}-10-01`, `${st.fy - 543}-09-30`];
  return st.from <= st.to ? [st.from, st.to] : [st.to, st.from];
}
function setMode(m){ st.mode = m; apply(); }
document.getElementById('modeFy').onclick = () => setMode('fy');
document.getElementById('modeRange').onclick = () => setMode('range');
$fy.onchange = () => { st.fy = +$fy.value; apply(); };
$f.onchange = $t.onchange = () => { if ($f.value && $t.value) { st.from = $f.value; st.to = $t.value; st.q = null; apply(); } };
document.querySelectorAll('#quick button').forEach(b => b.onclick = () => { [st.from, st.to] = quick(b.dataset.q); st.q = b.dataset.q; apply(); });
document.getElementById('period').onsubmit = e => e.preventDefault();
function apply(){
  try { localStorage.setItem('cc_tree', JSON.stringify(st)); } catch(e) {}
  const fy = st.mode === 'fy';
  document.getElementById('modeFy').setAttribute('aria-pressed', fy);
  document.getElementById('modeRange').setAttribute('aria-pressed', !fy);
  document.getElementById('fyFields').hidden = !fy;
  document.getElementById('rangeFields').hidden = fy;
  document.getElementById('quick').hidden = fy;
  $fy.value = st.fy; $f.value = st.from; $t.value = st.to;
  document.querySelectorAll('#quick button').forEach(b => b.setAttribute('aria-pressed', b.dataset.q === st.q));
  load();
}

// ---- ดึงตัวเลข ----
let token = 0;
async function load(){
  const my = ++token, [a, b] = range();
  setRangeText(a, b, 'กำลังดึงข้อมูล…');
  try {
    const r = await fetch(`api.php?from=${a}&to=${b}`, {cache: 'no-store'});
    const j = await r.json();
    if (my !== token) return;
    if (j.error) { setRangeText(a, b, j.error, true); return; }
    draw(j);
    // ช่วงยาวดึงไม่จบในรอบเดียว -> ดึงต่อ
    if (j.sync.left > 0 || j.sync.busy) setTimeout(() => { if (my === token) load(); }, j.sync.busy ? 4000 : 300);
  } catch(e) { if (my === token) setRangeText(a, b, 'ดึงข้อมูลไม่ได้: ' + e.message, true); }
}
function days(a, b){ return Math.round((new Date(b) - new Date(a)) / 864e5) + 1; }
function setRangeText(a, b, extra, warn){
  const end = b > iso(today) ? iso(today) : b;
  document.getElementById('rangeText').innerHTML = `ช่วงที่เลือก: <b>${th(a)}${a !== b ? ' – ' + th(b) : ''}</b>`
    + (b > iso(today) ? ` · นับถึงวันนี้ รวม ${fmt(days(a, end))} วัน` : (a !== b ? ` · รวม ${fmt(days(a, b))} วัน` : ''))
    + (extra ? ` · <span class="${warn ? 'warn' : ''}">${esc(extra)}</span>` : '');
}
function draw(j){
  const s = j.summary;
  document.getElementById('kAll').textContent = fmt(s.n);
  document.getElementById('kAllS').textContent = `ครั้ง มารับบริการทั้งหมด · ${fmt(s.p)} คน`;
  document.getElementById('kIn').textContent = fmt(s.mn);
  document.getElementById('kInS').textContent = `ครั้ง (${pct(s.mn, s.n)}) เข้าอย่างน้อย 1 ช่องทาง · ${fmt(s.mp)} คน`;
  document.getElementById('kOut').textContent = fmt(s.n - s.mn);
  document.getElementById('kOutS').textContent = `ครั้ง (${pct(s.n - s.mn, s.n)}) ไม่เข้าช่องทางใด · ${fmt(s.p - s.mp)} คน`;
  document.getElementById('sVisit').textContent = fmt(s.n);
  document.getElementById('sSub').textContent = `${fmt(s.p)} คน · OPD ${fmt(s.opd)} · IPD ${fmt(s.ipd)}`;
  document.getElementById('sNote').textContent = `จุดเริ่มต้นการให้บริการ · ข้อมูล HOSxP ณ ${j.at} น.`;
  for (const g in GROUPS) {
    const x = j.groups[g];
    document.getElementById('b-' + g).textContent = x ? `${fmt(x.n)} ครั้ง · ${fmt(x.p)} คน` : '–';
  }
  for (const code in j.totals) {
    const x = j.totals[code], el = document.getElementById('n-' + code);
    if (!el) continue;
    el.className = 'n' + (x.n ? '' : ' zero');
    el.innerHTML = `${fmt(x.n)}<small>${fmt(x.p)} คน · OPD ${fmt(x.opd)} · IPD ${fmt(x.ipd)}</small>`;
  }
  const [a, b] = range(), notes = [];
  let warn = false;
  if (j.missing > 0) { notes.push(`ยังดึงไม่ครบ อีก ${fmt(j.missing)} ช่อง-วัน กำลังดึงต่อ`); warn = true; }
  if (j.sync.busy) { notes.push('มีคนกำลังดึงข้อมูลอยู่ ใช้ยอดในเครื่องไปก่อน'); warn = true; }
  if (j.sync.err.length) { notes.push('ดึงบางช่วงไม่สำเร็จ'); warn = true; }
  if (a < '2026-08-08') notes.push('ก่อน 8 ส.ค. 69 เป็นข้อมูลย้ายระบบ ไม่มี Fee Schedule ยอด KTB/NPRP/OP Anywhere จะต่ำกว่าจริง');
  setRangeText(a, b, notes.join(' · '), warn || notes.length > 0);
}

// ---- ค้นหา / กาง-หุบ ----
const $q = document.getElementById('q');
$q.oninput = () => {
  const q = $q.value.trim().toLowerCase(); let n = 0;
  tree.querySelectorAll('li[data-s]').forEach(li => {
    const hit = q && li.dataset.s.includes(q);
    li.classList.toggle('hide', !!q && !hit);
    li.querySelector('.leaf').classList.toggle('hit', !!hit);
    if (hit) n++;
  });
  tree.querySelectorAll('details.group').forEach(d => { if (q) d.open = !!d.querySelector('li[data-s]:not(.hide)'); });
  document.getElementById('count').textContent = q ? `พบ ${n} ช่องทาง` : '';
};
document.getElementById('openAll').onclick = () => tree.querySelectorAll('details').forEach(d => d.open = true);
document.getElementById('closeAll').onclick = () => tree.querySelectorAll('details').forEach(d => d.open = false);

// ---- คำอธิบายเมื่อชี้เมาส์ ----
const tip = document.getElementById('tip');
tree.addEventListener('mouseover', e => {
  const l = e.target.closest('.leaf'); if (!l) return;
  const c = CH[l.dataset.ch];
  tip.style.setProperty('--tc', `var(--${c.g})`);
  tip.innerHTML = `<h4>${esc(l.dataset.ch)} · ${esc(c.name)}</h4><dl><dt>ตามประกาศ</dt><dd>${esc(c.cond)}</dd>
    <dt>วิธีนับ</dt><dd>${esc(c.how)}</dd><dt>สิทธิ</dt><dd>${esc(c.right)}</dd></dl>`;
  tip.classList.add('show'); tip.setAttribute('aria-hidden', 'false');
});
tree.addEventListener('mousemove', e => {
  if (!tip.classList.contains('show')) return;
  const x = Math.min(e.clientX + 14, innerWidth - tip.offsetWidth - 10);
  const y = e.clientY + 16 + tip.offsetHeight > innerHeight ? e.clientY - tip.offsetHeight - 12 : e.clientY + 16;
  tip.style.left = x + 'px'; tip.style.top = y + 'px';
});
tree.addEventListener('mouseout', e => { if (!e.relatedTarget || !e.relatedTarget.closest?.('.leaf')) { tip.classList.remove('show'); tip.setAttribute('aria-hidden', 'true'); } });

// ---- หน้าต่างรายวัน ----
const dlg = document.getElementById('dlg'), ctip = document.getElementById('ctip');
document.getElementById('dClose').onclick = () => dlg.close();
dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
dlg.addEventListener('close', () => ctip.hidden = true);
tree.addEventListener('click', e => { const l = e.target.closest('.leaf'); if (l) { tip.classList.remove('show'); openCh(l.dataset.ch); } });
async function openCh(code){
  const c = CH[code], [a, b] = range();
  dlg.style.setProperty('--c', `var(--${c.g})`);
  document.getElementById('dTitle').textContent = code;
  document.getElementById('dSub').textContent = c.name;
  const body = document.getElementById('dBody');
  const info = `<dl><dt>ตามประกาศ</dt><dd>${esc(c.cond)}</dd><dt>วิธีนับ</dt><dd>${esc(c.how)}</dd>
    <dt>สิทธิ</dt><dd>${esc(c.right)}</dd><dt>ช่วงเวลา</dt><dd>${th(a)}${a !== b ? ' – ' + th(b) : ''}</dd></dl>`;
  dlg.showModal();
  if (c.status !== 'ready') { body.innerHTML = info + `<div class="pend-box">ยังไม่นับ: ${WHY[c.why] || 'รอเงื่อนไข'}</div>`; return; }
  body.innerHTML = info + '<p>กำลังโหลด…</p>';
  const j = await (await fetch(`api.php?from=${a}&to=${b}&ch=${encodeURIComponent(code)}`, {cache: 'no-store'})).json();
  if (j.error) { body.innerHTML = info + `<p>${esc(j.error)}</p>`; return; }
  const d = j.days, sum = k => d.reduce((s, x) => s + x[k], 0), max = Math.max(1, ...d.map(x => x.n));
  let html = info + `<div class="big"><div>รวม (ครั้ง)<b>${fmt(sum('n'))}</b></div><div>คน<b>${fmt(j.p)}</b></div>
    <div>OPD<b>${fmt(sum('opd'))}</b></div><div>IPD<b>${fmt(sum('ipd'))}</b></div>
    ${d.length > 1 ? `<div>เฉลี่ยต่อวัน<b>${fmt(Math.round(sum('n') / d.length))}</b></div>` : ''}</div>`;
  if (d.length > 1) html += `<div class="chart" id="chart">${d.map((x, i) => `<div class="bar" style="height:${x.n / max * 100}%" data-i="${i}"></div>`).join('')}</div>
    <div class="axis"><span>${th(d[0].d)}</span><span>${th(d[d.length - 1].d)}</span></div>`;
  html += `<button type="button" class="tbl-t" id="tblT">แสดงตารางรายวัน</button><div class="tw" id="tblW" hidden><table>
    <thead><tr><th>วันที่</th><th>OPD</th><th>IPD</th><th>รวม (ครั้ง)</th><th>คน</th></tr></thead><tbody>
    ${d.map(x => `<tr><td>${th(x.d)}</td><td>${fmt(x.opd)}</td><td>${fmt(x.ipd)}</td><td>${fmt(x.n)}</td><td>${fmt(x.p)}</td></tr>`).join('')}</tbody></table></div>`;
  body.innerHTML = html;
  document.getElementById('tblT').onclick = () => { const w = document.getElementById('tblW'); w.hidden = !w.hidden; };
  const ch = document.getElementById('chart');
  if (ch) {
    ch.onmousemove = e => {
      const bar = e.target.closest('.bar'); if (!bar) { ctip.hidden = true; return; }
      const x = d[bar.dataset.i];
      ctip.innerHTML = `<b>${th(x.d)}</b><br>${fmt(x.n)} ครั้ง · ${fmt(x.p)} คน · OPD ${fmt(x.opd)} · IPD ${fmt(x.ipd)}`;
      ctip.hidden = false;
      ctip.style.left = Math.min(e.clientX + 12, innerWidth - ctip.offsetWidth - 8) + 'px';
      ctip.style.top = (e.clientY - 48) + 'px';
    };
    ch.onmouseleave = () => ctip.hidden = true;
  }
}

apply();
</script>
</body>
</html>
