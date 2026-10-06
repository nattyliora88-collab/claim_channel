<?php
// ==========================================================================
//  28 ช่องทางเคลม ตามอินโฟกราฟิก "ช่องทางเคลม ปีงบประมาณ 2569" (99 Training)
//  เงื่อนไขตามเอกสาร สปสช. ที่ค้นไว้ 5 ต.ค. 69 -> ดู CONDITIONS.md (แหล่ง + เลขหน้า)
//
//  แต่ละช่อง:
//    status  'ready'   = นับจริง
//            'pending' = ยังไม่นับ - why: 'ask' ต้องถามงานประกัน / 'metric' สปสช. ไม่จ่ายรายครั้ง ต้องตกลงตัวเลข
//    right   ข้อความสิทธิตามประกาศ (โชว์บนการ์ด)
//    cond    เงื่อนไขตามประกาศ (โชว์ในหน้าต่าง) · how = วิธีนับใน HOSxP
//    opd     SQL คืน (vn, d)  ใช้ {A} {B} แทนวันเริ่ม/จบ - d = วันรับบริการ
//    ipd     SQL คืน (an, d)  d = วันจำหน่าย
//    rights  null = ทุกสิทธิ · CC_UC = เฉพาะบัตรทอง (hipdata UCS/WEL)
//            ['pttype' => ['89','99']] = ระบุ pttype เอง
//
//  แก้เงื่อนไขช่องไหน เครื่องดึงใหม่เอง (ver คิดจาก SQL + rights)
// ==========================================================================

// เวอร์ชันที่แสดงท้ายหน้า / ชื่อไฟล์ชุดติดตั้ง (ขึ้น HOSxP Marketplace 6 ต.ค. 69 = 1.0.0)
const CC_VERSION = '1.0.1';

const CC_GROUPS = [
    'f43'  => ['name' => 'ช่องทางกลุ่ม 43 แฟ้ม',              'en' => 'Standard 43 Files'],
    'ecl'  => ['name' => 'ช่องทางกลุ่ม E-Claim',              'en' => 'Main Electronic Claiming'],
    'dmis' => ['name' => 'ช่องทางกลุ่ม DMIS',                 'en' => 'Specific Disease Management'],
    'nat'  => ['name' => 'ช่องทางกลุ่มโปรแกรมระดับชาติ',       'en' => 'National Programs'],
    'plat' => ['name' => 'ช่องทางแพลตฟอร์มใหม่และโครงการพิเศษ', 'en' => 'Platforms & Projects'],
];

// ลำดับชนะ (mazza 5 ต.ค. 69 เลือก "โรคเฉพาะก่อน"): ครั้งที่เข้าหลายช่องทาง นับให้ช่องแรกตามลำดับกลุ่มนี้
// แล้วตามลำดับช่องในกลุ่ม -> ทุกกล่องรวมกับ "ไม่เข้าช่องทาง" = ฐานส่งเบิกพอดี (แนวเดียวกับ ClaimMap)
const CC_PRIORITY = ['dmis', 'nat', 'ecl', 'f43', 'plat'];

// ช่องที่ส่งผ่าน e-Claim / FDH มีร่องรอยในฐาน (ovst_fund_batch_item_ma, ovst_eclaim_sent_ma, fdh_claim_status)
// -> ดูสถานะการเบิกได้ · ช่องอื่นส่งนอก HOSxP (via = ระบบปลายทาง)
const CC_TRACK = ['DP_REFER', 'VASCULAR', 'DMIHT', 'HOMEWARD', 'DMISRC', 'UCEP24', 'DCNB', 'OP_ANYWHERE'];
const CC_VIA = ['f43' => '43 แฟ้ม', 'ecl' => 'e-Claim / FDH', 'dmis' => 'DMIS / Seamless', 'nat' => 'โปรแกรม NAP / NTIP / NPRP', 'plat' => 'แพลตฟอร์มภายนอก'];
const CC_VIA_CH = ['DCNB' => 'e-Claim', 'DTSH' => 'NPRP', 'KTB' => 'Krungthai Digital Health Platform',
    'MOPH_CLAIM' => 'MOPH Claim', 'OP_ANYWHERE' => 'e-Claim / FDH', 'ASTHMA_COPD' => '43 แฟ้ม + e-Claim (จ่ายตามผลลัพธ์)'];

// บัตรทอง - ผู้ประกันตนคนพิการ (D1) ยังไม่ได้แยก เพราะยังไม่รู้ว่าหาดใหญ่ตั้ง pttype ตัวไหน
const CC_UC = ['hipdata' => ['UCS', 'WEL']];
// SQL เดียวกันแบบฝังในคำสั่ง (ใช้กับช่องที่ผสมสิทธิภายในช่อง เช่น MOPH_CLAIM)
const CC_UC_SQL = "(SELECT TRIM(pttype) FROM pttype WHERE hipdata_code IN ('UCS','WEL'))";

