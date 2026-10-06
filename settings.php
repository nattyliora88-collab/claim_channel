<?php
// ==========================================================================
//  ตั้งค่าโรงพยาบาล - รหัสที่แต่ละ รพ. ใช้ไม่เหมือนกัน (CC_SETTINGS ใน channels.php)
//  บันทึกแล้วช่องที่เกี่ยวข้องดึงข้อมูลใหม่เอง (ver เปลี่ยนตาม SQL)
//  GET ?find=lab&q=  -> ค้นรายการ Lab · ?find=drug&re= -> ตัวอย่างชื่อยาที่ตรงคำค้น
// ==========================================================================
require_once __DIR__ . '/channels.php';
require_once __DIR__ . '/auth.php';
auth_require();

$pg = cc_pg();
if (isset($_GET['find'])) {
    header('Content-Type: application/json; charset=utf-8');
    session_write_close();
    try {
        if ($_GET['find'] === 'lab') {
            $q = trim((string)($_GET['q'] ?? ''));
            $st = $pg->prepare("SELECT lab_items_code AS code, TRIM(lab_items_name) AS name FROM lab_items
                                WHERE lab_items_name ILIKE ? OR CAST(lab_items_code AS text) = ? ORDER BY lab_items_name LIMIT 25");
            $st->execute(['%' . $q . '%', $q]);
            echo json_encode($st->fetchAll(), JSON_UNESCAPED_UNICODE);
        } elseif ($_GET['find'] === 'drug') {
            $re = preg_replace("/['\\\\;]/u", '', (string)($_GET['re'] ?? ''));
            if (trim($re, '| ') === '') { echo '{"n":0,"names":[]}'; exit; }
            $st = $pg->prepare("SELECT TRIM(name) AS name FROM drugitems WHERE name ~* ? ORDER BY name LIMIT 30");
            $st->execute([$re]);
            echo json_encode(['names' => $st->fetchAll(PDO::FETCH_COLUMN)], JSON_UNESCAPED_UNICODE);
        }
    } catch (Throwable $e) { http_response_code(400); echo json_encode(['error' => 'คำค้นไม่ถูกต้อง'], JSON_UNESCAPED_UNICODE); }
    exit;
}

$msg = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $by = auth_user()['fullname'] ?? '';
    foreach (CC_SETTINGS as $k => $d) {
        if ($d['type'] === 'wards') {
            $v = implode(',', array_map(fn($x) => preg_replace('/[^A-Za-z0-9_.-]/', '', $x), (array)($_POST['wards'] ?? [])));
        } else {
            $v = trim((string)($_POST[$k] ?? ''));
            if ($d['type'] === 'labs') $v = implode(',', array_values(array_filter(array_map(fn($x) => preg_replace('/[^A-Za-z0-9_.-]/', '', trim($x)), explode(',', $v)))));
            if ($d['type'] === 'int')  $v = (string)max(0, (int)$v);
            if ($d['type'] === 'regex') $v = trim(preg_replace("/['\\\\;]/u", '', $v), '| ');
        }
        // เท่าค่าเริ่มต้น = ลบออก (ใช้ค่าเริ่มต้นต่อ)
        cc_setting_save($k, ($v === '' || $v === $d['default']) ? null : $v, $by);
    }
    header('Location: settings.php?saved=1');
    exit;
}
if (isset($_GET['saved'])) $msg = 'บันทึกแล้ว ช่องที่เกี่ยวข้องจะดึงข้อมูลใหม่ตามค่าที่ตั้งเมื่อเปิดหน้าหลัก';

