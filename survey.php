<?php
// survey.php - หน้าตอบแบบสอบถาม
require_once 'config.php';

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // จัดการการอัพโหลดรูปภาพ
        $uploadedImages = [];
        if (isset($_FILES['damage_images'])) {
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            foreach ($_FILES['damage_images']['tmp_name'] as $key => $tmpName) {
                if (!empty($tmpName) && $_FILES['damage_images']['error'][$key] === 0) {
                    $fileName = time() . '_' . $key . '_' . $_FILES['damage_images']['name'][$key];
                    $targetPath = $uploadDir . $fileName;
                    
                    // ตรวจสอบประเภทไฟล์
                    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    $fileType = $_FILES['damage_images']['type'][$key];
                    
                    if (in_array($fileType, $allowedTypes) && $_FILES['damage_images']['size'][$key] <= 5000000) {
                        if (move_uploaded_file($tmpName, $targetPath)) {
                            $uploadedImages[] = $fileName;
                        }
                    }
                }
            }
        }

        // ฟังก์ชันแปลงค่าว่างเป็น NULL
        function emptyToNull($value) {
            return (empty($value) || $value === '') ? null : $value;
        }

        // เตรียมข้อมูลสำหรับบันทึก
        $houseDamageParts = isset($_POST['house_damage_parts']) ? json_encode($_POST['house_damage_parts']) : null;
        $vehicleTypes = isset($_POST['vehicle_types']) ? json_encode($_POST['vehicle_types']) : null;
        $applianceTypes = isset($_POST['appliance_types']) ? json_encode($_POST['appliance_types']) : null;
        $cropTypes = isset($_POST['crop_types']) ? json_encode($_POST['crop_types']) : null;
        $livestockTypes = isset($_POST['livestock_types']) ? json_encode($_POST['livestock_types']) : null;
        $farmStructureTypes = isset($_POST['farm_structure_types']) ? json_encode($_POST['farm_structure_types']) : null;
        $damageImages = !empty($uploadedImages) ? json_encode($uploadedImages) : null;

        $sql = "INSERT INTO survey_responses (
            respondent_type, age, gender, address, border_distance,
            house_damage, house_damage_parts, house_repair_cost,
            vehicle_damage, vehicle_types, vehicle_repair_cost,
            appliance_damage, appliance_types, appliance_repair_cost,
            crop_damage, crop_types, crop_loss_cost,
            livestock_impact, livestock_types, livestock_loss_cost,
            farm_structure_damage, farm_structure_types, farm_structure_cost,
            total_damage_cost, has_insurance, insurance_help, self_repair, damage_images
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['respondent_type'], 
            $_POST['age'], 
            $_POST['gender'], 
            $_POST['address'], 
            $_POST['border_distance'],
            $_POST['house_damage'], 
            $houseDamageParts, 
            emptyToNull($_POST['house_repair_cost'] ?? null),
            $_POST['vehicle_damage'], 
            $vehicleTypes, 
            emptyToNull($_POST['vehicle_repair_cost'] ?? null),
            $_POST['appliance_damage'], 
            $applianceTypes, 
            emptyToNull($_POST['appliance_repair_cost'] ?? null),
            $_POST['crop_damage'], 
            $cropTypes, 
            emptyToNull($_POST['crop_loss_cost'] ?? null),
            emptyToNull($_POST['livestock_impact'] ?? null), 
            $livestockTypes, 
            emptyToNull($_POST['livestock_loss_cost'] ?? null),
            emptyToNull($_POST['farm_structure_damage'] ?? null), 
            $farmStructureTypes, 
            emptyToNull($_POST['farm_structure_cost'] ?? null),
            emptyToNull($_POST['total_damage_cost'] ?? null), 
            emptyToNull($_POST['has_insurance'] ?? null), 
            emptyToNull($_POST['insurance_help'] ?? null), 
            emptyToNull($_POST['self_repair'] ?? null), 
            $damageImages
        ]);

        $successMessage = true;
    } catch(PDOException $e) {
        $errorMessage = "ไม่สามารถบันทึกข้อมูลได้: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แบบสอบถามผลกระทบจากการสู้รบชายแดนไทย-กัมพูชา</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(45deg, #2c5282, #3182ce);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .form-content {
            padding: 40px;
        }

        .section {
            margin-bottom: 40px;
            padding: 25px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            background: #f8fafc;
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4299e1;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a5568;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #4299e1;
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
        }

        .radio-group,
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 10px;
        }

        .radio-item,
        .checkbox-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 10px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            min-width: 120px;
        }

        .radio-item:hover,
        .checkbox-item:hover {
            border-color: #4299e1;
            background: #ebf8ff;
        }

        .radio-item input,
        .checkbox-item input {
            margin-right: 8px;
        }

        .submit-section {
            text-align: center;
            margin-top: 40px;
            padding: 30px;
            background: #f0f9ff;
            border-radius: 10px;
        }

        .btn-submit {
            background: linear-gradient(45deg, #4299e1, #3182ce);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-decoration: none;
            display: inline-block;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .hidden {
            display: none;
        }

        .progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(45deg, #4299e1, #3182ce);
            border-radius: 3px;
            transition: width 0.3s;
        }

        .alert-success {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            color: #22543d;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-error {
            background: #fed7d7;
            border: 1px solid #fc8181;
            color: #742a2a;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .navigation-links {
            text-align: center;
            margin-top: 20px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .nav-link {
            color: #4299e1;
            text-decoration: none;
            font-weight: 500;
            margin: 0 15px;
            padding: 10px 20px;
            border: 2px solid #4299e1;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .nav-link:hover {
            background: #4299e1;
            color: white;
        }

        .file-upload-container {
            margin-top: 20px;
            padding: 20px;
            border: 2px dashed #4299e1;
            border-radius: 10px;
            text-align: center;
            background: #f8fafc;
        }

        .file-upload-container.dragover {
            background: #ebf8ff;
            border-color: #3182ce;
        }

        .file-input {
            display: none;
        }

        .file-upload-btn {
            background: #4299e1;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }

        .file-upload-btn:hover {
            background: #3182ce;
        }

        .file-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            justify-content: center;
        }

        .file-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #e2e8f0;
        }

        .file-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .file-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e53e3e;
            color: white;
            border: none;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upload-progress {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f0f9ff;
            border-radius: 8px;
            border: 1px solid #4299e1;
        }

        .progress-bar-upload {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }

        .progress-fill-upload {
            height: 100%;
            background: linear-gradient(45deg, #4299e1, #3182ce);
            border-radius: 4px;
            transition: width 0.3s;
            width: 0%;
        }

        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            max-width: 300px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #4299e1;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .damage-summary {
            background: #fff5f5;
            border: 1px solid #fed7d7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .damage-summary.no-damage {
            background: #f0fff4;
            border-color: #9ae6b4;
        }

        .damage-summary h4 {
            color: #742a2a;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .damage-summary.no-damage h4 {
            color: #22543d;
        }

        .damage-list {
            list-style: none;
            padding: 0;
        }

        .damage-list li {
            padding: 5px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .damage-list li:last-child {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .radio-group,
            .checkbox-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .radio-item,
            .checkbox-item {
                min-width: auto;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>แบบสอบถามผลกระทบจากการสู้รบชายแดนไทย-กัมพูชา</h1>
            <p>เพื่อใช้เป็นข้อมูลประกอบการขอรับการสนับสนุนงบประมาณจาก สำนักงานคณะกรรมการการอาชีวศึกษา</p>
        </div>

        <div class="form-content">
            <?php if (isset($successMessage)): ?>
                <div class="alert-success">
                    <div style="font-size: 48px; margin-bottom: 15px;">✓</div>
                    <h2>บันทึกข้อมูลสำเร็จ!</h2>
                    <p>ขอบคุณสำหรับการตอบแบบสอบถาม ข้อมูลของท่านได้รับการบันทึกเรียบร้อยแล้ว</p>
                </div>
            <?php endif; ?>

            <?php if (isset($errorMessage)): ?>
                <div class="alert-error">
                    <h2>เกิดข้อผิดพลาด!</h2>
                    <p><?= htmlspecialchars($errorMessage) ?></p>
                </div>
            <?php endif; ?>

            <?php if (!isset($successMessage)): ?>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>

            <form id="surveyForm" method="POST" action="" enctype="multipart/form-data">
                
                <!-- ส่วนที่ 1: ข้อมูลทั่วไป -->
                <div class="section" id="section1">
                    <h2 class="section-title">ส่วนที่ 1: ข้อมูลทั่วไป</h2>
                    
                    <div class="form-group">
                        <label>กลุ่มผู้ตอบ:</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="respondent_type" value="ผู้บริหาร" required>
                                <span>ผู้บริหาร</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="respondent_type" value="ครู">
                                <span>ครู</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="respondent_type" value="บุคลากรทางการศึกษา">
                                <span>บุคลากรทางการศึกษา</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="respondent_type" value="นักเรียน">
                                <span>นักเรียน</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="respondent_type" value="ผู้ปกครอง">
                                <span>ผู้ปกครอง</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="age">อายุ (ปี):</label>
                        <input type="number" id="age" name="age" min="1" max="120" required>
                    </div>

                    <div class="form-group">
                        <label>เพศ:</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="gender" value="ชาย" required>
                                <span>ชาย</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="gender" value="หญิง">
                                <span>หญิง</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="gender" value="อื่นๆ">
                                <span>อื่นๆ</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="gender" value="ไม่ระบุ">
                                <span>ไม่ระบุ</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">ที่อยู่:</label>
                        <textarea id="address" name="address" rows="3" required placeholder="กรุณากรอกที่อยู่ของท่าน"></textarea>
                    </div>

                    <div class="form-group">
                        <label>ระยะห่างจากชายแดน:</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="border_distance" value="น้อยกว่า 5 กม." required>
                                <span>น้อยกว่า 5 กม.</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="border_distance" value="5-20 กม.">
                                <span>5-20 กม.</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="border_distance" value="21-50 กม.">
                                <span>21-50 กม.</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="border_distance" value="มากกว่า 50 กม.">
                                <span>มากกว่า 50 กม.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ส่วนที่ 2.1: บ้านและอาคาร -->
                <div class="section" id="section2">
                    <h2 class="section-title">ส่วนที่ 2.1: ความเสียหายของบ้านและอาคาร</h2>
                    
                    <div class="form-group">
                        <label>บ้าน/อาคารของท่านได้รับความเสียหายหรือไม่?</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="house_damage" value="เสียหายหนัก" required onchange="toggleDamageDetails('house')">
                                <span>เสียหายหนัก (พังทลาย/อยู่ไม่ได้)</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="house_damage" value="เสียหายปานกลาง" onchange="toggleDamageDetails('house')">
                                <span>เสียหายปานกลาง (ซ่อมได้)</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="house_damage" value="เสียหายเล็กน้อย" onchange="toggleDamageDetails('house')">
                                <span>เสียหายเล็กน้อย (รอยแตก/รอยขีดข่วน)</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="house_damage" value="ไม่เสียหาย" onchange="toggleDamageDetails('house')">
                                <span>ไม่เสียหาย</span>
                            </div>
                        </div>
                    </div>

                    <div id="houseDamageDetails" class="hidden">
                        <div class="form-group">
                            <label>หากบ้าน/อาคารเสียหาย ส่วนใดบ้าง? (ตอบได้หลายข้อ)</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="house_damage_parts[]" value="หลังคา">
                                    <span>หลังคา</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="house_damage_parts[]" value="ผนัง">
                                    <span>ผนัง</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="house_damage_parts[]" value="หน้าต่าง/ประตู">
                                    <span>หน้าต่าง/ประตู</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="house_damage_parts[]" value="ระบบไฟฟ้า">
                                    <span>ระบบไฟฟ้า</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="house_damage_parts[]" value="ระบบประปา">
                                    <span>ระบบประปา</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="house_damage_parts[]" value="พื้น">
                                    <span>พื้น</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="house_damage_parts[]" value="รั้ว">
                                    <span>รั้ว</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ประเมินค่าใช้จ่ายในการซ่อมแซมบ้าน/อาคาร:</label>
                            <select name="house_repair_cost">
                                <option value="">เลือกช่วงค่าใช้จ่าย</option>
                                <option value="น้อยกว่า 10,000 บาท">น้อยกว่า 10,000 บาท</option>
                                <option value="10,001-50,000 บาท">10,001-50,000 บาท</option>
                                <option value="50,001-100,000 บาท">50,001-100,000 บาท</option>
                                <option value="100,001-300,000 บาท">100,001-300,000 บาท</option>
                                <option value="300,001-500,000 บาท">300,001-500,000 บาท</option>
                                <option value="มากกว่า 500,000 บาท">มากกว่า 500,000 บาท</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ส่วนที่ 2.2: ยานพาหนะ -->
                <div class="section" id="section3">
                    <h2 class="section-title">ส่วนที่ 2.2: ความเสียหายของยานพาหนะ</h2>
                    
                    <div class="form-group">
                        <label>ยานพาหนะของท่านได้รับความเสียหายหรือไม่?</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="vehicle_damage" value="เสียหายหนัก" required onchange="toggleDamageDetails('vehicle')">
                                <span>เสียหายหนัก (ใช้งานไม่ได้)</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="vehicle_damage" value="เสียหายปานกลาง" onchange="toggleDamageDetails('vehicle')">
                                <span>เสียหายปานกลาง (ซ่อมได้)</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="vehicle_damage" value="เสียหายเล็กน้อย" onchange="toggleDamageDetails('vehicle')">
                                <span>เสียหายเล็กน้อย (รอยขีดข่วน)</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="vehicle_damage" value="ไม่เสียหาย" onchange="toggleDamageDetails('vehicle')">
                                <span>ไม่เสียหาย</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="vehicle_damage" value="ไม่มียานพาหนะ" onchange="toggleDamageDetails('vehicle')">
                                <span>ไม่มียานพาหนะ</span>
                            </div>
                        </div>
                    </div>

                    <div id="vehicleDamageDetails" class="hidden">
                        <div class="form-group">
                            <label>ประเภทยานพาหนะที่เสียหาย: (ตอบได้หลายข้อ)</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="vehicle_types[]" value="รถยนต์">
                                    <span>รถยนต์</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="vehicle_types[]" value="รถจักรยานยนต์">
                                    <span>รถจักรยานยนต์</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="vehicle_types[]" value="รถบรรทุก">
                                    <span>รถบรรทุก</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="vehicle_types[]" value="รถตู้">
                                    <span>รถตู้</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="vehicle_types[]" value="จักรยาน">
                                    <span>จักรยาน</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="vehicle_types[]" value="เรือ">
                                    <span>เรือ</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ประเมินค่าใช้จ่ายในการซ่อมยานพาหนะ:</label>
                            <select name="vehicle_repair_cost">
                                <option value="">เลือกช่วงค่าใช้จ่าย</option>
                                <option value="น้อยกว่า 5,000 บาท">น้อยกว่า 5,000 บาท</option>
                                <option value="5,001-20,000 บาท">5,001-20,000 บาท</option>
                                <option value="20,001-50,000 บาท">20,001-50,000 บาท</option>
                                <option value="50,001-100,000 บาท">50,001-100,000 บาท</option>
                                <option value="มากกว่า 100,000 บาท">มากกว่า 100,000 บาท</option>
                                <option value="ซ่อมไม่ได้/ต้องซื้อใหม่">ซ่อมไม่ได้/ต้องซื้อใหม่</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ส่วนที่ 2.3: เครื่องใช้ไฟฟ้า -->
                <div class="section" id="section4">
                    <h2 class="section-title">ส่วนที่ 2.3: ความเสียหายของเครื่องใช้และอุปกรณ์</h2>
                    
                    <div class="form-group">
                        <label>เครื่องใช้ไฟฟ้าได้รับความเสียหายหรือไม่?</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="appliance_damage" value="เสียหายหนัก" required onchange="toggleDamageDetails('appliance')">
                                <span>เสียหายหนัก</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="appliance_damage" value="เสียหายบ้าง" onchange="toggleDamageDetails('appliance')">
                                <span>เสียหายบ้าง</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="appliance_damage" value="ไม่เสียหาย" onchange="toggleDamageDetails('appliance')">
                                <span>ไม่เสียหาย</span>
                            </div>
                        </div>
                    </div>

                    <div id="applianceDamageDetails" class="hidden">
                        <div class="form-group">
                            <label>เครื่องใช้ไฟฟ้าที่เสียหาย: (ตอบได้หลายข้อ)</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="appliance_types[]" value="ตู้เย็น">
                                    <span>ตู้เย็น</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="appliance_types[]" value="ทีวี">
                                    <span>ทีวี</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="appliance_types[]" value="เครื่องซักผ้า">
                                    <span>เครื่องซักผ้า</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="appliance_types[]" value="แอร์">
                                    <span>แอร์</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="appliance_types[]" value="พัดลม">
                                    <span>พัดลม</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="appliance_types[]" value="คอมพิวเตร์/แท็บเล็ต">
                                    <span>คอมพิวเตร์/แท็บเล็ต</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="appliance_types[]" value="โทรศัพท์">
                                    <span>โทรศัพท์</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ประเมินค่าใช้จ่ายซ่อม/เปลี่ยนเครื่องใช้ไฟฟ้า:</label>
                            <select name="appliance_repair_cost">
                                <option value="">เลือกช่วงค่าใช้จ่าย</option>
                                <option value="น้อยกว่า 5,000 บาท">น้อยกว่า 5,000 บาท</option>
                                <option value="5,001-20,000 บาท">5,001-20,000 บาท</option>
                                <option value="20,001-50,000 บาท">20,001-50,000 บาท</option>
                                <option value="50,001-100,000 บาท">50,001-100,000 บาท</option>
                                <option value="มากกว่า 100,000 บาท">มากกว่า 100,000 บาท</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ส่วนที่ 2.4: การเกษตร -->
                <div class="section" id="section5">
                    <h2 class="section-title">ส่วนที่ 2.4: ความเสียหายด้านการเกษตร</h2>
                    
                    <div class="form-group">
                        <label>พืชผล/สวน/ไร่นาได้รับความเสียหายหรือไม่?</label>
                        <div class="radio-group">
                            <div class="radio-item">
                                <input type="radio" name="crop_damage" value="เสียหายหนัก" required onchange="toggleDamageDetails('crop')">
                                <span>เสียหายหนัก (เก็บเกี่ยวไม่ได้)</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="crop_damage" value="เสียหายปานกลาง" onchange="toggleDamageDetails('crop')">
                                <span>เสียหายปานกลาง</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="crop_damage" value="เสียหายเล็กน้อย" onchange="toggleDamageDetails('crop')">
                                <span>เสียหายเล็กน้อย</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="crop_damage" value="ไม่เสียหาย" onchange="toggleDamageDetails('crop')">
                                <span>ไม่เสียหาย</span>
                            </div>
                            <div class="radio-item">
                                <input type="radio" name="crop_damage" value="ไม่มี" onchange="toggleDamageDetails('crop')">
                                <span>ไม่มี</span>
                            </div>
                        </div>
                    </div>

                    <div id="cropDamageDetails" class="hidden">
                        <div class="form-group">
                            <label>ประเภทพืชผลที่เสียหาย: (ตอบได้หลายข้อ)</label>
                            <div class="checkbox-group">
                                <div class="checkbox-item">
                                    <input type="checkbox" name="crop_types[]" value="ข้าว">
                                    <span>ข้าว</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="crop_types[]" value="ข้าวโพด">
                                    <span>ข้าวโพด</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="crop_types[]" value="มันสำปะหลัง">
                                    <span>มันสำปะหลัง</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="crop_types[]" value="ผลไม้">
                                    <span>ผลไม้</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="crop_types[]" value="ผัก">
                                    <span>ผัก</span>
                                </div>
                                <div class="checkbox-item">
                                    <input type="checkbox" name="crop_types[]" value="ไม้ยืนต้น">
                                    <span>ไม้ยืนต้น</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ประเมินความสูญเสียจากพืชผล:</label>
                            <select name="crop_loss_cost">
                                <option value="">เลือกช่วงความสูญเสีย</option>
                                <option value="น้อยกว่า 10,000 บาท">น้อยกว่า 10,000 บาท</option>
                                <option value="10,001-50,000 บาท">10,001-50,000 บาท</option>
                                <option value="50,001-100,000 บาท">50,001-100,000 บาท</option>
                                <option value="100,001-300,000 บาท">100,001-300,000 บาท</option>
                                <option value="มากกว่า 300,000 บาท">มากกว่า 300,000 บาท</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>ปศุสัตว์ได้รับผลกระทบหรือไม่?</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="livestock_impact" value="ตาย/หายไป" onchange="toggleDamageDetails('livestock')">
                                    <span>ตาย/หายไป</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="livestock_impact" value="บาดเจ็บ" onchange="toggleDamageDetails('livestock')">
                                    <span>บาดเจ็บ</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="livestock_impact" value="เครียด/ไม่กินอาหาร" onchange="toggleDamageDetails('livestock')">
                                    <span>เครียด/ไม่กินอาหาร</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="livestock_impact" value="ไม่ได้รับผลกระทบ" onchange="toggleDamageDetails('livestock')">
                                    <span>ไม่ได้รับผลกระทบ</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="livestock_impact" value="ไม่มี" onchange="toggleDamageDetails('livestock')">
                                    <span>ไม่มี</span>
                                </div>
                            </div>
                        </div>

                        <div id="livestockDetails" class="hidden">
                            <div class="form-group">
                                <label>ประเภทปศุสัตว์ที่ได้รับผลกระทบ: (ตอบได้หลายข้อ)</label>
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="livestock_types[]" value="วัว/ควาย">
                                        <span>วัว/ควาย</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="livestock_types[]" value="หมู">
                                        <span>หมู</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="livestock_types[]" value="ไก่">
                                        <span>ไก่</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="livestock_types[]" value="เป็ด">
                                        <span>เป็ด</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="livestock_types[]" value="ปลา">
                                        <span>ปลา</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>ประเมินความสูญเสียจากปศุสัตว์:</label>
                                <select name="livestock_loss_cost">
                                    <option value="">เลือกช่วงความสูญเสีย</option>
                                    <option value="น้อยกว่า 5,000 บาท">น้อยกว่า 5,000 บาท</option>
                                    <option value="5,001-20,000 บาท">5,001-20,000 บาท</option>
                                    <option value="20,001-50,000 บาท">20,001-50,000 บาท</option>
                                    <option value="50,001-100,000 บาท">50,001-100,000 บาท</option>
                                    <option value="มากกว่า 100,000 บาท">มากกว่า 100,000 บาท</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>สิ่งปลูกสร้างทางการเกษตรได้รับความเสียหายหรือไม่?</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="farm_structure_damage" value="เสียหายหนัก" onchange="toggleDamageDetails('farmStructure')">
                                    <span>เสียหายหนัก (ใช้งานไม่ได้)</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="farm_structure_damage" value="เสียหายปานกลาง" onchange="toggleDamageDetails('farmStructure')">
                                    <span>เสียหายปานกลาง (ซ่อมได้)</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="farm_structure_damage" value="เสียหายเล็กน้อย" onchange="toggleDamageDetails('farmStructure')">
                                    <span>เสียหายเล็กน้อย</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="farm_structure_damage" value="ไม่เสียหาย" onchange="toggleDamageDetails('farmStructure')">
                                    <span>ไม่เสียหาย</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="farm_structure_damage" value="ไม่มี" onchange="toggleDamageDetails('farmStructure')">
                                    <span>ไม่มี</span>
                                </div>
                            </div>
                        </div>

                        <div id="farmStructureDetails" class="hidden">
                            <div class="form-group">
                                <label>ประเภทสิ่งปลูกสร้างที่เสียหาย: (ตอบได้หลายข้อ)</label>
                                <div class="checkbox-group">
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="farm_structure_types[]" value="ยุ้งข้าว">
                                        <span>ยุ้งข้าว</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="farm_structure_types[]" value="โรงเก็บปุ๋ย">
                                        <span>โรงเก็บปุ๋ย</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="farm_structure_types[]" value="เล้าสัตว์">
                                        <span>เล้าสัตว์</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="farm_structure_types[]" value="บ่อปลา">
                                        <span>บ่อปลา</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="farm_structure_types[]" value="ระบบชลประทาน">
                                        <span>ระบบชลประทาน</span>
                                    </div>
                                    <div class="checkbox-item">
                                        <input type="checkbox" name="farm_structure_types[]" value="รั้วไร่/สวน">
                                        <span>รั้วไร่/สวน</span>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>ประเมินค่าใช้จ่ายซ่อมแซมสิ่งปลูกสร้างการเกษตร:</label>
                                <select name="farm_structure_cost">
                                    <option value="">เลือกช่วงค่าใช้จ่าย</option>
                                    <option value="น้อยกว่า 10,000 บาท">น้อยกว่า 10,000 บาท</option>
                                    <option value="10,001-30,000 บาท">10,001-30,000 บาท</option>
                                    <option value="30,001-50,000 บาท">30,001-50,000 บาท</option>
                                    <option value="50,001-100,000 บาท">50,001-100,000 บาท</option>
                                    <option value="มากกว่า 100,000 บาท">มากกว่า 100,000 บาท</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ส่วนที่ 2.5: สรุปความเสียหายโดยรวม -->
                <div class="section" id="section6">
                    <h2 class="section-title">ส่วนที่ 2.5: สรุปความเสียหายโดยรวม</h2>
                    
                    <!-- สรุปความเสียหายแบบ dynamic -->
                    <div id="damageSummary" class="damage-summary">
                        <h4>📋 สรุปความเสียหายของท่าน</h4>
                        <ul id="damageList" class="damage-list">
                            <li>กรุณาตอบคำถามในส่วนต่างๆ เพื่อดูสรุปความเสียหาย</li>
                        </ul>
                    </div>

                    <!-- ส่วนประเมินมูลค่า (แสดงเฉพาะเมื่อมีความเสียหาย) -->
                    <div id="damageEvaluationSection" class="hidden">
                        <div class="form-group">
                            <label>ประเมินมูลค่าความเสียหายทั้งหมด:</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="total_damage_cost" value="น้อยกว่า 50,000 บาท">
                                    <span>น้อยกว่า 50,000 บาท</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="total_damage_cost" value="50,001-100,000 บาท">
                                    <span>50,001-100,000 บาท</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="total_damage_cost" value="100,001-300,000 บาท">
                                    <span>100,001-300,000 บาท</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="total_damage_cost" value="300,001-500,000 บาท">
                                    <span>300,001-500,000 บาท</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="total_damage_cost" value="500,001-1,000,000 บาท">
                                    <span>500,001-1,000,000 บาท</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="total_damage_cost" value="มากกว่า 1,000,000 บาท">
                                    <span>มากกว่า 1,000,000 บาท</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ท่านมีประกันภัยคุ้มครองทรัพย์สินหรือไม่?</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="has_insurance" value="มี (ครอบคลุมความเสียหายทั้งหมด)">
                                    <span>มี (ครอบคลุมความเสียหายทั้งหมด)</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="has_insurance" value="มี (ครอบคลุมบางส่วน)">
                                    <span>มี (ครอบคลุมบางส่วน)</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="has_insurance" value="ไม่มี">
                                    <span>ไม่มี</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>หากมีประกันภัย บริษัทประกันให้ความช่วยเหลือหรือไม่?</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="insurance_help" value="ให้ความช่วยเหลือเต็มจำนวน">
                                    <span>ให้ความช่วยเหลือเต็มจำนวน</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="insurance_help" value="ให้ความช่วยเหลือบางส่วน">
                                    <span>ให้ความช่วยเหลือบางส่วน</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="insurance_help" value="ไม่ให้ความช่วยเหลือ">
                                    <span>ไม่ให้ความช่วยเหลือ</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="insurance_help" value="อยู่ระหว่างดำเนินการ">
                                    <span>อยู่ระหว่างดำเนินการ</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="insurance_help" value="ไม่มีประกันภัย">
                                    <span>ไม่มีประกันภัย</span>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ท่านสามารถซ่อมแซมทรัพย์สินด้วยตนเองได้หรือไม่?</label>
                            <div class="radio-group">
                                <div class="radio-item">
                                    <input type="radio" name="self_repair" value="ได้ทั้งหมด">
                                    <span>ได้ทั้งหมด</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="self_repair" value="ได้บางส่วน">
                                    <span>ได้บางส่วน</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="self_repair" value="ไม่ได้เลย">
                                    <span>ไม่ได้เลย</span>
                                </div>
                                <div class="radio-item">
                                    <input type="radio" name="self_repair" value="ยังไม่แน่ใจ">
                                    <span>ยังไม่แน่ใจ</span>
                                </div>
                            </div>
                        </div>

                        <!-- ส่วนอัพโหลดรูปภาพ -->
                        <div class="form-group">
                            <label>📷 รูปภาพประกอบความเสียหาย (ไม่เกิน 8 ภาพ)</label>
                            <div class="file-upload-container" id="fileUploadContainer">
                                <p style="color: #718096; margin-bottom: 15px;">
                                    📎 ลากไฟล์มาวางที่นี่ หรือคลิกเพื่อเลือกไฟล์
                                </p>
                                <button type="button" class="file-upload-btn" onclick="document.getElementById('fileInput').click()">
                                    เลือกรูปภาพ
                                </button>
                                <input type="file" id="fileInput" name="damage_images[]" multiple accept="image/*" class="file-input" onchange="handleFileSelect(this.files)">
                                <p style="font-size: 14px; color: #718096; margin-top: 10px;">
                                    รองรับไฟล์: JPG, PNG, GIF (ขนาดไม่เกิน 5MB ต่อไฟล์)
                                </p>
                                <div id="filePreview" class="file-preview"></div>
                                
                                <!-- Progress Bar สำหรับอัพโหลด -->
                                <div id="uploadProgress" class="upload-progress">
                                    <p>🔄 กำลังอัพโหลดรูปภาพ...</p>
                                    <div class="progress-bar-upload">
                                        <div id="progressFillUpload" class="progress-fill-upload"></div>
                                    </div>
                                    <p id="uploadStatus">กำลังเตรียมการอัพโหลด...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ข้อความสำหรับกรณีไม่มีความเสียหาย -->
                    <div id="noDamageMessage" class="hidden">
                        <div class="damage-summary no-damage">
                            <h4>✅ ท่านไม่ได้รับความเสียหายจากเหตุการณ์นี้</h4>
                            <p style="color: #22543d;">ขอบคุณสำหรับการให้ข้อมูล แม้ว่าท่านจะไม่ได้รับความเสียหาย แต่ข้อมูลของท่านก็มีความสำคัญต่อการวิเคราะห์สถานการณ์โดยรวม</p>
                        </div>
                    </div>
                </div>

                <div class="submit-section">
                    <button type="submit" class="btn-submit" id="submitBtn">ส่งแบบสอบถาม</button>
                    <p style="margin-top: 15px; color: #718096;">ขอบคุณสำหรับการให้ข้อมูล</p>
                </div>
            </form>
            <?php endif; ?>

            <!-- Loading Overlay -->
            <div id="loadingOverlay" class="loading-overlay">
                <div class="loading-content">
                    <div class="spinner"></div>
                    <h3>กำลังส่งข้อมูล...</h3>
                    <p id="loadingText">กรุณารอสักครู่</p>
                </div>
            </div>

            <div class="navigation-links">
                <?php if (isset($successMessage)): ?>
                    <a href="survey.php" class="nav-link">ตอบแบบสอบถามใหม่</a>
                <?php endif; ?>
                <a href="report.php" class="nav-link">📊 ดูรายงานสรุปผล</a>
                <a href="view_images.php" class="nav-link">📷 ดูรูปภาพทั้งหมด</a>
            </div>
        </div>
    </div>

    <script>
        let selectedFiles = [];
        const maxFiles = 8;

        // Conditional Logic สำหรับซ่อน/แสดงคำถาม
        function toggleDamageDetails(type) {
            const containers = {
                'house': 'houseDamageDetails',
                'vehicle': 'vehicleDamageDetails', 
                'appliance': 'applianceDamageDetails',
                'crop': 'cropDamageDetails',
                'livestock': 'livestockDetails',
                'farmStructure': 'farmStructureDetails'
            };

            const radioName = type + '_damage';
            if (type === 'livestock') radioName = 'livestock_impact';
            if (type === 'farmStructure') radioName = 'farm_structure_damage';

            const selected = document.querySelector(`input[name="${radioName}"]:checked`);
            const container = document.getElementById(containers[type]);
            
            if (!container) return;

            // เงื่อนไขสำหรับแสดง/ซ่อน
            let shouldShow = false;
            if (selected) {
                const value = selected.value;
                if (type === 'house' && value !== 'ไม่เสียหาย') shouldShow = true;
                if (type === 'vehicle' && value !== 'ไม่เสียหาย' && value !== 'ไม่มียานพาหนะ') shouldShow = true;
                if (type === 'appliance' && value !== 'ไม่เสียหาย') shouldShow = true;
                if (type === 'crop' && value !== 'ไม่เสียหาย' && value !== 'ไม่มี') shouldShow = true;
                if (type === 'livestock' && value !== 'ไม่ได้รับผลกระทบ' && value !== 'ไม่มี') shouldShow = true;
                if (type === 'farmStructure' && value !== 'ไม่เสียหาย' && value !== 'ไม่มี') shouldShow = true;
            }

            container.style.display = shouldShow ? 'block' : 'none';
            container.classList.toggle('hidden', !shouldShow);

            // เคลียร์ข้อมูลเมื่อซ่อน
            if (!shouldShow) {
                const inputs = container.querySelectorAll('input, select');
                inputs.forEach(input => {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                    input.removeAttribute('required');
                });
            } else {
                // เพิ่ม required เมื่อแสดง
                const selects = container.querySelectorAll('select');
                selects.forEach(select => {
                    if (select.name.includes('cost')) {
                        select.setAttribute('required', 'true');
                    }
                });
            }

            updateDamageSummary();
            updateProgress();
        }

        // อัพเดทสรุปความเสียหาย
        function updateDamageSummary() {
            const summary = document.getElementById('damageSummary');
            const damageList = document.getElementById('damageList');
            const evaluationSection = document.getElementById('damageEvaluationSection');
            const noDamageMessage = document.getElementById('noDamageMessage');
            
            const damages = [];
            let hasDamage = false;

            // ตรวจสอบความเสียหายแต่ละประเภท
            const houseDamage = document.querySelector('input[name="house_damage"]:checked');
            if (houseDamage && houseDamage.value !== 'ไม่เสียหาย') {
                damages.push(`🏠 บ้าน/อาคาร: ${houseDamage.value}`);
                hasDamage = true;
            }

            const vehicleDamage = document.querySelector('input[name="vehicle_damage"]:checked');
            if (vehicleDamage && vehicleDamage.value !== 'ไม่เสียหาย' && vehicleDamage.value !== 'ไม่มียานพาหนะ') {
                damages.push(`🚗 ยานพาหนะ: ${vehicleDamage.value}`);
                hasDamage = true;
            }

            const applianceDamage = document.querySelector('input[name="appliance_damage"]:checked');
            if (applianceDamage && applianceDamage.value !== 'ไม่เสียหาย') {
                damages.push(`⚡ เครื่องใช้ไฟฟ้า: ${applianceDamage.value}`);
                hasDamage = true;
            }

            const cropDamage = document.querySelector('input[name="crop_damage"]:checked');
            if (cropDamage && cropDamage.value !== 'ไม่เสียหาย' && cropDamage.value !== 'ไม่มี') {
                damages.push(`🌾 พืชผล: ${cropDamage.value}`);
                hasDamage = true;
            }

            const livestockDamage = document.querySelector('input[name="livestock_impact"]:checked');
            if (livestockDamage && livestockDamage.value !== 'ไม่ได้รับผลกระทบ' && livestockDamage.value !== 'ไม่มี') {
                damages.push(`🐄 ปศุสัตว์: ${livestockDamage.value}`);
                hasDamage = true;
            }

            const farmStructureDamage = document.querySelector('input[name="farm_structure_damage"]:checked');
            if (farmStructureDamage && farmStructureDamage.value !== 'ไม่เสียหาย' && farmStructureDamage.value !== 'ไม่มี') {
                damages.push(`🏗️ สิ่งปลูกสร้างการเกษตร: ${farmStructureDamage.value}`);
                hasDamage = true;
            }

            // อัพเดท UI ตามสถานะ
            if (hasDamage) {
                summary.className = 'damage-summary';
                summary.querySelector('h4').textContent = '📋 สรุปความเสียหายของท่าน';
                damageList.innerHTML = damages.map(damage => `<li>${damage}</li>`).join('');
                
                evaluationSection.style.display = 'block';
                evaluationSection.classList.remove('hidden');
                noDamageMessage.style.display = 'none';
                noDamageMessage.classList.add('hidden');

                // เพิ่ม required attributes
                const requiredInputs = evaluationSection.querySelectorAll('input[name="total_damage_cost"], input[name="has_insurance"], input[name="insurance_help"], input[name="self_repair"]');
                requiredInputs.forEach(input => input.setAttribute('required', 'true'));
                
            } else {
                summary.className = 'damage-summary no-damage';
                summary.querySelector('h4').textContent = '✅ ไม่มีความเสียหาย';
                damageList.innerHTML = '<li>ท่านไม่ได้รับความเสียหายจากเหตุการณ์นี้</li>';
                
                evaluationSection.style.display = 'none';
                evaluationSection.classList.add('hidden');
                noDamageMessage.style.display = 'none';
                noDamageMessage.classList.add('hidden');

                // ลบ required attributes และ clear values
                const inputs = evaluationSection.querySelectorAll('input, select');
                inputs.forEach(input => {
                    input.removeAttribute('required');
                    if (input.type === 'radio') {
                        input.checked = false;
                    } else {
                        input.value = '';
                    }
                });
            }
        }

        // จัดการการเลือกไฟล์
        function handleFileSelect(files) {
            if (selectedFiles.length + files.length > maxFiles) {
                alert(`สามารถอัพโหลดได้ไม่เกิน ${maxFiles} ภาพ`);
                return;
            }

            // แสดง progress
            const uploadProgress = document.getElementById('uploadProgress');
            const progressFill = document.getElementById('progressFillUpload');
            const uploadStatus = document.getElementById('uploadStatus');
            
            uploadProgress.style.display = 'block';
            uploadStatus.textContent = 'กำลังเตรียมไฟล์...';
            progressFill.style.width = '20%';

            let validFiles = 0;
            for (let i = 0; i < files.length && selectedFiles.length < maxFiles; i++) {
                const file = files[i];
                if (file.type.startsWith('image/') && file.size <= 5000000) {
                    selectedFiles.push(file);
                    validFiles++;
                }
            }

            // อัพเดท progress
            progressFill.style.width = '70%';
            uploadStatus.textContent = `เลือกไฟล์แล้ว ${validFiles} ภาพ`;

            setTimeout(() => {
                updateFilePreview();
                updateFileInput();
                
                // เสร็จสิ้น
                progressFill.style.width = '100%';
                uploadStatus.textContent = `✅ เลือกรูปภาพสำเร็จ ${selectedFiles.length} ภาพ`;
                
                setTimeout(() => {
                    uploadProgress.style.display = 'none';
                    progressFill.style.width = '0%';
                }, 1500);
            }, 500);
        }

        // อัพเดทการแสดงตัวอย่างไฟล์
        function updateFilePreview() {
            const preview = document.getElementById('filePreview');
            preview.innerHTML = '';

            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const fileItem = document.createElement('div');
                    fileItem.className = 'file-item';
                    fileItem.innerHTML = `
                        <img src="${e.target.result}" alt="รูปภาพ ${index + 1}">
                        <button type="button" class="file-remove" onclick="removeFile(${index})" title="ลบรูปภาพ">×</button>
                    `;
                    preview.appendChild(fileItem);
                };
                reader.readAsDataURL(file);
            });
        }

        // ลบไฟล์
        function removeFile(index) {
            selectedFiles.splice(index, 1);
            updateFilePreview();
            updateFileInput();
            
            // แสดงข้อความแจ้ง
            const uploadProgress = document.getElementById('uploadProgress');
            const uploadStatus = document.getElementById('uploadStatus');
            uploadProgress.style.display = 'block';
            uploadStatus.textContent = `ลบรูปภาพแล้ว เหลือ ${selectedFiles.length} ภาพ`;
            
            setTimeout(() => {
                uploadProgress.style.display = 'none';
            }, 1000);
        }

        // อัพเดท input file
        function updateFileInput() {
            const fileInput = document.getElementById('fileInput');
            const dt = new DataTransfer();
            
            selectedFiles.forEach(file => {
                dt.items.add(file);
            });
            
            fileInput.files = dt.files;
        }

        // Drag and Drop
        function setupDragAndDrop() {
            const container = document.getElementById('fileUploadContainer');
            
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                container.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                container.addEventListener(eventName, () => container.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                container.addEventListener(eventName, () => container.classList.remove('dragover'), false);
            });

            container.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                handleFileSelect(files);
            }
        }

        // อัพเดทแถบความคืบหน้า
        function updateProgress() {
    const sections = document.querySelectorAll('.section');
    let completed = 0;

    sections.forEach(section => {
        const requiredInputs = section.querySelectorAll('input[required], select[required], textarea[required]');
        let sectionCompleted = true;

        requiredInputs.forEach(input => {
            if (input.type === 'radio') {
                const radioGroup = section.querySelectorAll(`input[name="${input.name}"]`);
                const isChecked = Array.from(radioGroup).some(radio => radio.checked);
                if (!isChecked) sectionCompleted = false;
            } else if (input.type === 'file') {
                // ตรวจสอบว่ามีไฟล์แนบหรือไม่
                if (!input.files || input.files.length === 0) {
                    sectionCompleted = false;
                }
            } else if (!input.value.trim()) {
                sectionCompleted = false;
            }
        });

        if (sectionCompleted) completed++;
    });

    const progress = (completed / sections.length) * 100;
    const progressFill = document.getElementById('progressFill');
    if (progressFill) {
        progressFill.style.width = progress + '%';
    }
}document.getElementById('surveyForm').addEventListener('submit', function(e) {
    const requiredFields = ['respondent_type', 'age', 'gender', 'address', 'border_distance', 
                            'house_damage', 'vehicle_damage', 'appliance_damage', 'crop_damage'];

    let isValid = true;
    let missingField = '';

    // ตรวจสอบฟิลด์ที่จำเป็น
    for (const field of requiredFields) {
        const input = document.querySelector(`input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`);
        if (input && input.type === 'radio') {
            const radioGroup = document.querySelectorAll(`input[name="${field}"]`);
            const isChecked = Array.from(radioGroup).some(radio => radio.checked);
            if (!isChecked) {
                isValid = false;
                missingField = field;
                break;
            }
        } else if (input && !input.value.trim()) {
            isValid = false;
            missingField = field;
            break;
        }
    }

    // ตรวจสอบไฟล์แนบ
    const fileInput = document.getElementById('fileInput');
    if (fileInput && fileInput.files.length > 0) {
        for (let i = 0; i < fileInput.files.length; i++) {
            const file = fileInput.files[i];
            if (!file.type.startsWith('image/') || file.size > 5000000) {
                isValid = false;
                alert('ไฟล์รูปภาพต้องเป็น JPG, PNG, หรือ GIF และขนาดไม่เกิน 5MB');
                break;
            }
        }
    }

    if (!isValid) {
        e.preventDefault();
        alert(`กรุณากรอกข้อมูล: ${missingField}`);
        return false;
    }

    // แสดง Loading UI
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');
    const submitBtn = document.getElementById('submitBtn');

    submitBtn.disabled = true;
    submitBtn.textContent = 'กำลังส่ง...';
    loadingOverlay.style.display = 'flex';

    if (fileInput.files.length > 0) {
        loadingText.textContent = `กำลังอัพโหลดรูปภาพ ${fileInput.files.length} ภาพ กรุณารอสักครู่...`;
    } else {
        loadingText.textContent = 'กำลังบันทึกข้อมูล กรุณารอสักครู่...';
    }

    return true;
});function updateFileInput() {
    const fileInput = document.getElementById('fileInput');
    const dt = new DataTransfer();

    selectedFiles.forEach(file => {
        dt.items.add(file);
    });

    fileInput.files = dt.files;
}document.getElementById('surveyForm').addEventListener('submit', function (e) {
    const fileInput = document.getElementById('fileInput');
    const submitBtn = document.getElementById('submitBtn');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const loadingText = document.getElementById('loadingText');

    // ตรวจสอบว่ามีไฟล์แนบหรือไม่
    if (fileInput.files.length > 0) {
        loadingText.textContent = `กำลังอัพโหลดรูปภาพ ${fileInput.files.length} ภาพ กรุณารอสักครู่...`;
    } else {
        loadingText.textContent = 'กำลังบันทึกข้อมูล กรุณารอสักครู่...';
    }

    // แสดง Loading UI
    submitBtn.disabled = true;
    submitBtn.textContent = 'กำลังส่ง...';
    loadingOverlay.style.display = 'flex';

    return true;
});