// ==========================================================================
//  ค่าที่แต่ละโรงพยาบาลใช้รหัสไม่เหมือนกัน -> ตั้งที่หน้า settings.php (เก็บใน SQLite)
//  default = ค่าของ รพ.หาดใหญ่ (ใช้อยู่ก่อนมีหน้าตั้งค่า) · แก้แล้ว ver ของช่องเปลี่ยน -> ดึงข้อมูลใหม่เอง
//  type: labs = รหัส lab_items_code คั่นด้วย comma / wards = รหัสตึก (ว่าง = ใช้ชื่อตึกขึ้นต้น HOME WARD)
//        int = ตัวเลข / regex = คำค้นชื่อยา คั่นด้วย |
// ==========================================================================
require_once __DIR__ . '/db.php';
const CC_SETTINGS = [
    'dtsh_screen_labs' => ['ch' => 'DTSH', 'type' => 'labs', 'default' => '62,2697,6025',
        'label' => 'รหัส Lab คัดกรองสุขภาพทารกแรกเกิด', 'help' => 'รายการ Lab ที่ส่งตรวจคัดกรองทารกแรกเกิด (TSH/PKU) นับทุกครั้งที่สั่ง'],
    'dtsh_tsh_labs'    => ['ch' => 'DTSH', 'type' => 'labs', 'default' => '1549,4986',
        'label' => 'รหัส Lab TSH', 'help' => 'นับเฉพาะเมื่อสั่งให้ทารกที่อายุไม่เกินจำนวนวันด้านล่าง'],
    'dtsh_age_days'    => ['ch' => 'DTSH', 'type' => 'int', 'default' => '30',
        'label' => 'อายุทารกไม่เกิน (วัน)', 'help' => 'ใช้กับรหัส Lab TSH'],
    'hba1c_labs'       => ['ch' => 'MOPH_CLAIM', 'type' => 'labs', 'default' => '561,5723',
        'label' => 'รหัส Lab HbA1c', 'help' => 'นับ HbA1c ในผู้ป่วย Dx E11 สิทธิ UC'],
    'homeward_wards'   => ['ch' => 'HOMEWARD', 'type' => 'wards', 'default' => '',
        'label' => 'ตึก Home Ward', 'help' => 'ไม่เลือก = ใช้ตึกที่ชื่อขึ้นต้น "HOME WARD"'],
    'dcnb_drug_re'     => ['ch' => 'DCNB', 'type' => 'regex', 'default' => 'กัญชา|cannab|THC|CBD',
        'label' => 'คำค้นชื่อยากัญชา', 'help' => 'คั่นแต่ละคำด้วย | ค้นในชื่อยา (ไม่สนตัวเล็กใหญ่)'],
];
function cc_set(string $k): string
{
    $v = cc_setting_all()[$k] ?? null;
    return ($v === null || $v === '') && CC_SETTINGS[$k]['type'] !== 'wards' ? CC_SETTINGS[$k]['default'] : (string)$v;
}
// รายการรหัส -> 'a','b' สำหรับ IN (...) · ว่าง -> NULL (ไม่ตรงอะไรเลย)
function cc_in(string $k): string
{
    $codes = array_values(array_filter(array_map(fn($x) => preg_replace('/[^A-Za-z0-9_.-]/', '', trim($x)), explode(',', cc_set($k)))));
    return $codes ? "'" . implode("','", $codes) . "'" : 'NULL';
}
function cc_int(string $k): int { return max(0, (int)cc_set($k)); }
function cc_drug_re(): string
{
    $v = preg_replace("/['\\;]/u", '', cc_set('dcnb_drug_re'));
    return trim($v, '| ') !== '' ? $v : CC_SETTINGS['dcnb_drug_re']['default'];
}
function cc_homeward_where(): string
{
    return cc_set('homeward_wards') !== '' ? 'i.ward IN (' . cc_in('homeward_wards') . ')' : "w.name ILIKE 'HOME WARD%'";
}

