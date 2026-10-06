<?php
// ==========================================================================
//  ตัวดึง + ตัวนับ
//
//  ดึง: เฉพาะช่อง ready / เฉพาะวันที่ยังไม่มี หรือดึงด้วยเงื่อนไขเก่า (ver ไม่ตรง)
//       หรือยังไม่นิ่ง (7 วันล่าสุด ดึงซ้ำได้ทุก 10 นาที) - วันเก่ากว่านั้นไม่แตะ HOSxP อีก
//       ดึงทีละไม่เกิน 1 เดือนต่อคำสั่ง (statement_timeout 30 วิ)
//       กันดึงซ้อนด้วย cache/sync.lock - ถ้ามีคนดึงอยู่ ใช้ยอดในเครื่องไปก่อน
//  นับ: ในเครื่องจาก hit + กรองสิทธิผ่าน pttype_ref
// ==========================================================================

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/channels.php';

const CC_FRESH_DAYS = 7;     // ข้อมูลยังแก้ย้อนหลังได้กี่วัน
const CC_REPULL_SEC = 600;   // ดึงซ้ำวันที่ยังไม่นิ่งได้ไม่ถี่กว่านี้

function cc_days(string $a, string $b): array
{
    $out = [];
    for ($t = new DateTime($a), $e = new DateTime($b); $t <= $e; $t->modify('+1 day')) $out[] = $t->format('Y-m-d');
    return $out;
}

// วันไหนต้องดึง (ช่องเดียว)
function cc_need(array $ch, string $a, string $b): array
{
    $lite  = cc_lite();
    $today = date('Y-m-d');
    $fresh = date('Y-m-d', strtotime("-" . CC_FRESH_DAYS . " day"));
    $st = $lite->prepare("SELECT d, ver, at FROM synced WHERE ch = ? AND d BETWEEN ? AND ?");
    $st->execute([$ch['code'], $a, $b]);
    $have = [];
    foreach ($st as $r) $have[$r['d']] = $r;
    $need = [];
    foreach (cc_days($a, min($b, $today)) as $d) {
        $h = $have[$d] ?? null;
        if (!$h || $h['ver'] !== $ch['ver']) { $need[] = $d; continue; }
        // วันที่ยังไม่นิ่ง หรือดึงไว้ตอนที่ยังไม่นิ่ง -> ดึงซ้ำได้เมื่อครบรอบ
        $pulledFresh = date('Y-m-d', (int)$h['at'] - CC_FRESH_DAYS * 86400) <= $d;
        if (($d >= $fresh || $pulledFresh) && time() - (int)$h['at'] >= CC_REPULL_SEC) $need[] = $d;
    }
    return $need;
}

// ตัดรายการวันเป็นช่วงต่อเนื่อง ไม่เกิน 31 วันต่อช่วง
function cc_runs(array $days): array
{
    $runs = [];
    foreach ($days as $d) {
        $n = count($runs);
        if ($n && date('Y-m-d', strtotime($runs[$n - 1][1] . ' +1 day')) === $d
               && (new DateTime($runs[$n - 1][0]))->diff(new DateTime($d))->days < 31) {
            $runs[$n - 1][1] = $d;
        } else {
            $runs[] = [$d, $d];
        }
    }
    return $runs;
}

// สิทธิของ visit: visit_pttype ลำดับ 1 (ตัวที่ตรง ovst.pttype ก่อน) ไม่มีค่อยใช้ ovst.pttype - แบบเดียวกับ claim_map
function cc_wrap_opd(string $sql): string
{
    return "WITH base AS ($sql)
            SELECT DISTINCT base.d::text AS d, 'O:' || base.vn AS id,
                   TRIM(COALESCE(vp.pttype, o.pttype, '')) AS pttype, o.hn
            FROM base JOIN ovst o ON o.vn = base.vn
            LEFT JOIN LATERAL (SELECT x.pttype FROM visit_pttype x
                               WHERE x.vn = o.vn AND x.pttype_number = '1'
                               ORDER BY (TRIM(x.pttype) = TRIM(o.pttype)) DESC, x.pttype LIMIT 1) vp ON true";
}
function cc_wrap_ipd(string $sql): string
{
    return "WITH base AS ($sql)
            SELECT DISTINCT base.d::text AS d, 'I:' || base.an AS id, TRIM(COALESCE(i.pttype, '')) AS pttype, i.hn
            FROM base JOIN ipt i ON i.an = base.an";
}