// ชื่อรายการ Lab ของรหัสที่ตั้งอยู่
$labCodes = [];
foreach (CC_SETTINGS as $k => $d) if ($d['type'] === 'labs') $labCodes = array_merge($labCodes, explode(',', cc_set($k)));
$labName = [];
if ($labCodes) {
    $in = implode(',', array_fill(0, count($labCodes), '?'));
    $st = $pg->prepare("SELECT CAST(lab_items_code AS text) AS code, TRIM(lab_items_name) AS name FROM lab_items WHERE CAST(lab_items_code AS text) IN ($in)");
    $st->execute(array_map('trim', $labCodes));
    foreach ($st as $r) $labName[$r['code']] = $r['name'];
}
$wards = $pg->query("SELECT TRIM(ward) AS ward, TRIM(name) AS name, COALESCE(ward_active,'Y') AS active FROM ward ORDER BY (name ILIKE 'HOME%') DESC, (COALESCE(ward_active,'Y') <> 'Y'), name")->fetchAll();
$selWards = array_filter(explode(',', cc_set('homeward_wards')));
$meta = [];
foreach (cc_lite()->query("SELECT k, by_user, at FROM setting") as $r) $meta[$r['k']] = $r;
$cfg = cc_cfg();
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ตั้งค่าโรงพยาบาล · Fund &amp; Claim Channel</title>
<style>
@font-face{font-family:"Sarabun";font-weight:400;src:url(assets/fonts/sarabun-thai-400.woff2) format("woff2");unicode-range:U+0E01-0E5B}
@font-face{font-family:"Sarabun";font-weight:600;src:url(assets/fonts/sarabun-thai-600.woff2) format("woff2");unicode-range:U+0E01-0E5B}
@font-face{font-family:"Sarabun";font-weight:700;src:url(assets/fonts/sarabun-thai-700.woff2) format("woff2");unicode-range:U+0E01-0E5B}
@font-face{font-family:"Sarabun";font-weight:400;src:url(assets/fonts/sarabun-latin-400.woff2) format("woff2");unicode-range:U+0000-00FF}
@font-face{font-family:"Sarabun";font-weight:700;src:url(assets/fonts/sarabun-latin-700.woff2) format("woff2");unicode-range:U+0000-00FF}
:root{--bg:#f5f8fc;--surface:#fff;--ink:#172a43;--ink2:#3a4a5f;--muted:#56667c;--line:#dce6f0;--title:#123d63;--title2:#167f98;--go:#0f7d89;--okc:#1e8449;--okb:#e3f4ea;--chip:#eef3f8}
@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){color-scheme:dark;--bg:#101b29;--surface:#1a293a;--ink:#e9f1f8;--ink2:#c9d5e2;--muted:#a4b5c7;--line:#32475e;--title:#d6eafa;--title2:#71cbd3;--go:#169aa7;--okc:#6fcf97;--okb:#173326;--chip:#22344a}}
:root[data-theme="dark"]{color-scheme:dark;--bg:#101b29;--surface:#1a293a;--ink:#e9f1f8;--ink2:#c9d5e2;--muted:#a4b5c7;--line:#32475e;--title:#d6eafa;--title2:#71cbd3;--go:#169aa7;--okc:#6fcf97;--okb:#173326;--chip:#22344a}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--ink);font:16px/1.55 "Sarabun","Leelawadee UI",Tahoma,sans-serif;padding:0 16px}
.wrap{max-width:980px;margin:0 auto;padding-block:28px 56px}
a{color:var(--title2)}
h1{margin:0;font-size:32px;color:var(--title);letter-spacing:-.02em}
.sub{color:var(--ink2);margin:4px 0 0;font-size:15px}
.msg{margin:16px 0 0;padding:10px 14px;border-radius:10px;background:var(--okb);color:var(--okc);font-weight:600}
.db{margin:16px 0 0;font-size:14px;color:var(--ink2)}
.card{margin-top:18px;background:var(--surface);border:1px solid var(--line);border-radius:16px;padding:18px 20px}
.card h2{margin:0 0 2px;font-size:19px}
.card h2 .ch{font:600 13px/1 Consolas,monospace;color:var(--title2);background:var(--chip);border-radius:6px;padding:3px 7px;margin-left:8px;vertical-align:2px}
.row{display:grid;grid-template-columns:240px minmax(0,1fr);gap:8px 18px;padding:12px 0;border-top:1px solid var(--line)}
.row:first-of-type{border-top:0}
.row label{font-weight:600}
.row .help{display:block;font-weight:400;font-size:13.5px;color:var(--ink2)}
.row .who{display:block;font-weight:400;font-size:12.5px;color:var(--muted);margin-top:4px}
input[type=text],input[type=number],input[type=search]{width:100%;padding:9px 11px;border:1px solid var(--line);border-radius:9px;background:var(--bg);color:var(--ink);font:inherit}
.names{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
.names span{font-size:13.5px;background:var(--chip);border-radius:999px;padding:2px 10px}
.names span.bad{background:#fbe4e1;color:#c0392b}
.finder{margin-top:8px;display:flex;gap:8px}
.finder input{flex:1}
.res{margin-top:6px;max-height:220px;overflow:auto;border:1px solid var(--line);border-radius:9px;display:none}
.res button{display:flex;gap:10px;width:100%;border:0;border-bottom:1px solid var(--line);background:var(--surface);padding:7px 10px;text-align:left;cursor:pointer;font:inherit;font-size:14px;color:var(--ink)}
.res button:hover{background:var(--bg)}
.res code{font-family:Consolas,monospace;color:var(--title2);min-width:56px}
.wards{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:4px 14px;max-height:280px;overflow:auto;border:1px solid var(--line);border-radius:9px;padding:8px 10px}
.wards label{font-weight:400;font-size:14px;display:flex;gap:6px;align-items:baseline}
.wards label.off{color:var(--muted)}
.wards label.hint{font-weight:700}
.wards code{font-family:Consolas,monospace;font-size:12.5px;color:var(--title2)}
.bar{position:sticky;bottom:0;margin-top:20px;padding:12px 0;background:var(--bg);display:flex;gap:12px;align-items:center}
.bar button{border:0;border-radius:10px;background:var(--go);color:#fff;font:inherit;font-weight:700;padding:10px 22px;cursor:pointer}
.bar small{color:var(--ink2)}
@media(max-width:700px){.row{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="wrap">
  <p><a href="index.php">‹ กลับหน้าหลัก</a></p>
  <h1>ตั้งค่าโรงพยาบาล</h1>
  <p class="sub">รหัสที่แต่ละโรงพยาบาลใช้ไม่เหมือนกัน · ค่าเริ่มต้นเป็นของโรงพยาบาลหาดใหญ่ · บันทึกแล้วช่องที่เกี่ยวข้องจะดึงข้อมูลใหม่เอง</p>
  <?php if ($msg): ?><div class="msg"><?= h($msg) ?></div><?php endif; ?>
  <p class="db">ฐานข้อมูลที่ต่ออยู่: <b><?= h($cfg['dbname'] ?? '') ?></b> @ <?= h($cfg['host'] ?? '') ?> · ผู้ใช้: <?= h(auth_user()['fullname'] ?? '') ?></p>

  <form method="post" id="f">
  <?php
  $byCh = [];
  foreach (CC_SETTINGS as $k => $d) $byCh[$d['ch']][$k] = $d;
  $chs = cc_channels();
  foreach ($byCh as $code => $items): ?>
    <section class="card">
      <h2><?= h($chs[$code]['name'] ?? $code) ?><span class="ch"><?= h($code) ?></span></h2>
      <?php foreach ($items as $k => $d): $val = cc_set($k); $m = $meta[$k] ?? null; ?>
      <div class="row">
        <label for="<?= h($k) ?>"><?= h($d['label']) ?><span class="help"><?= h($d['help']) ?></span>
          <span class="who"><?= $m ? 'แก้ล่าสุด ' . h($m['at']) . ' โดย ' . h($m['by_user']) : 'ใช้ค่าเริ่มต้น' ?></span></label>
        <div>
        <?php if ($d['type'] === 'labs'): ?>
          <input type="text" id="<?= h($k) ?>" name="<?= h($k) ?>" value="<?= h($val) ?>" placeholder="รหัส Lab คั่นด้วย ,">
          <div class="names"><?php foreach (array_filter(explode(',', $val)) as $c): $c = trim($c); ?>
            <span class="<?= isset($labName[$c]) ? '' : 'bad' ?>"><?= h($c) ?> · <?= h($labName[$c] ?? 'ไม่พบรหัสนี้') ?></span><?php endforeach; ?></div>
          <div class="finder"><input type="search" data-for="<?= h($k) ?>" placeholder="ค้นชื่อ Lab เพื่อเพิ่ม เช่น TSH, HbA1c, ทารก"></div>
          <div class="res" data-res="<?= h($k) ?>"></div>
        <?php elseif ($d['type'] === 'int'): ?>
          <input type="number" min="0" id="<?= h($k) ?>" name="<?= h($k) ?>" value="<?= h($val) ?>" style="max-width:140px">
        <?php elseif ($d['type'] === 'regex'): ?>
          <input type="text" id="<?= h($k) ?>" name="<?= h($k) ?>" value="<?= h($val) ?>">
          <div class="names" id="drugPrev"></div>
        <?php elseif ($d['type'] === 'wards'): ?>
          <div class="wards">
          <?php foreach ($wards as $w): $hint = stripos($w['name'], 'HOME WARD') === 0; ?>
            <label class="<?= $w['active'] === 'Y' ? '' : 'off' ?> <?= $hint ? 'hint' : '' ?>"><input type="checkbox" name="wards[]" value="<?= h($w['ward']) ?>" <?= in_array($w['ward'], $selWards, true) ? 'checked' : '' ?>>
              <span><?= h($w['name']) ?> <code><?= h($w['ward']) ?></code></span></label>
          <?php endforeach; ?>
          </div>
          <span class="help"><?= $selWards ? 'เลือกไว้ ' . count($selWards) . ' ตึก' : 'ยังไม่เลือก = ใช้ตึกที่ชื่อขึ้นต้น HOME WARD (ตัวหนา)' ?></span>
        <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </section>
  <?php endforeach; ?>
    <div class="bar"><button type="submit">บันทึก</button><small>ลบค่าในช่องให้ว่าง = กลับไปใช้ค่าเริ่มต้น</small></div>
  </form>
</div>
<script>
const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));
// ค้น Lab แล้วกดเพิ่มรหัส
document.querySelectorAll('.finder input').forEach(inp => {
  let t;
  inp.addEventListener('input', () => {
    clearTimeout(t);
    const box = document.querySelector(`[data-res="${inp.dataset.for}"]`);
    if (inp.value.trim().length < 2) { box.style.display = 'none'; return; }
    t = setTimeout(async () => {
      const r = await (await fetch('settings.php?find=lab&q=' + encodeURIComponent(inp.value.trim()))).json();
      box.innerHTML = r.length ? r.map(x => `<button type="button" data-code="${esc(x.code)}"><code>${esc(x.code)}</code>${esc(x.name)}</button>`).join('') : '<button type="button" disabled>ไม่พบ</button>';
      box.style.display = 'block';
    }, 250);
  });
});
document.addEventListener('click', e => {
  const b = e.target.closest('.res button[data-code]'); if (!b) return;
  const k = b.closest('.res').dataset.res, f = document.getElementById(k);
  const codes = f.value.split(',').map(s => s.trim()).filter(Boolean);
  if (!codes.includes(b.dataset.code)) codes.push(b.dataset.code);
  f.value = codes.join(',');
  b.style.opacity = .5;
});
// ตัวอย่างชื่อยาที่ตรงคำค้นกัญชา
const dr = document.getElementById('dcnb_drug_re'), prev = document.getElementById('drugPrev');
async function drugPreview(){
  if (!dr) return;
  try {
    const r = await (await fetch('settings.php?find=drug&re=' + encodeURIComponent(dr.value))).json();
    prev.innerHTML = r.error ? `<span class="bad">${esc(r.error)}</span>`
      : (r.names.length ? r.names.map(n => `<span>${esc(n)}</span>`).join('') : '<span class="bad">ไม่พบยาที่ตรงคำค้น</span>');
  } catch(e) {}
}
let dt; if (dr) { dr.addEventListener('input', () => { clearTimeout(dt); dt = setTimeout(drugPreview, 400); }); drugPreview(); }
</script>
</body>
</html>
