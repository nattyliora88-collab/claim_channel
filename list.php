<?php
// ==========================================================================
//  รายชื่อคนไข้ของช่องทาง (แสดงแค่ HN แบบ ClaimMap ไม่โชว์ชื่อ)
//    GET list.php?ch=UCEP24&from=&to=[&scope=full|won|lost][&st=all|none|resv|sent][&cp=all|ok|bad][&count=1]
//  scope: full = ทุกครั้งที่เข้าเงื่อนไขช่องนี้ · won = ตกกล่องนี้ตามลำดับชนะ · lost = เข้าเงื่อนไขแต่ไปตกช่องอื่น
//  st: สถานะการเบิก (เฉพาะ CC_TRACK) · cp: ความสมบูรณ์ (cc_complete_map: Authen / Dx หลัก / IPD DRG)
//  count=1 = ส่งแค่ตัวเลขสรุป (หน้าต่างช่องทาง) ไม่ส่งรายชื่อ
//  ช่วงยาวสุด 62 วัน (เหมือน ClaimMap) · แสดงสูงสุด 5,000 แถว
// ==========================================================================
require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/auth.php';
auth_require(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_write_close();
set_time_limit(180);

function out(int $c, array $d): void { http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE); exit; }
$pick = fn(string $k, array $ok) => in_array($_GET[$k] ?? $ok[0], $ok, true) ? ($_GET[$k] ?? $ok[0]) : $ok[0];

$re = '/^\d{4}-\d{2}-\d{2}$/';
$a = $_GET['from'] ?? ''; $b = $_GET['to'] ?? '';
if (!preg_match($re, $a) || !preg_match($re, $b)) out(400, ['error' => 'วันที่ไม่ถูกต้อง']);
if ($a > $b) [$a, $b] = [$b, $a];
if ((new DateTime($a))->diff(new DateTime($b))->days > 61) out(400, ['error' => 'ดูรายชื่อ / ความสมบูรณ์ได้ครั้งละไม่เกิน 62 วัน กรุณาเลือกช่วงวันที่ให้สั้นลง', 'toolong' => true]);
$ch = cc_channels()[$_GET['ch'] ?? ''] ?? null;
if (!$ch) out(400, ['error' => 'ไม่มีช่องนี้']);
if ($ch['status'] !== 'ready') out(400, ['error' => 'ช่องนี้ยังไม่นับ']);
$scope = $pick('scope', ['full', 'won', 'lost']);
$st    = $pick('st', ['all', 'none', 'resv', 'sent']);
$cp    = $pick('cp', ['all', 'ok', 'bad']);
$only  = !empty($_GET['count']);

cc_sync($a, $b, 60);
$par = [$ch['code'], $a, $b];
$w = cc_rights_where($ch['rights'], $par);
$q = cc_lite()->prepare("SELECT h.id, h.d, h.hn, h.pttype, p.name AS ptname FROM hit h
                         LEFT JOIN pttype_ref p ON p.pttype = h.pttype
                         WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w ORDER BY h.d, h.id");
$q->execute($par);
$rows = $q->fetchAll();

// ตกกล่องไหน (ใบที่ไม่อยู่ในฐานส่งเบิก เช่น Dx ของใบ OPD ที่ admit ต่อ = out)
$win = cc_winners($a, $b);
$cnt = ['won' => 0, 'lost' => 0, 'out' => 0];
foreach ($rows as &$r) {
    $wc = $win[$r['id']] ?? null;
    $r['win'] = $wc;
    $r['wk'] = $wc === null ? 'out' : ($wc === $ch['code'] ? 'won' : 'lost');
    $cnt[$r['wk']]++;
}
unset($r);
if ($scope === 'won')  $rows = array_values(array_filter($rows, fn($r) => $r['wk'] === 'won'));
if ($scope === 'lost') $rows = array_values(array_filter($rows, fn($r) => $r['wk'] === 'lost'));

// ความสมบูรณ์ + สถานะการเบิก (ถามฐานครั้งเดียวต่อชุด)
$O = []; $I = [];
foreach ($rows as $r) { if ($r['id'][0] === 'O') $O[] = substr($r['id'], 2); else $I[] = substr($r['id'], 2); }
$cmap = cc_complete_map($O, $I);
$smap = $ch['track'] ? cc_status_map($O, $I) : [];
$ccnt = ['ok' => 0, 'bad' => 0, 'auth' => 0, 'pdx' => 0, 'drg' => 0];
$scnt = $ch['track'] ? ['none' => 0, 'resv' => 0, 'sent' => 0] : null;
foreach ($rows as &$r) {
    $k = $r['id'][0] . substr($r['id'], 2);
    $r['miss'] = $cmap[$k] ?? [];
    $ccnt[$r['miss'] ? 'bad' : 'ok']++;
    foreach ($r['miss'] as $m) $ccnt[$m]++;
    if ($ch['track']) { $r['st'] = $smap[$k] ?? 'none'; $scnt[$r['st']]++; }
}
unset($r);
// ใบไม่สมบูรณ์ขึ้นก่อน (คนตามแก้เห็นทันที) แล้วตามวันที่
usort($rows, fn($x, $y) => [(int)!$x['miss'], $x['d'], $x['id']] <=> [(int)!$y['miss'], $y['d'], $y['id']]);
if ($cp === 'ok')  $rows = array_values(array_filter($rows, fn($r) => !$r['miss']));
if ($cp === 'bad') $rows = array_values(array_filter($rows, fn($r) => (bool)$r['miss']));
if ($ch['track'] && $st !== 'all') $rows = array_values(array_filter($rows, fn($r) => $r['st'] === $st));

$res = ['code' => $ch['code'], 'name' => $ch['name'], 'track' => $ch['track'], 'from' => $a, 'to' => $b,
    'scope' => $scope, 'st' => $st, 'cp' => $cp, 'total' => count($rows),
    'scope_count' => $cnt, 'cp_count' => $ccnt, 'st_count' => $scnt];
if (!$only) {
    $res['rows'] = [];
    foreach (array_slice($rows, 0, 5000) as $r) {
        $res['rows'][] = ['hn' => $r['hn'], 'd' => $r['d'], 'side' => $r['id'][0] === 'O' ? 'OPD' : 'IPD',
            'ref' => substr($r['id'], 2), 'pttype' => $r['pttype'], 'ptname' => $r['ptname'],
            'win' => $r['win'], 'wk' => $r['wk'], 'miss' => $r['miss'], 'st' => $r['st'] ?? null];
    }
    $res['shown'] = count($res['rows']);
}
out(200, $res);
