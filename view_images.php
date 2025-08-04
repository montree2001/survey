<?php
// view_images.php - หน้าดูรูปภาพทั้งหมด
require_once 'config.php';

try {
    // ดึงข้อมูลรูปภาพทั้งหมด
    $imageData = $pdo->query("
        SELECT id, respondent_type, damage_images, house_damage, vehicle_damage, total_damage_cost, created_at
        FROM survey_responses 
        WHERE damage_images IS NOT NULL
        ORDER BY created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $errorMessage = "ไม่สามารถดึงข้อมูลได้: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รูปภาพประกอบความเสียหาย - วิทยาลัยการอาชีพปราสาท</title>
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
            max-width: 1200px;
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

        .content {
            padding: 40px;
        }

        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .image-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: transform 0.3s;
        }

        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
        }

        .image-header {
            background: #f8fafc;
            padding: 15px;
            border-bottom: 1px solid #e2e8f0;
        }

        .image-info {
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 5px;
        }

        .damage-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }

        .damage-high {
            background: #fed7d7;
            color: #742a2a;
        }

        .damage-medium {
            background: #fef5e7;
            color: #c05621;
        }

        .damage-low {
            background: #fefcbf;
            color: #744210;
        }

        .no-damage {
            background: #c6f6d5;
            color: #22543d;
        }

        .image-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            padding: 15px;
        }

        .image-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .image-item:hover {
            transform: scale(1.05);
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 12px;
        }

        .image-item:hover .image-overlay {
            opacity: 1;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
        }

        .modal-content {
            position: relative;
            margin: 2% auto;
            width: 90%;
            max-width: 800px;
            text-align: center;
        }

        .modal img {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 10px;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 35px;
            color: #fff;
            font-size: 40px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            opacity: 0.7;
        }

        .navigation-links {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .nav-link, .btn {
            color: white;
            background: linear-gradient(45deg, #4299e1, #3182ce);
            text-decoration: none;
            font-weight: 500;
            margin: 0 10px;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            transition: all 0.3s;
            display: inline-block;
            cursor: pointer;
            font-size: 16px;
        }

        .nav-link:hover, .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .stats {
            background: #f0f9ff;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .stat-item {
            display: inline-block;
            margin: 0 20px;
            color: #2d3748;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #4299e1;
        }

        @media (max-width: 768px) {
            .image-gallery {
                grid-template-columns: 1fr;
            }
            
            .image-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📷 รูปภาพประกอบความเสียหาย</h1>
            <p>ผลกระทบจากการสู้รบชายแดนไทย-กัมพูชา</p>
            <p style="margin-top: 10px; font-size: 16px;">วิทยาลัยการอาชีพปราสาท</p>
        </div>

        <div class="content">
            <?php if (isset($errorMessage)): ?>
                <div style="background: #fed7d7; border: 1px solid #fc8181; color: #742a2a; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                    <h2>เกิดข้อผิดพลาด!</h2>
                    <p><?= htmlspecialchars($errorMessage) ?></p>
                </div>
            <?php else: ?>

            <!-- สถิติรูปภาพ -->
            <div class="stats">
                <div class="stat-item">
                    <div class="stat-number"><?= count($imageData) ?></div>
                    <div>รายการที่มีรูปภาพ</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">
                        <?= array_sum(array_map(function($item) { 
                            return $item['damage_images'] ? count(json_decode($item['damage_images'], true)) : 0; 
                        }, $imageData)) ?>
                    </div>
                    <div>จำนวนรูปภาพทั้งหมด</div>
                </div>
            </div>

            <?php if (empty($imageData)): ?>
                <div style="text-align: center; padding: 60px; color: #718096;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📷</div>
                    <h2>ยังไม่มีรูปภาพประกอบ</h2>
                    <p>เมื่อมีผู้ตอบแบบสอบถามและแนบรูปภาพ จะแสดงที่นี่</p>
                </div>
            <?php else: ?>

            <!-- แกลเลอรี่รูปภาพ -->
            <div class="image-gallery">
                <?php foreach ($imageData as $item): ?>
                    <?php $images = json_decode($item['damage_images'], true); ?>
                    <?php if ($images): ?>
                    <div class="image-card">
                        <div class="image-header">
                            <div class="image-info">
                                📅 <?= date('d/m/Y H:i', strtotime($item['created_at'])) ?>
                            </div>
                            <div class="image-info">
                                👤 <?= htmlspecialchars($item['respondent_type']) ?>
                            </div>
                            <div class="image-info">
                                🏠 <span class="damage-status <?= getDamageClass($item['house_damage']) ?>">
                                    <?= htmlspecialchars($item['house_damage']) ?>
                                </span>
                                🚗 <span class="damage-status <?= getDamageClass($item['vehicle_damage']) ?>">
                                    <?= htmlspecialchars($item['vehicle_damage']) ?>
                                </span>
                            </div>
                            <div class="image-info">
                                💰 <?= htmlspecialchars($item['total_damage_cost'] ?? 'ไม่ระบุ') ?>
                            </div>
                        </div>
                        
                        <div class="image-grid">
                            <?php foreach ($images as $index => $image): ?>
                            <div class="image-item" onclick="openImageModal('uploads/<?= htmlspecialchars($image) ?>')">
                                <img src="uploads/<?= htmlspecialchars($image) ?>" alt="รูปภาพ <?= $index + 1 ?>" loading="lazy">
                                <div class="image-overlay">
                                    <span>คลิกเพื่อดูขนาดเต็ม</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <?php endif; ?>

            <div class="navigation-links">
                <a href="survey.php" class="nav-link">← กลับไปแบบสอบถาม</a>
                <a href="report.php" class="nav-link">📊 ดูรายงานสรุป</a>
                <a href="debug.php" class="nav-link">🔧 Debug</a>
                <button onclick="window.print()" class="btn">🖨️ พิมพ์หน้านี้</button>
            </div>

            <?php endif; ?>
        </div>

        <!-- Modal สำหรับแสดงรูปภาพขนาดเต็ม -->
        <div id="fullImageModal" class="modal" onclick="closeFullImageModal()">
            <span class="close" onclick="closeFullImageModal()">&times;</span>
            <div class="modal-content">
                <img id="fullImage" src="" alt="รูปภาพขนาดเต็ม">
            </div>
        </div>
    </div>

    <script>
        // ฟังก์ชันเปิดรูปภาพขนาดเต็ม
        function openImageModal(imageSrc) {
            document.getElementById('fullImage').src = imageSrc;
            document.getElementById('fullImageModal').style.display = 'block';
        }

        // ฟังก์ชันปิด Modal
        function closeFullImageModal() {
            document.getElementById('fullImageModal').style.display = 'none';
        }

        // ปิด modal เมื่อกด ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeFullImageModal();
            }
        });
    </script>
</body>
</html>

<?php
// ฟังก์ชันกำหนดคลาส CSS ตามระดับความเสียหาย
function getDamageClass($damage) {
    switch ($damage) {
        case 'เสียหายหนัก':
            return 'damage-high';
        case 'เสียหายปานกลาง':
            return 'damage-medium';
        case 'เสียหายเล็กน้อย':
            return 'damage-low';
        case 'ไม่เสียหาย':
        case 'ไม่มียานพาหนะ':
            return 'no-damage';
        default:
            return '';
    }
}
?>