function cc_pull_run(array $ch, string $a, string $b): int
{
    $pg = cc_pg();
    $rows = [];
    foreach (['opd' => 'cc_wrap_opd', 'ipd' => 'cc_wrap_ipd'] as $k => $wrap) {
        if (!$ch[$k]) continue;
        $sql = str_replace(['{A}', '{B}'], ["DATE '$a'", "DATE '$b'"], $wrap($ch[$k]));
        foreach ($pg->query($sql) as $r) $rows[] = $r;
    }
    $lite = cc_lite();
    $lite->beginTransaction();
    $lite->prepare("DELETE FROM hit WHERE ch = ? AND d BETWEEN ? AND ?")->execute([$ch['code'], $a, $b]);
    $ins = $lite->prepare("INSERT OR IGNORE INTO hit (ch, d, id, pttype, hn) VALUES (?, ?, ?, ?, ?)");
    foreach ($rows as $r) $ins->execute([$ch['code'], $r['d'], $r['id'], $r['pttype'], $r['hn']]);
    $syn = $lite->prepare("INSERT OR REPLACE INTO synced (ch, d, ver, at) VALUES (?, ?, ?, ?)");
    foreach (cc_days($a, $b) as $d) $syn->execute([$ch['code'], $d, $ch['ver'], time()]);
    $lite->commit();
    return count($rows);
}

