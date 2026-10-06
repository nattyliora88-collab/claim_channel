<?php
// ==========================================================================
//  api.php?from=YYYY-MM-DD&to=YYYY-MM-DD           -> ยอดทุกช่อง
//  api.php?from=..&to=..&ch=OP_ANYWHERE            -> รายวันของช่องเดียว
// ==========================================================================
require_once __DIR__ . '/engine.php';
require_once __DIR__ . '/auth.php';
auth_require(true);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
session_write_close();
set_time_limit(180);

function bad(string $m) { http_response_code(400); echo json_encode(['error' => $m], JSON_UNESCAPED_UNICODE); exit; }

$re = '/^\d{4}-\d{2}-\d{2}$/';
$a = $_GET['from'] ?? ''; $b = $_GET['to'] ?? '';
if (!preg_match($re, $a) || !preg_match($re, $b) || !strtotime($a) || !strtotime($b)) bad('วันที่ไม่ถูกต้อง');
if ($a > $b) [$a, $b] = [$b, $a];
if ((new DateTime($a))->diff(new DateTime($b))->days > 400) bad('ช่วงยาวเกิน 400 วัน');

$sync = cc_sync($a, $b, 90);
$chs  = cc_channels();

if (isset($_GET['ch'])) {
    $ch = $chs[$_GET['ch']] ?? null;
    if (!$ch) bad('ไม่มีช่องนี้');
    echo json_encode([
        'code' => $ch['code'], 'name' => $ch['name'], 'cond' => $ch['cond'], 'how' => $ch['how'],
        'rights' => cc_rights_label($ch['rights']), 'status' => $ch['status'],
        'days' => $ch['status'] === 'ready' ? cc_daily($ch, $a, $b) : [],
        'p'    => $ch['status'] === 'ready' ? (cc_totals($a, $b)[$ch['code']]['p'] ?? 0) : 0,
        'sync' => $sync,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'from' => $a, 'to' => $b,
    'totals'  => cc_totals($a, $b),
    'summary' => cc_summary($a, $b),
    'groups'  => cc_groups($a, $b),
    'part'    => cc_partition($a, $b),
    'visit'   => cc_visit($a, $b),
    'missing' => cc_missing($a, $b),
    'sync'    => $sync,
    'at'      => date('H:i'),
], JSON_UNESCAPED_UNICODE);
