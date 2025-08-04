<?php
// config.php - การตั้งค่าฐานข้อมูล
$servername = "localhost";
$username = "border_survey";
$password = "HwsTiD4Txjk4HA8t";
$dbname = "border_survey";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // ตั้งค่า charset สำหรับภาษาไทย
    $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
    
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
        border_distance ENUM('น้อยกว่า 5 กม.','5-20 กม.','21-50 กม.','มากกว่า 50 กม.'),
        
        -- ข้อมูลส่วนตัว (แสดงเฉพาะเมื่อมีความเสียหาย)
        first_name VARCHAR(100),
        last_name VARCHAR(100),
        phone_number VARCHAR(20),
        address TEXT,
        
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
        damage_images JSON,
        
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    try {
        $pdo->exec($sql);
        
        // สร้างโฟลเดอร์ uploads ถ้าไม่มี
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
            chmod('uploads', 0777);
        }
        
        // สร้างไฟล์ .htaccess สำหรับความปลอดภัย
        $htaccess = 'uploads/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "# Prevent script execution\n<Files *>\n    SetHandler default-handler\n</Files>\n\n# Allow image files only\n<FilesMatch \"\\.(jpg|jpeg|png|gif)$\">\n    Order Allow,Deny\n    Allow from all\n</FilesMatch>");
        }
        
        echo "ตารางและโฟลเดอร์ถูกสร้างเรียบร้อยแล้ว";
        
    } catch(PDOException $e) {
        die("Error creating table: " . $e->getMessage());
    }
}

// ฟังก์ชันตรวจสอบการตั้งค่า PHP
function checkPHPSettings() {
    $settings = [
        'file_uploads' => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'memory_limit' => ini_get('memory_limit')
    ];
    
    echo "<h3>การตั้งค่า PHP สำหรับการอัพโหลดไฟล์:</h3>";
    echo "<ul>";
    foreach ($settings as $setting => $value) {
        echo "<li><strong>$setting:</strong> $value</li>";
    }
    echo "</ul>";
    
    // แนะนำการตั้งค่า
    echo "<h4>การตั้งค่าที่แนะนำ:</h4>";
    echo "<ul>";
    echo "<li>upload_max_filesize = 10M</li>";
    echo "<li>post_max_size = 50M</li>";
    echo "<li>max_file_uploads = 20</li>";
    echo "<li>memory_limit = 128M</li>";
    echo "</ul>";
}

// เรียกใช้ function สร้างตาราง (เอา comment ออกแล้วรันครั้งเดียว)
// createTables($pdo);
// checkPHPSettings();
?>