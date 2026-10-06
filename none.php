<?php
// ==========================================================================
//  แยก "ไม่เข้าช่องทาง" (ใบในฐานส่งเบิกที่ไม่ตกช่องใดใน 28 ช่อง) ตามกองทุนแบบ ClaimMap
//    GET none.php?from=&to=              -> สรุปรายกอง (ครั้ง / คน / OPD / IPD) + สิทธิอื่นแยก hipdata
//    GET none.php?from=&to=&fund=csop    -> รายชื่อ (แสดงแค่ HN) ช่วงไม่เกิน 62 วัน
//  ผลรวมทุกกอง = ไม่เข้าช่องทาง ใน cc_partition พอดี (check)
// ==========================================================================
require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/auth.php';
auth_require(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_write_close();
set_time_limit(180);
ini_set('memory_limit', '1G');

function out(int $c, array $d): void { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
$re = '/^\d{4}-\d{2}-\d{2}$/';
$a = $_GET['from'] ?? ''; $b = $_GET['to'] ?? '';
if (!preg_match($re, $a) || !preg_match($re, $b)) out(400, ['error' => 'วันที่ไม่ถูกต้อง']);
if ($a > $b) [$a, $b] = [$b, $a];
$days = (new DateTime($a))->diff(new DateTime($b))->days;
if ($days > 400) out(400, ['error' => 'ช่วงยาวเกิน 400 วัน']);
$fund = $_GET['fund'] ?? '';
if ($fund !== '' && !isset(CC_FUNDS[$fund])) out(400, ['error' => 'ไม่มีกองนี้']);
if ($fund !== '' && $days > 61) out(400, ['error' => 'ดูรายชื่อได้ครั้งละไม่เกิน 62 วัน กรุณาเลือกช่วงวันที่ให้สั้นลง']);

cc_sync($a, $b, 60);
$lite = cc_lite();
$pt = [];
foreach ($lite->query("SELECT pttype, name, hipdata, upp FROM pttype_ref") as $r) $pt[$r['pttype']] = $r;
$win = cc_winners($a, $b);

$z = ['n' => 0, 'opd' => 0, 'ipd' => 0];
$g = []; $hn = []; $hip = []; $rows = []; $total = 0;
foreach (CC_FUNDS as $k => $_) $g[$k] = $z;
$q = $lite->prepare("SELECT id, d, hn, pttype FROM hit WHERE ch = '_ALL' AND d BETWEEN ? AND ? ORDER BY d, id");
$q->execute([$a, $b]);
while ($r = $q->fetch()) {
    if (isset($win[$r['id']])) continue;
    $side = $r['id'][0];
    $k = cc_fund_of($pt[$r['pttype']] ?? null, $side);
    $total++;
    $g[$k]['n']++; $g[$k][$side === 'O' ? 'opd' : 'ipd']++;
    $hn[$k][$r['hn']] = 1;
    if ($k === 'other') { $h = ($pt[$r['pttype']]['hipdata'] ?? '') ?: 'ไม่ระบุ'; $hip[$h] = ($hip[$h] ?? 0) + 1; }
    if ($fund === $k && count($rows) < 5000)
        $rows[] = ['hn' => $r['hn'], 'd' => $r['d'], 'side' => $side === 'O' ? 'OPD' : 'IPD', 'ref' => substr($r['id'], 2),
                   'pttype' => $r['pttype'], 'ptname' => $pt[$r['pttype']]['name'] ?? ''];
}
foreach ($g as $k => &$x) $x['p'] = count($hn[$k] ?? []);
unset($x);
arsort($hip);
$part = cc_partition($a, $b);
$res = ['from' => $a, 'to' => $b, 'total' => $total, 'base' => $part['total']['n'],
        'check' => $total === $part['none']['n'], 'funds' => $g, 'other_hip' => $hip];
if ($fund !== '') { $res['fund'] = $fund; $res['rows'] = $rows; $res['count'] = $g[$fund]['n']; }
out(200, $res);
