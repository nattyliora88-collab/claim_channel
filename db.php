<?php
// ==========================================================================
//  ช่องทางเคลม 2569 - ต่อฐาน HOSxP (อ่านอย่างเดียว) + cache SQLite ในเครื่อง
//  ค่าเชื่อมต่ออยู่ที่ db_settings.php (สำเนาจาก claim_map)
// ==========================================================================

// php.ini ของเครื่องนี้ตั้ง Europe/Berlin (มีเวลาออมแสง วันยาว 23/25 ชม. ทำวันซ้ำ/ข้าม)
date_default_timezone_set('Asia/Bangkok');

function cc_pg(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;
    if (!is_file(__DIR__ . '/db_settings.php'))
        throw new RuntimeException('ยังไม่ได้ตั้งค่าฐานข้อมูล HOSxP: คัดลอก db_settings.example.php เป็น db_settings.php แล้วแก้ค่าให้ตรงกับโรงพยาบาล');
    $c = require __DIR__ . '/db_settings.php';
    $pdo = new PDO(
        "pgsql:host={$c['host']};port={$c['port']};dbname={$c['dbname']}",
        $c['user'], $c['password'],
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 10,
        ]
    );
    $pdo->exec("SET client_encoding TO 'UTF8'");
    // ฐาน production - ห้ามค้างนานไปกวนงานคนอื่น
    $pdo->exec("SET statement_timeout = 30000");
    $pdo->exec("SET lock_timeout = 3000");
    $pdo->exec("SET application_name = 'claim_channel (BMS)'");
    return $pdo;
}

function cc_lite(): PDO
{
    static $db = null;
    if ($db instanceof PDO) return $db;
    $db = new PDO('sqlite:' . __DIR__ . '/cache/claim_channel.sqlite', null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec("PRAGMA journal_mode = WAL");
    $db->exec("PRAGMA busy_timeout = 5000");
    // hit    : รายการที่เข้าเงื่อนไขของแต่ละช่อง ราย วัน x visit/AN (id = O:vn หรือ I:an)
    // synced : ช่องไหน วันไหน ดึงมาแล้วด้วยเงื่อนไขชุดไหน (ver) เมื่อไร - แก้เงื่อนไขแล้ว ver เปลี่ยน ดึงใหม่เอง
    // pttype_ref : สำเนา pttype ไว้กรองสิทธิในเครื่อง
    $db->exec("CREATE TABLE IF NOT EXISTS hit (ch TEXT, d TEXT, id TEXT, pttype TEXT, hn TEXT, PRIMARY KEY (ch, d, id))");
    // cache รุ่นแรกไม่มี hn - เพิ่มคอลัมน์ (ver รุ่นใหม่จะบังคับดึงใหม่อยู่แล้ว)
    $cols = $db->query("PRAGMA table_info(hit)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('hn', $cols, true)) $db->exec("ALTER TABLE hit ADD COLUMN hn TEXT");
    $db->exec("CREATE TABLE IF NOT EXISTS synced (ch TEXT, d TEXT, ver TEXT, at INTEGER, PRIMARY KEY (ch, d))");
    $db->exec("CREATE TABLE IF NOT EXISTS pttype_ref (pttype TEXT PRIMARY KEY, name TEXT, hipdata TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS meta (k TEXT PRIMARY KEY, v TEXT)");
    // รหัส UPP ของสิทธิ (แยกกองทุนแบบ ClaimMap) - cache รุ่นก่อนไม่มี -> เพิ่มคอลัมน์แล้วบังคับดึงสิทธิใหม่
    $pc = $db->query("PRAGMA table_info(pttype_ref)")->fetchAll(PDO::FETCH_COLUMN, 1);
    if (!in_array('upp', $pc, true)) { $db->exec("ALTER TABLE pttype_ref ADD COLUMN upp TEXT"); $db->exec("DELETE FROM meta WHERE k = 'pttype_at'"); }
    return $db;
}

// ==========================================================================
//  ค่าตั้งของโรงพยาบาล (หน้า settings.php) - เก็บใน SQLite ตาราง setting
//  ค่าไหนยังไม่ตั้ง = ใช้ค่าเริ่มต้นใน CC_SETTINGS (channels.php) ซึ่งเป็นของหาดใหญ่
// ==========================================================================
function cc_setting_all(): array
{
    static $c = null;
    if ($c !== null) return $c;
    $db = cc_lite();
    $db->exec("CREATE TABLE IF NOT EXISTS setting (k TEXT PRIMARY KEY, v TEXT, by_user TEXT, at TEXT)");
    $c = [];
    foreach ($db->query("SELECT k, v FROM setting") as $r) $c[$r['k']] = $r['v'];
    return $c;
}
function cc_setting_save(string $k, ?string $v, string $by): void
{
    $db = cc_lite();
    $db->exec("CREATE TABLE IF NOT EXISTS setting (k TEXT PRIMARY KEY, v TEXT, by_user TEXT, at TEXT)");
    if ($v === null) { $db->prepare("DELETE FROM setting WHERE k = ?")->execute([$k]); return; }
    $db->prepare("INSERT OR REPLACE INTO setting (k, v, by_user, at) VALUES (?, ?, ?, ?)")->execute([$k, $v, $by, date('Y-m-d H:i:s')]);
}