// สำเนา pttype (วันละครั้ง)
function cc_pull_pttype(): void
{
    $lite = cc_lite();
    $last = $lite->query("SELECT v FROM meta WHERE k = 'pttype_at'")->fetchColumn();
    if ($last && time() - (int)$last < 86400) return;
    $rows = cc_pg()->query("SELECT TRIM(p.pttype) AS pttype, p.name, TRIM(COALESCE(p.hipdata_code,'')) AS hipdata,
                                   TRIM(COALESCE(u.pttype_upp_type_code,'')) AS upp
                            FROM pttype p LEFT JOIN pttype_upp_type u ON u.pttype_upp_type_id = p.pttype_upp_type_id")->fetchAll();
    $lite->beginTransaction();
    $lite->exec("DELETE FROM pttype_ref");
    $ins = $lite->prepare("INSERT OR REPLACE INTO pttype_ref (pttype, name, hipdata, upp) VALUES (?, ?, ?, ?)");
    foreach ($rows as $r) $ins->execute([$r['pttype'], $r['name'], $r['hipdata'], $r['upp']]);
    $lite->prepare("INSERT OR REPLACE INTO meta (k, v) VALUES ('pttype_at', ?)")->execute([time()]);
    $lite->commit();
}

// ดึงทุกช่อง ready ในช่วง - คืน ['busy' => bool, 'pulled' => จำนวนช่วงที่ดึง, 'err' => [...]]
function cc_sync(string $a, string $b, float $budgetSec = 60): array
{
    $fh = fopen(__DIR__ . '/cache/sync.lock', 'c');
    if (!flock($fh, LOCK_EX | LOCK_NB)) return ['busy' => true, 'pulled' => 0, 'err' => [], 'left' => 0];
    $t0 = microtime(true);
    $out = ['busy' => false, 'pulled' => 0, 'err' => [], 'left' => 0];
    try {
        cc_pull_pttype();
        // ยอดตั้งต้น (ทุกคนที่มารับบริการ) ดึงก่อน แล้วค่อยทีละช่อง
        $all = cc_all(); $all['ver'] = cc_ver($all);
        $adm = cc_adm(); $adm['ver'] = cc_ver($adm);
        foreach (array_merge([$all, $adm], array_values(cc_channels())) as $ch) {
            if ($ch['status'] !== 'ready') continue;
            foreach (cc_runs(cc_need($ch, $a, $b)) as [$x, $y]) {
                if (microtime(true) - $t0 > $budgetSec) { $out['left']++; continue; }
                try { cc_pull_run($ch, $x, $y); $out['pulled']++; }
                catch (Throwable $e) { $out['err'][] = $ch['code'] . " $x-$y: " . $e->getMessage(); }
            }
        }
    } catch (Throwable $e) {
        $out['err'][] = $e->getMessage();
    } finally {
        flock($fh, LOCK_UN);
        fclose($fh);
    }
    return $out;
}

// เงื่อนไขสิทธิเป็น SQL ฝั่ง SQLite
function cc_rights_where(?array $r, array &$par): string
{
    if (!$r) return '';
    if (!empty($r['hipdata'])) {
        $par = array_merge($par, $r['hipdata']);
        return " AND h.pttype IN (SELECT pttype FROM pttype_ref WHERE hipdata IN (" . implode(',', array_fill(0, count($r['hipdata']), '?')) . "))";
    }
    if (!empty($r['pttype'])) {
        $par = array_merge($par, $r['pttype']);
        return " AND h.pttype IN (" . implode(',', array_fill(0, count($r['pttype']), '?')) . ")";
    }
    return '';
}

// ยอดต่อช่อง: [code => ['n' => ครั้ง, 'p' => คน, 'opd' =>, 'ipd' =>]]
function cc_totals(string $a, string $b): array
{
    $lite = cc_lite();
    $out = [];
    foreach (cc_channels() as $ch) {
        if ($ch['status'] !== 'ready') continue;
        $par = [$ch['code'], $a, $b];
        $w = cc_rights_where($ch['rights'], $par);
        $st = $lite->prepare("SELECT count(*) AS n, count(DISTINCT hn) AS p,
                                     sum(substr(id,1,1) = 'O') AS opd, sum(substr(id,1,1) = 'I') AS ipd
                              FROM hit h WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w");
        $st->execute($par);
        $r = $st->fetch();
        $out[$ch['code']] = ['n' => (int)$r['n'], 'p' => (int)$r['p'], 'opd' => (int)$r['opd'], 'ipd' => (int)$r['ipd']];
    }
    return $out;
}

// ตัวตั้ง: ทั้งหมดกี่ครั้ง/กี่คน · เข้าอย่างน้อย 1 ช่อง (ช่อง ready ตามสิทธิของแต่ละช่อง) · ไม่เข้าช่องไหน
// ช่องที่นับ Dx ของใบ OPD ที่ admit ต่อจะมี id ที่ไม่อยู่ในยอดตั้งต้น -> ตัดให้เหลือเฉพาะ id ในยอดตั้งต้น
function cc_summary(string $a, string $b): array
{
    $lite = cc_lite();
    $u = []; $par = [];
    foreach (cc_channels() as $ch) {
        if ($ch['status'] !== 'ready') continue;
        $p = [$ch['code'], $a, $b];
        $w = cc_rights_where($ch['rights'], $p);
        $u[] = "SELECT h.id, h.hn FROM hit h WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w";
        $par = array_merge($par, $p);
    }
    $all = $lite->prepare("SELECT count(*) AS n, count(DISTINCT hn) AS p, sum(substr(id,1,1) = 'O') AS opd, sum(substr(id,1,1) = 'I') AS ipd
                           FROM hit WHERE ch = '_ALL' AND d BETWEEN ? AND ?");
    $all->execute([$a, $b]);
    $t = $all->fetch();
    $m = ['n' => 0, 'p' => 0];
    if ($u) {
        $st = $lite->prepare("WITH m AS (" . implode(' UNION ', $u) . ")
                              SELECT count(DISTINCT m.id) AS n, count(DISTINCT m.hn) AS p FROM m
                              WHERE m.id IN (SELECT id FROM hit WHERE ch = '_ALL' AND d BETWEEN ? AND ?)");
        $st->execute(array_merge($par, [$a, $b]));
        $m = $st->fetch();
    }
    return ['n' => (int)$t['n'], 'p' => (int)$t['p'], 'opd' => (int)$t['opd'], 'ipd' => (int)$t['ipd'], 'mn' => (int)$m['n'], 'mp' => (int)$m['p']];
}

// รายวันของช่องเดียว
function cc_daily(array $ch, string $a, string $b): array
{
    $par = [$ch['code'], $a, $b];
    $w = cc_rights_where($ch['rights'], $par);
    $st = cc_lite()->prepare("SELECT d, count(*) AS n, count(DISTINCT hn) AS p,
                                     sum(substr(id,1,1) = 'O') AS opd, sum(substr(id,1,1) = 'I') AS ipd
                              FROM hit h WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w GROUP BY d");
    $st->execute($par);
    $have = [];
    foreach ($st as $r) $have[$r['d']] = $r;
    $out = [];
    foreach (cc_days($a, min($b, date('Y-m-d'))) as $d)
        $out[] = ['d' => $d, 'n' => (int)($have[$d]['n'] ?? 0), 'p' => (int)($have[$d]['p'] ?? 0),
                  'opd' => (int)($have[$d]['opd'] ?? 0), 'ipd' => (int)($have[$d]['ipd'] ?? 0)];
    return $out;
}

// ยังขาดกี่ช่อง-วัน (ไว้บอกบนหน้าจอว่ายอดยังไม่ครบ)
function cc_missing(string $a, string $b): int
{
    $n = 0;
    $all = cc_all(); $all['ver'] = cc_ver($all);
    $adm = cc_adm(); $adm['ver'] = cc_ver($adm);
    $days = count(cc_days($a, min($b, date('Y-m-d'))));
    foreach (array_merge([$all, $adm], array_values(cc_channels())) as $ch) {
        if ($ch['status'] !== 'ready') continue;
        $st = cc_lite()->prepare("SELECT count(*) FROM synced WHERE ch = ? AND ver = ? AND d BETWEEN ? AND ?");
        $st->execute([$ch['code'], $ch['ver'], $a, min($b, date('Y-m-d'))]);
        $n += max(0, $days - (int)$st->fetchColumn());
    }
    return $n;
}

// ยอดต่อกลุ่ม (ไม่นับซ้ำในกลุ่ม) ตัดให้อยู่ในยอดตั้งต้นเหมือน cc_summary: [g => ['n' =>, 'p' =>]]
function cc_groups(string $a, string $b): array
{
    $lite = cc_lite();
    $by = [];
    foreach (cc_channels() as $ch) {
        if ($ch['status'] !== 'ready') continue;
        $p = [$ch['code'], $a, $b];
        $w = cc_rights_where($ch['rights'], $p);
        $by[$ch['g']]['sql'][] = "SELECT h.id, h.hn FROM hit h WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w";
        $by[$ch['g']]['par'] = array_merge($by[$ch['g']]['par'] ?? [], $p);
    }
    $out = [];
    foreach ($by as $g => $x) {
        $st = $lite->prepare("WITH m AS (" . implode(' UNION ', $x['sql']) . ")
                              SELECT count(DISTINCT m.id) AS n, count(DISTINCT m.hn) AS p FROM m
                              WHERE m.id IN (SELECT id FROM hit WHERE ch = '_ALL' AND d BETWEEN ? AND ?)");
        $st->execute(array_merge($x['par'], [$a, $b]));
        $r = $st->fetch();
        $out[$g] = ['n' => (int)$r['n'], 'p' => (int)$r['p']];
    }
    return $out;
}

// ==========================================================================
//  แบ่งตามลำดับชนะ (คอนเซปต์ ClaimMap: อยู่กล่องไหนแล้วไม่ต้องอยู่กล่องอื่น)
//  ทุกครั้งในฐานส่งเบิก (_ALL) ตกช่องแรกที่เข้า ตาม CC_PRIORITY แล้วตามลำดับช่องในกลุ่ม
//  ที่ไม่เข้าช่องใดเลย = none -> ผลรวมทุกช่อง + none = ฐานส่งเบิกพอดี (check)
// ==========================================================================
function cc_partition(string $a, string $b): array
{
    $lite = cc_lite();
    $u = []; $par = []; $prio = 0;
    $chs = cc_channels();
    foreach (CC_PRIORITY as $g) {
        foreach ($chs as $ch) {
            if ($ch['g'] !== $g || $ch['status'] !== 'ready') continue;
            $p = [$prio++, $ch['code'], $a, $b];
            $w = cc_rights_where($ch['rights'], $p);
            $u[] = "SELECT CAST(? AS INTEGER) AS prio, h.ch, h.id, h.hn FROM hit h WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w";
            $par = array_merge($par, $p);
        }
    }
    $base = ['n' => 0, 'p' => 0, 'opd' => 0, 'ipd' => 0];
    $out = ['ch' => [], 'g' => [], 'none' => $base, 'total' => $base, 'check' => false];
    $t = $lite->prepare("SELECT count(*) AS n, count(DISTINCT hn) AS p, sum(substr(id,1,1) = 'O') AS opd, sum(substr(id,1,1) = 'I') AS ipd
                         FROM hit WHERE ch = '_ALL' AND d BETWEEN ? AND ?");
    $t->execute([$a, $b]);
    $tot = array_map('intval', $t->fetch());
    $out['total'] = $tot;
    if (!$u) { $out['none'] = $tot; $out['check'] = true; return $out; }

    // ฐานเป็นตารางชั่วคราวมีดัชนี - IN (subquery) ตรง ๆ ทั้งปีงบช้า
    $lite->exec("CREATE TEMP TABLE IF NOT EXISTS base_ids (id TEXT PRIMARY KEY, hn TEXT)");
    $lite->exec("DELETE FROM base_ids");
    $lite->prepare("INSERT OR IGNORE INTO base_ids SELECT id, hn FROM hit WHERE ch = '_ALL' AND d BETWEEN ? AND ?")->execute([$a, $b]);
    $st = $lite->prepare("WITH m AS (" . implode(' UNION ALL ', $u) . "),
                               w AS (SELECT m.ch, m.id, b.hn, ROW_NUMBER() OVER (PARTITION BY m.id ORDER BY m.prio) AS rn
                                     FROM m JOIN base_ids b ON b.id = m.id)
                          SELECT ch, count(*) AS n, count(DISTINCT hn) AS p,
                                 sum(substr(id,1,1) = 'O') AS opd, sum(substr(id,1,1) = 'I') AS ipd
                          FROM w WHERE rn = 1 GROUP BY ch");
    $st->execute($par);
    $won = [];
    foreach ($st as $r) $won[$r['ch']] = ['n' => (int)$r['n'], 'p' => (int)$r['p'], 'opd' => (int)$r['opd'], 'ipd' => (int)$r['ipd']];

    // คนต่อกลุ่ม / คนที่ไม่เข้า ต้องนับ HN ไม่ซ้ำจากชุดที่ชนะ ไม่ใช่บวกกันรายช่อง
    $gp = $lite->prepare("WITH m AS (" . implode(' UNION ALL ', $u) . "),
                               w AS (SELECT m.ch, m.id, b.hn, ROW_NUMBER() OVER (PARTITION BY m.id ORDER BY m.prio) AS rn
                                     FROM m JOIN base_ids b ON b.id = m.id)
                          SELECT ch, hn FROM w WHERE rn = 1");
    $gp->execute($par);
    $hnG = []; $hnIn = [];
    foreach ($gp as $r) { $hnG[$chs[$r['ch']]['g']][$r['hn']] = 1; $hnIn[$r['hn']] = 1; }

    $sum = 0;
    foreach ($chs as $code => $ch) {
        if ($ch['status'] !== 'ready') continue;
        $x = $won[$code] ?? $base;
        $out['ch'][$code] = $x;
        $g = &$out['g'][$ch['g']];
        $g = $g ?? $base;
        foreach (['n', 'opd', 'ipd'] as $k) $g[$k] += $x[$k];
        unset($g);
        $sum += $x['n'];
    }
    foreach ($out['g'] as $g => &$x) $x['p'] = count($hnG[$g] ?? []);
    unset($x);
    $in = ['n' => $sum, 'opd' => 0, 'ipd' => 0];
    foreach ($out['ch'] as $x) { $in['opd'] += $x['opd']; $in['ipd'] += $x['ipd']; }
    // คนที่ไม่เข้า = HN ในฐานที่ไม่เคยชนะช่องไหนเลย
    $np = 0;
    foreach ($lite->query("SELECT DISTINCT hn FROM base_ids") as $r) if (!isset($hnIn[$r['hn']])) $np++;
    $out['none'] = ['n' => $tot['n'] - $sum, 'p' => $np, 'opd' => $tot['opd'] - $in['opd'], 'ipd' => $tot['ipd'] - $in['ipd']];
    $out['check'] = ($sum + $out['none']['n']) === $tot['n'] && $out['none']['n'] >= 0;
    $lite->exec("DELETE FROM base_ids");
    return $out;
}

// ยอด visit ทั้งหมดแบบ Visit List ของ HOSxP: OPD (ใบไม่ admit) + admit ตามวันรับไว้ · คน = HN ไม่ซ้ำของทั้งสองชุด
function cc_visit(string $a, string $b): array
{
    $st = cc_lite()->prepare("SELECT sum(ch = '_ALL') AS opd, sum(ch = '_ADM') AS adm, count(DISTINCT hn) AS p FROM hit
                              WHERE d BETWEEN ? AND ? AND (ch = '_ADM' OR (ch = '_ALL' AND substr(id,1,1) = 'O'))");
    $st->execute([$a, $b]);
    $r = $st->fetch();
    return ['opd' => (int)$r['opd'], 'adm' => (int)$r['adm'], 'n' => (int)$r['opd'] + (int)$r['adm'], 'p' => (int)$r['p']];
}

// ==========================================================================
//  สถานะการเบิกรายใบ (ใช้ร่วม status.php / list.php) - ตารางเดียวกับ ClaimMap claim_status.php
//  $O = [vn...] ผู้ป่วยนอก, $I = [an...] ผู้ป่วยใน -> ['Ovn' => 'sent'|'resv'|'none', 'Ian' => ...]
//  ส่ง = ovst_fund_batch_item_ma (vn หรือ AN) หรือ ovst_eclaim_sent_ma · จอง = fdh_claim_status Y ไม่ยกเลิก
// ==========================================================================
function cc_status_map(array $O, array $I): array
{
    $pg = cc_pg();
    $out = [];
    $arr = fn(array $x) => '{' . implode(',', array_map(fn($v) => '"' . str_replace('"', '', $v) . '"', $x)) . '}';
    foreach (array_chunk(array_values($O), 3000) as $part) {
        $q = $pg->prepare("WITH v AS (SELECT unnest(CAST(:a AS text[])) AS vn)
            SELECT v.vn,
                   (EXISTS (SELECT 1 FROM ovst_fund_batch_item_ma i WHERE i.vn = v.vn)
                    OR EXISTS (SELECT 1 FROM ovst_eclaim_sent_ma e WHERE e.vn = v.vn)) AS sent,
                   EXISTS (SELECT 1 FROM fdh_claim_status f WHERE f.vn = v.vn AND f.fdh_reservation_status = 'Y'
                           AND COALESCE(f.fdh_cancel_status,'N') <> 'Y') AS resv
            FROM v");
        $q->execute([':a' => $arr($part)]);
        foreach ($q as $r) $out['O' . $r['vn']] = $r['sent'] ? 'sent' : ($r['resv'] ? 'resv' : 'none');
    }
    foreach (array_chunk(array_values($I), 3000) as $part) {
        $q = $pg->prepare("WITH a AS (SELECT t.an, t.vn FROM ipt t WHERE t.an = ANY(CAST(:a AS text[])))
            SELECT a.an,
                   (EXISTS (SELECT 1 FROM ovst_fund_batch_item_ma i WHERE i.vn IN (a.an, a.vn))
                    OR EXISTS (SELECT 1 FROM ovst_eclaim_sent_ma e WHERE e.an = a.an)) AS sent,
                   EXISTS (SELECT 1 FROM fdh_claim_status f WHERE f.vn = a.vn AND f.fdh_reservation_status = 'Y'
                           AND COALESCE(f.fdh_cancel_status,'N') <> 'Y') AS resv
            FROM a");
        $q->execute([':a' => $arr($part)]);
        foreach ($q as $r) $out['I' . $r['an']] = $r['sent'] ? 'sent' : ($r['resv'] ? 'resv' : 'none');
    }
    return $out;
}

// ใบไหนตกช่องไหนตามลำดับชนะ (เฉพาะใบในฐานส่งเบิก): [id => code]
function cc_winners(string $a, string $b): array
{
    $lite = cc_lite();
    $u = []; $par = []; $prio = 0;
    $chs = cc_channels();
    foreach (CC_PRIORITY as $g) {
        foreach ($chs as $ch) {
            if ($ch['g'] !== $g || $ch['status'] !== 'ready') continue;
            $p = [$prio++, $ch['code'], $a, $b];
            $w = cc_rights_where($ch['rights'], $p);
            $u[] = "SELECT CAST(? AS INTEGER) AS prio, h.ch, h.id FROM hit h WHERE h.ch = ? AND h.d BETWEEN ? AND ?$w";
            $par = array_merge($par, $p);
        }
    }
    if (!$u) return [];
    $st = $lite->prepare("WITH m AS (" . implode(' UNION ALL ', $u) . "),
                               w AS (SELECT m.ch, m.id, ROW_NUMBER() OVER (PARTITION BY m.id ORDER BY m.prio) AS rn
                                     FROM m WHERE m.id IN (SELECT id FROM hit WHERE ch = '_ALL' AND d BETWEEN ? AND ?))
                          SELECT id, ch FROM w WHERE rn = 1");
    $st->execute(array_merge($par, [$a, $b]));
    $out = [];
    foreach ($st as $r) $out[$r['id']] = $r['ch'];
    return $out;
}

// ==========================================================================
//  ความสมบูรณ์รายใบ (mazza ยืนยัน 6 ต.ค. 69)
//    1) มี Authen Code = visit_pttype.auth_code ของสิทธิลำดับ 1 (IPD ใช้ visit ที่ admit)
//       claim_code / confirm_and_locked ว่างทั้งฐาน ใช้ไม่ได้
//    2) มี Dx หลัก = ovstdiag diagtype 1 (OPD) / iptdiag diagtype 1 (IPD)
//    3) IPD มี DRG + AdjRW > 0
//  คืน ['Ovn' => [], 'Ian' => ['auth','drg']] = รายการที่ขาด (ว่าง = สมบูรณ์)
// ==========================================================================
function cc_complete_map(array $O, array $I): array
{
    $pg = cc_pg();
    $out = [];
    $arr = fn(array $x) => '{' . implode(',', array_map(fn($v) => '"' . str_replace('"', '', $v) . '"', $x)) . '}';
    foreach (array_chunk(array_values($O), 3000) as $part) {
        $q = $pg->prepare("WITH v AS (SELECT unnest(CAST(:a AS text[])) AS vn)
            SELECT v.vn,
                   EXISTS (SELECT 1 FROM visit_pttype p WHERE p.vn = v.vn AND p.pttype_number = '1'
                           AND COALESCE(TRIM(p.auth_code),'') <> '') AS auth,
                   EXISTS (SELECT 1 FROM ovstdiag x WHERE x.vn = v.vn AND x.diagtype = '1') AS pdx
            FROM v");
        $q->execute([':a' => $arr($part)]);
        foreach ($q as $r) {
            $miss = [];
            if (!$r['auth']) $miss[] = 'auth';
            if (!$r['pdx'])  $miss[] = 'pdx';
            $out['O' . $r['vn']] = $miss;
        }
    }
    foreach (array_chunk(array_values($I), 3000) as $part) {
        $q = $pg->prepare("WITH a AS (SELECT t.an, t.vn, t.drg, t.adjrw FROM ipt t WHERE t.an = ANY(CAST(:a AS text[])))
            SELECT a.an,
                   EXISTS (SELECT 1 FROM visit_pttype p WHERE p.vn = a.vn AND p.pttype_number = '1'
                           AND COALESCE(TRIM(p.auth_code),'') <> '') AS auth,
                   EXISTS (SELECT 1 FROM iptdiag x WHERE x.an = a.an AND x.diagtype = '1') AS pdx,
                   (COALESCE(TRIM(a.drg),'') <> '' AND COALESCE(a.adjrw, 0) > 0) AS drg
            FROM a");
        $q->execute([':a' => $arr($part)]);
        foreach ($q as $r) {
            $miss = [];
            if (!$r['auth']) $miss[] = 'auth';
            if (!$r['pdx'])  $miss[] = 'pdx';
            if (!$r['drg'])  $miss[] = 'drg';
            $out['I' . $r['an']] = $miss;
        }
    }
    return $out;
}
