<?php
// ==========================================================================
//  สถานะการเบิกของช่องที่ส่งผ่าน e-Claim / FDH (CC_TRACK) - ตารางเดียวกับ ClaimMap claim_status.php
//    GET status.php?ch=UCEP24&from=YYYY-MM-DD&to=YYYY-MM-DD
//  เคส = ทุกครั้งที่เข้าช่องนี้ (ยอดเต็ม ไม่ตัดลำดับชนะ) ตามสิทธิของช่อง จาก cache
//  ส่งแล้ว  = ovst_fund_batch_item_ma (รอบส่งแยกกอง fdh_claim เก็บ vn หรือ AN) หรือ ovst_eclaim_sent_ma (vn / an)
//  จองเคลม = fdh_claim_status.fdh_reservation_status Y ไม่ยกเลิก แต่ยังไม่อยู่ในรอบส่ง
//  ผลตอบกลับ / ใบแจ้งยอด: 5 ต.ค. 69 ยังไม่มีในฐาน (process_ok ว่าง, rep_eclaim 0 แถว, fdh_settle_at ว่าง) -> ส่งแล้ว = รอตอบกลับ
//  แคช cache/st_*.json: ช่วงที่มี 7 วันล่าสุด 15 นาที / นอกนั้น 6 ชม.
// ==========================================================================
require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/auth.php';
auth_require(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_write_close();
set_time_limit(180);

function out(int $c, array $d): void { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }

$re = '/^\d{4}-\d{2}-\d{2}$/';
$a = $_GET['from'] ?? ''; $b = $_GET['to'] ?? '';
if (!preg_match($re, $a) || !preg_match($re, $b)) out(400, ['error' => 'วันที่ไม่ถูกต้อง']);
if ($a > $b) [$a, $b] = [$b, $a];
if ((new DateTime($a))->diff(new DateTime($b))->days > 400) out(400, ['error' => 'ช่วงยาวเกิน 400 วัน']);
$ch = cc_channels()[$_GET['ch'] ?? ''] ?? null;
if (!$ch) out(400, ['error' => 'ไม่มีช่องนี้']);
if (!$ch['track']) out(200, ['track' => false, 'via' => $ch['via']]);

$fresh = $b >= date('Y-m-d', strtotime('-7 day'));
$cf = __DIR__ . '/cache/st_' . md5($ch['code'] . "|$a|$b|" . $ch['ver']) . '.json';
if (is_file($cf) && time() - filemtime($cf) < ($fresh ? 900 : 21600)) { readfile($cf); exit; }

// เคสจาก cache (ต้องดึงช่วงนี้มาแล้ว)
cc_sync($a, $b, 60);
$par = [$ch['code'], $a, $b];
$w = cc_rights_where($ch['rights'], $par);
$st = cc_lite()->prepare("SELECT h.id, h.d FROM hit h WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w");
$st->execute($par);
$O = []; $I = [];
foreach ($st as $r) {
    $k = substr($r['id'], 2);
    if ($r['id'][0] === 'O') $O[$k] = substr($r['d'], 0, 7); else $I[$k] = substr($r['d'], 0, 7);
}

// ถามฐาน (cc_status_map ใช้ร่วมกับ list.php)
$map = cc_status_map(array_keys($O), array_keys($I));
$sent = []; $resv = [];
foreach ($map as $k => $v) { if ($v === 'sent') $sent[$k] = 1; elseif ($v === 'resv') $resv[$k] = 1; }

// สรุปรวม + รายเดือน: none / resv / sent แยก OPD / IPD
$z = ['none' => 0, 'resv' => 0, 'sent' => 0];
$tot = ['O' => $z, 'I' => $z];
$mon = [];
foreach (['O' => $O, 'I' => $I] as $side => $set) {
    foreach ($set as $k => $m) {
        $s = isset($sent[$side . $k]) ? 'sent' : (isset($resv[$side . $k]) ? 'resv' : 'none');
        $tot[$side][$s]++;
        $mon[$m] ??= ['O' => $z, 'I' => $z];
        $mon[$m][$side][$s]++;
    }
}
ksort($mon);
$res = json_encode(['track' => true, 'via' => $ch['via'], 'code' => $ch['code'], 'from' => $a, 'to' => $b,
    'total' => $tot, 'months' => $mon, 'at' => date('H:i'),
    'note' => 'ยังไม่มีผลตอบกลับ / ใบแจ้งยอดจาก สปสช. ในฐาน ส่งแล้วทุกใบจึงเป็น "รอตอบกลับ"'], JSON_UNESCAPED_UNICODE);
@file_put_contents($cf, $res);
echo $res;
