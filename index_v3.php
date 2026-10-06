<?php
// ==========================================================================
//  ช่องทางเคลม 2569 - หน้าหลักแบบ ClaimMap (mazza 5 ต.ค. 69: "แบบนี้นะครับ" ภาพหน้าหลัก claim_map)
//  แถบเทียบยอดตามกลุ่ม (โดนัท เข้า/ไม่เข้า + การ์ด 5 กลุ่ม + ช่องยอดสูงสุด)
//  ล่าง 3 คอลัมน์: ซ้าย 43 แฟ้ม + E-Claim · กลาง ยอดรวม + ระดับชาติ · ขวา DMIS + แพลตฟอร์ม
//  หน้าสำรอง: index_tree.php (ต้นไม้) · index_cards.php (การ์ดตามอินโฟกราฟิก) · เงื่อนไขอยู่ที่ channels.php
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
@font-face{font-family:"Sarabun";font-weight:400;font-display:swap;src:url(assets/fonts/sarabun-thai-400.woff2) format("woff2");unicode-range:U+0E01-0E5B,U+200C-200D,U+25CC}
@font-face{font-family:"Sarabun";font-weight:600;font-display:swap;src:url(assets/fonts/sarabun-thai-600.woff2) format("woff2");unicode-range:U+0E01-0E5B,U+200C-200D,U+25CC}
@font-face{font-family:"Sarabun";font-weight:700;font-display:swap;src:url(assets/fonts/sarabun-thai-700.woff2) format("woff2");unicode-range:U+0E01-0E5B,U+200C-200D,U+25CC}
@font-face{font-family:"Sarabun";font-weight:400;font-display:swap;src:url(assets/fonts/sarabun-latin-400.woff2) format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+2000-206F,U+20AC,U+2122,U+2212}
@font-face{font-family:"Sarabun";font-weight:600;font-display:swap;src:url(assets/fonts/sarabun-latin-600.woff2) format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+2000-206F,U+20AC,U+2122,U+2212}
@font-face{font-family:"Sarabun";font-weight:700;font-display:swap;src:url(assets/fonts/sarabun-latin-700.woff2) format("woff2");unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+2000-206F,U+20AC,U+2122,U+2212}
:root{
  --bg:#f5f8fc; --surface:#fff; --ink:#172a43; --muted:#62748a; --line:#dce6f0;
  --title:#123d63; --title2:#167f98; --go:#0f7d89; --shadow:rgba(24,54,87,.12); --shadow2:rgba(24,54,87,.07);
  /* สีกลุ่มตามอินโฟกราฟิก 99 Training (โทนเดียวกับ ClaimMap) */
  --f43:#c4497a; --f43s:#fcf1f6; --f43b:#f0d6e2;
  --ecl:#327daf; --ecls:#f0f7fc; --eclb:#d5e6f2;
  --dmis:#707e91; --dmiss:#f4f6f8; --dmisb:#e0e5eb;
  --nat:#c0413f; --nats:#fdf2f2; --natb:#f2d5d4;
  --plat:#2f55a8; --plats:#eff3fb; --platb:#d6dff3;
  --in:#1f9d83; --out:#c3cfdb; --opd:#5fd6c8; --ipd:#ffc36b; --warn:#a86400;
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    color-scheme:dark;
    --bg:#101b29; --surface:#1a293a; --ink:#e9f1f8; --muted:#a4b5c7; --line:#32475e;
    --title:#d6eafa; --title2:#71cbd3; --go:#169aa7; --shadow:rgba(0,0,0,.28); --shadow2:rgba(0,0,0,.16);
    --f43:#ec86ae; --f43s:#2f1c27; --f43b:#5c3349;
    --ecl:#6db4e6; --ecls:#152a3c; --eclb:#2b4d68;
    --dmis:#a9b3c0; --dmiss:#222c38; --dmisb:#3f4b5a;
    --nat:#ee8a88; --nats:#311d20; --natb:#5e3437;
    --plat:#8ea9ee; --plats:#1a2443; --platb:#34457a;
    --in:#4ccdb2; --out:#3c4e63; --warn:#f0b65a;
  }
}
:root[data-theme="dark"]{
  color-scheme:dark;
  --bg:#101b29; --surface:#1a293a; --ink:#e9f1f8; --muted:#a4b5c7; --line:#32475e;
  --title:#d6eafa; --title2:#71cbd3; --go:#169aa7; --shadow:rgba(0,0,0,.28); --shadow2:rgba(0,0,0,.16);
  --f43:#ec86ae; --f43s:#2f1c27; --f43b:#5c3349;
  --ecl:#6db4e6; --ecls:#152a3c; --eclb:#2b4d68;
  --dmis:#a9b3c0; --dmiss:#222c38; --dmisb:#3f4b5a;
  --nat:#ee8a88; --nats:#311d20; --natb:#5e3437;
  --plat:#8ea9ee; --plats:#1a2443; --platb:#34457a;
  --in:#4ccdb2; --out:#3c4e63; --warn:#f0b65a;
}
*{box-sizing:border-box}
[hidden]{display:none!important}
html{scroll-behavior:smooth}
body{margin:0;padding:0 24px;background:var(--bg);color:var(--ink);font-family:"Sarabun","Leelawadee UI",Tahoma,sans-serif;font-size:16px;line-height:1.55}
button,select,input{font:inherit;color:inherit}
.wrap{padding-block:28px 56px}
.mono{font-family:Consolas,"IBM Plex Mono",monospace}
:where(button,summary,a,select,input):focus-visible{outline:3px solid #10a4ae;outline-offset:2px}

/* ---- หัว + ตัวเลือกช่วงเวลา (แถวเดียว) ---- */
.top{display:flex;flex-wrap:wrap;align-items:center;gap:16px 28px;margin-bottom:22px}
.top h1{margin:0;font-size:clamp(34px,3.2vw,50px);line-height:1.05;letter-spacing:-.03em;color:var(--title);font-weight:700}
.top h1 small{display:block;font-size:14px;letter-spacing:0;font-weight:400;color:var(--muted);margin-top:4px}
.period{flex:1;display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;padding:14px 22px;background:var(--surface);border:1px solid var(--line);border-radius:20px;box-shadow:0 12px 34px var(--shadow2)}
.seg{display:inline-flex;background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:4px}
.seg button{border:0;background:transparent;color:var(--muted);font-weight:600;font-size:15px;padding:8px 16px;border-radius:9px;cursor:pointer;white-space:nowrap}
.seg button[aria-pressed="true"]{background:var(--title);color:var(--surface)}
.field{display:flex;align-items:center;gap:7px;font-size:13.5px;color:var(--muted);white-space:nowrap}
.field select,.field input{height:40px;padding:6px 10px;font-size:15px;border:1px solid var(--line);border-radius:9px;background:var(--bg);min-width:0}
.go{height:40px;padding:6px 18px;border:0;border-radius:9px;background:var(--go);color:#fff;font-weight:700;cursor:pointer}
.go:hover{filter:brightness(.92)}
.quick{display:flex;flex-wrap:wrap;gap:6px}
.quick button{font-size:13.5px;padding:5px 11px;border:1px solid var(--line);background:var(--surface);border-radius:8px;cursor:pointer;white-space:nowrap}
.quick button[aria-pressed="true"]{border-color:var(--title);color:var(--title);font-weight:700}
.range{font-size:14px;color:var(--muted);border-left:1px solid var(--line);padding-left:16px}
.range b{color:var(--title)}
.range .warn{color:var(--warn)}
@media(max-width:1400px){.range{border-left:0;padding-left:0;flex-basis:100%}}

/* ---- แถบเทียบยอดตามกลุ่ม ---- */
.cmp{margin-bottom:28px;padding:20px 24px 22px;background:linear-gradient(135deg,var(--surface),color-mix(in srgb,#dff1f5 40%,var(--surface)));border:1px solid var(--line);border-radius:22px;box-shadow:0 12px 34px var(--shadow2)}
.cmp-top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:baseline;gap:6px 16px;margin-bottom:14px}
.cmp-h{font-size:19px;font-weight:700;color:var(--title)}
.cmp-h small{font-size:13px;font-weight:400;color:var(--muted);margin-left:8px}
.cmp-note{font-size:13px;color:var(--muted)}
.cmp-body{display:grid;grid-template-columns:230px minmax(0,1.7fr) minmax(250px,1fr);gap:24px;align-items:center}
.dn{position:relative;width:230px;aspect-ratio:1}
.dn svg{width:100%;height:100%;transform:rotate(-90deg)}
.dn circle{fill:none;stroke-width:15}
.dn .seg-in{stroke:var(--in);transition:stroke-dasharray .7s ease}
.dn .seg-out{stroke:var(--out)}
.dn-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;line-height:1.1}
.dn-c span{font-size:13px;color:var(--muted)}
.dn-c b{font-size:31px;font-weight:700;font-variant-numeric:tabular-nums;margin:3px 0}
.dn-lg{display:flex;justify-content:center;gap:14px;margin-top:8px;font-size:13px;color:var(--muted)}
.dn-lg i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:5px;vertical-align:-1px}
.gcards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
.gcard{position:relative;display:flex;flex-direction:column;gap:2px;padding:13px 14px 12px 18px;border:1px solid var(--line);border-radius:14px;background:var(--surface);overflow:hidden;text-decoration:none;color:var(--ink);transition:transform .15s,box-shadow .15s}
.gcard::before{content:"";position:absolute;left:0;top:0;bottom:0;width:6px;background:var(--c)}
.gcard:hover{transform:translateY(-2px);box-shadow:0 10px 20px -12px var(--shadow)}
.gcard span{font-size:13.5px;font-weight:600;color:var(--muted);line-height:1.3}
.gcard b{font-size:26px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums;line-height:1.15}
.gcard small{font-size:12.5px;color:var(--muted)}
.gcard em{align-self:flex-start;font-style:normal;font-size:12.5px;font-weight:700;padding:2px 9px;border-radius:999px;color:var(--c);background:color-mix(in srgb,var(--c) 12%,transparent)}
.cmp-side{align-self:stretch;display:flex;flex-direction:column;padding-left:22px;border-left:1px dashed var(--line)}
.cmp-sec{margin:0 0 10px;font-size:14px;font-weight:700;color:var(--title)}
.tops{display:flex;flex-direction:column;gap:10px;flex:1}
.topc{display:flex;align-items:center;gap:12px;padding:11px 14px;border:1px solid var(--cb);border-radius:12px;background:var(--cs);cursor:pointer;text-align:left;width:100%}
.topc b{min-width:76px;font-size:23px;font-weight:700;color:var(--c);font-variant-numeric:tabular-nums}
.topc span{flex:1;font-size:13.5px;font-weight:600;line-height:1.3}
.topc small{font-size:12px;color:var(--muted);text-align:right}
@media(max-width:1300px){.cmp-body{grid-template-columns:210px minmax(0,1fr)}.dn{width:210px}.cmp-side{grid-column:1/-1;padding:16px 0 0;border-left:0;border-top:1px dashed var(--line)}.tops{flex-direction:row;flex-wrap:wrap}.topc{flex:1 1 220px}}
@media(max-width:820px){.cmp-body{grid-template-columns:1fr}.dn{margin:0 auto}}

/* ---- 3 คอลัมน์ ---- */
.poster{display:grid;grid-template-columns:minmax(0,1fr) minmax(340px,440px) minmax(0,1fr);gap:28px;align-items:start}
.pcol{display:flex;flex-direction:column;gap:24px;min-width:0}
@media(max-width:1250px){.poster{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.pmid{grid-column:1/-1;order:-1}}
@media(max-width:760px){.poster{grid-template-columns:1fr}body{padding:0 16px}}

.box{padding:21px;background:var(--surface);border:1px solid var(--line);border-radius:18px;box-shadow:0 8px 28px var(--shadow2),0 1px 3px var(--shadow2);scroll-margin-top:16px}
.box:hover{border-color:var(--cb)}
.box>.head{margin:0 0 16px;padding:0 0 13px;border-bottom:1px solid var(--line)}
.box>.head::before{content:"";display:block;width:34px;height:4px;margin-bottom:12px;border-radius:4px;background:var(--c)}
.head-row{display:flex;align-items:flex-start;gap:12px}
.head-row div{flex:1;min-width:0}
.box>.head b{display:block;font-size:19px;line-height:1.35}
.box>.head small{display:block;margin-top:3px;font-size:13px;color:var(--muted)}
.head-n{text-align:right;white-space:nowrap}
.head-n b{font-size:26px!important;color:var(--c);font-variant-numeric:tabular-nums;line-height:1.1}
.head-n small{margin-top:0!important}
.rows{display:flex;flex-direction:column;gap:8px}
.row{display:flex;align-items:center;gap:12px;width:100%;padding:8px 12px 8px 8px;background:var(--surface);border:1px solid var(--line);border-radius:12px;text-align:left;cursor:pointer;transition:border-color .15s,background .15s}
.row:hover{border-color:var(--cb);background:var(--cs)}
.ico{flex:none;display:grid;place-items:center;width:42px;height:42px;border-radius:11px;background:var(--cs);border:1px solid var(--cb);color:var(--c);font:700 12px/1 Consolas,"IBM Plex Mono",monospace;letter-spacing:-.02em}
.row .tx{flex:1;min-width:0}
.row .nm{display:block;font-size:14.5px;font-weight:600;line-height:1.3}
.row .sb{display:block;font-size:12.5px;color:var(--muted);margin-top:1px}
.row .sb .code{font-family:Consolas,"IBM Plex Mono",monospace;color:var(--c);font-weight:600}
.row .n{text-align:right;font-size:21px;font-weight:700;color:var(--c);font-variant-numeric:tabular-nums;white-space:nowrap}
.row .n.zero{color:var(--muted);font-weight:400}
.row .chev{flex:none;color:var(--muted);font-size:13px}
.row.pending{background:transparent;cursor:pointer}
.row.pending .ico{background:transparent;border-style:dashed;color:var(--muted)}
.row.pending .nm{color:var(--muted)}
.pill{flex:none;font-size:12px;color:var(--muted);border:1px dashed var(--line);border-radius:999px;padding:1px 9px;white-space:nowrap}
.pill.metric{color:var(--warn);border-color:var(--warn)}

/* ---- กล่องกลาง: ยอดรวม ---- */
.stat{padding:22px 20px;border-radius:18px;color:#fff;background:linear-gradient(145deg,#123d63,#0b6677);box-shadow:0 14px 38px var(--shadow2)}
.st-head{font-size:14px;color:#c8eaf0;font-weight:600}
.st-head small{font-weight:400;opacity:.85}
.st-chart{display:flex;align-items:center;gap:18px;margin:14px 0 6px}
.st-dn{position:relative;flex:none;width:170px;height:170px}
.st-dn svg{width:100%;height:100%;transform:rotate(-90deg)}
.st-dn circle{fill:none;stroke-width:14}
.st-dn .tr{stroke:rgba(255,255,255,.14)}
.st-dn .o{stroke:var(--opd);transition:stroke-dasharray .7s ease}
.st-dn .i{stroke:var(--ipd);transition:stroke-dasharray .7s ease,stroke-dashoffset .7s ease}
.st-dn .c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.05}
.st-dn .c b{font-size:30px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.03em}
.st-dn .c b.sm{font-size:23px}
.st-dn .c span{color:#c8eaf0;font-size:13px;margin-top:3px}
.st-lg{flex:1;min-width:0;display:flex;flex-direction:column;gap:11px}
.st-lg>div{display:grid;grid-template-columns:auto 1fr;column-gap:8px;align-items:center}
.st-lg i{grid-row:1/3;width:12px;height:12px;border-radius:3px}
.st-lg span{color:#c8eaf0;font-size:13.5px}
.st-lg b{font-size:22px;font-variant-numeric:tabular-nums;line-height:1.15}
.st-lg small{grid-column:2;color:#9fdcd4;font-size:12.5px;font-weight:600}
.st-lg .hn{grid-template-columns:1fr;padding-top:9px;border-top:1px solid rgba(255,255,255,.22)}
.st-base{margin-top:14px;padding:12px 14px;border-radius:12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);font-size:13.5px;color:#d9f1f4}
.st-base b{color:#fff;font-variant-numeric:tabular-nums}
.st-base .bar{display:flex;height:10px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.14);margin-top:8px}
.st-base .bar i{display:block;background:#4ee0c2;transition:width .7s ease}
.st-foot{margin-top:14px;font-size:12.5px;color:#b6dae4}
.st-foot b{color:#fff}
@media(max-width:440px){.st-chart{flex-direction:column;align-items:stretch}.st-dn{align-self:center}}
footer{margin-top:44px;padding-top:16px;border-top:1px solid var(--line);font-size:12.5px;color:var(--muted)}

/* ---- คำอธิบายเมื่อชี้เมาส์ ---- */
#tip{position:fixed;z-index:50;max-width:380px;background:var(--surface);color:var(--ink);border:1px solid var(--line);border-top:4px solid var(--tc,var(--title));border-radius:10px;padding:10px 12px;font-size:13px;line-height:1.55;box-shadow:0 16px 40px var(--shadow);pointer-events:none;opacity:0;transition:opacity .12s}
#tip.show{opacity:1}
#tip h4{margin:0 0 6px;font-size:14px;color:var(--tc)}
#tip dl,.db dl{margin:0;display:grid;grid-template-columns:auto 1fr;gap:3px 10px}
#tip dt,.db dt{color:var(--muted);white-space:nowrap}
#tip dd,.db dd{margin:0}

/* ---- หน้าต่างรายวัน ---- */
dialog{border:none;border-radius:16px;padding:0;width:min(860px,calc(100vw - 32px));max-height:calc(100vh - 40px);background:var(--surface);color:var(--ink);box-shadow:0 20px 60px rgba(0,0,0,.3)}
dialog::backdrop{background:rgba(16,27,41,.5)}
.dh{padding:14px 18px;color:#fff;background:var(--c);display:flex;align-items:flex-start;gap:12px}
.dh b{font-size:19px;display:block}
.dh span{font-size:13.5px;opacity:.92}
.dh button{margin-left:auto;background:transparent;border:1px solid rgba(255,255,255,.7);color:#fff;border-radius:8px;padding:3px 12px;cursor:pointer}
.db{padding:16px 18px 18px;overflow:auto;max-height:calc(100vh - 130px)}
.db dl{font-size:14px;margin-bottom:12px}
.big{display:flex;gap:28px;margin:6px 0 10px;flex-wrap:wrap}
.big div{font-size:13px;color:var(--muted)}
.big b{display:block;font-size:25px;color:var(--ink);font-variant-numeric:tabular-nums}
.chart{height:190px;border-bottom:1px solid var(--line);display:flex;align-items:flex-end;gap:2px;padding-top:8px}
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
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.gcard,.row,#tip,.dn .seg-in,.st-dn .o,.st-dn .i,.st-base .bar i{transition:none}.gcard:hover{transform:none}}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>ช่องทางเคลม<small>ปีงบประมาณ 2569 · 28 ช่องทาง สปสช. · นับแล้ว <?= $ready ?>/28</small></h1>
    <form class="period" id="period" autocomplete="off">
      <div class="seg" role="group" aria-label="เลือกวิธีกำหนดช่วงเวลา">
        <button type="button" id="modeFy" aria-pressed="false">ปีงบประมาณ</button>
        <button type="button" id="modeRange" aria-pressed="true">ช่วงวันที่</button>
      </div>
      <label class="field" id="fyFields" hidden>ปีงบ <select id="fy"></select></label>
      <span id="rangeFields" style="display:contents">
        <label class="field">ตั้งแต่วันที่ <input type="date" id="dFrom"></label>
        <label class="field">ถึงวันที่ <input type="date" id="dTo"></label>
      </span>
      <button type="submit" class="go">แสดงผล</button>
      <div class="quick" id="quick">
        <button type="button" data-q="today">วันนี้</button><button type="button" data-q="yesterday">เมื่อวาน</button>
        <button type="button" data-q="month">เดือนนี้</button><button type="button" data-q="lastmonth">เดือนที่แล้ว</button>
        <button type="button" data-q="fy">ไตรมาสนี้</button>
      </div>
      <div class="range" id="rangeText" aria-live="polite"></div>
    </form>
  </div>

  <section class="cmp" aria-label="เทียบยอดตามกลุ่ม">
    <div class="cmp-top">
      <div class="cmp-h">เทียบยอดตามกลุ่ม<small>ฐาน = ทุกคนที่มารับบริการ (OPD ตามวันรับบริการ + IPD ตามวันจำหน่าย) · กดการ์ดเพื่อไปที่กล่อง</small></div>
      <div class="cmp-note">คนหนึ่งคนอยู่ได้หลายช่องทาง ยอดแต่ละกลุ่มรวมกันจึงเกินยอดเข้าช่องทาง</div>
    </div>
    <div class="cmp-body">
      <div>
        <div class="dn">
          <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="seg-out" cx="60" cy="60" r="50"/><circle class="seg-in" id="dnIn" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="0 100"/></svg>
          <div class="dn-c"><span>มารับบริการ</span><b id="dnAll">–</b><span>ครั้ง</span></div>
        </div>
        <div class="dn-lg"><span><i style="background:var(--in)"></i>เข้าช่องทาง <b id="dnPct">–</b></span><span><i style="background:var(--out)"></i>ไม่เข้า</span></div>
      </div>
      <div class="gcards" id="gcards"></div>
      <div class="cmp-side">
        <div class="cmp-sec">ช่องทางที่ยอดสูงสุด</div>
        <div class="tops" id="tops"></div>
      </div>
    </div>
  </section>

  <div class="poster">
    <div class="pcol" id="colL"></div>
    <div class="pcol pmid">
      <div class="stat">
        <div class="st-head">คนไข้มารับบริการทั้งหมด <small>(ตรงกับ Visit List ของ HOSxP)</small></div>
        <div class="st-chart">
          <div class="st-dn">
            <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="tr" cx="60" cy="60" r="50"/><circle class="o" id="sO" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="0 100"/><circle class="i" id="sI" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="0 100"/></svg>
            <div class="c"><b id="sAll">–</b><span>ครั้ง</span></div>
          </div>
          <div class="st-lg">
            <div><i style="background:var(--opd)"></i><span>OPD (ครั้ง)</span><b id="sOpd">–</b><small id="sOpdP"></small></div>
            <div><i style="background:var(--ipd)"></i><span>IPD จำหน่าย (AN)</span><b id="sIpd">–</b><small id="sIpdP"></small></div>
            <div class="hn"><span>คน (HN)</span><b id="sHn">–</b></div>
          </div>
        </div>
        <div class="st-base">เข้าอย่างน้อย 1 ช่องทาง <b id="sIn">–</b> ครั้ง · <b id="sInP">–</b> คน
          <div class="bar"><i id="sBar" style="width:0"></i></div>
          <div style="margin-top:6px">ไม่เข้าช่องทางใด <b id="sOut">–</b> ครั้ง (เหมาจ่ายรายหัว/สิทธิอื่น)</div>
        </div>
        <div class="st-foot"><b id="sDate">–</b><br><span id="sAt">ข้อมูล HOSxP</span></div>
      </div>
    </div>
    <div class="pcol" id="colR"></div>
  </div>

  <footer>โครงช่องทางตามอินโฟกราฟิก "ช่องทางเคลม ปีงบประมาณ 2569" (99 Training) · เงื่อนไขตามเอกสาร สปสช. ปีงบ 2569 ·
    ชี้เมาส์ที่ช่องทางเพื่อดูเงื่อนไข กดเพื่อดูรายวัน · UC = สิทธิ UCS/WEL · หน้าแบบอื่น: <a href="index_tree.php">ต้นไม้</a> · <a href="index_cards.php">การ์ด</a></footer>
</div>
<div id="tip" role="tooltip" aria-hidden="true"></div>
<div id="ctip" hidden></div>
<dialog id="dlg"><div class="dh"><div><b id="dTitle"></b><span id="dSub"></span></div><button type="button" id="dClose">ปิด</button></div><div class="db" id="dBody"></div></dialog>

<script>
const GROUPS = <?= json_encode(CC_GROUPS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const CH = <?= json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
// ตำแหน่งกล่อง: ซ้าย / กลาง (ใต้ยอดรวม) / ขวา
const LAYOUT = {colL: ['f43', 'ecl'], pmid: ['nat'], colR: ['dmis', 'plat']};
const fmt = n => Number(n).toLocaleString('th-TH');
const MON = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
const th = s => { const [y,m,d] = s.split('-').map(Number); return `${d} ${MON[m-1]} ${y+543}`; };
const iso = t => { const z = new Date(t.getTime() - t.getTimezoneOffset()*60000); return z.toISOString().slice(0,10); };
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const pct = (a, b) => b ? (a / b * 100).toFixed(1) + '%' : '0%';
const WHY = {ask: 'รอคำตอบงานประกัน', metric: 'ต้องตกลงตัวเลข'};
const ab = code => code.replace(/^DMIS_/, '').replace(/[^A-Z]/g, '').slice(0, 4) || code.slice(0, 3);

// ---- กล่องกลุ่ม (สร้างครั้งเดียว ตัวเลขเติมทีหลัง) ----
function boxHtml(g){
  const x = GROUPS[g], items = Object.entries(CH).filter(([, c]) => c.g === g);
  const nReady = items.filter(([, c]) => c.status === 'ready').length;
  return `<section class="box" id="box-${g}" style="--c:var(--${g});--cs:var(--${g}s);--cb:var(--${g}b)">
    <div class="head"><div class="head-row"><div><b>${esc(x.name)}</b><small>${esc(x.en)} · ${items.length} ช่องทาง · นับแล้ว ${nReady}</small></div>
      <div class="head-n"><b id="gN-${g}">–</b><small id="gP-${g}">ครั้ง</small></div></div></div>
    <div class="rows">${items.map(([code, c]) => `
      <button type="button" class="row ${c.status}" data-ch="${esc(code)}">
        <span class="ico">${esc(ab(code))}</span>
        <span class="tx"><span class="nm">${esc(c.name)}</span>
          <span class="sb" id="sb-${esc(code)}"><span class="code">${esc(code)}</span> · ${esc(c.right)}</span></span>
        ${c.status === 'ready' ? `<span class="n" id="n-${esc(code)}">–</span><span class="chev" aria-hidden="true">›</span>`
                               : `<span class="pill ${c.why}">${WHY[c.why] || 'รอเงื่อนไข'}</span>`}
      </button>`).join('')}</div>
  </section>`;
}
for (const col in LAYOUT) {
  const el = col === 'pmid' ? document.querySelector('.pmid') : document.getElementById(col);
  el.insertAdjacentHTML('beforeend', LAYOUT[col].map(boxHtml).join(''));
}
document.getElementById('gcards').innerHTML = Object.entries(GROUPS).map(([g, x]) =>
  `<a class="gcard" href="#box-${g}" style="--c:var(--${g})"><span>${esc(x.name.replace('ช่องทางกลุ่ม', '').replace('ช่องทาง', '').trim())}</span>
    <b id="gc-${g}">–</b><small id="gcp-${g}">คน</small><em id="gce-${g}">–</em></a>`).join('');

// ---- ช่วงเวลา ----
const $fy = document.getElementById('fy'), $f = document.getElementById('dFrom'), $t = document.getElementById('dTo');
const today = new Date(); today.setHours(12);
const curFy = today.getMonth() >= 9 ? today.getFullYear() + 544 : today.getFullYear() + 543;
for (let y = curFy; y >= 2569; y--) $fy.insertAdjacentHTML('beforeend', `<option value="${y}">${y} (1 ต.ค. ${y-1} – 30 ก.ย. ${y})</option>`);
function quick(q){
  const y = today.getFullYear(), m = today.getMonth();
  switch(q){
    case 'today': return [iso(today), iso(today)];
    case 'yesterday': { const a = new Date(today); a.setDate(a.getDate()-1); return [iso(a), iso(a)]; }
    case 'month': return [iso(new Date(y, m, 1, 12)), iso(today)];
    case 'lastmonth': return [iso(new Date(y, m-1, 1, 12)), iso(new Date(y, m, 0, 12))];
    // ไตรมาสปีงบเริ่ม ต.ค. / ม.ค. / เม.ย. / ก.ค. = ต้นไตรมาสปฏิทินพอดี
    case 'fy': return [iso(new Date(y, Math.floor(m / 3) * 3, 1, 12)), iso(today)];
  }
}
let st = {mode: 'range', fy: curFy, from: iso(today), to: iso(today), q: 'today'};
try { const s = JSON.parse(localStorage.getItem('cc_main') || 'null'); if (s && s.from) st = {...st, ...s}; } catch(e) {}
const range = () => st.mode === 'fy' ? [`${st.fy - 544}-10-01`, `${st.fy - 543}-09-30`] : (st.from <= st.to ? [st.from, st.to] : [st.to, st.from]);
document.getElementById('modeFy').onclick = () => { st.mode = 'fy'; apply(); };
document.getElementById('modeRange').onclick = () => { st.mode = 'range'; apply(); };
$fy.onchange = () => { st.fy = +$fy.value; apply(); };
document.getElementById('period').onsubmit = e => { e.preventDefault(); if (st.mode === 'range' && $f.value && $t.value) { st.from = $f.value; st.to = $t.value; st.q = null; } apply(); };
document.querySelectorAll('#quick button').forEach(b => b.onclick = () => { [st.from, st.to] = quick(b.dataset.q); st.q = b.dataset.q; apply(); });
function apply(){
  try { localStorage.setItem('cc_main', JSON.stringify(st)); } catch(e) {}
  const fy = st.mode === 'fy';
  document.getElementById('modeFy').setAttribute('aria-pressed', fy);
  document.getElementById('modeRange').setAttribute('aria-pressed', !fy);
  document.getElementById('fyFields').hidden = !fy;
  document.querySelectorAll('#rangeFields .field').forEach(e => e.hidden = fy);
  document.getElementById('quick').hidden = fy;
  $fy.value = st.fy; $f.value = st.from; $t.value = st.to;
  document.querySelectorAll('#quick button').forEach(b => b.setAttribute('aria-pressed', b.dataset.q === st.q));
  load();
}

// ---- ดึงตัวเลข ----
let token = 0;
async function load(){
  const my = ++token, [a, b] = range();
  setRange(a, b, 'กำลังดึงข้อมูล…');
  try {
    const j = await (await fetch(`api.php?from=${a}&to=${b}`, {cache: 'no-store'})).json();
    if (my !== token) return;
    if (j.error) { setRange(a, b, j.error, true); return; }
    draw(j);
    if (j.sync.left > 0 || j.sync.busy) setTimeout(() => { if (my === token) load(); }, j.sync.busy ? 4000 : 300);
  } catch(e) { if (my === token) setRange(a, b, 'ดึงข้อมูลไม่ได้: ' + e.message, true); }
}
const days = (a, b) => Math.round((new Date(b) - new Date(a)) / 864e5) + 1;
function setRange(a, b, extra, warn){
  const end = b > iso(today) ? iso(today) : b;
  document.getElementById('rangeText').innerHTML = `ช่วงวันที่ที่เลือก: <b>${th(a)}${a !== b ? ' – ' + th(b) : ''}</b> รวม ${fmt(days(a, end))} วัน`
    + (extra ? ` · <span class="${warn ? 'warn' : ''}">${esc(extra)}</span>` : '');
}
function draw(j){
  const s = j.summary, [a, b] = range();
  // แถบเทียบยอด
  document.getElementById('dnAll').textContent = fmt(s.n);
  document.getElementById('dnIn').setAttribute('stroke-dasharray', `${s.n ? s.mn / s.n * 100 : 0} 100`);
  document.getElementById('dnPct').textContent = pct(s.mn, s.n);
  for (const g in GROUPS) {
    const x = j.groups[g] || {n: 0, p: 0};
    document.getElementById('gc-' + g).textContent = fmt(x.n);
    document.getElementById('gcp-' + g).textContent = `ครั้ง · ${fmt(x.p)} คน`;
    document.getElementById('gce-' + g).textContent = pct(x.n, s.n);
    document.getElementById('gN-' + g).textContent = fmt(x.n);
    document.getElementById('gP-' + g).textContent = `ครั้ง · ${fmt(x.p)} คน`;
  }
  const top = Object.entries(j.totals).sort((x, y) => y[1].n - x[1].n).slice(0, 3);
  document.getElementById('tops').innerHTML = top.map(([code, x]) => { const g = CH[code].g;
    return `<button type="button" class="topc" data-ch="${esc(code)}" style="--c:var(--${g});--cs:var(--${g}s);--cb:var(--${g}b)">
      <b>${fmt(x.n)}</b><span>${esc(CH[code].name)}</span><small>${esc(code)}<br>${pct(x.n, s.n)}</small></button>`; }).join('');
  // แต่ละช่อง
  for (const code in j.totals) {
    const x = j.totals[code], n = document.getElementById('n-' + code), sb = document.getElementById('sb-' + code);
    if (!n) continue;
    n.textContent = fmt(x.n); n.className = 'n' + (x.n ? '' : ' zero');
    sb.innerHTML = `<span class="code">${esc(code)}</span> · ${esc(CH[code].right)} · ${fmt(x.p)} คน · OPD ${fmt(x.opd)} · IPD ${fmt(x.ipd)}`;
  }
  // กล่องกลาง
  const all = s.opd + s.ipd;
  document.getElementById('sAll').textContent = fmt(s.n);
  document.getElementById('sAll').className = s.n >= 100000 ? 'sm' : '';
  document.getElementById('sO').setAttribute('stroke-dasharray', `${all ? s.opd / all * 100 : 0} 100`);
  document.getElementById('sI').setAttribute('stroke-dasharray', `${all ? s.ipd / all * 100 : 0} 100`);
  document.getElementById('sI').setAttribute('stroke-dashoffset', `${all ? -s.opd / all * 100 : 0}`);
  document.getElementById('sOpd').textContent = fmt(s.opd);
  document.getElementById('sOpdP').textContent = pct(s.opd, all);
  document.getElementById('sIpd').textContent = fmt(s.ipd);
  document.getElementById('sIpdP').textContent = pct(s.ipd, all) + ' · นับตามวันจำหน่าย';
  document.getElementById('sHn').textContent = fmt(s.p);
  document.getElementById('sIn').textContent = fmt(s.mn) + ` (${pct(s.mn, s.n)})`;
  document.getElementById('sInP').textContent = fmt(s.mp);
  document.getElementById('sBar').style.width = (s.n ? s.mn / s.n * 100 : 0) + '%';
  document.getElementById('sOut').textContent = fmt(s.n - s.mn);
  document.getElementById('sDate').textContent = th(a) + (a !== b ? ' – ' + th(b) : '');
  document.getElementById('sAt').textContent = `ข้อมูล HOSxP ณ ${j.at} น.`;
  const notes = []; let warn = false;
  if (j.missing > 0) { notes.push(`ยังดึงไม่ครบ อีก ${fmt(j.missing)} ช่อง-วัน กำลังดึงต่อ`); warn = true; }
  if (j.sync.busy) { notes.push('มีคนกำลังดึงข้อมูลอยู่ ใช้ยอดในเครื่องไปก่อน'); warn = true; }
  if (j.sync.err.length) { notes.push('ดึงบางช่วงไม่สำเร็จ'); warn = true; }
  if (a < '2026-08-08') { notes.push('ก่อน 8 ส.ค. 69 เป็นข้อมูลย้ายระบบ ยอด Fee Schedule จะต่ำกว่าจริง'); warn = true; }
  setRange(a, b, notes.join(' · '), warn);
}

// ---- คำอธิบายเมื่อชี้เมาส์ ----
const tip = document.getElementById('tip');
document.addEventListener('mouseover', e => {
  const l = e.target.closest('.row,.topc'); if (!l) return;
  const c = CH[l.dataset.ch];
  tip.style.setProperty('--tc', `var(--${c.g})`);
  tip.innerHTML = `<h4>${esc(l.dataset.ch)} · ${esc(c.name)}</h4><dl><dt>ตามประกาศ</dt><dd>${esc(c.cond)}</dd>
    <dt>วิธีนับ</dt><dd>${esc(c.how)}</dd><dt>สิทธิ</dt><dd>${esc(c.right)}</dd></dl>`;
  tip.classList.add('show'); tip.setAttribute('aria-hidden', 'false');
});
document.addEventListener('mousemove', e => {
  if (!tip.classList.contains('show')) return;
  const x = Math.min(e.clientX + 14, innerWidth - tip.offsetWidth - 10);
  const y = e.clientY + 16 + tip.offsetHeight > innerHeight ? e.clientY - tip.offsetHeight - 12 : e.clientY + 16;
  tip.style.left = x + 'px'; tip.style.top = y + 'px';
});
document.addEventListener('mouseout', e => { if (e.target.closest('.row,.topc') && !e.relatedTarget?.closest?.('.row,.topc')) { tip.classList.remove('show'); tip.setAttribute('aria-hidden', 'true'); } });

// ---- หน้าต่างรายวัน ----
const dlg = document.getElementById('dlg'), ctip = document.getElementById('ctip');
document.getElementById('dClose').onclick = () => dlg.close();
dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
dlg.addEventListener('close', () => ctip.hidden = true);
document.addEventListener('click', e => { const l = e.target.closest('.row,.topc'); if (l) { tip.classList.remove('show'); openCh(l.dataset.ch); } });
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
