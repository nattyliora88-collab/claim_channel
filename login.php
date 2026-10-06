<?php
// ==========================================================================
//  เข้าระบบได้ทางเดียว: เปิดจาก HOSxP / HOSxP Marketplace พร้อม ?bms-session-id=<GUID> (mazza 6 ต.ค. 69)
//  ไม่มีช่องกรอกชื่อผู้ใช้ / รหัสผ่าน · เปิดลิงก์ตรง / session หมดอายุ / ออกจากระบบ -> บอกให้เปิดจาก HOSxP ใหม่
// ==========================================================================
require_once __DIR__ . '/auth.php';

if (isset($_GET['logout'])) { auth_logout(); header('Location: login.php?bye=1'); exit; }
$err = null;
$bmsSid = bms_session_id_from_request();
if ($bmsSid !== '') {
    $r = auth_login_bms_session($bmsSid);
    if (is_array($r)) {
        auth_establish($r);
        header('Location: index.php');   // redirect ให้ URL ไม่ค้าง session id
        exit;
    }
    $err = $r;
}
if (auth_user()) { header('Location: index.php'); exit; }
if (!is_file(__DIR__ . '/db_settings.php')) {
    $err = 'ยังไม่ได้ตั้งค่าฐานข้อมูล HOSxP: คัดลอก db_settings.example.php เป็น db_settings.php แล้วแก้ค่าให้ตรงกับโรงพยาบาล (ดู README_INSTALL.txt)';
}
$notice = isset($_GET['timeout']) ? 'ออกจากระบบอัตโนมัติเพราะไม่ได้ใช้งานนาน'
        : (isset($_GET['bye']) ? 'ออกจากระบบแล้ว' : null);
?><!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบ · Fund &amp; Claim Channel</title>
<style>
@font-face{font-family:"Sarabun";font-weight:400;src:url(assets/fonts/sarabun-thai-400.woff2) format("woff2");unicode-range:U+0E01-0E5B}
@font-face{font-family:"Sarabun";font-weight:700;src:url(assets/fonts/sarabun-thai-700.woff2) format("woff2");unicode-range:U+0E01-0E5B}
@font-face{font-family:"Sarabun";font-weight:400;src:url(assets/fonts/sarabun-latin-400.woff2) format("woff2");unicode-range:U+0000-00FF}
@font-face{font-family:"Sarabun";font-weight:700;src:url(assets/fonts/sarabun-latin-700.woff2) format("woff2");unicode-range:U+0000-00FF}
:root{--ink:#172a43;--ink2:#3a4a5f;--line:#dce6f0;--title:#123d63;--bad:#c0392b;--badb:#fbe4e1;--warn:#7a5e12;--warnb:#fff8ea;--card:#fff;--step:#eef6f7}
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;padding:16px;font-family:"Sarabun","Leelawadee UI",Tahoma,sans-serif;background:linear-gradient(145deg,#123d63,#0b6677);color:var(--ink)}
.card{background:var(--card);width:440px;max-width:100%;border-radius:18px;overflow:hidden;box-shadow:0 14px 40px rgba(0,0,0,.3)}
.top{padding:24px 24px 16px;border-bottom:1px solid var(--line)}
.top h1{margin:0;font-size:26px;color:var(--title);letter-spacing:-.02em}
.top p{margin:4px 0 0;font-size:14px;color:var(--ink2)}
.bd{padding:22px 24px 24px}
.msg{padding:10px 12px;border-radius:9px;font-size:14.5px;margin-bottom:16px;line-height:1.5}
.err{background:var(--badb);color:var(--bad)} .warn{background:var(--warnb);color:var(--warn)}
.how{margin:0;padding:0;list-style:none;display:flex;flex-direction:column;gap:10px}
.how li{display:flex;gap:12px;align-items:flex-start;background:var(--step);border-radius:10px;padding:10px 12px;font-size:15px;line-height:1.5}
.how b.n{flex:none;width:26px;height:26px;border-radius:50%;background:#0f7d89;color:#fff;display:grid;place-items:center;font-size:14px}
.foot{font-size:12.5px;color:var(--ink2);margin-top:16px;line-height:1.5}
</style>
</head>
<body>
<div class="card">
  <div class="top"><h1>Fund &amp; Claim Channel</h1><p>ภาพรวมกองทุนและช่องทางการเบิกจ่าย</p></div>
  <div class="bd">
    <?php if ($notice): ?><div class="msg warn"><?= h($notice) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="msg err"><?= h($err) ?></div><?php endif; ?>
    <ol class="how">
      <li><b class="n">1</b><span>เข้าโปรแกรม HOSxP ด้วยบัญชีของท่าน</span></li>
      <li><b class="n">2</b><span>เปิด <b>HOSxP Dashboard / Marketplace</b> แล้วกดการ์ด <b>Fund &amp; Claim Channel</b></span></li>
      <li><b class="n">3</b><span>ระบบจะเข้าให้อัตโนมัติ ไม่ต้องกรอกรหัสผ่าน</span></li>
    </ol>
    <div class="foot">ระบบนี้เข้าได้จากการเปิดผ่าน HOSxP เท่านั้น (ยืนยันตัวตนด้วย session ของ HOSxP) · มี HN ผู้ป่วย เข้าได้เฉพาะเจ้าหน้าที่</div>
  </div>
</div>
</body>
</html>
