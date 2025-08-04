<?php
// export_csv.php - ส่งออกข้อมูลเป็น CSV
require_once 'config.php';

// ตั้งค่า header สำหรับดาวน์โหลด CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="border_conflict_survey_' . date('Y-m-d_H-i-s') . '.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// เปิด output stream
$output = fopen('php://output', 'w');

// เขียน BOM สำหรับ UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// สร้าง header ของ CSV
$headers = [
    'ลำดับ',
    'วันที่ตอบ',
    'กลุ่มผู้ตอบ',
    'อายุ',
    'เพศ',
    'ที่อยู่',
    'ระยะห่างจากชายแดน',
    'ความเสียหายบ้าน',
    'ส่วนบ้านที่เสียหาย',
    'ค่าซ่อมบ้าน',
    'ความเสียหายยานพาหนะ',
    'ประเภทยานพาหนะที่เสียหาย',
    'ค่าซ่อมยานพาหนะ',
    'ความเสียหายเครื่องใช้ไฟฟ้า',
    'ประเภทเครื่องใช้ไฟฟ้าที่เสียหาย',
    'ค่าซ่อมเครื่องใช้ไฟฟ้า',
    'ความเสียหายพืชผล',
    'ประเภทพืชผลที่เสียหาย',
    'ความสูญเสียพืชผล',
    'ผลกระทบปศุสัตว์',
    'ประเภทปศุสัตว์ที่ได้รับผลกระทบ',
    'ความสูญเสียปศุสัตว์',
    'ความเสียหายสิ่งปลูกสร้างการเกษตร',
    'ประเภทสิ่งปลูกสร้างที่เสียหาย',
    'ค่าซ่อมสิ่งปลูกสร้างการเกษตร',
    'มูลค่าความเสียหายรวม',
    'มีประกันภัย',
    'ความช่วยเหลือจากประกันภัย',
    'ซ่อมแซมด้วยตนเอง',
    'รูปภาพประกอบ'
];

fputcsv($output, $headers);

try {
    // ดึงข้อมูลทั้งหมด
    $stmt = $pdo->query("
        SELECT *, 
               DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as formatted_date
        FROM survey_responses 
        ORDER BY created_at DESC
    ");
    
    $rowNumber = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // แปลง JSON เป็นข้อความ
        $houseParts = $row['house_damage_parts'] ? implode(', ', json_decode($row['house_damage_parts'], true)) : '';
        $vehicleTypes = $row['vehicle_types'] ? implode(', ', json_decode($row['vehicle_types'], true)) : '';
        $applianceTypes = $row['appliance_types'] ? implode(', ', json_decode($row['appliance_types'], true)) : '';
        $cropTypes = $row['crop_types'] ? implode(', ', json_decode($row['crop_types'], true)) : '';
        $livestockTypes = $row['livestock_types'] ? implode(', ', json_decode($row['livestock_types'], true)) : '';
        $farmStructureTypes = $row['farm_structure_types'] ? implode(', ', json_decode($row['farm_structure_types'], true)) : '';
        $damageImages = $row['damage_images'] ? count(json_decode($row['damage_images'], true)) . ' ภาพ' : 'ไม่มี';
        
        $csvRow = [
            $rowNumber++,
            $row['formatted_date'],
            $row['respondent_type'],
            $row['age'],
            $row['gender'],
            $row['address'],
            $row['border_distance'],
            $row['house_damage'],
            $houseParts,
            $row['house_repair_cost'] ?? '',
            $row['vehicle_damage'],
            $vehicleTypes,
            $row['vehicle_repair_cost'] ?? '',
            $row['appliance_damage'],
            $applianceTypes,
            $row['appliance_repair_cost'] ?? '',
            $row['crop_damage'],
            $cropTypes,
            $row['crop_loss_cost'] ?? '',
            $row['livestock_impact'] ?? '',
            $livestockTypes,
            $row['livestock_loss_cost'] ?? '',
            $row['farm_structure_damage'] ?? '',
            $farmStructureTypes,
            $row['farm_structure_cost'] ?? '',
            $row['total_damage_cost'] ?? '',
            $row['has_insurance'] ?? '',
            $row['insurance_help'] ?? '',
            $row['self_repair'] ?? '',
            $damageImages
        ];
        
        fputcsv($output, $csvRow);
    }
    
} catch(PDOException $e) {
    // ในกรณีเกิดข้อผิดพลาด
    fputcsv($output, ['เกิดข้อผิดพลาด: ' . $e->getMessage()]);
}

fclose($output);
exit;
?>