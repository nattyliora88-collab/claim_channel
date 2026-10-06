<?php
// ==========================================================================
//  Fund & Claim Channel (ชื่อใหม่ mazza 6 ต.ค. 69 เดิม "ช่องทางเคลม 2569") - คอนเซปต์เดียวกับ ClaimMap
//   1) ฐานส่งเบิก = OPD ตามวันรับบริการ + IPD ตามวันจำหน่าย ทุกครั้งอยู่กล่องเดียว (ลำดับชนะ CC_PRIORITY)
//      ทุกกล่อง + "ไม่เข้าช่องทาง" = ฐานพอดี -> ป้าย ✓ ครบ
//   2) กล่องกลุ่มมีวงกลม OPD/IPD + คน + % ของฐาน · กดการ์ดด้านบน = โหมดเลือกดูกล่องเดียว
//   3) กดช่องทาง = รายวัน + สถานะการเบิก (เฉพาะช่องที่ส่ง e-Claim / FDH มีร่องรอยในฐาน, status.php)
//  หน้าสำรอง: index_v3.php (ก่อนใช้ลำดับชนะ) · index_tree.php · index_cards.php
// ==========================================================================
require_once __DIR__ . '/channels.php';
require_once __DIR__ . '/auth.php';
auth_require();
$chs = cc_channels();
$ready = count(array_filter($chs, fn($c) => $c['status'] === 'ready'));
$meta = [];
foreach ($chs as $c) $meta[$c['code']] = ['g' => $c['g'], 'name' => $c['name'], 'right' => $c['right'], 'status' => $c['status'],
    'why' => $c['why'], 'cond' => $c['cond'], 'how' => $c['how'], 'track' => $c['track'], 'via' => $c['via']];
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Fund &amp; Claim Channel</title>
<style>
@font-face{font-family:"Sarabun";font-weight:400;font-display:swap;src:url(assets/fonts/sarabun-thai-400.woff2) format("woff2");unicode-range:U+0E01-0E5B,U+200C-200D,U+25CC}
@font-face{font-family:"Sarabun";font-weight:600;font-display:swap;src:url(assets/fonts/sarabun-thai-600.woff2) format("woff2");unicode-range:U+0E01-0E5B,U+200C-200D,U+25CC}
@font-face{font-family:"Sarabun";font-weight:700;font-display:swap;src:url(assets/fonts/sarabun-thai-700.woff2) format("woff2");unicode-range:U+0E01-0E5B,U+200C-200D,U+25CC}
@font-face{font-family:"Sarabun";font-weight:400;font-display:swap;src:url(assets/fonts/sarabun-latin-400.woff2) format("woff2");unicode-range:U+0000-00FF,U+2000-206F,U+20AC,U+2122,U+2212}
@font-face{font-family:"Sarabun";font-weight:600;font-display:swap;src:url(assets/fonts/sarabun-latin-600.woff2) format("woff2");unicode-range:U+0000-00FF,U+2000-206F,U+20AC,U+2122,U+2212}
@font-face{font-family:"Sarabun";font-weight:700;font-display:swap;src:url(assets/fonts/sarabun-latin-700.woff2) format("woff2");unicode-range:U+0000-00FF,U+2000-206F,U+20AC,U+2122,U+2212}
:root{
  --bg:#f5f8fc; --surface:#fff; --ink:#172a43; --ink2:#3a4a5f; --muted:#56667c; --line:#dce6f0;
  --title:#123d63; --title2:#167f98; --go:#0f7d89; --shadow:rgba(24,54,87,.12); --shadow2:rgba(24,54,87,.07);
  /* สีกลุ่มตามอินโฟกราฟิก 99 Training (โทนเดียวกับ ClaimMap) */
  --f43:#c4497a; --f43s:#fcf1f6; --f43b:#f0d6e2;
  --ecl:#327daf; --ecls:#f0f7fc; --eclb:#d5e6f2;
  --dmis:#707e91; --dmiss:#f4f6f8; --dmisb:#e0e5eb;
  --nat:#c0413f; --nats:#fdf2f2; --natb:#f2d5d4;
  --plat:#2f55a8; --plats:#eff3fb; --platb:#d6dff3;
  --none:#c3cfdb; --nones:#f4f7fa; --noneb:#dfe6ee;
  --opd:#2fb3a3; --ipd:#f2a93b; --warn:#a86400; --okc:#1e8449; --okb:#e3f4ea; --badc:#c0392b; --badb:#fbe4e1;
  --s-none:#c9d3de; --s-resv:#f0b44c; --s-sent:#2e9d8f;
}
@media (prefers-color-scheme: dark){
  :root:not([data-theme="light"]){
    color-scheme:dark;
    --bg:#101b29; --surface:#1a293a; --ink:#e9f1f8; --ink2:#c9d5e2; --muted:#a4b5c7; --line:#32475e;
    --title:#d6eafa; --title2:#71cbd3; --go:#169aa7; --shadow:rgba(0,0,0,.28); --shadow2:rgba(0,0,0,.16);
    --f43:#ec86ae; --f43s:#2f1c27; --f43b:#5c3349; --ecl:#6db4e6; --ecls:#152a3c; --eclb:#2b4d68;
    --dmis:#a9b3c0; --dmiss:#222c38; --dmisb:#3f4b5a; --nat:#ee8a88; --nats:#311d20; --natb:#5e3437;
    --plat:#8ea9ee; --plats:#1a2443; --platb:#34457a; --none:#4a5c72; --nones:#1d2a39; --noneb:#34475d;
    --warn:#f0b65a; --okc:#6fcf97; --okb:#173326; --badc:#ff8a7a; --badb:#3b1a1a;
    --s-none:#4a5c72; --s-resv:#e0a440; --s-sent:#3fbfae;
  }
}
:root[data-theme="dark"]{
  color-scheme:dark;
  --bg:#101b29; --surface:#1a293a; --ink:#e9f1f8; --ink2:#c9d5e2; --muted:#a4b5c7; --line:#32475e;
  --title:#d6eafa; --title2:#71cbd3; --go:#169aa7; --shadow:rgba(0,0,0,.28); --shadow2:rgba(0,0,0,.16);
  --f43:#ec86ae; --f43s:#2f1c27; --f43b:#5c3349; --ecl:#6db4e6; --ecls:#152a3c; --eclb:#2b4d68;
  --dmis:#a9b3c0; --dmiss:#222c38; --dmisb:#3f4b5a; --nat:#ee8a88; --nats:#311d20; --natb:#5e3437;
  --plat:#8ea9ee; --plats:#1a2443; --platb:#34457a; --none:#4a5c72; --nones:#1d2a39; --noneb:#34475d;
  --warn:#f0b65a; --okc:#6fcf97; --okb:#173326; --badc:#ff8a7a; --badb:#3b1a1a;
  --s-none:#4a5c72; --s-resv:#e0a440; --s-sent:#3fbfae;
}
*{box-sizing:border-box}
[hidden]{display:none!important}
html{scroll-behavior:smooth}
body{margin:0;padding:0 24px;background:var(--bg);color:var(--ink);font-family:"Sarabun","Leelawadee UI",Tahoma,sans-serif;font-size:16px;line-height:1.55}
button,select,input{font:inherit;color:inherit}
.wrap{padding-block:28px 56px}
:where(button,summary,a,select,input):focus-visible{outline:3px solid #10a4ae;outline-offset:2px}
.mono{font-family:Consolas,"IBM Plex Mono",monospace}

/* ---- หัว + ช่วงเวลา ---- */
.top{display:flex;flex-wrap:wrap;align-items:center;gap:16px 28px;margin-bottom:22px}
.top h1{margin:0;font-size:clamp(30px,2.6vw,42px);line-height:1.05;letter-spacing:-.03em;color:var(--title);font-weight:700}
.top h1 small{display:block;font-size:14px;letter-spacing:0;font-weight:400;color:var(--ink2);margin-top:4px}
.top h1 .th{font-size:19px;font-weight:600;color:var(--ink2);margin-top:6px}
.period{flex:1;display:flex;flex-wrap:wrap;align-items:center;gap:10px 16px;padding:14px 22px;background:var(--surface);border:1px solid var(--line);border-radius:20px;box-shadow:0 12px 34px var(--shadow2)}
.seg{display:inline-flex;background:var(--bg);border:1px solid var(--line);border-radius:12px;padding:4px}
.seg button{border:0;background:transparent;color:var(--muted);font-weight:600;font-size:15px;padding:8px 16px;border-radius:9px;cursor:pointer;white-space:nowrap}
.seg button[aria-pressed="true"]{background:var(--title);color:var(--surface)}
.field{display:flex;align-items:center;gap:7px;font-size:13.5px;color:var(--muted);white-space:nowrap}
.field select,.field input{height:40px;padding:6px 10px;font-size:15px;border:1px solid var(--line);border-radius:9px;background:var(--bg);min-width:0}
.go{height:40px;padding:6px 18px;border:0;border-radius:9px;background:var(--go);color:#fff;font-weight:700;cursor:pointer}
.quick{display:flex;flex-wrap:wrap;gap:6px}
.quick button{font-size:13.5px;padding:5px 11px;border:1px solid var(--line);background:var(--surface);border-radius:8px;cursor:pointer;white-space:nowrap}
.quick button[aria-pressed="true"]{border-color:var(--title);color:var(--title);font-weight:700}
.range{font-size:14px;color:var(--muted);border-left:1px solid var(--line);padding-left:16px}
.range b{color:var(--title)}
.range .warn{color:var(--warn)}
@media(max-width:1400px){.range{border-left:0;padding-left:0;flex-basis:100%}}
/* ชื่อยาว (Fund &amp; Claim Channel) - จอไม่ถึง 1700 ให้แถบช่วงเวลาลงไปอีกแถว ไม่ถูกบีบ */
@media(max-width:1700px){.period{flex-basis:100%}.range{border-left:0;padding-left:0;flex-basis:100%}}

/* ---- เทียบยอดตามกล่อง ---- */
.cmp{margin-bottom:28px;padding:20px 24px 22px;background:linear-gradient(135deg,var(--surface),color-mix(in srgb,#dff1f5 40%,var(--surface)));border:1px solid var(--line);border-radius:22px;box-shadow:0 12px 34px var(--shadow2)}
.cmp-top{display:flex;flex-wrap:wrap;justify-content:space-between;align-items:flex-start;gap:6px 16px;margin-bottom:14px}
.cmp-h{font-size:19px;font-weight:700;color:var(--title)}
.cmp-h small{font-size:14px;font-weight:400;color:var(--ink2);margin-left:8px}
.cmp-check{display:flex;align-items:center;gap:10px;font-size:13px;color:var(--muted)}
.chk{display:inline-block;padding:3px 10px;border-radius:999px;font-weight:700;white-space:nowrap}
.chk.ok{background:var(--okb);color:var(--okc)} .chk.bad{background:var(--badb);color:var(--badc)}
.cmp-body{display:grid;grid-template-columns:240px minmax(0,1.8fr) minmax(250px,1fr);gap:24px;align-items:center}
.dn{position:relative;width:240px;aspect-ratio:1}
.dn svg{width:100%;height:100%;transform:rotate(-90deg)}
.dn circle{fill:none;stroke-width:16;transition:stroke-dasharray .7s ease,stroke-dashoffset .7s ease}
.dn-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;line-height:1.1;pointer-events:none}
.dn-c span{font-size:13px;color:var(--muted)}
.dn-c b{font-size:31px;font-weight:700;font-variant-numeric:tabular-nums;margin:3px 0}
.gcards{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.gcard{position:relative;display:flex;flex-direction:column;gap:2px;padding:12px 14px 11px 18px;border:1px solid var(--line);border-radius:14px;background:var(--surface);overflow:hidden;color:var(--ink);text-align:left;cursor:pointer;transition:transform .15s,box-shadow .15s}
.gcard::before{content:"";position:absolute;left:0;top:0;bottom:0;width:6px;background:var(--c)}
.gcard:hover{transform:translateY(-2px);box-shadow:0 10px 20px -12px var(--shadow)}
.gcard.sel{border-color:#e08a2c;box-shadow:0 0 0 2px #e08a2c}
.gcard span{font-size:15px;font-weight:600;color:var(--ink2);line-height:1.3}
.gcard b{font-size:26px;font-weight:700;letter-spacing:-.02em;font-variant-numeric:tabular-nums;line-height:1.15}
.gcard small{font-size:14px;color:var(--ink2)}
.gcard .why{font-size:13px;line-height:1.4;color:var(--ink2);margin:2px 0 4px}
.gcard em{align-self:flex-start;font-style:normal;font-size:12.5px;font-weight:700;padding:2px 9px;border-radius:999px;color:var(--c);background:color-mix(in srgb,var(--c) 12%,transparent)}
.gcard.none{background:color-mix(in srgb,var(--bg) 70%,var(--surface));cursor:pointer}
.gcard.none .go-n{font-style:normal;font-size:12.5px;color:var(--title2);font-weight:700;background:none;padding:0;margin-left:4px}
.gcard.none em{color:var(--muted);background:var(--line)}
.cmp-side{align-self:stretch;display:flex;flex-direction:column;padding-left:22px;border-left:1px dashed var(--line)}
.cmp-sec{margin:0 0 10px;font-size:14px;font-weight:700;color:var(--title)}
.cmp-sec small{font-weight:400;color:var(--muted)}
.tops{display:flex;flex-direction:column;gap:10px;flex:1}
.topc{display:flex;align-items:center;gap:12px;padding:11px 14px;border:1px solid var(--cb);border-radius:12px;background:var(--cs);cursor:pointer;text-align:left;width:100%}
.topc b{min-width:76px;font-size:23px;font-weight:700;color:var(--c);font-variant-numeric:tabular-nums}
.topc span{flex:1;font-size:15px;font-weight:600;line-height:1.3}
.topc small{font-size:13px;color:var(--ink2);text-align:right}
@media(max-width:1300px){.cmp-body{grid-template-columns:220px minmax(0,1fr)}.dn{width:220px}.cmp-side{grid-column:1/-1;padding:16px 0 0;border-left:0;border-top:1px dashed var(--line)}.tops{flex-direction:row;flex-wrap:wrap}.topc{flex:1 1 220px}}
@media(max-width:820px){.cmp-body{grid-template-columns:1fr}.dn{margin:0 auto}.gcards{grid-template-columns:repeat(2,minmax(0,1fr))}}

/* ---- 3 คอลัมน์ ---- */
.focusbar{display:flex;align-items:center;gap:12px;margin:-10px 0 18px;padding:10px 16px;border-radius:12px;background:#fff4e6;border:1px solid #f3c58d;color:#7a4a10;font-size:14px}
.focusbar button{margin-left:auto;border:0;border-radius:8px;background:var(--title);color:#fff;padding:6px 14px;cursor:pointer;font-weight:600}
.poster{display:grid;grid-template-columns:minmax(0,1fr) minmax(340px,440px) minmax(0,1fr);gap:28px;align-items:start}
.pcol{display:flex;flex-direction:column;gap:24px;min-width:0}
.poster.focus .box:not(.on){display:none}
.poster.focus .pcol:empty{display:none}
/* จอประชุม/โน้ตบุ๊ก (<1500) 3 คอลัมน์แคบจนชื่อช่องตัดหลายบรรทัด -> 2 คอลัมน์ กล่องกลางเต็มกว้างด้านบน */
@media(max-width:1500px){.poster{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.pmid{grid-column:1/-1;order:-1;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);align-items:start}}
@media(max-width:900px){.pmid{grid-template-columns:1fr}}
@media(max-width:760px){.poster{grid-template-columns:1fr}body{padding:0 16px}}

.box{padding:21px;background:var(--surface);border:1px solid var(--line);border-radius:18px;box-shadow:0 8px 28px var(--shadow2),0 1px 3px var(--shadow2);scroll-margin-top:16px}
.box:hover{border-color:var(--cb)}
.box>.head{margin:0 0 14px;padding:0 0 13px;border-bottom:1px solid var(--line)}
.box>.head::before{content:"";display:block;width:34px;height:4px;margin-bottom:12px;border-radius:4px;background:var(--c)}
.box>.head b{display:block;font-size:21px;line-height:1.35}
.box>.head small{display:block;margin-top:3px;font-size:14px;color:var(--ink2)}
/* วงกลม OPD/IPD ของกล่อง (แบบ fchart ของ ClaimMap) */
.fchart{display:flex;align-items:center;gap:18px;padding:14px 16px;margin-bottom:14px;border:1px solid var(--cb);border-radius:14px;background:var(--cs)}
.fc-dn{position:relative;flex:none;width:150px;height:150px}
.fc-dn svg{width:100%;height:100%;transform:rotate(-90deg)}
.fc-dn circle{fill:none;stroke-width:14;transition:stroke-dasharray .7s ease,stroke-dashoffset .7s ease}
.fc-dn .tr{stroke:color-mix(in srgb,var(--cb) 70%,var(--surface))}
.fc-dn .o{stroke:var(--opd)} .fc-dn .i{stroke:var(--ipd)}
.fc-c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.05}
.fc-c b{font-size:28px;font-weight:700;color:var(--c);letter-spacing:-.03em;font-variant-numeric:tabular-nums}
.fc-c span{font-size:13px;color:var(--muted);margin-top:3px}
.fc-lg{flex:1;min-width:0;display:flex;flex-direction:column;gap:10px}
.fc-lg>div{display:grid;grid-template-columns:auto 1fr auto;column-gap:8px;align-items:baseline}
.fc-lg i{width:12px;height:12px;border-radius:3px;align-self:center}
.fc-lg span{font-size:15px;color:var(--ink2)}
.fc-lg>div>b{font-size:20px;font-variant-numeric:tabular-nums;text-align:right}
.fc-lg small{grid-column:2/4;font-size:12.5px;font-weight:600;color:var(--c);margin-top:-2px}
.fc-lg .hn{grid-template-columns:1fr auto;padding-top:9px;border-top:1px solid var(--cb)}
.fc-lg .hn span{color:var(--muted);font-size:13.5px}
.fc-lg .hn small{grid-column:1/-1;color:var(--muted);font-weight:400}
.pcol .box{container-type:inline-size}
@container (max-width:460px){.fchart{flex-direction:column;align-items:stretch}.fc-dn{align-self:center}}
.rows{display:flex;flex-direction:column;gap:8px}
.row{display:flex;align-items:center;gap:12px;width:100%;padding:8px 12px 8px 8px;background:var(--surface);border:1px solid var(--line);border-radius:12px;text-align:left;cursor:pointer;transition:border-color .15s,background .15s}
.row:hover{border-color:var(--cb);background:var(--cs)}
.ico{flex:none;display:grid;place-items:center;width:42px;height:42px;border-radius:11px;background:var(--cs);border:1px solid var(--cb);color:var(--c);font:700 12px/1 Consolas,"IBM Plex Mono",monospace}
.row .tx{flex:1;min-width:0}
.row .nm{display:block;font-size:17px;font-weight:600;line-height:1.3;color:var(--ink)}
.row .sb{display:block;font-size:14px;color:var(--ink2);margin-top:2px}
.row .sb .code{font-family:Consolas,"IBM Plex Mono",monospace;color:var(--c);font-weight:600}
.row .trk{display:inline-block;margin-left:6px;font-size:11px;font-weight:700;color:var(--s-sent);border:1px solid currentColor;border-radius:999px;padding:0 6px;vertical-align:1px}
.row .nb{text-align:right;white-space:nowrap}
.row .n{display:inline-block;font-size:22px;font-weight:700;color:var(--c);font-variant-numeric:tabular-nums;line-height:1.15}
.row .n.zero{color:var(--muted);font-weight:400}
.row .u{display:inline-block;margin-left:4px;font-size:13px;color:var(--ink2)}
.row .full{display:block;font-size:13px;color:var(--ink2)}
.row .nb{min-width:92px}
.row .chev{flex:none;color:var(--muted);font-size:13px}
.row.pending{background:transparent}
.row.pending .ico{background:transparent;border-style:dashed;color:var(--muted)}
.row.pending .nm{color:var(--muted)}
.pill{flex:none;font-size:12px;color:var(--muted);border:1px dashed var(--line);border-radius:999px;padding:1px 9px;white-space:nowrap}
.pill.metric{color:var(--warn);border-color:var(--warn)}
.box-note{margin:12px 0 0;font-size:13.5px;color:var(--ink2)}

/* ---- กล่องกลาง ---- */
.stat{padding:22px 20px;border-radius:18px;color:#fff;background:linear-gradient(145deg,#123d63,#0b6677);box-shadow:0 14px 38px var(--shadow2)}
.st-head{font-size:14px;color:#c8eaf0;font-weight:600}
.st-chart{display:flex;align-items:center;gap:18px;margin:14px 0 6px}
.st-dn{position:relative;flex:none;width:170px;height:170px}
.st-dn svg{width:100%;height:100%;transform:rotate(-90deg)}
.st-dn circle{fill:none;stroke-width:14;transition:stroke-dasharray .7s ease,stroke-dashoffset .7s ease}
.st-dn .tr{stroke:rgba(255,255,255,.14)} .st-dn .o{stroke:var(--opd)} .st-dn .i{stroke:var(--ipd)}
.st-dn .c{position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;line-height:1.05}
.st-dn .c b{font-size:30px;font-weight:700;font-variant-numeric:tabular-nums;letter-spacing:-.03em}
.st-dn .c b.sm{font-size:23px}
.st-dn .c span{color:#c8eaf0;font-size:13px;margin-top:3px}
.st-lg{flex:1;min-width:0;display:flex;flex-direction:column;gap:11px}
.st-lg>div{display:grid;grid-template-columns:auto 1fr;column-gap:8px;align-items:center}
.st-lg i{grid-row:1/3;width:12px;height:12px;border-radius:3px}
.st-lg span{color:#d6eff3;font-size:14.5px}
.st-lg b{font-size:22px;font-variant-numeric:tabular-nums;line-height:1.15}
.st-lg small{grid-column:2;color:#9fdcd4;font-size:12.5px;font-weight:600}
.st-lg .hn{grid-template-columns:1fr;padding-top:9px;border-top:1px solid rgba(255,255,255,.22)}
.st-base{margin-top:14px;padding:12px 14px;border-radius:12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);font-size:13.5px;color:#d9f1f4}
.st-base b{color:#fff;font-variant-numeric:tabular-nums}
.st-base .bar{display:flex;height:10px;border-radius:999px;overflow:hidden;background:rgba(255,255,255,.14);margin:8px 0 6px}
.st-base .bar i{display:block;height:100%;transition:width .7s ease}
.st-base #sChk{display:block;font-size:15px;font-weight:700;color:#fff}
.st-why{margin-top:6px;font-size:13px;line-height:1.5;color:#d6eff3}
.st-calc{margin-top:8px;font-size:13px;color:#d6eff3}
.st-calc summary{cursor:pointer;color:#9fe6dc;font-weight:600}
.st-calc div{margin-top:6px;line-height:1.6}
.st-calc b{color:#fff}
.st-foot{margin-top:14px;font-size:12.5px;color:#b6dae4}
.st-foot b{color:#fff}
@media(max-width:440px){.st-chart{flex-direction:column;align-items:stretch}.st-dn{align-self:center}}
footer{margin-top:44px;padding-top:16px;border-top:1px solid var(--line);font-size:12.5px;color:var(--muted)}
footer a{color:var(--title2)}

/* ---- tooltip ---- */
#tip{position:fixed;z-index:50;max-width:380px;background:var(--surface);color:var(--ink);border:1px solid var(--line);border-top:4px solid var(--tc,var(--title));border-radius:10px;padding:10px 12px;font-size:13px;line-height:1.55;box-shadow:0 16px 40px var(--shadow);pointer-events:none;opacity:0;transition:opacity .12s}
#tip.show{opacity:1}
#tip h4{margin:0 0 6px;font-size:14px;color:var(--tc)}
#tip dl,.db dl{margin:0;display:grid;grid-template-columns:auto 1fr;gap:3px 10px}
#tip dt,.db dt{color:var(--muted);white-space:nowrap}
#tip dd,.db dd{margin:0}

/* ---- หน้าต่างช่องทาง ---- */
dialog{border:none;border-radius:16px;padding:0;width:min(900px,calc(100vw - 32px));max-height:calc(100vh - 40px);background:var(--surface);color:var(--ink);box-shadow:0 20px 60px rgba(0,0,0,.3)}
dialog::backdrop{background:rgba(16,27,41,.5)}
.dh{padding:14px 18px;color:#fff;background:var(--c);display:flex;align-items:flex-start;gap:12px}
.dh b{font-size:19px;display:block}
.dh span{font-size:13.5px;opacity:.92}
.dh button{margin-left:auto;background:transparent;border:1px solid rgba(255,255,255,.7);color:#fff;border-radius:8px;padding:3px 12px;cursor:pointer}
.db{padding:16px 18px 18px;overflow:auto;max-height:calc(100vh - 130px)}
.db dl{font-size:14px;margin-bottom:12px}
.db h3{font-size:15.5px;margin:18px 0 8px;color:var(--title)}
.db h3 small{font-weight:400;color:var(--muted);font-size:12.5px}
.big{display:flex;gap:26px;margin:6px 0 10px;flex-wrap:wrap}
.big div{font-size:13px;color:var(--muted)}
.big b{display:block;font-size:24px;color:var(--ink);font-variant-numeric:tabular-nums}
.big .won b{color:var(--c)}
.chart{height:170px;border-bottom:1px solid var(--line);display:flex;align-items:flex-end;gap:2px;padding-top:8px}
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
/* สถานะการเบิก */
.sts{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(260px,100%),1fr));gap:12px}
.st-card{border:1px solid var(--line);border-radius:12px;padding:12px 14px}
.st-card h4{margin:0 0 8px;font-size:14px}
.st-bar{display:flex;height:14px;border-radius:999px;overflow:hidden;background:var(--line)}
.st-bar i{display:block;height:100%}
.st-lgd{display:grid;grid-template-columns:auto 1fr auto;gap:4px 8px;margin-top:10px;font-size:13.5px;align-items:center}
.st-lgd i{width:11px;height:11px;border-radius:3px}
.st-lgd b{font-variant-numeric:tabular-nums}
.sw-none{background:var(--s-none)} .sw-resv{background:var(--s-resv)} .sw-sent{background:var(--s-sent)}
.st-note{font-size:12.5px;color:var(--muted);margin-top:8px}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}.gcard,.row,#tip,.dn circle,.fc-dn circle,.st-dn circle,.st-base .bar i{transition:none}.gcard:hover{transform:none}}
/* กล่องกลาง: visit / admit / IPD จำหน่าย (แบบ ClaimMap) - จัดคอลัมน์ชัด ไม่ให้เส้นคั่นทับป้าย คน (HN) */
.st-head small{font-weight:400;opacity:.85}
.st-lg .lr{display:grid;grid-template-columns:14px 1fr;column-gap:8px;row-gap:0}
.st-lg .lr i{grid-column:1;grid-row:1/4;align-self:start;margin-top:6px}
.st-lg .lr>:not(i){grid-column:2}
.st-lg .dch{padding-top:8px;border-top:1px dashed rgba(255,255,255,.3)}
.st-lg .dch i{background:transparent;border:2px dashed var(--ipd)}
.st-lg .dch b{font-size:19px}
.st-lg .hn{display:block;padding-top:9px;border-top:1px solid rgba(255,255,255,.22)}
.st-lg .hn span,.st-lg .hn b{display:block}
.st-base .bl{display:block;font-size:13px;color:#9fe6dc;font-weight:600}
.st-base .bf{display:block;font-size:15px;margin:2px 0 8px}
.lst-btns{display:flex;flex-wrap:wrap;gap:8px;margin:4px 0 6px}
.lst-btns button{border:1px solid var(--line);background:var(--surface);border-radius:9px;padding:7px 13px;font-size:14px;cursor:pointer;color:var(--ink2)}
.lst-btns button.go-l{background:var(--c);border-color:var(--c);color:#fff;font-weight:700}
.lst-btns button:hover{border-color:var(--c)}
.st-lgd{display:flex;flex-direction:column;gap:4px}
.st-row{display:grid;grid-template-columns:auto 1fr auto auto;gap:8px;align-items:center;width:100%;padding:5px 8px;border:1px solid transparent;border-radius:8px;background:none;cursor:pointer;text-align:left;font-size:14px}
.st-row:hover{border-color:var(--line);background:var(--bg)}
.st-row em{font-style:normal;font-size:12.5px;color:var(--title2);font-weight:600}
.dayr{cursor:pointer}.dayr:hover td{background:var(--bg)}
.lnk{font-size:12px;color:var(--title2);font-weight:600;margin-left:6px}
.chart .bar{cursor:pointer}
.back{border:1px solid var(--line);background:var(--surface);border-radius:999px;padding:4px 14px;cursor:pointer;color:var(--title2);font-weight:600}
.lst-sub{margin:10px 0 6px;font-size:14px;color:var(--ink2)}
.chips{display:flex;flex-wrap:wrap;gap:6px;margin:8px 0 0}
.chip{border:1px solid var(--line);background:var(--surface);border-radius:999px;padding:4px 12px;font-size:13.5px;cursor:pointer;color:var(--ink2)}
.chip.on{background:var(--c);border-color:var(--c);color:#fff}
.chip b{font-variant-numeric:tabular-nums}
.lst{max-height:min(60vh,620px);overflow:auto;border:1px solid var(--line);border-radius:10px}
.lst table{margin:0}
.lst thead th{position:sticky;top:0;background:var(--surface);z-index:1}
.lst td,.lst th{white-space:nowrap;text-align:left}
.lst td.hn{font-weight:700}
.lst td.mono{font-family:Consolas,"IBM Plex Mono",monospace}
.wk{font-size:12.5px;padding:1px 8px;border-radius:999px;background:var(--bg)}
.wk.won{color:var(--okc);background:var(--okb)}
.wk.lost{color:var(--warn)}
.stp{font-size:12.5px;font-weight:600;padding:1px 8px;border-radius:999px}
.stp.sent{color:#fff;background:var(--s-sent)}.stp.resv{background:var(--s-resv);color:#3a2600}.stp.none{background:var(--s-none);color:var(--ink)}
.cp{font-size:12.5px;font-weight:600;padding:1px 9px;border-radius:999px;white-space:nowrap}
.cp.ok{color:var(--okc);background:var(--okb)}
.cp.bad{color:#8a4b00;background:#fdecd3}
.cp-ok{background:var(--okc)} .cp-bad{background:#f0a24a}
.nfunds{display:flex;flex-direction:column;gap:8px;margin-top:6px}
.nfund{display:grid;grid-template-columns:minmax(0,1.3fr) minmax(0,1fr) minmax(0,1.2fr) auto;gap:14px;align-items:center;width:100%;padding:10px 14px;border:1px solid var(--line);border-left:6px solid var(--fc);border-radius:12px;background:var(--surface);cursor:pointer;text-align:left}
.nfund:hover{background:var(--bg)}
.nf-h b{display:block;font-size:15.5px}
.nf-h small{display:block;font-size:12.5px;color:var(--ink2)}
.nf-bar{height:10px;border-radius:999px;background:var(--line);overflow:hidden}
.nf-bar i{display:block;height:100%;background:var(--fc)}
.nf-n{font-size:14px;color:var(--ink2)}
.nf-n b{font-size:20px;color:var(--ink);font-variant-numeric:tabular-nums}
.nf-n small{display:block;font-size:12.5px}
.nfund em{font-style:normal;font-size:12.5px;color:var(--title2);font-weight:700}
@media(max-width:760px){.nfund{grid-template-columns:1fr}}
.top h1 .who{font-size:13.5px;color:var(--ink2);margin-top:4px}
.top h1 .who a{color:var(--title2);font-weight:600}
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <h1>Fund &amp; Claim Channel<small class="th">ภาพรวมกองทุนและช่องทางการเบิกจ่าย</small><small>ช่องทางเคลม สปสช. ปีงบประมาณ 2569 · 28 ช่องทาง · นับแล้ว <?= $ready ?>/28</small><small class="who">ผู้ใช้: <?= h(auth_user()['fullname'] ?? '') ?><?= (auth_user()['src'] ?? '') === 'hosxp_sso' ? ' (เข้าจาก HOSxP)' : '' ?> · <a href="settings.php">ตั้งค่าโรงพยาบาล</a> · <a href="login.php?logout=1">ออกจากระบบ</a></small></h1>
    <form class="period" id="period" autocomplete="off">
      <div class="seg" role="group" aria-label="เลือกวิธีกำหนดช่วงเวลา">
        <button type="button" id="modeFy" aria-pressed="false">ปีงบประมาณ</button>
        <button type="button" id="modeRange" aria-pressed="true">ช่วงวันที่</button>
      </div>
      <label class="field" id="fyFields" hidden>ปีงบ <select id="fy"></select></label>
      <label class="field rf">ตั้งแต่วันที่ <input type="date" id="dFrom"></label>
      <label class="field rf">ถึงวันที่ <input type="date" id="dTo"></label>
      <button type="submit" class="go">แสดงผล</button>
      <div class="quick" id="quick">
        <button type="button" data-q="today">วันนี้</button><button type="button" data-q="yesterday">เมื่อวาน</button>
        <button type="button" data-q="month">เดือนนี้</button><button type="button" data-q="lastmonth">เดือนที่แล้ว</button>
        <button type="button" data-q="qtr">ไตรมาสนี้</button>
      </div>
      <div class="range" id="rangeText" aria-live="polite"></div>
    </form>
  </div>

  <section class="cmp" aria-label="เทียบยอดตามกล่อง">
    <div class="cmp-top">
      <div class="cmp-h">เทียบยอดตามกล่อง<small>ทุกกล่องรวมกัน = ฐานส่งเบิก (OPD + IPD จำหน่าย) · ครั้งที่เข้าหลายช่องนับให้ช่องแรกตามลำดับชนะ · กดการ์ดเพื่อดูเฉพาะกล่อง</small></div>
      <div class="cmp-check"><span id="ckTxt">ฐานส่งเบิก –</span><span class="chk" id="ck">–</span></div>
    </div>
    <div class="cmp-body">
      <div class="dn">
        <svg viewBox="0 0 120 120" aria-hidden="true" id="dnSvg"></svg>
        <div class="dn-c"><span>ฐานส่งเบิก</span><b id="dnAll">–</b><span>ครั้ง</span></div>
      </div>
      <div class="gcards" id="gcards"></div>
      <div class="cmp-side">
        <div class="cmp-sec">ช่องทางที่ยอดสูงสุด <small>(หลังลำดับชนะ)</small></div>
        <div class="tops" id="tops"></div>
      </div>
    </div>
  </section>

  <div class="focusbar" id="focusbar" hidden><span id="focusTxt"></span><button type="button" id="unfocus">แสดงทุกกล่อง</button></div>

  <div class="poster" id="poster">
    <div class="pcol" id="colL"></div>
    <div class="pcol pmid" id="colM">
      <div class="stat">
        <div class="st-head">คนไข้มารับบริการทั้งหมด <small>(ตรงกับ Visit List ของ HOSxP)</small></div>
        <div class="st-chart">
          <div class="st-dn">
            <svg viewBox="0 0 120 120" aria-hidden="true"><circle class="tr" cx="60" cy="60" r="50"/><circle class="o" id="sO" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="0 100"/><circle class="i" id="sI" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="0 100"/></svg>
            <div class="c"><b id="sAll">–</b><span>visit</span></div>
          </div>
          <div class="st-lg">
            <div class="lr"><i style="background:var(--opd)"></i><span>OPD (ครั้ง)</span><b id="sOpd">–</b><small id="sOpdP"></small></div>
            <div class="lr"><i style="background:var(--ipd)"></i><span>Admit ต่อ (รับไว้)</span><b id="sAdm">–</b><small id="sAdmP"></small></div>
            <div class="lr dch"><i></i><span>IPD จำหน่าย (ใช้ส่งเบิก)</span><b id="sIpd">–</b><small>ไม่อยู่ในวง · นับตามวันจำหน่าย</small></div>
            <div class="hn"><span>คน (HN)</span><b id="sHn">–</b></div>
          </div>
        </div>
        <div class="st-base">
          <span class="bl">ฐานส่งเบิก (ใช้เทียบทุกกล่อง)</span>
          <span class="bf">OPD <b id="bO">–</b> + IPD จำหน่าย <b id="bI">–</b> = <b id="bT">–</b> ครั้ง</span>
          เข้าช่องทาง <b id="sIn">–</b> ครั้ง · ไม่เข้าช่องทาง <b id="sOut">–</b> ครั้ง
          <div class="bar" id="sBar"></div>
          <span id="sChk">–</span>
          <div class="st-why">ไม่เข้าช่องทาง = รายการที่ไม่เข้าเงื่อนไขช่องทางที่ระบบกำหนด (เช่น บริการในเหมาจ่ายรายหัว หรือสิทธิอื่น) ไม่ใช่ยอดเคลมตกหล่น</div>
          <details class="st-calc"><summary>ดูวิธีคำนวณ</summary><div id="sCalc"></div></details>
        </div>
        <div class="st-foot"><b id="sDate">–</b><br><span id="sAt">ข้อมูล HOSxP</span></div>
      </div>
    </div>
    <div class="pcol" id="colR"></div>
  </div>

  <footer>โครงช่องทางตามอินโฟกราฟิก "ช่องทางเคลม ปีงบประมาณ 2569" (99 Training) · เงื่อนไขตามเอกสาร สปสช. ปีงบ 2569 ·
    ลำดับชนะ: DMIS → โปรแกรมระดับชาติ → E-Claim → 43 แฟ้ม → แพลตฟอร์ม · ป้าย "ติดตามได้" = ส่งผ่าน e-Claim / FDH ดูสถานะการเบิกได้ ·
    UC = สิทธิ UCS/WEL · เวอร์ชัน <?= CC_VERSION ?></footer>
</div>
<div id="tip" role="tooltip" aria-hidden="true"></div>
<div id="ctip" hidden></div>
<dialog id="dlg"><div class="dh"><div><b id="dTitle"></b><span id="dSub"></span></div><button type="button" id="dClose">ปิด</button></div><div class="db" id="dBody"></div></dialog>

<script>
// session หมดอายุ (API ตอบ 401) -> กลับหน้าเข้าสู่ระบบ
const _fetch = window.fetch.bind(window);
window.fetch = async (...a) => { const r = await _fetch(...a); if (r.status === 401) location.href = 'login.php?timeout=1'; return r; };
const GROUPS = <?= json_encode(CC_GROUPS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const PRIORITY = <?= json_encode(CC_PRIORITY) ?>;
const CH = <?= json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const LAYOUT = {colL: ['f43', 'ecl'], colM: ['nat'], colR: ['dmis', 'plat']};
const SHORT = {f43: '43 แฟ้ม', ecl: 'E-Claim', dmis: 'DMIS', nat: 'โปรแกรมระดับชาติ', plat: 'แพลตฟอร์ม / โครงการพิเศษ'};
const fmt = n => Number(n || 0).toLocaleString('th-TH');
const MON = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
const th = s => { const [y,m,d] = s.split('-').map(Number); return `${d} ${MON[m-1]} ${y+543}`; };
const thm = s => { const [y,m] = s.split('-').map(Number); return `${MON[m-1]} ${String(y+543).slice(2)}`; };
const iso = t => { const z = new Date(t.getTime() - t.getTimezoneOffset()*60000); return z.toISOString().slice(0,10); };
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
const pct = (a, b) => b ? (a / b * 100).toFixed(1) + '%' : '0%';
const WHY = {ask: 'รอคำตอบงานประกัน', metric: 'ต้องตกลงตัวเลข'};
const ab = code => code.replace(/^DMIS_/, '').replace(/[^A-Z]/g, '').slice(0, 4) || code.slice(0, 3);
const $ = id => document.getElementById(id);
let last = null;

// ---- กล่องกลุ่ม ----
function boxHtml(g){
  const items = Object.entries(CH).filter(([, c]) => c.g === g);
  const rank = PRIORITY.indexOf(g) + 1;
  return `<section class="box" id="box-${g}" data-g="${g}" style="--c:var(--${g});--cs:var(--${g}s);--cb:var(--${g}b)">
    <div class="head"><b>${esc(GROUPS[g].name)}</b><small>${esc(GROUPS[g].en)} · ${items.length} ช่องทาง · ลำดับชนะที่ ${rank}</small></div>
    <div class="fchart">
      <div class="fc-dn"><svg viewBox="0 0 120 120" aria-hidden="true"><circle class="tr" cx="60" cy="60" r="50"/>
        <circle class="o" id="fo-${g}" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="0 100"/>
        <circle class="i" id="fi-${g}" cx="60" cy="60" r="50" pathLength="100" stroke-dasharray="0 100"/></svg>
        <div class="fc-c"><b id="fn-${g}">–</b><span>ครั้ง</span></div></div>
      <div class="fc-lg">
        <div><i style="background:var(--opd)"></i><span>ผู้ป่วยนอก (ครั้ง)</span><b id="fO-${g}">–</b><small id="fOp-${g}"></small></div>
        <div><i style="background:var(--ipd)"></i><span>ผู้ป่วยใน · จำหน่าย (AN)</span><b id="fI-${g}">–</b><small id="fIp-${g}"></small></div>
        <div class="hn"><span>คน (HN)</span><b id="fP-${g}">–</b><small id="fPp-${g}"></small></div>
      </div>
    </div>
    <div class="rows">${items.map(([code, c]) => `
      <button type="button" class="row ${c.status}" data-ch="${esc(code)}">
        <span class="ico">${esc(ab(code))}</span>
        <span class="tx"><span class="nm">${esc(c.name)}${c.track ? '<span class="trk">ติดตามได้</span>' : ''}</span>
          <span class="sb" id="sb-${esc(code)}"><span class="code">${esc(code)}</span> · ${esc(c.right)}</span></span>
        ${c.status === 'ready' ? `<span class="nb"><span class="n" id="n-${esc(code)}">–</span><span class="u">ครั้ง</span><span class="full" id="f-${esc(code)}"></span></span><span class="chev" aria-hidden="true">›</span>`
                               : `<span class="pill ${c.why}">${WHY[c.why] || 'รอเงื่อนไข'}</span>`}
      </button>`).join('')}</div>
    <p class="box-note">ตัวเลขใหญ่ = ครั้งที่ตกกล่องนี้ตามลำดับชนะ · "เต็ม" = ทุกครั้งที่เข้าเงื่อนไขช่องนี้ (รวมที่ไปตกช่องอื่น)</p>
  </section>`;
}
for (const col in LAYOUT) $(col).insertAdjacentHTML('beforeend', LAYOUT[col].map(boxHtml).join(''));
$('gcards').innerHTML = PRIORITY.map(g => `<button type="button" class="gcard" data-g="${g}" style="--c:var(--${g})">
    <span>${esc(SHORT[g])}</span><b id="gc-${g}">–</b><small id="gcp-${g}">ครั้ง</small><em id="gce-${g}">–</em></button>`).join('')
  + `<button type="button" class="gcard none" id="noneCard" style="--c:var(--none)"><span>ไม่เข้าช่องทาง <em class="go-n">แยกตามกองทุน ›</em></span><span class="why">รายการที่ไม่เข้าเงื่อนไขช่องทางที่ระบบกำหนด ไม่ใช่ยอดเคลมตกหล่น</span><b id="gc-none">–</b><small id="gcp-none">ครั้ง</small><em id="gce-none">–</em></button>`;

// ---- ช่วงเวลา ----
const $fy = $('fy'), $f = $('dFrom'), $t = $('dTo');
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
    case 'qtr': return [iso(new Date(y, Math.floor(m / 3) * 3, 1, 12)), iso(today)];
  }
}
let st = {mode: 'range', fy: curFy, from: iso(today), to: iso(today), q: 'today'};
try { const s = JSON.parse(localStorage.getItem('cc_main') || 'null'); if (s && s.from) st = {...st, ...s}; } catch(e) {}
const range = () => st.mode === 'fy' ? [`${st.fy - 544}-10-01`, `${st.fy - 543}-09-30`] : (st.from <= st.to ? [st.from, st.to] : [st.to, st.from]);
$('modeFy').onclick = () => { st.mode = 'fy'; apply(); };
$('modeRange').onclick = () => { st.mode = 'range'; apply(); };
$fy.onchange = () => { st.fy = +$fy.value; apply(); };
$('period').onsubmit = e => { e.preventDefault(); if (st.mode === 'range' && $f.value && $t.value) { st.from = $f.value; st.to = $t.value; st.q = null; } apply(); };
document.querySelectorAll('#quick button').forEach(b => b.onclick = () => { [st.from, st.to] = quick(b.dataset.q); st.q = b.dataset.q; apply(); });
function apply(){
  try { localStorage.setItem('cc_main', JSON.stringify(st)); } catch(e) {}
  const fy = st.mode === 'fy';
  $('modeFy').setAttribute('aria-pressed', fy); $('modeRange').setAttribute('aria-pressed', !fy);
  $('fyFields').hidden = !fy; $('quick').hidden = fy;
  document.querySelectorAll('.rf').forEach(e => e.hidden = fy);
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
    last = j; draw(j);
    if (j.sync.left > 0 || j.sync.busy) setTimeout(() => { if (my === token) load(); }, j.sync.busy ? 4000 : 300);
  } catch(e) { if (my === token) setRange(a, b, 'ดึงข้อมูลไม่ได้: ' + e.message, true); }
}
const days = (a, b) => Math.round((new Date(b) - new Date(a)) / 864e5) + 1;
function setRange(a, b, extra, warn){
  const end = b > iso(today) ? iso(today) : b;
  $('rangeText').innerHTML = `ช่วงวันที่ที่เลือก: <b>${th(a)}${a !== b ? ' – ' + th(b) : ''}</b> รวม ${fmt(days(a, end))} วัน`
    + (extra ? ` · <span class="${warn ? 'warn' : ''}">${esc(extra)}</span>` : '');
}
// วงหลายสี: ส่วนของแต่ละกลุ่มตามลำดับชนะ + ไม่เข้าช่องทาง
function multiDonut(svg, parts, total){
  let off = 0, h = '<circle cx="60" cy="60" r="50" style="stroke:var(--line)"/>';
  for (const [v, color] of parts) {
    if (!v || !total) continue;
    const len = v / total * 100;
    h += `<circle cx="60" cy="60" r="50" pathLength="100" style="stroke:${color}" stroke-dasharray="${Math.max(len - .4, .2)} ${100 - Math.max(len - .4, .2)}" stroke-dashoffset="${-off}"/>`;
    off += len;
  }
  svg.innerHTML = h;
}
function ring(o, i, a, b, total){
  const t = a + b;
  o.setAttribute('stroke-dasharray', `${t ? a / t * 100 : 0} 100`);
  i.setAttribute('stroke-dasharray', `${t ? b / t * 100 : 0} 100`);
  i.setAttribute('stroke-dashoffset', `${t ? -a / t * 100 : 0}`);
}
function draw(j){
  const P = j.part, T = P.total, [a, b] = range();
  // ป้ายครบ
  const sumIn = Object.values(P.g).reduce((s, x) => s + x.n, 0);
  $('ckTxt').textContent = `ฐานส่งเบิก ${fmt(T.n)} ครั้ง`;
  $('ck').className = 'chk ' + (P.check ? 'ok' : 'bad');
  $('ck').textContent = P.check ? `✓ ครบ ${fmt(sumIn + P.none.n)} ครั้ง` : `✗ ไม่ตรง ${fmt(sumIn + P.none.n)}`;
  // วงหลายสี + การ์ด
  $('dnAll').textContent = fmt(T.n);
  multiDonut($('dnSvg'), [...PRIORITY.map(g => [P.g[g]?.n || 0, `var(--${g})`]), [P.none.n, 'var(--none)']], T.n);
  for (const g of PRIORITY) {
    const x = P.g[g] || {n: 0, p: 0, opd: 0, ipd: 0};
    $('gc-' + g).textContent = fmt(x.n); $('gcp-' + g).textContent = `ครั้ง · ${fmt(x.p)} คน (HN)`; $('gce-' + g).textContent = pct(x.n, T.n);
    $('fn-' + g).textContent = fmt(x.n);
    ring($('fo-' + g), $('fi-' + g), x.opd, x.ipd);
    $('fO-' + g).textContent = fmt(x.opd); $('fOp-' + g).textContent = pct(x.opd, x.n);
    $('fI-' + g).textContent = fmt(x.ipd); $('fIp-' + g).textContent = pct(x.ipd, x.n);
    $('fP-' + g).textContent = fmt(x.p); $('fPp-' + g).textContent = `${pct(x.n, T.n)} ของฐานส่งเบิก`;
  }
  $('gc-none').textContent = fmt(P.none.n); $('gcp-none').textContent = `ครั้ง · ${fmt(P.none.p)} คน (HN)`; $('gce-none').textContent = pct(P.none.n, T.n);
  const top = Object.entries(P.ch).sort((x, y) => y[1].n - x[1].n).slice(0, 3);
  $('tops').innerHTML = top.map(([code, x]) => { const g = CH[code].g;
    return `<button type="button" class="topc" data-ch="${esc(code)}" style="--c:var(--${g});--cs:var(--${g}s);--cb:var(--${g}b)">
      <b>${fmt(x.n)}</b><span>${esc(CH[code].name)}</span><small>${esc(code)}<br>${pct(x.n, T.n)}</small></button>`; }).join('');
  // แต่ละช่อง
  for (const code in P.ch) {
    const w = P.ch[code], f = j.totals[code] || w, n = $('n-' + code);
    if (!n) continue;
    n.textContent = fmt(w.n); n.className = 'n' + (w.n ? '' : ' zero');
    $('f-' + code).textContent = f.n !== w.n ? `เต็ม ${fmt(f.n)}` : '';
    $('sb-' + code).innerHTML = `<span class="code">${esc(code)}</span> · ${esc(CH[code].right)} · ${fmt(w.p)} คน (HN) · OPD ${fmt(w.opd)} ครั้ง · IPD ${fmt(w.ipd)} จำหน่าย (AN)`;
  }
  // กล่องกลาง
  // วง = visit ทั้งหมด (OPD + admit ตามวันรับไว้) ตรง Visit List · IPD จำหน่ายแยกไว้ใช้กับฐานส่งเบิก
  const V = j.visit || {n: T.n, opd: T.opd, adm: 0, p: T.p};
  $('sAll').textContent = fmt(V.n); $('sAll').className = V.n >= 100000 ? 'sm' : '';
  ring($('sO'), $('sI'), V.opd, V.adm);
  $('sOpd').textContent = fmt(V.opd); $('sOpdP').textContent = pct(V.opd, V.n);
  $('sAdm').textContent = fmt(V.adm); $('sAdmP').textContent = pct(V.adm, V.n);
  $('sIpd').textContent = fmt(T.ipd);
  $('sHn').textContent = fmt(V.p);
  $('bO').textContent = fmt(T.opd); $('bI').textContent = fmt(T.ipd); $('bT').textContent = fmt(T.n);
  $('sIn').textContent = `${fmt(sumIn)} (${pct(sumIn, T.n)})`; $('sOut').textContent = fmt(P.none.n);
  $('sBar').innerHTML = PRIORITY.map(g => `<i style="width:${T.n ? (P.g[g]?.n || 0) / T.n * 100 : 0}%;background:var(--${g})" title="${esc(SHORT[g])}"></i>`).join('');
  $('sChk').textContent = P.check ? 'ยอดจำแนกรวมตรงกับฐานส่งเบิก ✓' : 'ยอดจำแนกรวมไม่ตรงกับฐานส่งเบิก ✗';
  $('sCalc').innerHTML = PRIORITY.map(g => `${esc(SHORT[g])} <b>${fmt(P.g[g]?.n || 0)}</b>`).join(' + ')
    + ` + ไม่เข้าช่องทาง <b>${fmt(P.none.n)}</b> = <b>${fmt(sumIn + P.none.n)}</b> ครั้ง · ฐานส่งเบิก <b>${fmt(T.n)}</b> ครั้ง`
    + `<br>ครั้งที่เข้าหลายช่อง นับให้ช่องแรกตามลำดับชนะ: ${PRIORITY.map(g => esc(SHORT[g])).join(' → ')}`;
  $('sDate').textContent = th(a) + (a !== b ? ' – ' + th(b) : '');
  $('sAt').textContent = `ข้อมูล HOSxP ณ ${j.at} น.`;
  const notes = []; let warn = false;
  if (j.missing > 0) { notes.push(`ยังดึงไม่ครบ อีก ${fmt(j.missing)} ช่อง-วัน กำลังดึงต่อ`); warn = true; }
  if (j.sync.busy) { notes.push('มีคนกำลังดึงข้อมูลอยู่ ใช้ยอดในเครื่องไปก่อน'); warn = true; }
  if (j.sync.err.length) { notes.push('ดึงบางช่วงไม่สำเร็จ'); warn = true; }
  if (a < '2026-08-08') { notes.push('ก่อน 8 ส.ค. 69 เป็นข้อมูลย้ายระบบ ยอด Fee Schedule จะต่ำกว่าจริง'); warn = true; }
  setRange(a, b, notes.join(' · '), warn);
}

// ---- โหมดเลือกดูกล่องเดียว ----
const poster = $('poster');
function focusOn(g){
  document.querySelectorAll('.gcard[data-g]').forEach(c => c.classList.toggle('sel', c.dataset.g === g));
  document.querySelectorAll('.box').forEach(x => x.classList.toggle('on', x.dataset.g === g));
  poster.classList.add('focus');
  $('focusbar').hidden = false;
  $('focusTxt').textContent = `กำลังดูเฉพาะ ${GROUPS[g].name} คู่กับฐานส่งเบิก`;
  $('box-' + g).scrollIntoView({block: 'start'});
}
function unfocus(){
  poster.classList.remove('focus'); $('focusbar').hidden = true;
  document.querySelectorAll('.gcard.sel').forEach(c => c.classList.remove('sel'));
}
document.querySelectorAll('.gcard[data-g]').forEach(c => c.onclick = () => c.classList.contains('sel') ? unfocus() : focusOn(c.dataset.g));
$('unfocus').onclick = unfocus;

// ---- tooltip ----
const tip = $('tip');
document.addEventListener('mouseover', e => {
  const l = e.target.closest('.row,.topc'); if (!l) return;
  const c = CH[l.dataset.ch];
  tip.style.setProperty('--tc', `var(--${c.g})`);
  tip.innerHTML = `<h4>${esc(l.dataset.ch)} · ${esc(c.name)}</h4><dl><dt>ตามประกาศ</dt><dd>${esc(c.cond)}</dd>
    <dt>วิธีนับ</dt><dd>${esc(c.how)}</dd><dt>สิทธิ</dt><dd>${esc(c.right)}</dd><dt>ส่งผ่าน</dt><dd>${esc(c.via)}</dd></dl>`;
  tip.classList.add('show'); tip.setAttribute('aria-hidden', 'false');
});
document.addEventListener('mousemove', e => {
  if (!tip.classList.contains('show')) return;
  const x = Math.min(e.clientX + 14, innerWidth - tip.offsetWidth - 10);
  const y = e.clientY + 16 + tip.offsetHeight > innerHeight ? e.clientY - tip.offsetHeight - 12 : e.clientY + 16;
  tip.style.left = x + 'px'; tip.style.top = y + 'px';
});
document.addEventListener('mouseout', e => { if (e.target.closest('.row,.topc') && !e.relatedTarget?.closest?.('.row,.topc')) { tip.classList.remove('show'); tip.setAttribute('aria-hidden', 'true'); } });

// ---- หน้าต่างช่องทาง: ยอด + สถานะการเบิก + รายวัน ----
const dlg = $('dlg'), ctip = $('ctip');
$('dClose').onclick = () => dlg.close();
dlg.addEventListener('click', e => { if (e.target === dlg) dlg.close(); });
dlg.addEventListener('close', () => ctip.hidden = true);
document.addEventListener('click', e => { const l = e.target.closest('.row,.topc'); if (l) { tip.classList.remove('show'); openCh(l.dataset.ch); } });
function stCard(title, x){
  const t = x.none + x.resv + x.sent;
  const w = k => t ? x[k] / t * 100 : 0;
  return `<div class="st-card"><h4>${title} · ${fmt(t)} ราย</h4>
    <div class="st-bar"><i class="sw-sent" style="width:${w('sent')}%"></i><i class="sw-resv" style="width:${w('resv')}%"></i><i class="sw-none" style="width:${w('none')}%"></i></div>
    <div class="st-lgd">
      <button type="button" class="st-row" data-st="sent"><i class="sw-sent"></i><span>ส่งแล้ว (รอตอบกลับ)</span><b>${fmt(x.sent)} · ${pct(x.sent, t)}</b><em>รายชื่อ ›</em></button>
      <button type="button" class="st-row" data-st="resv"><i class="sw-resv"></i><span>จองเคลมแล้ว ยังไม่ส่ง</span><b>${fmt(x.resv)} · ${pct(x.resv, t)}</b><em>รายชื่อ ›</em></button>
      <button type="button" class="st-row" data-st="none"><i class="sw-none"></i><span>ยังไม่ส่ง</span><b>${fmt(x.none)} · ${pct(x.none, t)}</b><em>รายชื่อ ›</em></button></div></div>`;
}

// ---- รายชื่อคนไข้ (แสดงแค่ HN แบบ ClaimMap) ----
const SCOPE = {full: 'ทุกครั้งที่เข้าเงื่อนไข', won: 'ตกกล่องนี้ (ลำดับชนะ)', lost: 'ไปตกช่องอื่น'};
const STN = {all: 'ทุกสถานะ', sent: 'ส่งแล้ว', resv: 'จองเคลม', none: 'ยังไม่ส่ง'};
const WK = {won: 'กล่องนี้', lost: 'ช่องอื่น', out: 'ไม่อยู่ในฐาน'};
const MISS = {auth: 'Authen', pdx: 'Dx หลัก', drg: 'DRG/AdjRW'};
let listCtx = null;
async function openList(code, opt){
  const c = CH[code], [ra, rb] = range();
  listCtx = {code, scope: opt.scope || 'full', st: opt.st || 'all', cp: opt.cp || 'all', a: opt.a || ra, b: opt.b || rb};
  const L = listCtx, body = $('dBody');
  const head = `<button type="button" class="back" data-back="1">‹ กลับหน้าสรุป</button>
    <p class="lst-sub">${esc(code)} · ${esc(c.name)} · ${th(L.a)}${L.a !== L.b ? ' – ' + th(L.b) : ''}</p>`;
  body.innerHTML = head + '<p>กำลังโหลดรายชื่อ…</p>';
  const q = `ch=${encodeURIComponent(code)}&from=${L.a}&to=${L.b}&scope=${L.scope}&st=${L.st}&cp=${L.cp}`;
  let j;
  try { j = await (await fetch(`list.php?${q}`, {cache: 'no-store'})).json(); }
  catch(e) { body.innerHTML = head + `<p class="pend-box">ดึงรายชื่อไม่ได้: ${esc(e.message)}</p>`; return; }
  if (listCtx !== L) return;
  if (j.error) { body.innerHTML = head + `<p class="pend-box">${esc(j.error)}</p>`; return; }
  const sc = j.scope_count, full = sc.won + sc.lost + sc.out;
  const chip = (k, lbl, n, on, attr) => `<button type="button" class="chip${on ? ' on' : ''}" ${attr}="${k}">${lbl} <b>${fmt(n)}</b></button>`;
  let h = head + `<div class="chips">${chip('full', SCOPE.full, full, L.scope === 'full', 'data-scope')}
      ${chip('won', SCOPE.won, sc.won, L.scope === 'won', 'data-scope')}${chip('lost', SCOPE.lost, sc.lost, L.scope === 'lost', 'data-scope')}</div>`;
  const cc = j.cp_count;
  h += `<div class="chips">${chip('all', 'ทุกใบ', cc.ok + cc.bad, L.cp === 'all', 'data-cpf')}${chip('ok', 'สมบูรณ์', cc.ok, L.cp === 'ok', 'data-cpf')}
      ${chip('bad', 'ไม่สมบูรณ์', cc.bad, L.cp === 'bad', 'data-cpf')}</div>`;
  if (j.track && j.st_count) {
    const s = j.st_count, all = s.none + s.resv + s.sent;
    h += `<div class="chips">${chip('all', STN.all, all, L.st === 'all', 'data-stf')}${chip('sent', STN.sent, s.sent, L.st === 'sent', 'data-stf')}
      ${chip('resv', STN.resv, s.resv, L.st === 'resv', 'data-stf')}${chip('none', STN.none, s.none, L.st === 'none', 'data-stf')}</div>`;
  }
  h += `<p class="lst-sub">${fmt(j.total)} รายการ${j.shown < j.total ? ` (แสดง ${fmt(j.shown)} แรก)` : ''} · แสดงแค่ HN ·
      ${sc.out ? `"ไม่อยู่ในฐาน" = ใบ OPD ที่ admit ต่อ นับที่ AN แทน` : ''}</p>
    <div class="tw lst"><table><thead><tr><th>#</th><th>HN</th><th>วันที่</th><th>ประเภท</th><th>VN / AN</th><th>สิทธิ</th><th>ความสมบูรณ์</th>${L.scope === 'lost' ? '<th>ไปตกช่อง</th>' : ''}${j.track ? '<th>สถานะการเบิก</th>' : ''}</tr></thead><tbody>
    ${j.rows.map((r, i) => `<tr><td>${i + 1}</td><td class="hn">${esc(r.hn)}</td><td>${th(r.d)}</td><td>${r.side === 'OPD' ? 'OPD' : 'IPD (AN)'}</td>
      <td class="mono">${esc(r.ref)}</td><td>${esc(r.pttype)} ${esc(r.ptname || '')}</td>
      <td>${r.miss.length ? `<span class="cp bad">ไม่สมบูรณ์ · ขาด ${r.miss.map(m => MISS[m]).join(', ')}</span>` : '<span class="cp ok">สมบูรณ์</span>'}</td>
      ${L.scope === 'lost' ? `<td><span class="wk lost">${esc(r.win)}</span></td>` : ''}
      ${j.track ? `<td><span class="stp ${r.st}">${STN[r.st]}</span></td>` : ''}</tr>`).join('')}
    </tbody></table></div>`;
  body.innerHTML = h;
}
async function loadStatus(code, a, b){
  const box = $('stBox'); if (!box) return;
  try {
    const s = await (await fetch(`status.php?ch=${encodeURIComponent(code)}&from=${a}&to=${b}`, {cache: 'no-store'})).json();
    if (!$('stBox')) return;
    if (s.error) { box.innerHTML = `<p class="pend-box">${esc(s.error)}</p>`; return; }
    if (!s.track) { box.innerHTML = `<div class="pend-box">ช่องนี้ส่งผ่าน <b>${esc(s.via)}</b> นอก HOSxP ไม่มีร่องรอยการส่งในฐาน จึงยังติดตามสถานะไม่ได้</div>`; return; }
    const ms = Object.entries(s.months);
    box.innerHTML = `<div class="sts">${s.total.O.none + s.total.O.resv + s.total.O.sent ? stCard('ผู้ป่วยนอก (visit)', s.total.O) : ''}
        ${s.total.I.none + s.total.I.resv + s.total.I.sent ? stCard('ผู้ป่วยใน (AN)', s.total.I) : ''}</div>
      ${ms.length > 1 ? `<div class="tw"><table><thead><tr><th>เดือน</th><th>ส่งแล้ว</th><th>จองเคลม</th><th>ยังไม่ส่ง</th><th>รวม</th></tr></thead><tbody>
        ${ms.map(([m, x]) => { const q = k => x.O[k] + x.I[k]; return `<tr><td>${thm(m)}</td><td>${fmt(q('sent'))}</td><td>${fmt(q('resv'))}</td><td>${fmt(q('none'))}</td><td>${fmt(q('sent') + q('resv') + q('none'))}</td></tr>`; }).join('')}
      </tbody></table></div>` : ''}
      <p class="st-note">${esc(s.note)} · นับจากยอดเต็มของช่องนี้ · ส่ง = รอบส่ง FDH หรือ e-Claim · ข้อมูล ณ ${esc(s.at)} น.</p>`;
  } catch(e) { box.innerHTML = `<p class="pend-box">ดึงสถานะไม่ได้: ${esc(e.message)}</p>`; }
}
async function loadComplete(code, a, b){
  const box = $('cpBox'); if (!box) return;
  try {
    const j = await (await fetch(`list.php?ch=${encodeURIComponent(code)}&from=${a}&to=${b}&count=1`, {cache: 'no-store'})).json();
    if (!$('cpBox')) return;
    if (j.error) { box.innerHTML = `<div class="pend-box">${esc(j.error)}${j.toolong ? ' (กดแถวรายวันหรือแท่งกราฟเพื่อดูทีละวันได้)' : ''}</div>`; return; }
    const c = j.cp_count, t = c.ok + c.bad;
    box.innerHTML = `<div class="st-card"><h4>${fmt(t)} ใบ · สมบูรณ์ ${pct(c.ok, t)}</h4>
      <div class="st-bar"><i class="cp-ok" style="width:${t ? c.ok / t * 100 : 0}%"></i><i class="cp-bad" style="width:${t ? c.bad / t * 100 : 0}%"></i></div>
      <div class="st-lgd">
        <button type="button" class="st-row" data-cpg="ok"><i class="cp-ok"></i><span>สมบูรณ์</span><b>${fmt(c.ok)} · ${pct(c.ok, t)}</b><em>รายชื่อ ›</em></button>
        <button type="button" class="st-row" data-cpg="bad"><i class="cp-bad"></i><span>ไม่สมบูรณ์ · ขาด Authen ${fmt(c.auth)} · ขาด Dx หลัก ${fmt(c.pdx)}${c.drg ? ` · ขาด DRG ${fmt(c.drg)}` : ''}</span><b>${fmt(c.bad)} · ${pct(c.bad, t)}</b><em>รายชื่อ ›</em></button>
      </div><p class="st-note">นับจากยอดเต็มของช่องนี้ · 1 ใบขาดได้หลายอย่าง · ผู้ป่วยในที่เพิ่งจำหน่ายมักยังไม่ได้ลงรหัส Dx / DRG</p></div>`;
  } catch(e) { box.innerHTML = `<p class="pend-box">ตรวจความสมบูรณ์ไม่ได้: ${esc(e.message)}</p>`; }
}
async function openCh(code){
  listCtx = null;
  const c = CH[code], [a, b] = range();
  dlg.style.setProperty('--c', `var(--${c.g})`);
  $('dTitle').textContent = code; $('dSub').textContent = `${c.name} · ${GROUPS[c.g].name}`;
  const body = $('dBody');
  const info = `<dl><dt>ตามประกาศ</dt><dd>${esc(c.cond)}</dd><dt>วิธีนับ</dt><dd>${esc(c.how)}</dd>
    <dt>สิทธิ</dt><dd>${esc(c.right)}</dd><dt>ส่งผ่าน</dt><dd>${esc(c.via)}</dd><dt>ช่วงเวลา</dt><dd>${th(a)}${a !== b ? ' – ' + th(b) : ''}</dd></dl>`;
  dlg.showModal();
  if (c.status !== 'ready') { body.innerHTML = info + `<div class="pend-box">ยังไม่นับ: ${WHY[c.why] || 'รอเงื่อนไข'}</div>`; return; }
  body.innerHTML = info + '<p>กำลังโหลด…</p>';
  const j = await (await fetch(`api.php?from=${a}&to=${b}&ch=${encodeURIComponent(code)}`, {cache: 'no-store'})).json();
  if (j.error) { body.innerHTML = info + `<p>${esc(j.error)}</p>`; return; }
  const d = j.days, sum = k => d.reduce((s, x) => s + x[k], 0), max = Math.max(1, ...d.map(x => x.n));
  const w = last?.part?.ch?.[code];
  let html = info + `<div class="big">${w ? `<div class="won">ตกกล่องนี้ (ลำดับชนะ)<b>${fmt(w.n)}</b></div>` : ''}
    <div>ยอดเต็ม (ครั้ง)<b>${fmt(sum('n'))}</b></div><div>คน<b>${fmt(j.p)}</b></div>
    <div>OPD<b>${fmt(sum('opd'))}</b></div><div>IPD<b>${fmt(sum('ipd'))}</b></div></div>
    <div class="lst-btns"><button type="button" class="go-l" data-l="full">ดูรายชื่อคนไข้ (ยอดเต็ม) ›</button>
      <button type="button" data-l="won">เฉพาะที่ตกกล่องนี้ ›</button><button type="button" data-l="lost">เข้าเงื่อนไขแต่ไปตกช่องอื่น ›</button></div>`;
  html += `<h3>ความสมบูรณ์ของข้อมูล <small>มี Authen · มี Dx หลัก · IPD มี DRG/AdjRW</small></h3><div id="cpBox"><p>กำลังตรวจ…</p></div>`;
  html += `<h3>สถานะการเบิก <small>${c.track ? 'ส่งผ่าน e-Claim / FDH' : 'ส่งนอก HOSxP'}</small></h3><div id="stBox"><p>กำลังโหลดสถานะ…</p></div>`;
  html += `<h3>รายวัน <small>ยอดเต็ม</small></h3>`;
  if (d.length > 1) html += `<div class="chart" id="chart">${d.map((x, i) => `<div class="bar" style="height:${x.n / max * 100}%" data-i="${i}"></div>`).join('')}</div>
    <div class="axis"><span>${th(d[0].d)}</span><span>${th(d[d.length - 1].d)}</span></div>`;
  html += `<button type="button" class="tbl-t" id="tblT">แสดงตารางรายวัน</button><div class="tw" id="tblW" hidden><table>
    <thead><tr><th>วันที่</th><th>OPD</th><th>IPD</th><th>รวม (ครั้ง)</th><th>คน</th></tr></thead><tbody>
    ${d.map(x => `<tr class="dayr" data-day="${x.d}"><td>${th(x.d)} <span class="lnk">รายชื่อ ›</span></td><td>${fmt(x.opd)}</td><td>${fmt(x.ipd)}</td><td>${fmt(x.n)}</td><td>${fmt(x.p)}</td></tr>`).join('')}</tbody></table></div>`;
  body.innerHTML = html;
  curCh = code;
  $('tblT').onclick = () => { const t = $('tblW'); t.hidden = !t.hidden; };
  loadStatus(code, a, b);
  loadComplete(code, a, b);
  const ch = $('chart');
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

// ---- ไม่เข้าช่องทาง แยกตามกองทุน (นิยามเดียวกับ ClaimMap) ----
const FUNDS = <?= json_encode(CC_FUNDS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;
const FCOL = {hd: '#0f8a78', csop: '#208b79', ssop: '#ad7637', eclaim: '#327daf', agency: '#6b7a3a', uc: '#596fae', other: '#8a96a6'};
async function openNone(){
  const [a, b] = range();
  listCtx = null; curCh = null;
  dlg.style.setProperty('--c', '#5f6f84');
  $('dTitle').textContent = 'ไม่เข้าช่องทาง';
  $('dSub').textContent = 'รายการที่ไม่เข้าเงื่อนไขช่องทางที่ระบบกำหนด ไม่ใช่ยอดเคลมตกหล่น · แยกตามกองทุนแบบ ClaimMap';
  const body = $('dBody');
  if (!dlg.open) dlg.showModal();
  body.innerHTML = '<p>กำลังแยกตามกองทุน…</p>';
  const j = await (await fetch(`none.php?from=${a}&to=${b}`, {cache: 'no-store'})).json();
  if (j.error) { body.innerHTML = `<p class="pend-box">${esc(j.error)}</p>`; return; }
  const max = Math.max(1, ...Object.values(j.funds).map(x => x.n));
  const hip = Object.entries(j.other_hip).slice(0, 8).map(([k, v]) => `${esc(k)} ${fmt(v)}`).join(' · ');
  body.innerHTML = `<p class="lst-sub">${th(a)}${a !== b ? ' – ' + th(b) : ''} · ไม่เข้าช่องทาง <b>${fmt(j.total)}</b> ครั้ง จากฐานส่งเบิก ${fmt(j.base)} (${pct(j.total, j.base)})
      · ${j.check ? '<span class="cp ok">ผลรวมทุกกองตรงกับยอดไม่เข้าช่องทาง ✓</span>' : '<span class="cp bad">ผลรวมไม่ตรง</span>'}</p>
    <div class="nfunds">${Object.entries(FUNDS).map(([k, f]) => { const x = j.funds[k];
      return `<button type="button" class="nfund" data-fund="${k}" style="--fc:${FCOL[k]}">
        <span class="nf-h"><b>${esc(f.name)}</b><small>${esc(f.note)}</small></span>
        <span class="nf-bar"><i style="width:${x.n / max * 100}%"></i></span>
        <span class="nf-n"><b>${fmt(x.n)}</b> ครั้ง · ${pct(x.n, j.total)}<small>${fmt(x.p)} คน (HN) · OPD ${fmt(x.opd)} ครั้ง · IPD ${fmt(x.ipd)} จำหน่าย (AN)</small></span>
        <em>รายชื่อ ›</em></button>`; }).join('')}</div>
    ${hip ? `<p class="st-note">สิทธิอื่น / ชำระเอง แยกกลุ่มสิทธิ (hipdata): ${hip}</p>` : ''}
    <p class="st-note">บัตรทองที่ไม่เข้า 28 ช่อง = บริการที่อยู่ในเงินเหมาจ่ายรายหัว ไม่ต้องส่งเบิกแยก · ข้าราชการ / ประกันสังคม / ต้นสังกัด / E-Claim เบิกผ่านช่องทางของกองตัวเอง (ดูใน ClaimMap)</p>`;
}
async function openNoneList(fund){
  const [a, b] = range(), body = $('dBody');
  body.innerHTML = `<button type="button" class="back" data-nback="1">‹ กลับหน้าแยกกองทุน</button><p>กำลังโหลดรายชื่อ…</p>`;
  const j = await (await fetch(`none.php?from=${a}&to=${b}&fund=${fund}`, {cache: 'no-store'})).json();
  const head = `<button type="button" class="back" data-nback="1">‹ กลับหน้าแยกกองทุน</button>
    <p class="lst-sub">ไม่เข้าช่องทาง · ${esc(FUNDS[fund].name)} · ${th(a)}${a !== b ? ' – ' + th(b) : ''}</p>`;
  if (j.error) { body.innerHTML = head + `<p class="pend-box">${esc(j.error)}</p>`; return; }
  body.innerHTML = head + `<p class="lst-sub">${fmt(j.count)} รายการ${j.rows.length < j.count ? ` (แสดง ${fmt(j.rows.length)} แรก)` : ''} · แสดงแค่ HN</p>
    <div class="tw lst"><table><thead><tr><th>#</th><th>HN</th><th>วันที่</th><th>ประเภท</th><th>VN / AN</th><th>สิทธิ</th></tr></thead><tbody>
    ${j.rows.map((r, i) => `<tr><td>${i + 1}</td><td class="hn">${esc(r.hn)}</td><td>${th(r.d)}</td><td>${r.side === 'OPD' ? 'OPD' : 'IPD (AN)'}</td>
      <td class="mono">${esc(r.ref)}</td><td>${esc(r.pttype)} ${esc(r.ptname || '')}</td></tr>`).join('')}</tbody></table></div>`;
}
$('noneCard').addEventListener('click', openNone);
let curCh = null;
// คลิกในหน้าต่าง: ปุ่มรายชื่อ / แถบสถานะ / แถวรายวัน / แท่งกราฟ / ตัวกรอง / กลับ
$('dBody').addEventListener('click', e => {
  const n = e.target.closest('[data-fund],[data-nback]');
  if (n) return n.dataset.nback ? openNone() : openNoneList(n.dataset.fund);
  const t = e.target.closest('[data-l],[data-st],[data-day],.bar,[data-scope],[data-stf],[data-cpf],[data-cpg],[data-back]'); if (!t) return;
  const code = listCtx?.code || curCh; if (!code) return;
  if (t.dataset.back) return openCh(code);
  if (t.dataset.l) return openList(code, {scope: t.dataset.l});
  if (t.dataset.st) return openList(code, {st: t.dataset.st});
  if (t.dataset.day) return openList(code, {a: t.dataset.day, b: t.dataset.day});
  if (t.classList.contains('bar') && last) { const [a, b] = range(); const d = new Date(a); d.setDate(d.getDate() + (+t.dataset.i)); const k = iso(new Date(d.getTime() + 12 * 36e5)); return openList(code, {a: k, b: k}); }
  if (t.dataset.scope) return openList(code, {...listCtx, scope: t.dataset.scope});
  if (t.dataset.stf) return openList(code, {...listCtx, st: t.dataset.stf});
  if (t.dataset.cpf) return openList(code, {...listCtx, cp: t.dataset.cpf});
  if (t.dataset.cpg) return openList(code, {cp: t.dataset.cpg});
});

apply();
</script>
</body>
</html>