// ---- ตัวช่วยเขียน SQL ----------------------------------------------------
// visit ไม่ admit ที่มี Fee Schedule ชนิดในชุด ตามวันรับบริการ
// (กรอง ovst ตามวันก่อนแบบ MATERIALIZED - ต่อตรงทีเดียว planner เลือกแผนผิดจนเกิน 60 วิ บทเรียนจาก claim_map)
function cc_fs_opd(array $tids): string
{
    $in = implode(',', array_map('intval', $tids));
    return "WITH o AS MATERIALIZED (SELECT vn, vstdate FROM ovst
              WHERE vstdate BETWEEN {A} AND {B} AND COALESCE(TRIM(an),'') = '' AND hn IS NOT NULL AND hn <> '')
            SELECT DISTINCT o.vn, o.vstdate AS d FROM o JOIN ovst_fee_schedule f ON f.vn = o.vn
            WHERE f.nhso_fee_schedule_type_id IN ($in)";
}
// AN ที่ใบ visit มี Fee Schedule ชนิดในชุด ตามวันจำหน่าย
function cc_fs_ipd(array $tids): string
{
    $in = implode(',', array_map('intval', $tids));
    return "WITH t AS MATERIALIZED (SELECT an, vn, dchdate FROM ipt WHERE dchdate BETWEEN {A} AND {B})
            SELECT DISTINCT t.an, t.dchdate AS d FROM t JOIN ovst_fee_schedule f ON f.vn = t.vn
            WHERE f.nhso_fee_schedule_type_id IN ($in)";
}
// Dx: OPD ทุกลำดับ Dx ตามวันรับบริการ / IPD เฉพาะ Dx หลัก ตามวันจำหน่าย
function cc_dx_opd(string $re): string
{
    return "SELECT DISTINCT x.vn, x.vstdate AS d FROM ovstdiag x
            WHERE x.vstdate BETWEEN {A} AND {B} AND x.icd10 ~ '$re'";
}
function cc_dx_ipd(string $re): string
{
    return "SELECT DISTINCT i.an, i.dchdate AS d FROM ipt i JOIN iptdiag x ON x.an = i.an
            WHERE i.dchdate BETWEEN {A} AND {B} AND x.diagtype = '1' AND x.icd10 ~ '$re'";
}
// รายการค่าบริการ (nondrugitems.nhso_adp_code) - OPD ใบไม่ admit / IPD ตาม AN
function cc_adp_opd(string $code): string
{
    return "SELECT DISTINCT o.vn, o.vstdate AS d FROM opitemrece o JOIN nondrugitems n ON n.icode = o.icode
            WHERE o.vstdate BETWEEN {A} AND {B} AND COALESCE(TRIM(o.an),'') = '' AND n.nhso_adp_code = '$code'";
}
function cc_adp_ipd(string $code): string
{
    return "SELECT DISTINCT i.an, i.dchdate AS d FROM ipt i JOIN opitemrece o ON o.an = i.an
            JOIN nondrugitems n ON n.icode = o.icode
            WHERE i.dchdate BETWEEN {A} AND {B} AND n.nhso_adp_code = '$code'";
}

// ยอดตั้งต้น: ทุกคนที่มารับบริการ (OPD ใบไม่ admit + IPD ตามวันจำหน่าย)
function cc_all(): array
{
    return [
        'code' => '_ALL', 'status' => 'ready', 'rights' => null,
        'opd' => "SELECT vn, vstdate AS d FROM ovst
                  WHERE vstdate BETWEEN {A} AND {B} AND COALESCE(TRIM(an),'') = '' AND hn IS NOT NULL AND hn <> ''",
        'ipd' => "SELECT an, dchdate AS d FROM ipt WHERE dchdate BETWEEN {A} AND {B}",
    ];
}

// Admit ต่อ = visit ที่ admit (ovst.an มีค่า) ตามวันรับบริการ (ไม่ใช่ฐานส่งเบิก)
//   visit ทั้งหมด = OPD (ใบไม่ admit) + visit ที่ admit = นับ ovst ตามวัน = ตรง Visit List ของ HOSxP พอดี
//   เดิม (6 ต.ค. 69 เช้า) ใช้ ipt.regdate -> 5 ต.ค. ได้ 269 AN แต่ Visit List มี visit ที่ admit 262
//   (13 AN ไม่มี visit วันนั้น / 6 visit ที่ AN ลงวันรับไว้เป็นอีกวัน) ยอดวงเกิน Visit List 7 ครั้ง
//   ฐานส่งเบิก = OPD + IPD ตามวันจำหน่าย เหมือนเดิม
function cc_adm(): array
{
    return [
        'code' => '_ADM', 'status' => 'ready', 'rights' => null, 'ipd' => null,
        'opd' => "SELECT vn, vstdate AS d FROM ovst
                  WHERE vstdate BETWEEN {A} AND {B} AND COALESCE(TRIM(an),'') <> '' AND hn IS NOT NULL AND hn <> ''",
    ];
}