function updateFileInput() {
    const fileInput = document.getElementById('fileInput');
    const dt = new DataTransfer();

    selectedFiles.forEach(file => {
        dt.items.add(file);
    });

    fileInput.files = dt.files;
}
        // เพิ่ม event listeners
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('change', function() {
                    updateProgress();
                    updateDamageSummary();
                });
                input.addEventListener('input', updateProgress);
            });
            
            setupDragAndDrop();
            updateProgress();
            updateDamageSummary();
        });

        // Form validation ก่อนส่ง
        document.getElementById('surveyForm').addEventListener('submit', function(e) {
            // แสดง Loading
            const loadingOverlay = document.getElementById('loadingOverlay');
            const loadingText = document.getElementById('loadingText');
            const submitBtn = document.getElementById('submitBtn');
            
            // ตรวจสอบข้อมูลพื้นฐานที่จำเป็น
            const requiredFields = ['respondent_type', 'age', 'gender', 'address', 'border_distance', 
                                   'house_damage', 'vehicle_damage', 'appliance_damage', 'crop_damage'];
            
            let isValid = true;
            let missingField = '';
            
            for (const field of requiredFields) {
                const input = document.querySelector(`input[name="${field}"], select[name="${field}"], textarea[name="${field}"]`);
                if (input && input.type === 'radio') {
                    const radioGroup = document.querySelectorAll(`input[name="${field}"]`);
                    const isChecked = Array.from(radioGroup).some(radio => radio.checked);
                    if (!isChecked) {
                        isValid = false;
                        missingField = field;
                        break;
                    }
                } else if (input && !input.value.trim()) {
                    isValid = false;
                    missingField = field;
                    break;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                alert(`กรุณากรอกข้อมูล: ${missingField}`);
                return false;
            }

            // แสดง Loading UI
            submitBtn.disabled = true;
            submitBtn.textContent = 'กำลังส่ง...';
            loadingOverlay.style.display = 'flex';
            
            if (selectedFiles.length > 0) {
                loadingText.textContent = `กำลังอัพโหลดรูปภาพ ${selectedFiles.length} ภาพ กรุณารอสักครู่...`;
            } else {
                loadingText.textContent = 'กำลังบันทึกข้อมูล กรุณารอสักครู่...';
            }

            // ปล่อยให้ฟอร์มส่งไปปกติ
            return true;
        });

        // ตรวจสอบว่ามีความเสียหายหรือไม่
        function checkIfHasDamage() {
            const houseDamage = document.querySelector('input[name="house_damage"]:checked');
            const vehicleDamage = document.querySelector('input[name="vehicle_damage"]:checked');
            const applianceDamage = document.querySelector('input[name="appliance_damage"]:checked');
            const cropDamage = document.querySelector('input[name="crop_damage"]:checked');
            const livestockDamage = document.querySelector('input[name="livestock_impact"]:checked');
            const farmStructureDamage = document.querySelector('input[name="farm_structure_damage"]:checked');

            return (
                (houseDamage && houseDamage.value !== 'ไม่เสียหาย') ||
                (vehicleDamage && vehicleDamage.value !== 'ไม่เสียหาย' && vehicleDamage.value !== 'ไม่มียานพาหนะ') ||
                (applianceDamage && applianceDamage.value !== 'ไม่เสียหาย') ||
                (cropDamage && cropDamage.value !== 'ไม่เสียหาย' && cropDamage.value !== 'ไม่มี') ||
                (livestockDamage && livestockDamage.value !== 'ไม่ได้รับผลกระทบ' && livestockDamage.value !== 'ไม่มี') ||
                (farmStructureDamage && farmStructureDamage.value !== 'ไม่เสียหาย' && farmStructureDamage.value !== 'ไม่มี')
            );
        }

        // ซ่อน Loading เมื่อกลับมาที่หน้า (กรณี submit ไม่สำเร็จ)
        window.addEventListener('pageshow', function() {
            const loadingOverlay = document.getElementById('loadingOverlay');
            const submitBtn = document.getElementById('submitBtn');
            
            if (loadingOverlay) loadingOverlay.style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'ส่งแบบสอบถาม';
            }
        });
    </script>
</body>
</html>