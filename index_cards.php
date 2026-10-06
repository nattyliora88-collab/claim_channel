<?php
require_once __DIR__ . '/channels.php';
require_once __DIR__ . '/auth.php';
auth_require();
$chs = cc_channels();
$ready = count(array_filter($chs, fn($c) => $c['status'] === 'ready'));
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ช่องทางเคลม 2569</title>
<style>
:root{
  --bg:#f4f6f9; --card:#fff; --ink:#1d2433; --ink2:#4a5468; --mute:#8a93a6; --line:#e3e7ee;
  /* สีตามอินโฟกราฟิก 99 Training: ชมพู / ฟ้า / เทา / ขาว-แดง / น้ำเงิน */
  --f43:#e0457b;  --f43s:#fde8f0;
  --ecl:#3a9ae0;  --ecls:#e6f2fc;
  --dmis:#7b828c; --dmiss:#eff1f3;
  --nat:#d23f3f;  --nats:#fff;
  --plat:#1b4f9c; --plats:#e7eefa;
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:15px/1.5 "Leelawadee UI","Leelawadee",Tahoma,sans-serif}
header{background:var(--card);border-bottom:1px solid var(--line);padding:14px 20px;display:flex;flex-wrap:wrap;gap:12px 24px;align-items:center;position:sticky;top:0;z-index:5}
header h1{margin:0;font-size:22px;letter-spacing:.2px;min-width:0;flex:1 1 280px}
header h1 small,.tile .nm{overflow-wrap:anywhere}
header h1 small{display:block;font-size:13px;font-weight:400;color:var(--mute)}
.period{display:flex;flex-wrap:wrap;gap:6px;align-items:center;margin-left:auto}
.period button{border:1px solid var(--line);background:var(--card);color:var(--ink2);border-radius:999px;padding:5px 12px;font:inherit;font-size:14px;cursor:pointer}
.period button.on{background:var(--ink);color:#fff;border-color:var(--ink)}
.period input{border:1px solid var(--line);border-radius:8px;padding:4px 8px;font:inherit;font-size:14px;color:var(--ink);min-width:0;width:150px}
.status{padding:8px 20px;font-size:13px;color:var(--ink2);display:flex;gap:16px;flex-wrap:wrap}
.status .warn{color:#a15c00}
/* วางตำแหน่งตามภาพ: ซ้าย 43 แฟ้ม / โรงพยาบาล / ระดับชาติ  ขวา E-Claim / DMIS / แพลตฟอร์ม */
main{padding:4px 20px 28px;display:grid;gap:16px;grid-template-columns:minmax(0,1fr);grid-template-areas:"hosp" "f43" "ecl" "dmis" "nat" "plat"}
@media (min-width:1000px){main{grid-template-columns:minmax(0,1fr) minmax(0,1.3fr);grid-template-areas:"f43 ecl" "hosp dmis" "nat plat";align-items:start}}
.hosp{grid-area:hosp;align-self:center;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px;text-align:center}
.hosp svg{width:120px;height:auto;display:block;margin:0 auto 8px}
.hosp b{display:block;font-size:17px}
.hosp p{margin:6px auto 0;max-width:340px;font-size:13.5px;color:var(--ink2)}
.hosp .cnt{margin-top:10px;font-size:12.5px;color:var(--mute)}
.tot{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:12px;text-align:left}
.tot div{background:var(--bg);border-radius:10px;padding:8px 10px;min-width:0}
.tot span{display:block;font-size:12px;color:var(--ink2)}
.tot b{display:block;font-size:17px;font-variant-numeric:tabular-nums;line-height:1.3}
.tot small{display:block;font-size:12px;color:var(--mute)}
.tile .rt{font-size:11.5px;color:var(--ink2);background:var(--card);border:1px solid var(--line);border-radius:999px;padding:0 8px;align-self:flex-start;margin-top:4px}
section.g{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden}
section.g h2{margin:0;padding:10px 16px;font-size:16px;color:#fff;background:var(--c)}
section.g.nat h2{background:#fff;color:var(--ink);border-bottom:3px solid var(--c)}
section.g h2 small{font-weight:400;opacity:.85;margin-left:6px;font-size:13px}
.tiles{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(150px,100%),1fr));gap:10px;padding:12px}
.tile{border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:var(--cs);cursor:pointer;text-align:left;font:inherit;color:inherit;display:flex;flex-direction:column;gap:2px;min-height:108px}
.tile:hover{border-color:var(--c)}
.tile .code{font:600 12px/1.2 Consolas,monospace;color:var(--c)}
.tile .nm{font-size:13.5px;color:var(--ink2);flex:1}
.tile .n{font-size:26px;font-weight:700;font-variant-numeric:tabular-nums;line-height:1.1}
.tile .n small{font-size:13px;font-weight:400;color:var(--mute)}
.tile .sub{font-size:12px;color:var(--mute)}
.tile.pending{background:#fafbfc}
.tile.pending .n{font-size:14px;font-weight:400;color:var(--mute)}
.tile.loading .n{color:var(--line)}
/* หน้าต่างรายละเอียด */
dialog{border:none;border-radius:14px;padding:0;width:min(860px,calc(100vw - 32px));max-height:calc(100vh - 40px);box-shadow:0 20px 60px rgba(0,0,0,.25)}
dialog::backdrop{background:rgba(20,26,40,.45)}
.dh{padding:14px 18px;color:#fff;background:var(--c);display:flex;align-items:flex-start;gap:12px}
.dh b{font-size:18px;display:block}
.dh span{font-size:13px;opacity:.9}
.dh button{margin-left:auto;background:transparent;border:1px solid rgba(255,255,255,.6);color:#fff;border-radius:8px;padding:3px 10px;font:inherit;cursor:pointer}
.db{padding:14px 18px 18px;overflow:auto;max-height:calc(100vh - 140px)}
.db dl{display:grid;grid-template-columns:110px 1fr;gap:4px 12px;margin:0 0 12px;font-size:14px}
.db dt{color:var(--mute)}
.big{display:flex;gap:28px;margin:6px 0 10px;flex-wrap:wrap}
.big div{font-size:13px;color:var(--mute)}
.big b{display:block;font-size:26px;color:var(--ink);font-variant-numeric:tabular-nums}
.chart{position:relative;height:200px;border-bottom:1px solid var(--line);display:flex;align-items:flex-end;gap:2px;padding-top:8px}
.chart .bar{flex:1;background:var(--c);border-radius:4px 4px 0 0;min-height:1px;position:relative}
.chart .bar:hover{opacity:.75}
.chart .hit{position:absolute;left:-1px;right:-1px;top:-200px;bottom:0}
.axis{display:flex;justify-content:space-between;font-size:12px;color:var(--mute);margin-top:4px}
.tip{position:fixed;pointer-events:none;background:var(--ink);color:#fff;font-size:13px;padding:6px 9px;border-radius:6px;display:none;z-index:20;white-space:nowrap}
.tbl-t{margin-top:12px;background:none;border:1px solid var(--line);border-radius:8px;padding:4px 10px;font:inherit;font-size:13px;cursor:pointer;color:var(--ink2)}
table{border-collapse:collapse;width:100%;margin-top:8px;font-size:14px}
th,td{padding:4px 8px;border-bottom:1px solid var(--line);text-align:right;font-variant-numeric:tabular-nums}
th:first-child,td:first-child{text-align:left}
th{color:var(--mute);font-weight:400}
.pend-box{background:#fafbfc;border:1px dashed var(--line);border-radius:10px;padding:12px 14px;color:var(--ink2);font-size:14px}
@media (max-width:640px){header,main,.status{padding-left:16px;padding-right:16px}.period{margin-left:0}.period input{width:calc(50% - 20px)}}
</style>
</head>
<body>
<header>
  <h1>ช่องทางเคลม ปีงบประมาณ 2569<small>จำนวนมารับบริการตามช่องทางเบิก สปสช. 28 ช่องทาง · นับแล้ว <?= $ready ?>/28 ช่อง</small></h1>
  <div class="period" id="period">
    <button data-p="today">วันนี้</button>
    <button data-p="yday">เมื่อวาน</button>
    <button data-p="month">เดือนนี้</button>
    <button data-p="pmonth">เดือนที่แล้ว</button>
    <button data-p="fy69">ปีงบ 2569</button>
    <button data-p="fy70">ปีงบ 2570</button>
    <input type="date" id="dFrom" aria-label="ตั้งแต่วันที่"> ถึง <input type="date" id="dTo" aria-label="ถึงวันที่">
  </div>
</header>
<div class="status" id="status"></div>
<main>
  <div class="hosp">
    <svg viewBox="0 0 120 80" aria-hidden="true"><rect x="10" y="22" width="100" height="54" rx="4" fill="#e7eefa" stroke="#1b4f9c" stroke-width="2"/><rect x="38" y="6" width="44" height="26" rx="3" fill="#fff" stroke="#1b4f9c" stroke-width="2"/><path d="M60 11v16M52 19h16" stroke="#d23f3f" stroke-width="4" stroke-linecap="round"/><g fill="#9fbbe6"><rect x="20" y="38" width="12" height="10" rx="1"/><rect x="40" y="38" width="12" height="10" rx="1"/><rect x="68" y="38" width="12" height="10" rx="1"/><rect x="88" y="38" width="12" height="10" rx="1"/></g><rect x="52" y="54" width="16" height="22" rx="1" fill="#1b4f9c"/></svg>
    <b>โรงพยาบาล / หน่วยบริการ</b>
    <p>จุดเริ่มต้นของการให้บริการแก่ผู้มีสิทธิ และรวบรวมข้อมูลเพื่อส่งเคลมตามช่องทางที่ สปสช. กำหนด</p>
    <div class="tot" id="tot">
      <div><span>มารับบริการทั้งหมด</span><b id="tN">-</b><small id="tP"></small></div>
      <div><span>เข้าอย่างน้อย 1 ช่องทาง</span><b id="mN">-</b><small id="mP"></small></div>
      <div><span>ไม่เข้าช่องทางใด</span><b id="xN">-</b><small id="xP"></small></div>
    </div>
    <div class="cnt">นับแล้ว <?= $ready ?> จาก 28 ช่องทาง · คนหนึ่งคนอยู่ได้หลายช่อง ยอดแต่ละช่องรวมกันจึงไม่เท่ากับยอดรวม</div>
  </div>
<?php foreach (CC_GROUPS as $gk => $g): ?>
  <section class="g <?= $gk ?>" style="grid-area:<?= $gk ?>;--c:var(--<?= $gk ?>);--cs:var(--<?= $gk ?>s)">
    <h2><?= $h($g['name']) ?><small><?= $h($g['en']) ?></small></h2>
    <div class="tiles">
    <?php foreach ($chs as $c): if ($c['g'] !== $gk) continue; ?>
      <button type="button" class="tile <?= $c['status'] ?>" data-ch="<?= $h($c['code']) ?>">
        <span class="code"><?= $h($c['code']) ?></span>
        <span class="nm"><?= $h($c['name']) ?></span>
        <span class="n"><?= $c['status'] === 'ready' ? '&nbsp;' : ($c['why'] === 'metric' ? 'ต้องตกลงตัวเลข' : 'รอคำตอบงานประกัน') ?></span>
        <span class="sub"></span>
        <span class="rt"><?= $h($c['right']) ?></span>
      </button>
    <?php endforeach; ?>
    </div>
  </section>
<?php endforeach; ?>
</main>

<dialog id="dlg"><div class="dh"><div><b id="dTitle"></b><span id="dSub"></span></div><button type="button" id="dClose">ปิด</button></div><div class="db" id="dBody"></div></dialog>
<div class="tip" id="tip"></div>

<script>
const GROUP = <?= json_encode(array_map(fn($c) => $c['g'], $chs)) ?>;
const fmt = n => Number(n).toLocaleString('th-TH');
const MON = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
const th = s => { const [y,m,d] = s.split('-').map(Number); return `${d} ${MON[m-1]} ${String(y+543).slice(2)}`; };
const iso = t => { const z = new Date(t.getTime() - t.getTimezoneOffset()*60000); return z.toISOString().slice(0,10); };
const esc = s => String(s).replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));

function preset(p){
  const t = new Date(); t.setHours(12);
  const y = t.getFullYear(), m = t.getMonth();
  switch(p){
    case 'today':  return [iso(t), iso(t)];
    case 'yday':   { const a = new Date(t); a.setDate(a.getDate()-1); return [iso(a), iso(a)]; }
    case 'month':  return [iso(new Date(y, m, 1, 12)), iso(t)];
    case 'pmonth': return [iso(new Date(y, m-1, 1, 12)), iso(new Date(y, m, 0, 12))];
    case 'fy69':   return ['2025-10-01', '2026-09-30'];
    case 'fy70':   return ['2026-10-01', '2027-09-30'];
  }
}
let range = {from: null, to: null, p: null};
try { const s = JSON.parse(localStorage.getItem('cc_range') || 'null'); if (s && s.from) range = s; } catch(e) {}
if (!range.from) { const [a,b] = preset('today'); range = {from: a, to: b, p: 'today'}; }

const $f = document.getElementById('dFrom'), $t = document.getElementById('dTo');
function setRange(a, b, p){
  if (a > b) [a, b] = [b, a];
  range = {from: a, to: b, p: p || null};
  try { localStorage.setItem('cc_range', JSON.stringify(range)); } catch(e) {}
  load();
}
document.querySelectorAll('#period button').forEach(b => b.onclick = () => { const [a,c] = preset(b.dataset.p); setRange(a, c, b.dataset.p); });
$f.onchange = $t.onchange = () => { if ($f.value && $t.value) setRange($f.value, $t.value, null); };

let token = 0, last = null;
async function load(){
  const my = ++token;
  $f.value = range.from; $t.value = range.to;
  document.querySelectorAll('#period button').forEach(b => b.classList.toggle('on', b.dataset.p === range.p));
  document.querySelectorAll('.tile.ready').forEach(t => t.classList.add('loading'));
  status('กำลังดึงข้อมูล...');
  try {
    const r = await fetch(`api.php?from=${range.from}&to=${range.to}`, {cache: 'no-store'});
    const j = await r.json();
    if (my !== token) return;
    if (j.error) { status(j.error, true); return; }
    last = j; draw(j);
    // ยังดึงไม่ครบ (ช่วงยาว ดึงครั้งละไม่เกิน 90 วิ) -> ดึงต่อ
    if (j.sync.left > 0 || j.sync.busy) setTimeout(() => { if (my === token) load(); }, j.sync.busy ? 4000 : 300);
  } catch(e) { if (my === token) status('ดึงข้อมูลไม่ได้: ' + e.message, true); }
}
function status(msg, warn){ document.getElementById('status').innerHTML = `<span class="${warn?'warn':''}">${esc(msg)}</span>`; }

function draw(j){
  document.querySelectorAll('.tile.ready').forEach(t => {
    const x = j.totals[t.dataset.ch]; t.classList.remove('loading');
    if (!x) return;
    t.querySelector('.n').innerHTML = `${fmt(x.n)} <small>ครั้ง</small>`;
    t.querySelector('.sub').textContent = `${fmt(x.p)} คน · OPD ${fmt(x.opd)} · IPD ${fmt(x.ipd)}`;
  });
  const s = j.summary, pct = (a, b) => b ? ` (${(a / b * 100).toFixed(1)}%)` : '';
  document.getElementById('tN').textContent = fmt(s.n) + ' ครั้ง';
  document.getElementById('tP').textContent = fmt(s.p) + ' คน';
  document.getElementById('mN').textContent = fmt(s.mn) + ' ครั้ง' + pct(s.mn, s.n);
  document.getElementById('mP').textContent = fmt(s.mp) + ' คน' + pct(s.mp, s.p);
  document.getElementById('xN').textContent = fmt(s.n - s.mn) + ' ครั้ง' + pct(s.n - s.mn, s.n);
  document.getElementById('xP').textContent = fmt(s.p - s.mp) + ' คน' + pct(s.p - s.mp, s.p);
  // [ข้อความ, เป็นคำเตือนไหม]
  const parts = [[`${th(j.from)}${j.from !== j.to ? ' ถึง ' + th(j.to) : ''} · อัปเดต ${j.at} น.`, false]];
  if (j.missing > 0) parts.push([`ยังดึงไม่ครบ อีก ${fmt(j.missing)} วัน กำลังดึงต่อ`, true]);
  if (j.sync.busy) parts.push(['มีคนกำลังดึงข้อมูลอยู่ ใช้ยอดในเครื่องไปก่อน', true]);
  if (j.sync.err.length) parts.push(['ดึงบางช่วงไม่สำเร็จ: ' + j.sync.err[0], true]);
  if (j.from < '2026-08-08') parts.push(['ก่อน 8 ส.ค. 69 เป็นข้อมูลย้ายระบบ ยังไม่มี Fee Schedule ยอดจะต่ำกว่าจริง', true]);
  document.getElementById('status').innerHTML = parts.map(([p, w]) => `<span class="${w ? 'warn' : ''}">${esc(p)}</span>`).join('');
}

// ---- หน้าต่างรายละเอียดช่อง ----
const dlg = document.getElementById('dlg'), tip = document.getElementById('tip');
document.getElementById('dClose').onclick = () => dlg.close();
dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
document.querySelectorAll('.tile').forEach(t => t.onclick = () => openCh(t.dataset.ch, t));

async function openCh(code, tile){
  const g = GROUP[code];
  dlg.style.setProperty('--c', `var(--${g})`);
  document.getElementById('dTitle').textContent = code;
  document.getElementById('dSub').textContent = tile.querySelector('.nm').textContent;
  const body = document.getElementById('dBody');
  body.innerHTML = '<p>กำลังโหลด...</p>';
  dlg.showModal();
  const r = await fetch(`api.php?from=${range.from}&to=${range.to}&ch=${encodeURIComponent(code)}`, {cache: 'no-store'});
  const j = await r.json();
  if (j.error) { body.innerHTML = `<p>${esc(j.error)}</p>`; return; }
  const info = `<dl><dt>ตามประกาศ</dt><dd>${esc(j.cond)}</dd><dt>วิธีนับ</dt><dd>${esc(j.how)}</dd><dt>สิทธิที่กรอง</dt><dd>${esc(j.rights)}</dd>
    <dt>ช่วงเวลา</dt><dd>${th(range.from)}${range.from !== range.to ? ' ถึง ' + th(range.to) : ''}</dd></dl>`;
  if (j.status !== 'ready') {
    body.innerHTML = info + `<div class="pend-box">ช่องนี้ยังรอเงื่อนไข ยังไม่นับ</div>`;
    return;
  }
  const d = j.days, sum = k => d.reduce((s, x) => s + x[k], 0);
  const max = Math.max(1, ...d.map(x => x.n));
  let html = info + `<div class="big"><div>รวม (ครั้ง)<b>${fmt(sum('n'))}</b></div><div>คน<b>${fmt(j.p)}</b></div><div>OPD<b>${fmt(sum('opd'))}</b></div><div>IPD<b>${fmt(sum('ipd'))}</b></div>
    ${d.length > 1 ? `<div>เฉลี่ยต่อวัน<b>${fmt(Math.round(sum('n')/d.length))}</b></div>` : ''}</div>`;
  if (d.length > 1) {
    html += `<div class="chart" id="chart">${d.map((x,i) => `<div class="bar" style="height:${x.n/max*100}%" data-i="${i}"><span class="hit"></span></div>`).join('')}</div>
      <div class="axis"><span>${th(d[0].d)}</span><span>${th(d[d.length-1].d)}</span></div>`;
  }
  html += `<button type="button" class="tbl-t" id="tblT">${d.length > 1 ? 'แสดงตารางรายวัน' : 'แสดงตาราง'}</button><div id="tblW" hidden>
    <table><thead><tr><th>วันที่</th><th>OPD</th><th>IPD</th><th>รวม</th></tr></thead><tbody>
    ${d.map(x => `<tr><td>${th(x.d)}</td><td>${fmt(x.opd)}</td><td>${fmt(x.ipd)}</td><td>${fmt(x.n)}</td></tr>`).join('')}</tbody></table></div>`;
  body.innerHTML = html;
  document.getElementById('tblT').onclick = () => { const w = document.getElementById('tblW'); w.hidden = !w.hidden; };
  const ch = document.getElementById('chart');
  if (ch) {
    ch.addEventListener('mousemove', e => {
      const b = e.target.closest('.bar'); if (!b) { tip.style.display = 'none'; return; }
      const x = d[b.dataset.i];
      tip.innerHTML = `<b>${th(x.d)}</b><br>รวม ${fmt(x.n)} · OPD ${fmt(x.opd)} · IPD ${fmt(x.ipd)}`;
      tip.style.display = 'block';
      tip.style.left = Math.min(e.clientX + 12, innerWidth - tip.offsetWidth - 8) + 'px';
      tip.style.top = (e.clientY - 44) + 'px';
    });
    ch.addEventListener('mouseleave', () => tip.style.display = 'none');
  }
}
dlg.addEventListener('close', () => tip.style.display = 'none');

load();
</script>
</body>
</html>
