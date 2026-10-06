<?php
// ==========================================================================
//  เติมข้อมูลล่วงหน้าจาก CLI (ควรรันนอกเวลาทำการ ไม่กวน DB prod ช่วง 8-16 น.)
//    php sync.php                      -> ปีงบ 2569 (1 ต.ค. 68 - 30 ก.ย. 69)
//    php sync.php 2026-08-01 2026-09-30
//  ดึงทีละเดือน ดึงซ้ำเฉพาะวันที่ขาด/เงื่อนไขเปลี่ยน รันซ้ำได้ไม่เสียหาย
// ==========================================================================
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require_once __DIR__ . '/engine.php';
set_time_limit(0);
ini_set('memory_limit', '1G');

$a = $argv[1] ?? '2025-10-01';
$b = $argv[2] ?? '2026-09-30';
$t0 = microtime(true);
for ($m = new DateTime(substr($a, 0, 7) . '-01'); $m->format('Y-m-d') <= $b; $m->modify('first day of next month')) {
    $x = max($a, $m->format('Y-m-01'));
    $y = min($b, $m->format('Y-m-t'));
    $t = microtime(true);
    $r = cc_sync($x, $y, 3600);
    printf("%s ถึง %s  ดึง %d ช่วง  %.1f วิ%s\n", $x, $y, $r['pulled'], microtime(true) - $t, $r['busy'] ? '  (มีคนดึงอยู่ ข้าม)' : '');
    foreach ($r['err'] as $e) echo "  ERR $e\n";
}
printf("เสร็จ %.1f นาที · ยังขาด %d ช่อง-วัน\n", (microtime(true) - $t0) / 60, cc_missing($a, $b));