// ---- 28 ช่อง ตามลำดับในภาพ ------------------------------------------------
function cc_channels(): array
{
    $PP = [1, 2, 3, 4, 5, 6, 8, 10, 12, 14, 16, 17, 19, 20, 21, 22, 23, 24];
    $c = [
        // ===== 43 แฟ้ม =====
        ['code' => 'DTTM', 'g' => 'f43', 'name' => 'แพทย์แผนไทย', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'มีรหัสโรค ICD-10/ThaiMed และหัตถการแผนไทย ปิดสิทธิ OPD วันละไม่เกิน 1 ครั้ง (F หน้า 290, ประกาศแผนไทย 2568)',
         'how'  => 'visit ที่มีบริการแพทย์แผนไทย (health_med_service) เฉพาะ UC',
         'opd'  => "SELECT DISTINCT h.vn, o.vstdate AS d FROM health_med_service h JOIN ovst o ON o.vn = h.vn
                    WHERE o.vstdate BETWEEN {A} AND {B}",
         'rights' => CC_UC],
        ['code' => 'PCC_PP', 'g' => 'f43', 'name' => 'บริการ PCC ตามผลงาน PP', 'status' => 'pending', 'why' => 'ask', 'right' => 'ทุกสิทธิ',
         'cond' => 'ไม่พบเอกสารตรงชื่อ ใกล้สุด = PP Basic ส่วนจ่ายตามผลงาน 5 รายการ (PP เขต 7 หน้า 6)',
         'how'  => 'ห้อง PCC (186, 280) ไม่มี visit ในฐานนี้ - รอคำตอบงานประกัน'],
        ['code' => 'HEARING', 'g' => 'f43', 'name' => 'การคัดกรองการได้ยิน', 'status' => 'pending', 'why' => 'ask', 'right' => 'ทุกสิทธิ',
         'cond' => 'ทารกแรกเกิดไทยทุกคน OAE/AABR อยู่ในเหมาจ่าย PP Basic ไม่มีอัตรารายครั้ง',
         'how'  => 'ไม่พบรายการตรวจ OAE/AABR ในฐาน - รอคำตอบงานประกัน'],
        ['code' => 'ASTHMA_COPD', 'g' => 'f43', 'name' => 'Asthma / COPD', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'สปสช. จ่ายตามผลลัพธ์ (IPD + พ่นยาฉุกเฉิน ต่อผู้ป่วย 100 ราย) ไม่ใช่รายครั้ง COPD นับอายุ 40 ปีขึ้นไป (ราชกิจจาฯ 3 พ.ย. 68)',
         'how'  => 'ชั่วคราว: นับครั้ง/คนที่มี Dx J44-J46 เฉพาะ UC (ยังไม่ใช่ตัวชี้วัดที่ สปสช. ใช้จ่ายเงิน)',
         'opd'  => cc_dx_opd('^J4[456]'), 'ipd' => cc_dx_ipd('^J4[456]'), 'rights' => CC_UC],

        // ===== E-Claim =====
        ['code' => 'DP_REFER', 'g' => 'ecl', 'name' => 'ค่าบริการส่งต่อ', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'ค่าพาหนะส่งต่อ ฉุกเฉินข้ามจังหวัด มะเร็ง รับส่งต่อผู้ป่วยใน (F หน้า 38)',
         'how'  => 'ส่งต่อ (referout) ที่ใช้รถพยาบาล เฉพาะ UC · OPD ตามวันรับบริการ / ส่งต่อจากตึกนับที่ AN ตามวันจำหน่าย',
         // referout.vn เก็บได้ทั้ง vn ผู้ป่วยนอก, vn ของใบที่ admit และ AN (ส่งจากตึก) - ก.ย. 69 = 3 / 5 / 10
         'opd'  => "SELECT DISTINCT o.vn, o.vstdate AS d FROM referout r JOIN ovst o ON o.vn = r.vn
                    WHERE o.vstdate BETWEEN {A} AND {B} AND COALESCE(TRIM(o.an),'') = '' AND r.with_ambulance = 'Y'",
         'ipd'  => "SELECT DISTINCT i.an, i.dchdate AS d FROM referout r JOIN ipt i ON (i.an = r.vn OR i.vn = r.vn)
                    WHERE i.dchdate BETWEEN {A} AND {B} AND r.with_ambulance = 'Y'",
         'rights' => CC_UC],
        ['code' => 'SPECIAL', 'g' => 'ecl', 'name' => 'บริการกรณีเฉพาะ', 'status' => 'pending', 'why' => 'ask', 'right' => 'UC',
         'cond' => 'ร่ม 18 บริการ เช่น Stroke/STEMI Fast Track, ต้อกระจก, ER คุณภาพ, อวัยวะเทียม, CAG-PCI (F หน้า 32-35)',
         'how'  => 'ต้องเลือกว่าจะนับบริการไหน - รอคำตอบงานประกัน'],
        ['code' => 'VASCULAR', 'g' => 'ecl', 'name' => 'ฟอกไต (Vascular Access)', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'ผู้ป่วย HD รายใหม่ ทำ TCC/AVF/AVG/TDC เกิน 3 ครั้งต้อง PAK (F หน้า 169-172)',
         'how'  => 'AN ที่มีหัตถการ ICD-9 3927/3929/3942/3943/3949/3953 ตามวันจำหน่าย เฉพาะ UC',
         'ipd'  => "SELECT DISTINCT i.an, i.dchdate AS d FROM ipt i JOIN iptoprt o ON o.an = i.an
                    WHERE i.dchdate BETWEEN {A} AND {B} AND o.icd9 IN ('3927','3929','3942','3943','3949','3953')",
         'rights' => CC_UC],
        ['code' => 'DMIHT', 'g' => 'ecl', 'name' => 'โรคเบาหวาน / ความดัน', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'ส่วนที่ส่ง e-Claim: T1DM (E10), GDM (O24.4/O24.9), PDM (O24.0/O24.1/O24.3) (ประกาศเบาหวานความดัน 2568, F หน้า 72-80)',
         'how'  => 'Dx E10 หรือ O24.0/1/3/4/9 เฉพาะ UC',
         'opd'  => cc_dx_opd('^(E10|O24[01349])'), 'ipd' => cc_dx_ipd('^(E10|O24[01349])'), 'rights' => CC_UC],
        ['code' => 'ORS', 'g' => 'ecl', 'name' => 'ค่าใช้จ่าย IP ที่สูงเกินเกณฑ์', 'status' => 'pending', 'why' => 'metric', 'right' => 'UC',
         'cond' => 'ขาดทุนต่อ AN เกิน FOLT 1 ล้าน หรือ VOLT 20 เท่าต้นทุนต่อ AdjRW (F หน้า 12, 29)',
         'how'  => 'ต้องมี AdjRW + ต้นทุนต่อ AN - ต้องตกลงตัวเลขที่จะแสดง'],
        ['code' => 'HOMEWARD', 'g' => 'ecl', 'name' => 'HOMEWARD', 'status' => 'ready', 'right' => 'UC',
         'cond' => '11-12 กลุ่มโรค บ้านห่างไม่เกิน 20 กม. บันทึกสัญญาณชีพทุกวัน (F หน้า 28, ข่าว e-Claim 13 ส.ค. 69)',
         'how'  => (cc_set('homeward_wards') !== '' ? 'AN ที่จำหน่ายจากตึกรหัส ' . cc_set('homeward_wards') : 'AN ที่จำหน่ายจากตึกชื่อขึ้นต้น HOME WARD') . ' เฉพาะ UC · เลือกตึกได้ที่หน้าตั้งค่า',
         'ipd'  => "SELECT DISTINCT i.an, i.dchdate AS d FROM ipt i JOIN ward w ON w.ward = i.ward
                    WHERE i.dchdate BETWEEN {A} AND {B} AND " . cc_homeward_where(),
         'rights' => CC_UC],
        ['code' => 'DMISRC', 'g' => 'ecl', 'name' => 'นิ่วในระบบทางเดินปัสสาวะ', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'สลายนิ่ว ESWL รพ.รัฐ 6,500 บาท/ครั้ง ไม่เกินข้างละ 4 ครั้ง/ปี (F หน้า 12, 17)',
         'how'  => 'มีรายการค่าสลายนิ่ว ESWL (รหัส 9851) เฉพาะ UC',
         'opd'  => cc_adp_opd('9851'), 'ipd' => cc_adp_ipd('9851'), 'rights' => CC_UC],
        ['code' => 'UCEP24', 'g' => 'ecl', 'name' => 'UCEP 24 ชั่วโมงแรก', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'ฉุกเฉินวิกฤต (สีแดง) รพ.รัฐที่ผ่าน ER คุณภาพ · HOSxP ใส่รายการ UCEP24 เองเมื่อคนไทย + UCS/WEL + ระดับ 1 (F หน้า 5, 10, 13)',
         'how'  => 'มีรายการรหัสโครงการ UCEP24 เฉพาะ UC',
         'opd'  => cc_adp_opd('UCEP24'), 'ipd' => cc_adp_ipd('UCEP24'), 'rights' => CC_UC],

        // ===== DMIS =====
        ['code' => 'DCKD', 'g' => 'dmis', 'name' => 'โรคไต / Palliative care', 'status' => 'pending', 'why' => 'ask', 'right' => 'UC',
         'cond' => 'HD/ปลูกถ่ายไต/PC ไต ผู้ป่วยใหม่ต้องได้ PAK ฟอกไม่เกิน 3 ครั้ง/สัปดาห์ อัตราปี 69 รอประกาศ (F หน้า 156-179)',
         'how'  => 'ต้องนับจากทะเบียนผู้ป่วยไต (Dx N18 กว้างเกิน) - รอคำตอบงานประกัน'],
        ['code' => 'DCNB', 'g' => 'dmis', 'name' => 'น้ำมันกัญชา', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'OPD ต้องมีรหัสโรค ยารหัส H002201-H002602 (F หน้า 294-302)',
         'how'  => 'visit ที่จ่ายยาชื่อมีคำว่า ' . str_replace('|', ' / ', cc_drug_re()) . ' เฉพาะ UC · แก้คำค้นได้ที่หน้าตั้งค่า',
         'opd'  => "SELECT DISTINCT o.vn, o.vstdate AS d FROM opitemrece o JOIN drugitems d ON d.icode = o.icode
                    WHERE o.vstdate BETWEEN {A} AND {B} AND d.name ~* '" . cc_drug_re() . "'",
         'rights' => CC_UC],
        ['code' => 'DHEM', 'g' => 'dmis', 'name' => 'ฮีโมฟีเลีย', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'ฮีโมฟีเลีย + von Willebrand type 3 ลงทะเบียน DMIS (F หน้า 215-216)',
         'how'  => 'Dx D66-D68 เฉพาะ UC',
         'opd'  => cc_dx_opd('^D6[678]'), 'ipd' => cc_dx_ipd('^D6[678]'), 'rights' => CC_UC],
        ['code' => 'DMXD', 'g' => 'dmis', 'name' => 'ทันตกรรม / แก้ไขการพูด', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'ปากแหว่งเพดานโหว่ ลงทะเบียน ยสส.1 รพ. ต้องสมัครเข้าร่วม (F หน้า 198-211)',
         'how'  => 'Dx Q35-Q37 เฉพาะ UC',
         'opd'  => cc_dx_opd('^Q3[567]'), 'ipd' => cc_dx_ipd('^Q3[567]'), 'rights' => CC_UC],
        ['code' => 'DTSH', 'g' => 'dmis', 'name' => 'คัดกรองไทรอยด์', 'status' => 'ready', 'right' => 'ทุกสิทธิ',
         'cond' => 'ตรวจ TSH ทารกแรกเกิดไทยทุกสิทธิ + ติดตามยืนยัน บันทึก NPRP (F หน้า 116)',
         'how'  => 'Lab คัดกรองทารกแรกเกิด (' . cc_set('dtsh_screen_labs') . ') หรือ TSH (' . cc_set('dtsh_tsh_labs') . ') ที่อายุไม่เกิน ' . cc_int('dtsh_age_days') . ' วัน · ทารกในตึกนับตามวันจำหน่าย · แก้รหัสได้ที่หน้าตั้งค่า',
         'opd'  => "SELECT DISTINCT h.vn, o.vstdate AS d FROM lab_head h
                    JOIN lab_order l ON l.lab_order_number = h.lab_order_number
                    JOIN ovst o ON o.vn = h.vn JOIN patient pt ON pt.hn = o.hn
                    WHERE o.vstdate BETWEEN {A} AND {B}
                      AND (l.lab_items_code IN (" . cc_in('dtsh_screen_labs') . ")
                           OR (l.lab_items_code IN (" . cc_in('dtsh_tsh_labs') . ") AND o.vstdate - pt.birthday <= " . cc_int('dtsh_age_days') . "))",
         // ทารกในตึก: lab_head.vn เก็บเป็น AN (ก.ย. 69 = 292 จาก 294 ราย)
         'ipd'  => "SELECT DISTINCT i.an, i.dchdate AS d FROM ipt i JOIN lab_head h ON h.vn = i.an
                    JOIN lab_order l ON l.lab_order_number = h.lab_order_number JOIN patient pt ON pt.hn = i.hn
                    WHERE i.dchdate BETWEEN {A} AND {B}
                      AND (l.lab_items_code IN (" . cc_in('dtsh_screen_labs') . ")
                           OR (l.lab_items_code IN (" . cc_in('dtsh_tsh_labs') . ") AND h.order_date - pt.birthday <= " . cc_int('dtsh_age_days') . "))"],
        ['code' => 'DMIS_DPRA', 'g' => 'dmis', 'name' => 'ใบสั่งยา', 'status' => 'pending', 'why' => 'ask', 'right' => 'ยังไม่ทราบ',
         'cond' => 'ไม่พบความหมายในเอกสาร สปสช. (สันนิษฐาน = จ่ายยาตามใบสั่งที่ร้านยา F หน้า 327)',
         'how'  => 'รอคำตอบงานประกัน'],
        ['code' => 'DMIS_HSCT', 'g' => 'dmis', 'name' => 'ปลูกถ่ายเซลล์ต้นกำเนิด', 'status' => 'pending', 'why' => 'ask', 'right' => 'UC',
         'cond' => 'ต้องอนุมัติคณะกรรมการ HSCT + รพ. ขึ้นทะเบียน R0204 (F หน้า 218-220)',
         'how'  => 'ไม่พบในฐาน - รอคำตอบงานประกันว่า รพ. ขึ้นทะเบียนไหม'],

        // ===== โปรแกรมระดับชาติ =====
        ['code' => 'NAP', 'g' => 'nat', 'name' => 'ผู้ติดเชื้อ HIV', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'รักษา: UC/สิทธิว่าง + ลงทะเบียน NAP · ป้องกัน (VCT/PrEP/PEP): ไทยทุกสิทธิ (F)',
         'how'  => 'ฝั่งรักษา: Dx B20-B24 หรือ Z21 + มีเลขทะเบียน NAP (clinicmember.nap_number) เฉพาะ UC · ฝั่งป้องกัน (Anti-HIV) หาดใหญ่ยังไม่ผูกรหัส NAP 101/102 กับรายการแล็บ จึงยังไม่นับ',
         // ทะเบียน NAP = clinicmember.nap_number (view bms_napplus ที่ส่งแล็บเข้า NAP Plus ก็ใช้ตัวนี้) ก.ย. 69 มี 2,986 แถว
         // ก.ย. 69: Dx HIV + UC 969 ครั้ง -> ผูกทะเบียนแล้วเหลือ 567
         'opd'  => "SELECT DISTINCT x.vn, x.vstdate AS d FROM ovstdiag x JOIN ovst o ON o.vn = x.vn
                    WHERE x.vstdate BETWEEN {A} AND {B} AND x.icd10 ~ '^(B2[0-4]|Z21)'
                      AND o.hn IN (SELECT hn FROM clinicmember WHERE nap_number IS NOT NULL AND TRIM(CAST(nap_number AS text)) <> '')",
         'ipd'  => "SELECT DISTINCT i.an, i.dchdate AS d FROM ipt i JOIN iptdiag x ON x.an = i.an
                    WHERE i.dchdate BETWEEN {A} AND {B} AND x.diagtype = '1' AND x.icd10 ~ '^(B2[0-4]|Z21)'
                      AND i.hn IN (SELECT hn FROM clinicmember WHERE nap_number IS NOT NULL AND TRIM(CAST(nap_number AS text)) <> '')",
         'rights' => CC_UC],
        ['code' => 'NTIP', 'g' => 'nat', 'name' => 'กองทุนวัณโรค', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'รักษา: UC + ลงทะเบียน NTIP · คัดกรองกลุ่มเสี่ยง: ทุกสิทธิ (F หน้า 37)',
         'how'  => 'Dx A15-A19 เฉพาะ UC (ทะเบียน tb_register ในฐานว่าง)',
         'opd'  => cc_dx_opd('^A1[5-9]'), 'ipd' => cc_dx_ipd('^A1[5-9]'), 'rights' => CC_UC],
        ['code' => 'NPRP', 'g' => 'nat', 'name' => 'คัดกรองดาวน์ซินโดรม / ธาลัสซีเมีย', 'status' => 'ready', 'right' => 'ทุกสิทธิ',
         'cond' => 'หญิงตั้งครรภ์ไทยทุกสิทธิ + สามี/คู่: คัดกรองสามี ตรวจยืนยัน NIPT Quadruple test (F)',
         'how'  => 'Fee Schedule ชนิด 3, 4, 10',
         'opd'  => cc_fs_opd([3, 4, 10]), 'ipd' => cc_fs_ipd([3, 4, 10])],

        // ===== แพลตฟอร์มใหม่และโครงการพิเศษ =====
        ['code' => 'KTB', 'g' => 'plat', 'name' => 'PP Fee Schedule / HIV Self Test', 'status' => 'ready', 'right' => 'ทุกสิทธิ',
         'cond' => 'บริการ PP 28 รายการ + HIV Self Test 100 บาท ส่ง Krungthai Digital Health Platform (ประกาศ PPFS 2568)',
         'how'  => 'Fee Schedule ชนิด PP 18 ชนิดที่ รพ. ใช้',
         'opd'  => cc_fs_opd($PP), 'ipd' => cc_fs_ipd($PP)],
        ['code' => 'MOPH_CLAIM', 'g' => 'plat', 'name' => 'DM/HT และวัคซีน EPI', 'status' => 'ready', 'right' => 'DM/HT: UC · วัคซีน: ทุกสิทธิ',
         'cond' => 'HbA1c ผู้ป่วยเบาหวานชนิดที่ 2 ปีละ 2 ครั้ง · Cr+K ความดันรายใหม่ปีละ 1 ครั้ง · วัคซีน EPI/dT/ไข้หวัดใหญ่/aP 20 บาท/โดส (ประกาศเบาหวานความดัน + PPFS)',
         'how'  => 'HbA1c (' . cc_set('hba1c_labs') . ') ในใบที่มี Dx E11 เฉพาะ UC + visit ที่ฉีดวัคซีนทุกสิทธิ (Cr+K ยังไม่นับ) · แก้รหัสได้ที่หน้าตั้งค่า',
         'opd'  => "SELECT DISTINCT o.vn, o.vstdate AS d FROM lab_head h
                    JOIN lab_order l ON l.lab_order_number = h.lab_order_number JOIN ovst o ON o.vn = h.vn
                    WHERE o.vstdate BETWEEN {A} AND {B} AND l.lab_items_code IN (" . cc_in('hba1c_labs') . ")
                      AND TRIM(o.pttype) IN " . CC_UC_SQL . "
                      AND EXISTS (SELECT 1 FROM ovstdiag x WHERE x.vn = o.vn AND x.icd10 ~ '^E11')
                    UNION
                    SELECT DISTINCT v.vn, o.vstdate AS d FROM ovst_vaccine v JOIN ovst o ON o.vn = v.vn
                    WHERE o.vstdate BETWEEN {A} AND {B}"],
        ['code' => 'AI_CHEST', 'g' => 'plat', 'name' => 'AI อ่านภาพรังสีทรวงอก', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'บอร์ดอนุมัติเป็นสิทธิประโยชน์ UC 7 ก.ค. 68 ปี 69 = 445 รพ. ยังไม่พบอัตรา/ช่องทาง',
         'how'  => 'ภาพ X-ray ที่อ่านด้วย AI (xray_report.ai_report_type_id = 1) เฉพาะ UC',
         'opd'  => "SELECT DISTINCT x.vn, o.vstdate AS d FROM xray_report x JOIN ovst o ON o.vn = x.vn
                    WHERE o.vstdate BETWEEN {A} AND {B} AND x.ai_report_type_id = 1",
         'rights' => CC_UC],
        ['code' => 'DTTM_ONTOP', 'g' => 'plat', 'name' => 'ยาสมุนไพรทดแทนยาแผนปัจจุบัน', 'status' => 'pending', 'why' => 'metric', 'right' => 'UC',
         'cond' => 'เงินรางวัลเมื่อใช้สมุนไพรแทน 100% ใน 5 คู่ยา (F หน้า 289)',
         'how'  => 'วัดสัดส่วนการใช้ยา ไม่ใช่นับคน - ต้องตกลงตัวเลขที่จะแสดง'],
        ['code' => 'OP_ANYWHERE', 'g' => 'plat', 'name' => 'OP Anywhere / บริการในจังหวัด', 'status' => 'ready', 'right' => 'UC',
         'cond' => 'OP เหตุสมควร (ในจังหวัดตามจ่ายกันเอง), OPAE ข้ามจังหวัด, Refer ข้ามจังหวัด (F หน้า 65-70)',
         'how'  => 'Fee Schedule ชนิด 101, 102, 103, 107, 108 (ชุดเดียวกับ fdh_claim) เฉพาะ UC',
         'opd'  => cc_fs_opd([101, 102, 103, 107, 108]), 'ipd' => cc_fs_ipd([101, 102, 103, 107, 108]),
         'rights' => CC_UC],
        ['code' => 'CPP', 'g' => 'plat', 'name' => 'ชดเชยตามจำนวนแพทย์ EP', 'status' => 'pending', 'why' => 'metric', 'right' => '-',
         'cond' => 'CPP = ระบบข้อมูลหน่วยบริการ ไม่ใช่ช่องเคลม · แพทย์ EP ได้ 120,000 บาท/คน/ปี ของ UCEP (F)',
         'how'  => 'ไม่ใช่การนับคนไข้ - ต้องตกลงตัวเลขที่จะแสดง'],
    ];
    $out = [];
    foreach ($c as $x) {
        $x += ['opd' => null, 'ipd' => null, 'rights' => null, 'why' => null, 'how' => ''];
        if ($x['status'] === 'ready' && !$x['opd'] && !$x['ipd']) $x['status'] = 'pending';
        $x['track'] = in_array($x['code'], CC_TRACK, true);
        $x['via'] = CC_VIA_CH[$x['code']] ?? CC_VIA[$x['g']];
        $x['ver'] = cc_ver($x);
        $out[$x['code']] = $x;
    }
    return $out;
}

// เปลี่ยน v2 -> v3 ถ้าเปลี่ยนสิ่งที่เก็บใน cache (บังคับดึงใหม่ทุกช่อง)
function cc_ver(array $x): string
{
    return substr(md5('v2' . json_encode([$x['opd'], $x['ipd'], $x['rights']])), 0, 12);
}

// ข้อความสิทธิที่ใช้กรองจริง
function cc_rights_label(?array $r): string
{
    if (!$r) return 'ทุกสิทธิ';
    if (!empty($r['hipdata'])) return 'เฉพาะสิทธิ ' . implode(', ', $r['hipdata']);
    if (!empty($r['pttype']))  return 'เฉพาะ pttype ' . implode(', ', $r['pttype']);
    return 'ทุกสิทธิ';
}

// ==========================================================================
//  แยก "ไม่เข้าช่องทาง" ตามกองทุน - นิยามชุดเดียวกับ ClaimMap funds.php (mazza 28 ก.ย. - 1 ต.ค. 69)
//  ตรวจตามลำดับ ตัวแรกที่เข้า = กองนั้น · สิทธิ = ตัวที่ใช้จริงในครั้งนั้น (OPD visit_pttype ลำดับ 1 / IPD ipt.pttype)
// ==========================================================================
const CC_FUNDS = [
    'hd'     => ['name' => 'HD ผู้ป่วยฟอกไต (ทุกสิทธิ)',          'note' => 'สิทธิที่ชื่อมี (HD) แยกจากทุกกองเหมือน ClaimMap'],
    'csop'   => ['name' => 'ข้าราชการ (CSOP / CIPN)',             'note' => 'UPP 10 กรมบัญชีกลาง, 21 เมืองพัทยา, 31 กกต.'],
    'ssop'   => ['name' => 'ประกันสังคม (SSOP / AIPN)',           'note' => 'UPP 80 · ผู้ป่วยในรวม 95 ฉุกเฉิน 72 ชม.'],
    'eclaim' => ['name' => 'E-Claim (อปท. / ต่างด้าว / STP / รถไฟ)', 'note' => 'hipdata LGO, FWF, STP, SRT'],
    'agency' => ['name' => 'ต้นสังกัด / รัฐวิสาหกิจ',              'note' => 'UPP 40'],
    'uc'     => ['name' => 'บัตรทอง เหมาจ่ายรายหัว',               'note' => 'UCS / WEL ที่ไม่เข้าเงื่อนไข 28 ช่อง = บริการที่อยู่ในเงินเหมาจ่ายรายหัว'],
    'other'  => ['name' => 'สิทธิอื่น / ชำระเอง',                  'note' => 'สิทธิที่ไม่เข้ากองใดข้างบน'],
];

function cc_fund_of(?array $pt, string $side): string
{
    if (!$pt) return 'other';
    $upp = (string)($pt['upp'] ?? ''); $hip = (string)($pt['hipdata'] ?? '');
    if (preg_match('/\(\s*HD\s*\)/iu', (string)($pt['name'] ?? ''))) return 'hd';
    if (in_array($upp, ['10', '21', '31'], true)) return 'csop';
    if ($upp === '80' || ($side === 'I' && $upp === '95')) return 'ssop';
    if (in_array($hip, ['LGO', 'FWF', 'STP', 'SRT'], true)) return 'eclaim';
    if ($upp === '40') return 'agency';
    if (in_array($hip, ['UCS', 'WEL'], true)) return 'uc';
    return 'other';
}
