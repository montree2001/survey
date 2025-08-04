<?php
// report.php - หน้ารายงานสรุปผล (อัพเดทแล้ว)
require_once 'config.php';

try {
    // สถิติทั่วไป
    $totalResponses = $pdo->query("SELECT COUNT(*) FROM survey_responses")->fetchColumn();
    
    // สถิติกลุ่มผู้ตอบ
    $respondentStats = $pdo->query("
        SELECT respondent_type, COUNT(*) as count 
        FROM survey_responses 
        GROUP BY respondent_type 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติความเสียหายบ้าน
    $houseDamageStats = $pdo->query("
        SELECT house_damage, COUNT(*) as count 
        FROM survey_responses 
        GROUP BY house_damage 
        ORDER BY FIELD(house_damage, 'เสียหายหนัก', 'เสียหายปานกลาง', 'เสียหายเล็กน้อย', 'ไม่เสียหาย')
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติยานพาหนะ
    $vehicleDamageStats = $pdo->query("
        SELECT vehicle_damage, COUNT(*) as count 
        FROM survey_responses 
        GROUP BY vehicle_damage 
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติระยะห่างจากชายแดน
    $distanceStats = $pdo->query("
        SELECT border_distance, COUNT(*) as count 
        FROM survey_responses 
        GROUP BY border_distance 
        ORDER BY FIELD(border_distance, 'น้อยกว่า 5 กม.', '5-20 กม.', '21-50 กม.', 'มากกว่า 50 กม.')
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติความเสียหายรวม
    $totalDamageStats = $pdo->query("
        SELECT total_damage_cost, COUNT(*) as count 
        FROM survey_responses 
        WHERE total_damage_cost IS NOT NULL
        GROUP BY total_damage_cost 
        ORDER BY FIELD(total_damage_cost, 'น้อยกว่า 50,000 บาท', '50,001-100,000 บาท', '100,001-300,000 บาท', '300,001-500,000 บาท', '500,001-1,000,000 บาท', 'มากกว่า 1,000,000 บาท')
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติประกันภัย
    $insuranceStats = $pdo->query("
        SELECT has_insurance, COUNT(*) as count 
        FROM survey_responses 
        WHERE has_insurance IS NOT NULL
        GROUP BY has_insurance
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติการเกษตร
    $agricultureStats = $pdo->query("
        SELECT 
            SUM(CASE WHEN crop_damage NOT IN ('ไม่เสียหาย', 'ไม่มี') THEN 1 ELSE 0 END) as crop_affected,
            SUM(CASE WHEN livestock_impact NOT IN ('ไม่ได้รับผลกระทบ', 'ไม่มี') THEN 1 ELSE 0 END) as livestock_affected,
            SUM(CASE WHEN farm_structure_damage NOT IN ('ไม่เสียหาย', 'ไม่มี') THEN 1 ELSE 0 END) as structure_affected
        FROM survey_responses
    ")->fetch(PDO::FETCH_ASSOC);

    // สถิติตามอายุ
    $ageStats = $pdo->query("
        SELECT 
            CASE 
                WHEN age < 18 THEN 'ต่ำกว่า 18 ปี'
                WHEN age BETWEEN 18 AND 25 THEN '18-25 ปี'
                WHEN age BETWEEN 26 AND 35 THEN '26-35 ปี'
                WHEN age BETWEEN 36 AND 50 THEN '36-50 ปี'
                WHEN age > 50 THEN 'มากกว่า 50 ปี'
            END as age_group,
            COUNT(*) as count
        FROM survey_responses 
        GROUP BY age_group
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // ข้อมูลรายละเอียดล่าสุด (เพิ่มข้อมูลส่วนตัว)
    $recentResponses = $pdo->query("
        SELECT respondent_type, age, gender, border_distance, 
               first_name, last_name, phone_number, address,
               house_damage, vehicle_damage, total_damage_cost, damage_images, created_at
        FROM survey_responses 
        ORDER BY created_at DESC 
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติผู้ได้รับความเสียหาย
    $damageContactStats = $pdo->query("
        SELECT 
            COUNT(*) as total_with_damage,
            COUNT(CASE WHEN first_name IS NOT NULL AND last_name IS NOT NULL THEN 1 END) as with_contact_info
        FROM survey_responses 
        WHERE (house_damage != 'ไม่เสียหาย' 
               OR vehicle_damage NOT IN ('ไม่เสียหาย', 'ไม่มียานพาหนะ')
               OR appliance_damage != 'ไม่เสียหาย'
               OR crop_damage NOT IN ('ไม่เสียหาย', 'ไม่มี')
               OR livestock_impact NOT IN ('ไม่ได้รับผลกระทบ', 'ไม่มี')
               OR farm_structure_damage NOT IN ('ไม่เสียหาย', 'ไม่มี'))
    ")->fetch(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $errorMessage = "ไม่สามารถดึงข้อมูลได้: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุปผลแบบสอบถาม - วิทยาลัยการอาชีพปราสาท</title>
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

        .header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .content {
            padding: 40px;
        }

        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: linear-gradient(45deg, #4299e1, #3182ce);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            opacity: 0.9;
        }

        .contact-info-card {
            background: linear-gradient(45deg, #38a169, #2f855a);
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .chart-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s, box-shadow 0.3s;
            min-height: 400px;
            display: flex;
            flex-direction: column;
        }

        .chart-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f9ff;
        }

        .chart-container {
            flex: 1;
            min-height: 300px;
            width: 100%;
        }

        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .table-header {
            background: #f8fafc;
            padding: 20px;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        th {
            background: #f8fafc;
            font-weight: 600;
            color: #4a5568;
        }

        tr:hover {
            background: #f8fafc;
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

        .btn-print {
            background: linear-gradient(45deg, #38a169, #2f855a);
        }

        .btn-export {
            background: linear-gradient(45deg, #ed8936, #dd6b20);
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

        .summary-section {
            background: #f0f9ff;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            border-left: 4px solid #4299e1;
        }

        .summary-title {
            font-size: 20px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 15px;
        }

        .highlight-stat {
            display: inline-block;
            background: #4299e1;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            margin: 0 5px;
        }

        .contact-info {
            background: #e6fffa;
            border: 1px solid #38b2ac;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .hidden-data {
            color: #999;
            font-style: italic;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .chart-card {
                min-height: 350px;
                padding: 15px;
            }
            
            .chart-title {
                font-size: 16px;
                margin-bottom: 10px;
            }
            
            .chart-container {
                min-height: 250px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }

        @media (max-width: 480px) {
            .charts-container {
                gap: 15px;
            }
            
            .chart-card {
                min-height: 300px;
                padding: 12px;
            }
            
            .chart-container {
                min-height: 200px;
            }
            
            .stats-overview {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .chart-loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 300px;
            flex-direction: column;
            color: #718096;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #e2e8f0;
            border-top: 4px solid #4299e1;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Chart Animation Classes */
        .chart-container {
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stagger animation for multiple charts */
        .chart-card:nth-child(1) .chart-container { animation-delay: 0.1s; }
        .chart-card:nth-child(2) .chart-container { animation-delay: 0.2s; }
        .chart-card:nth-child(3) .chart-container { animation-delay: 0.3s; }
        .chart-card:nth-child(4) .chart-container { animation-delay: 0.4s; }
        .chart-card:nth-child(5) .chart-container { animation-delay: 0.5s; }
        .chart-card:nth-child(6) .chart-container { animation-delay: 0.6s; }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border-radius: 0;
            }
            
            .navigation-links {
                display: none;
            }
            
            .chart-card {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>รายงานสรุปผลแบบสอบถาม</h1>
            <p>ผลกระทบจากการสู้รบชายแดนไทย-กัมพูชา</p>
            <p style="margin-top: 10px; font-size: 16px;">วิทยาลัยการอาชีพปราสาท</p>
        </div>

        <div class="content">
            <?php if (isset($errorMessage)): ?>
                <div class="alert-error">
                    <h2>เกิดข้อผิดพลาด!</h2>
                    <p><?= htmlspecialchars($errorMessage) ?></p>
                </div>
            <?php else: ?>

            <!-- สรุปสถิติรวม -->
            <div class="summary-section">
                <h2 class="summary-title">📊 สรุปผลการสำรวจ</h2>
                <p>จากการสำรวจผลกระทบการสู้รบชายแดนไทย-กัมพูชา พบว่า จำนวนผู้ตอบแบบสอบถามทั้งหมด 
                <span class="highlight-stat"><?= $totalResponses ?> คน</span> 
                โดยมีผู้ได้รับความเสียหายด้านที่อยู่อาศัย 
                <span class="highlight-stat"><?= $pdo->query("SELECT COUNT(*) FROM survey_responses WHERE house_damage != 'ไม่เสียหาย'")->fetchColumn() ?> คน</span> 
                และมีผู้ได้รับความเสียหายยานพาหนะ 
                <span class="highlight-stat"><?= $pdo->query("SELECT COUNT(*) FROM survey_responses WHERE vehicle_damage NOT IN ('ไม่เสียหาย', 'ไม่มียานพาหนะ')")->fetchColumn() ?> คน</span></p>
            </div>

            <!-- สถิติผู้ได้รับความเสียหายที่มีข้อมูลติดต่อ -->
            <?php if ($damageContactStats['total_with_damage'] > 0): ?>
            <div class="contact-info">
                <h3 style="color: #234e52; margin-bottom: 10px;">📞 ข้อมูลการติดต่อผู้ได้รับความเสียหาย</h3>
                <p>จากผู้ได้รับความเสียหายทั้งหมด <strong><?= $damageContactStats['total_with_damage'] ?> คน</strong> 
                มีผู้ที่กรอกข้อมูลติดต่อครบถ้วน <strong><?= $damageContactStats['with_contact_info'] ?> คน</strong> 
                (<?= $damageContactStats['total_with_damage'] > 0 ? round(($damageContactStats['with_contact_info'] / $damageContactStats['total_with_damage']) * 100, 1) : 0 ?>%)</p>
            </div>
            <?php endif; ?>

            <!-- สถิติภาพรวม -->
            <div class="stats-overview">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalResponses ?></div>
                    <div class="stat-label">จำนวนผู้ตอบทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= $pdo->query("SELECT COUNT(*) FROM survey_responses WHERE house_damage != 'ไม่เสียหาย'")->fetchColumn() ?>
                    </div>
                    <div class="stat-label">ผู้ได้รับความเสียหายบ้าน</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= $pdo->query("SELECT COUNT(*) FROM survey_responses WHERE vehicle_damage NOT IN ('ไม่เสียหาย', 'ไม่มียานพาหนะ')")->fetchColumn() ?>
                    </div>
                    <div class="stat-label">ผู้ได้รับความเสียหายยานพาหนะ</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= $agricultureStats['crop_affected'] ?? 0 ?>
                    </div>
                    <div class="stat-label">ผู้ได้รับผลกระทบด้านเกษตร</div>
                </div>
                <div class="stat-card contact-info-card">
                    <div class="stat-number">
                        <?= $damageContactStats['with_contact_info'] ?? 0 ?>
                    </div>
                    <div class="stat-label">ผู้ได้รับความเสียหายที่มีข้อมูลติดต่อ</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">
                        <?= $pdo->query("SELECT COUNT(*) FROM survey_responses WHERE border_distance = 'น้อยกว่า 5 กม.'")->fetchColumn() ?>
                    </div>
                    <div class="stat-label">อยู่ใกล้ชายแดน < 5 กม.</div>
                </div>
            </div>

            <!-- กราฟและชาร์ต -->
            <div class="charts-container">
                <!-- กราฟกลุ่มผู้ตอบ -->
                <div class="chart-card">
                    <h3 class="chart-title">🏢 กลุ่มผู้ตอบแบบสอบถาม</h3>
                    <div id="respondentChart" class="chart-container"></div>
                </div>

                <!-- กราฟความเสียหายบ้าน -->
                <div class="chart-card">
                    <h3 class="chart-title">🏠 ระดับความเสียหายของบ้านและอาคาร</h3>
                    <div id="houseDamageChart" class="chart-container"></div>
                </div>

                <!-- กราฟระยะห่างจากชายแดน -->
                <div class="chart-card">
                    <h3 class="chart-title">📍 ระยะห่างจากชายแดน</h3>
                    <div id="distanceChart" class="chart-container"></div>
                </div>

                <!-- กราฟช่วงอายุ -->
                <div class="chart-card">
                    <h3 class="chart-title">👥 กลุ่มอายุผู้ตอบ</h3>
                    <div id="ageChart" class="chart-container"></div>
                </div>

                <!-- กราฟความเสียหายรวม -->
                <?php if (!empty($totalDamageStats)): ?>
                <div class="chart-card">
                    <h3 class="chart-title">💰 การกระจายของมูลค่าความเสียหาย</h3>
                    <div id="damageDistributionChart" class="chart-container"></div>
                </div>
                <?php endif; ?>

                <!-- กราฟประกันภัย -->
                <?php if (!empty($insuranceStats)): ?>
                <div class="chart-card">
                    <h3 class="chart-title">🛡️ สถานะการมีประกันภัย</h3>
                    <div id="insuranceChart" class="chart-container"></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ตารางสรุปความเสียหายรวม -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">💰 มูลค่าความเสียหายรวม</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ช่วงมูลค่า</th>
                            <th style="text-align: center;">จำนวนคน</th>
                            <th style="text-align: center;">เปอร์เซ็นต์</th>
                            <th style="text-align: center;">สัดส่วน</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($totalDamageStats)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #718096; padding: 30px;">
                                ยังไม่มีข้อมูลการประเมินความเสียหาย
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php 
                        $totalDamageResponses = array_sum(array_column($totalDamageStats, 'count'));
                        foreach ($totalDamageStats as $stat): 
                        ?>
                        <tr>
                            <td style="font-weight: 500;"><?= htmlspecialchars($stat['total_damage_cost']) ?></td>
                            <td style="text-align: center; font-weight: 600; color: #2d3748;"><?= $stat['count'] ?></td>
                            <td style="text-align: center;">
                                <span style="background: #4299e1; color: white; padding: 4px 8px; border-radius: 12px; font-size: 14px;">
                                    <?= $totalDamageResponses > 0 ? round(($stat['count'] / $totalDamageResponses) * 100, 1) : 0 ?>%
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div style="background: #e2e8f0; border-radius: 10px; overflow: hidden; height: 20px; width: 100px; margin: 0 auto;">
                                    <div style="background: #4299e1; height: 100%; width: <?= $totalDamageResponses > 0 ? ($stat['count'] / $totalDamageResponses) * 100 : 0 ?>%; transition: width 0.3s;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ตารางข้อมูลล่าสุด -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">📋 ข้อมูลการตอบล่าสุด (10 รายการ)</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>วันที่ตอบ</th>
                            <th>กลุ่มผู้ตอบ</th>
                            <th>อายุ</th>
                            <th>เพศ</th>
                            <th>ข้อมูลติดต่อ</th>
                            <th>ระยะห่างชายแดน</th>
                            <th>ความเสียหายบ้าน</th>
                            <th>ความเสียหายยานพาหนะ</th>
                            <th>มูลค่าความเสียหายรวม</th>
                            <th>รูปภาพประกอบ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentResponses)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: #718096; padding: 30px;">
                                ยังไม่มีข้อมูลการตอบแบบสอบถาม
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentResponses as $response): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($response['created_at'])) ?></td>
                            <td><?= htmlspecialchars($response['respondent_type']) ?></td>
                            <td style="text-align: center;"><?= $response['age'] ?></td>
                            <td><?= htmlspecialchars($response['gender']) ?></td>
                            <td>
                                <?php if ($response['first_name'] || $response['last_name']): ?>
                                    <div style="font-size: 12px;">
                                        <strong><?= htmlspecialchars($response['first_name'] . ' ' . $response['last_name']) ?></strong><br>
                                        <?php if ($response['phone_number']): ?>
                                            📞 <?= htmlspecialchars($response['phone_number']) ?><br>
                                        <?php endif; ?>
                                        <?php if ($response['address']): ?>
                                            🏠 <?= htmlspecialchars(mb_substr($response['address'], 0, 30)) ?><?= mb_strlen($response['address']) > 30 ? '...' : '' ?>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="hidden-data">ไม่มีข้อมูลติดต่อ</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($response['border_distance']) ?></td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 12px; font-size: 12px; 
                                    background: <?= $response['house_damage'] === 'ไม่เสียหาย' ? '#c6f6d5' : '#fed7d7' ?>; 
                                    color: <?= $response['house_damage'] === 'ไม่เสียหาย' ? '#22543d' : '#742a2a' ?>;">
                                    <?= htmlspecialchars($response['house_damage']) ?>
                                </span>
                            </td>
                            <td>
                                <span style="padding: 3px 8px; border-radius: 12px; font-size: 12px; 
                                    background: <?= in_array($response['vehicle_damage'], ['ไม่เสียหาย', 'ไม่มียานพาหนะ']) ? '#c6f6d5' : '#fed7d7' ?>; 
                                    color: <?= in_array($response['vehicle_damage'], ['ไม่เสียหาย', 'ไม่มียานพาหนะ']) ? '#22543d' : '#742a2a' ?>;">
                                    <?= htmlspecialchars($response['vehicle_damage']) ?>
                                </span>
                            </td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($response['total_damage_cost'] ?? 'ไม่มีความเสียหาย') ?></td>
                            <td style="text-align: center;">
                                <?php if ($response['damage_images']): ?>
                                    <?php $images = json_decode($response['damage_images'], true); ?>
                                    <button onclick="showImages(<?= htmlspecialchars(json_encode($images)) ?>)" 
                                            style="background: #4299e1; color: white; border: none; padding: 5px 10px; border-radius: 4px; cursor: pointer; font-size: 12px;">
                                        📷 <?= count($images) ?> ภาพ
                                    </button>
                                <?php else: ?>
                                    <span style="color: #718096; font-size: 12px;">ไม่มีรูป</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="navigation-links">
                <a href="survey.php" class="nav-link">← กลับไปแบบสอบถาม</a>
                <button onclick="window.print()" class="btn btn-print">🖨️ พิมพ์รายงาน</button>
                <button onclick="exportToCSV()" class="btn btn-export">📊 ส่งออก CSV</button>
                <button onclick="refreshData()" class="btn">🔄 รีเฟรชข้อมูล</button>
            </div>

            <?php endif; ?>
        </div>

        <!-- Modal สำหรับแสดงรูปภาพ -->
        <div id="imageModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8);" onclick="closeImageModal()">
            <div style="position: relative; margin: 5% auto; width: 90%; max-width: 800px; background: white; border-radius: 10px; padding: 20px;">
                <span onclick="closeImageModal()" style="position: absolute; right: 15px; top: 10px; font-size: 28px; font-weight: bold; cursor: pointer; color: #666;">&times;</span>
                <h3 style="margin-bottom: 20px; color: #2d3748;">📷 รูปภาพประกอบความเสียหาย</h3>
                <div id="modalImageContainer" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                </div>
            </div>
        </div>
    </div>

    <!-- ApexCharts สำหรับกราฟที่สวยงาม -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // ข้อมูลสำหรับกราฟ
        const respondentData = <?= json_encode($respondentStats ?? []) ?>;
        const houseDamageData = <?= json_encode($houseDamageStats ?? []) ?>;
        const distanceData = <?= json_encode($distanceStats ?? []) ?>;
        const ageData = <?= json_encode($ageStats ?? []) ?>;
        const totalDamageData = <?= json_encode($totalDamageStats ?? []) ?>;
        const insuranceData = <?= json_encode($insuranceStats ?? []) ?>;

        // ธีมสีที่สวยงาม
        const colorPalette = [
            '#4F46E5', '#06B6D4', '#10B981', '#F59E0B', 
            '#EF4444', '#8B5CF6', '#EC4899', '#6B7280'
        ];

        const gradientColors = [
            ['#667eea', '#764ba2'],
            ['#f093fb', '#f5576c'],
            ['#4facfe', '#00f2fe'],
            ['#43e97b', '#38f9d7'],
            ['#fa709a', '#fee140'],
            ['#a8edea', '#fed6e3'],
            ['#ff9a9e', '#fecfef'],
            ['#ffecd2', '#fcb69f']
        ];

        // การตั้งค่าพื้นฐานสำหรับทุกกราฟ
        const baseChartOptions = {
            chart: {
                fontFamily: 'Sarabun, Arial, sans-serif',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800,
                    animateGradually: {
                        enabled: true,
                        delay: 150
                    },
                    dynamicAnimation: {
                        enabled: true,
                        speed: 350
                    }
                },
                toolbar: {
                    show: false
                }
            },
            theme: {
                mode: 'light',
                palette: 'palette1'
            },
            legend: {
                position: 'bottom',
                fontSize: '14px',
                fontWeight: 500,
                markers: {
                    width: 12,
                    height: 12,
                    radius: 6
                }
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontSize: '14px'
                }
            }
        };

        // กราฟกลุ่มผู้ตอบ (Donut Chart)
        if (respondentData.length > 0) {
            const respondentOptions = {
                ...baseChartOptions,
                series: respondentData.map(d => d.count),
                labels: respondentData.map(d => d.respondent_type),
                chart: {
                    ...baseChartOptions.chart,
                    type: 'donut',
                    height: 350
                },
                colors: colorPalette.slice(0, respondentData.length),
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '16px',
                                    fontWeight: 600,
                                    color: '#2d3748'
                                },
                                value: {
                                    show: true,
                                    fontSize: '24px',
                                    fontWeight: 700,
                                    color: '#4299e1'
                                },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'ทั้งหมด',
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#718096'
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return Math.round(val) + "%"
                    },
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    },
                    dropShadow: {
                        enabled: true
                    }
                }
            };

            const respondentChart = new ApexCharts(document.querySelector("#respondentChart"), respondentOptions);
            respondentChart.render();
        }

        // กราฟความเสียหายบ้าน (Column Chart)
        if (houseDamageData.length > 0) {
            const houseDamageOptions = {
                ...baseChartOptions,
                series: [{
                    name: 'จำนวนคน',
                    data: houseDamageData.map(d => d.count)
                }],
                chart: {
                    ...baseChartOptions.chart,
                    type: 'column',
                    height: 350
                },
                colors: ['#4F46E5'],
                xaxis: {
                    categories: houseDamageData.map(d => d.house_damage),
                    labels: {
                        style: {
                            fontSize: '12px',
                            fontWeight: 500,
                            colors: '#4a5568'
                        },
                        rotate: -45
                    }
                },
                yaxis: {
                    title: {
                        text: 'จำนวนคน',
                        style: {
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#4a5568'
                        }
                    },
                    labels: {
                        style: {
                            fontSize: '12px',
                            colors: '#4a5568'
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        columnWidth: '60%',
                        distributed: false
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        shadeIntensity: 0.3,
                        gradientToColors: ['#06B6D4'],
                        inverseColors: false,
                        opacityFrom: 0.9,
                        opacityTo: 0.7
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 5
                }
            };

            const houseDamageChart = new ApexCharts(document.querySelector("#houseDamageChart"), houseDamageOptions);
            houseDamageChart.render();
        }

        // กราฟระยะห่างจากชายแดน (Pie Chart)
        if (distanceData.length > 0) {
            const distanceOptions = {
                ...baseChartOptions,
                series: distanceData.map(d => d.count),
                labels: distanceData.map(d => d.border_distance),
                chart: {
                    ...baseChartOptions.chart,
                    type: 'pie',
                    height: 350
                },
                colors: ['#EF4444', '#F59E0B', '#10B981', '#06B6D4'],
                plotOptions: {
                    pie: {
                        expandOnClick: true,
                        donut: {
                            labels: {
                                show: false
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val, opts) {
                        return opts.w.config.series[opts.seriesIndex] + " คน"
                    },
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    },
                    dropShadow: {
                        enabled: true
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        chart: {
                            width: 280
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }]
            };

            const distanceChart = new ApexCharts(document.querySelector("#distanceChart"), distanceOptions);
            distanceChart.render();
        }

        // กราฟช่วงอายุ (Bar Chart)
        if (ageData.length > 0) {
            const ageOptions = {
                ...baseChartOptions,
                series: [{
                    name: 'จำนวนคน',
                    data: ageData.map(d => d.count)
                }],
                chart: {
                    ...baseChartOptions.chart,
                    type: 'bar',
                    height: 350
                },
                colors: ['#8B5CF6'],
                xaxis: {
                    categories: ageData.map(d => d.age_group),
                    labels: {
                        style: {
                            fontSize: '12px',
                            fontWeight: 500,
                            colors: '#4a5568'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'จำนวนคน',
                        style: {
                            fontSize: '14px',
                            fontWeight: 600,
                            color: '#4a5568'
                        }
                    },
                    labels: {
                        style: {
                            fontSize: '12px',
                            colors: '#4a5568'
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        horizontal: true,
                        barHeight: '60%'
                    }
                },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'horizontal',
                        shadeIntensity: 0.3,
                        gradientToColors: ['#EC4899'],
                        inverseColors: false,
                        opacityFrom: 0.9,
                        opacityTo: 0.7
                    }
                },
                dataLabels: {
                    enabled: true,
                    style: {
                        fontSize: '12px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    }
                },
                grid: {
                    borderColor: '#e2e8f0',
                    strokeDashArray: 5
                }
            };

            const ageChart = new ApexCharts(document.querySelector("#ageChart"), ageOptions);
            ageChart.render();
        }

        // กราฟการกระจายมูลค่าความเสียหาย (Radial Bar Chart)
        if (totalDamageData.length > 0) {
            const totalDamageOptions = {
                ...baseChartOptions,
                series: totalDamageData.map(d => {
                    const total = totalDamageData.reduce((sum, item) => sum + item.count, 0);
                    return Math.round((d.count / total) * 100);
                }),
                labels: totalDamageData.map(d => d.total_damage_cost),
                chart: {
                    ...baseChartOptions.chart,
                    type: 'radialBar',
                    height: 400
                },
                plotOptions: {
                    radialBar: {
                        offsetY: 0,
                        startAngle: 0,
                        endAngle: 270,
                        hollow: {
                            margin: 5,
                            size: '30%',
                            background: 'transparent',
                            image: undefined,
                        },
                        dataLabels: {
                            name: {
                                show: false,
                            },
                            value: {
                                show: false,
                            }
                        }
                    }
                },
                colors: colorPalette.slice(0, totalDamageData.length),
                legend: {
                    show: true,
                    floating: true,
                    fontSize: '12px',
                    position: 'left',
                    offsetX: 10,
                    offsetY: 15,
                    labels: {
                        useSeriesColors: true,
                    },
                    markers: {
                        size: 0
                    },
                    formatter: function(seriesName, opts) {
                        return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex] + "%"
                    },
                    itemMargin: {
                        vertical: 3
                    }
                },
                responsive: [{
                    breakpoint: 480,
                    options: {
                        legend: {
                            show: false
                        }
                    }
                }]
            };

            const damageDistributionChart = new ApexCharts(document.querySelector("#damageDistributionChart"), totalDamageOptions);
            damageDistributionChart.render();
        }

        // กราฟสถานะประกันภัย (Donut Chart)
        if (insuranceData.length > 0) {
            const insuranceOptions = {
                ...baseChartOptions,
                series: insuranceData.map(d => d.count),
                labels: insuranceData.map(d => d.has_insurance),
                chart: {
                    ...baseChartOptions.chart,
                    type: 'donut',
                    height: 350
                },
                colors: ['#10B981', '#F59E0B', '#EF4444'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                name: {
                                    show: true,
                                    fontSize: '14px',
                                    fontWeight: 600,
                                    color: '#2d3748'
                                },
                                value: {
                                    show: true,
                                    fontSize: '20px',
                                    fontWeight: 700,
                                    color: '#4299e1'
                                },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'ทั้งหมด',
                                    fontSize: '12px',
                                    fontWeight: 600,
                                    color: '#718096'
                                }
                            }
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) {
                        return Math.round(val) + "%"
                    },
                    style: {
                        fontSize: '11px',
                        fontWeight: 'bold',
                        colors: ['#fff']
                    }
                }
            };

            const insuranceChart = new ApexCharts(document.querySelector("#insuranceChart"), insuranceOptions);
            insuranceChart.render();
        }

        // ฟังก์ชันส่งออกเป็น CSV
        function exportToCSV() {
            // แสดง loading
            const button = event.target;
            const originalText = button.textContent;
            button.textContent = '📊 กำลังเตรียมข้อมูล...';
            button.disabled = true;
            
            setTimeout(() => {
                window.open('export_csv.php', '_blank');
                button.textContent = originalText;
                button.disabled = false;
            }, 1000);
        }

        // ฟังก์ชันรีเฟรชข้อมูล
        function refreshData() {
            // แสดง loading overlay
            const overlay = document.createElement('div');
            overlay.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(255,255,255,0.9); z-index: 9999;
                display: flex; justify-content: center; align-items: center;
                flex-direction: column;
            `;
            overlay.innerHTML = `
                <div class="loading-spinner"></div>
                <p style="color: #4a5568; font-size: 18px; font-weight: 500;">กำลังรีเฟรชข้อมูล...</p>
            `;
            document.body.appendChild(overlay);
            
            setTimeout(() => {
                location.reload();
            }, 1500);
        }

        // ฟังก์ชันแสดงรูปภาพ
        function showImages(images) {
            const modal = document.getElementById('imageModal');
            const container = document.getElementById('modalImageContainer');
            
            container.innerHTML = '<div class="chart-loading"><div class="loading-spinner"></div><p>กำลังโหลดรูปภาพ...</p></div>';
            modal.style.display = 'block';
            
            setTimeout(() => {
                container.innerHTML = '';
                
                images.forEach((image, index) => {
                    const imageDiv = document.createElement('div');
                    imageDiv.style.textAlign = 'center';
                    imageDiv.innerHTML = `
                        <img src="uploads/${image}" 
                             alt="รูปภาพ ${index + 1}" 
                             style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; cursor: pointer;
                                    transition: transform 0.3s; box-shadow: 0 4px 8px rgba(0,0,0,0.1);"
                             onclick="openFullImage('uploads/${image}')"
                             onmouseover="this.style.transform='scale(1.05)'"
                             onmouseout="this.style.transform='scale(1)'">
                        <p style="margin-top: 8px; font-size: 14px; color: #666; font-weight: 500;">รูปภาพ ${index + 1}</p>
                    `;
                    container.appendChild(imageDiv);
                });
            }, 800);
        }

        // ฟังก์ชันปิด Modal
        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.animation = 'fadeOut 0.3s ease-out';
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.animation = '';
            }, 300);
        }

        // ฟังก์ชันเปิดรูปภาพขนาดเต็ม
        function openFullImage(imageSrc) {
            window.open(imageSrc, '_blank');
        }

        // ฟังก์ชันอัพเดทเวลาล่าสุด
        function updateLastUpdated() {
            const now = new Date();
            const options = {
                year: 'numeric',
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                timeZone: 'Asia/Bangkok'
            };
            const timeString = now.toLocaleDateString('th-TH', options);
            const timeElement = document.getElementById('lastUpdated');
            if (timeElement) {
                timeElement.innerHTML = `⏰ อัพเดทล่าสุด: ${timeString}`;
            }
        }

        // ฟังก์ชันสำหรับการแสดง loading ก่อนโหลดกราฟ
        function showChartLoading(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = `
                    <div class="chart-loading">
                        <div class="loading-spinner"></div>
                        <p>กำลังโหลดกราฟ...</p>
                    </div>
                `;
            }
        }

        // เริ่มต้นการทำงาน
        document.addEventListener('DOMContentLoaded', function() {
            // แสดง loading สำหรับกราฟที่มีข้อมูล
            if (respondentData.length > 0) showChartLoading('respondentChart');
            if (houseDamageData.length > 0) showChartLoading('houseDamageChart');
            if (distanceData.length > 0) showChartLoading('distanceChart');
            if (ageData.length > 0) showChartLoading('ageChart');
            if (totalDamageData.length > 0) showChartLoading('damageDistributionChart');
            if (insuranceData.length > 0) showChartLoading('insuranceChart');
            
            // เพิ่มข้อมูลเวลาล่าสุดในส่วนหัว
            const header = document.querySelector('.header');
            const timeElement = document.createElement('p');
            timeElement.id = 'lastUpdated';
            timeElement.style.cssText = `
                margin-top: 15px; font-size: 14px; opacity: 0.8;
                background: rgba(255,255,255,0.1); padding: 8px 16px;
                border-radius: 20px; display: inline-block;
            `;
            header.appendChild(timeElement);
            updateLastUpdated();
            
            // อัพเดทเวลาทุก ๆ 60 วินาที
            setInterval(updateLastUpdated, 60000);
            
            // เพิ่ม smooth scrolling สำหรับลิงก์
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        });

        // เพิ่ม CSS animation สำหรับ modal
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; transform: scale(0.9); }
                to { opacity: 1; transform: scale(1); }
            }
            @keyframes fadeOut {
                from { opacity: 1; transform: scale(1); }
                to { opacity: 0; transform: scale(0.9); }
            }
            #imageModal {
                animation: fadeIn 0.3s ease-out;
            }
            .stat-card {
                transition: all 0.3s ease;
            }
            .stat-card:hover {
                transform: translateY(-8px) scale(1.02);
                box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>