<?php
// ==========================================================================
//  เข้าสู่ระบบ (มี HN ผู้ป่วย ต้องเข้าเฉพาะเจ้าหน้าที่ - PDPA) - แบบเดียวกับ telehealth_dashboard
//  1) SSO จาก HOSxP / HOSxP Marketplace: เปิดลิงก์พร้อม ?bms-session-id=<GUID>
//     -> ถาม HOSxP Cloud: GET https://hosxp.net/phapi/PasteJSON?Action=GET&code=<sid>
//        MessageCode 200 = ok (result.user_info) / 500 = session หมดอายุ · ต้องส่ง User-Agent ไม่งั้น 403
//     -> กันเปิดจาก HOSxP ที่ต่อฐานอื่น (bms_database_name ต้องตรงฐานที่แอปต่อ)
//  เข้าได้ทางเดียวด้วย session id (mazza 6 ต.ค. 69) - ไม่มี login ด้วยรหัสผ่าน
// ==========================================================================
require_once __DIR__ . '/db.php';

const CC_IDLE_TIMEOUT = 1800;   // ไม่ใช้งาน 30 นาที ออกจากระบบอัตโนมัติ

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('CLAIMCHANNELSESS');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

function cc_cfg(): array { static $c = null; return $c ??= (is_file(__DIR__ . '/db_settings.php') ? require __DIR__ . '/db_settings.php' : []); }
function auth_user(): ?array { return $_SESSION['cc_user'] ?? null; }
function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function bms_session_id_from_request(): string
{
    foreach ($_GET as $k => $v) {
        if (is_string($v) && preg_match('/^bms[-_]?session[-_]?id$/i', (string)$k)) {
            $v = trim($v);
            return preg_match('/^[A-Za-z0-9-]{8,80}$/', $v) ? $v : '';
        }
    }
    return '';
}

// คืน array ผู้ใช้ถ้าสำเร็จ หรือ string ข้อความผิดพลาด
function auth_login_bms_session(string $sid)
{
    $url = 'https://hosxp.net/phapi/PasteJSON?Action=GET&code=' . rawurlencode($sid);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 8, CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (FundClaimChannel)',
        CURLOPT_SSL_VERIFYPEER => false,   // XAMPP ไม่มี CA bundle (เหมือน ssop_export)
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($resp === false || $resp === '') {
        error_log('[claim_channel] SSO: cannot reach hosxp.net ' . $err);
        return 'ติดต่อ HOSxP (hosxp.net) ไม่ได้ ให้ผู้ดูแลระบบตรวจว่าเซิร์ฟเวอร์ต่อ hosxp.net ได้ แล้วเปิดจาก HOSxP ใหม่';
    }
    $j = json_decode($resp, true);
    $ui = $j['result']['user_info'] ?? null;
    if ((int)($j['MessageCode'] ?? 0) !== 200 || !is_array($ui)) {
        return 'session จาก HOSxP หมดอายุหรือไม่ถูกต้อง กรุณาเปิดจาก HOSxP ใหม่';
    }
    $sessDb = strtolower(trim((string)($ui['bms_database_name'] ?? '')));
    $appDb  = strtolower(trim((string)(cc_cfg()['dbname'] ?? '')));
    if ($sessDb !== '' && $appDb !== '' && $sessDb !== $appDb) {
        return "HOSxP ที่เปิดมาใช้ฐาน $sessDb แต่ระบบนี้ต่อฐาน $appDb จึงเข้าอัตโนมัติไม่ได้";
    }
    $name = trim((string)($ui['name'] ?? ''));
    $code = trim((string)($ui['doctor_code'] ?? ''));
    if ($name === '' && $code === '') return 'ข้อมูลผู้ใช้จาก HOSxP ไม่ครบ กรุณาเปิดจาก HOSxP ใหม่';
    return ['username' => $code !== '' ? $code : $name, 'fullname' => $name !== '' ? $name : $code, 'src' => 'hosxp_sso',
            'hospital' => trim((string)($ui['location'] ?? '')), 'hcode' => trim((string)($ui['hospital_code'] ?? ''))];
}

function auth_establish(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['cc_user'] = $user;
    $_SESSION['cc_last'] = time();
}

function auth_logout(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
}

// $json = true สำหรับ api.php / list.php ฯลฯ (ตอบ 401 แทน redirect)
function auth_require(bool $json = false): void
{
    // HOSxP / Marketplace เปิดหน้าใดก็ตามพร้อม bms-session-id -> ไปทำ SSO ที่ login.php
    if (!$json && ($sid = bms_session_id_from_request()) !== '') {
        header('Location: login.php?bms-session-id=' . rawurlencode($sid));
        exit;
    }
    $now = time();
    $expired = isset($_SESSION['cc_last']) && ($now - (int)$_SESSION['cc_last']) > CC_IDLE_TIMEOUT;
    if (!auth_user() || $expired) {
        if ($expired) auth_logout();
        if ($json) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'หมดเวลาเข้าสู่ระบบ กรุณาเข้าสู่ระบบใหม่', 'login' => true], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: login.php' . ($expired ? '?timeout=1' : ''));
        exit;
    }
    $_SESSION['cc_last'] = $now;
}
