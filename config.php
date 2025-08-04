<?php
// config.php - การตั้งค่าฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "border_survey";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// สร้างตาราง (ต้องรันครั้งเดียวตอนติดตั้ง)
function createTables($pdo) {
    $sql = "CREATE TABLE IF NOT EXISTS survey_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        respondent_type ENUM('ผู้บริหาร','ครู','บุคลากรทางการศึกษา','นักเรียน','ผู้ปกครอง') NOT NULL,
        age INT,
        gender ENUM('ชาย','หญิง','อื่นๆ','ไม่ระบุ'),
        address TEXT,
        border_distance ENUM('น้อยกว่า 5 กม.','5-20 กม.','21-50 กม.','มากกว่า 50 กม.'),
        
        -- บ้านและอาคาร
        house_damage ENUM('เสียหายหนัก','เสียหายปานกลาง','เสียหายเล็กน้อย','ไม่เสียหาย'),
        house_damage_parts JSON,
        house_repair_cost ENUM('น้อยกว่า 10,000 บาท','10,001-50,000 บาท','50,001-100,000 บาท','100,001-300,000 บาท','300,001-500,000 บาท','มากกว่า 500,000 บาท'),
        
        -- ยานพาหนะ
        vehicle_damage ENUM('เสียหายหนัก','เสียหายปานกลาง','เสียหายเล็กน้อย','ไม่เสียหาย','ไม่มียานพาหนะ'),
        vehicle_types JSON,
        vehicle_repair_cost ENUM('น้อยกว่า 5,000 บาท','5,001-20,000 บาท','20,001-50,000 บาท','50,001-100,000 บาท','มากกว่า 100,000 บาท','ซ่อมไม่ได้/ต้องซื้อใหม่'),
        
        -- เครื่องใช้ไฟฟ้า
        appliance_damage ENUM('เสียหายหนัก','เสียหายบ้าง','ไม่เสียหาย'),
        appliance_types JSON,
        appliance_repair_cost ENUM('น้อยกว่า 5,000 บาท','5,001-20,000 บาท','20,001-50,000 บาท','50,001-100,000 บาท','มากกว่า 100,000 บาท'),
        
        -- การเกษตร
        crop_damage ENUM('เสียหายหนัก','เสียหายปานกลาง','เสียหายเล็กน้อย','ไม่เสียหาย','ไม่มี'),
        crop_types JSON,
        crop_loss_cost ENUM('น้อยกว่า 10,000 บาท','10,001-50,000 บาท','50,001-100,000 บาท','100,001-300,000 บาท','มากกว่า 300,000 บาท'),
        livestock_impact ENUM('ตาย/หายไป','บาดเจ็บ','เครียด/ไม่กินอาหาร','ไม่ได้รับผลกระทบ','ไม่มี'),
        livestock_types JSON,
        livestock_loss_cost ENUM('น้อยกว่า 5,000 บาท','5,001-20,000 บาท','20,001-50,000 บาท','50,001-100,000 บาท','มากกว่า 100,000 บาท'),
        farm_structure_damage ENUM('เสียหายหนัก','เสียหายปานกลาง','เสียหายเล็กน้อย','ไม่เสียหาย','ไม่มี'),
        farm_structure_types JSON,
        farm_structure_cost ENUM('น้อยกว่า 10,000 บาท','10,001-30,000 บาท','30,001-50,000 บาท','50,001-100,000 บาท','มากกว่า 100,000 บาท'),
        
        -- สรุปรวม
        total_damage_cost ENUM('น้อยกว่า 50,000 บาท','50,001-100,000 บาท','100,001-300,000 บาท','300,001-500,000 บาท','500,001-1,000,000 บาท','มากกว่า 1,000,000 บาท'),
        has_insurance ENUM('มี (ครอบคลุมความเสียหายทั้งหมด)','มี (ครอบคลุมบางส่วน)','ไม่มี'),
        insurance_help ENUM('ให้ความช่วยเหลือเต็มจำนวน','ให้ความช่วยเหลือบางส่วน','ไม่ให้ความช่วยเหลือ','อยู่ระหว่างดำเนินการ','ไม่มีประกันภัย'),
        self_repair ENUM('ได้ทั้งหมด','ได้บางส่วน','ไม่ได้เลย','ยังไม่แน่ใจ'),
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "ตารางถูกสร้างเรียบร้อยแล้ว";
}

// เรียกใช้ function สร้างตาราง (เอา comment ออกแล้วรันครั้งเดียว)
// createTables($pdo);
?>