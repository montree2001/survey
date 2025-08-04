<?php
// report.php - หน้ารายงานสรุปผล
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
        GROUP BY total_damage_cost 
        ORDER BY FIELD(total_damage_cost, 'น้อยกว่า 50,000 บาท', '50,001-100,000 บาท', '100,001-300,000 บาท', '300,001-500,000 บาท', '500,001-1,000,000 บาท', 'มากกว่า 1,000,000 บาท')
    ")->fetchAll(PDO::FETCH_ASSOC);

    // สถิติประกันภัย
    $insuranceStats = $pdo->query("
        SELECT has_insurance, COUNT(*) as count 
        FROM survey_responses 
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

    // ข้อมูลรายละเอียดล่าสุด
    $recentResponses = $pdo->query("
        SELECT respondent_type, age, gender, border_distance, house_damage, vehicle_damage, total_damage_cost, damage_images, created_at
        FROM survey_responses 
        ORDER BY created_at DESC 
        LIMIT 10
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

        .charts-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }

        .chart-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border: 1px solid #e2e8f0;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 20px;
            text-align: center;
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

        @media (max-width: 768px) {
            .charts-container {
                grid-template-columns: 1fr;
            }
            
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }

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
                <div class="stat-card">
                    <div class="stat-number">
                        <?= $pdo->query("SELECT COUNT(*) FROM survey_responses WHERE has_insurance LIKE 'มี%'")->fetchColumn() ?>
                    </div>
                    <div class="stat-label">ผู้มีประกันภัย</div>
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
                    <canvas id="respondentChart" width="400" height="300"></canvas>
                </div>

                <!-- กราฟความเสียหายบ้าน -->
                <div class="chart-card">
                    <h3 class="chart-title">🏠 ระดับความเสียหายของบ้านและอาคาร</h3>
                    <canvas id="houseDamageChart" width="400" height="300"></canvas>
                </div>

                <!-- กราฟระยะห่างจากชายแดน -->
                <div class="chart-card">
                    <h3 class="chart-title">📍 ระยะห่างจากชายแดน</h3>
                    <canvas id="distanceChart" width="400" height="300"></canvas>
                </div>

                <!-- กราฟช่วงอายุ -->
                <div class="chart-card">
                    <h3 class="chart-title">👥 กลุ่มอายุผู้ตอบ</h3>
                    <canvas id="ageChart" width="400" height="300"></canvas>
                </div>
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
                                ยังไม่มีข้อมูลการตอบแบบสอบถาม
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($totalDamageStats as $stat): ?>
                        <tr>
                            <td style="font-weight: 500;"><?= htmlspecialchars($stat['total_damage_cost']) ?></td>
                            <td style="text-align: center; font-weight: 600; color: #2d3748;"><?= $stat['count'] ?></td>
                            <td style="text-align: center;">
                                <span style="background: #4299e1; color: white; padding: 4px 8px; border-radius: 12px; font-size: 14px;">
                                    <?= $totalResponses > 0 ? round(($stat['count'] / $totalResponses) * 100, 1) : 0 ?>%
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <div style="background: #e2e8f0; border-radius: 10px; overflow: hidden; height: 20px; width: 100px; margin: 0 auto;">
                                    <div style="background: #4299e1; height: 100%; width: <?= $totalResponses > 0 ? ($stat['count'] / $totalResponses) * 100 : 0 ?>%; transition: width 0.3s;"></div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- ตารางสถิติการเกษตร -->
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">🌾 สถิติผลกระทบด้านการเกษตร</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ประเภทความเสียหาย</th>
                            <th style="text-align: center;">จำนวนผู้ได้รับผลกระทบ</th>
                            <th style="text-align: center;">เปอร์เซ็นต์</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>พืชผล/สวน/ไร่นา</td>
                            <td style="text-align: center; font-weight: 600;"><?= $agricultureStats['crop_affected'] ?? 0 ?></td>
                            <td style="text-align: center;">
                                <?= $totalResponses > 0 ? round((($agricultureStats['crop_affected'] ?? 0) / $totalResponses) * 100, 1) : 0 ?>%
                            </td>
                        </tr>
                        <tr>
                            <td>ปศุสัตว์</td>
                            <td style="text-align: center; font-weight: 600;"><?= $agricultureStats['livestock_affected'] ?? 0 ?></td>
                            <td style="text-align: center;">
                                <?= $totalResponses > 0 ? round((($agricultureStats['livestock_affected'] ?? 0) / $totalResponses) * 100, 1) : 0 ?>%
                            </td>
                        </tr>
                        <tr>
                            <td>สิ่งปลูกสร้างทางการเกษตร</td>
                            <td style="text-align: center; font-weight: 600;"><?= $agricultureStats['structure_affected'] ?? 0 ?></td>
                            <td style="text-align: center;">
                                <?= $totalResponses > 0 ? round((($agricultureStats['structure_affected'] ?? 0) / $totalResponses) * 100, 1) : 0 ?>%
                            </td>
                        </tr>
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
                            <td colspan="9" style="text-align: center; color: #718096; padding: 30px;">
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

    <!-- Chart.js สำหรับกราฟ -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <script>
        // ข้อมูลสำหรับกราฟ
        const respondentData = <?= json_encode($respondentStats ?? []) ?>;
        const houseDamageData = <?= json_encode($houseDamageStats ?? []) ?>;
        const distanceData = <?= json_encode($distanceStats ?? []) ?>;
        const ageData = <?= json_encode($ageStats ?? []) ?>;

        // สีสำหรับกราฟ
        const colors = ['#4299e1', '#48bb78', '#ed8936', '#9f7aea', '#38b2ac', '#f56565'];
        const damageColors = ['#f56565', '#ed8936', '#ecc94b', '#48bb78'];

        // กราฟกลุ่มผู้ตอบ
        if (respondentData.length > 0) {
            const respondentCtx = document.getElementById('respondentChart').getContext('2d');
            new Chart(respondentCtx, {
                type: 'doughnut',
                data: {
                    labels: respondentData.map(d => d.respondent_type),
                    datasets: [{
                        data: respondentData.map(d => d.count),
                        backgroundColor: colors.slice(0, respondentData.length),
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} คน (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // กราฟความเสียหายบ้าน
        if (houseDamageData.length > 0) {
            const houseDamageCtx = document.getElementById('houseDamageChart').getContext('2d');
            new Chart(houseDamageCtx, {
                type: 'bar',
                data: {
                    labels: houseDamageData.map(d => d.house_damage),
                    datasets: [{
                        label: 'จำนวนคน',
                        data: houseDamageData.map(d => d.count),
                        backgroundColor: damageColors.slice(0, houseDamageData.length),
                        borderWidth: 1,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `จำนวน: ${context.parsed.y} คน`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // กราฟระยะห่างจากชายแดน
        if (distanceData.length > 0) {
            const distanceCtx = document.getElementById('distanceChart').getContext('2d');
            new Chart(distanceCtx, {
                type: 'pie',
                data: {
                    labels: distanceData.map(d => d.border_distance),
                    datasets: [{
                        data: distanceData.map(d => d.count),
                        backgroundColor: ['#f56565', '#ed8936', '#ecc94b', '#48bb78'],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed} คน (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        // กราฟช่วงอายุ
        if (ageData.length > 0) {
            const ageCtx = document.getElementById('ageChart').getContext('2d');
            new Chart(ageCtx, {
                type: 'bar',
                data: {
                    labels: ageData.map(d => d.age_group),
                    datasets: [{
                        label: 'จำนวนคน',
                        data: ageData.map(d => d.count),
                        backgroundColor: '#9f7aea',
                        borderWidth: 1,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        // ฟังก์ชันส่งออกเป็น CSV
        function exportToCSV() {
            window.open('export_csv.php', '_blank');
        }

        // ฟังก์ชันรีเฟรชข้อมูล
        function refreshData() {
            location.reload();
        }

        // ฟังก์ชันแสดงรูปภาพ
        function showImages(images) {
            const modal = document.getElementById('imageModal');
            const container = document.getElementById('modalImageContainer');
            
            container.innerHTML = '';
            
            images.forEach((image, index) => {
                const imageDiv = document.createElement('div');
                imageDiv.style.textAlign = 'center';
                imageDiv.innerHTML = `
                    <img src="uploads/${image}" 
                         alt="รูปภาพ ${index + 1}" 
                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px; cursor: pointer;"
                         onclick="openFullImage('uploads/${image}')">
                    <p style="margin-top: 8px; font-size: 14px; color: #666;">รูปภาพ ${index + 1}</p>
                `;
                container.appendChild(imageDiv);
            });
            
            modal.style.display = 'block';
        }

        // ฟังก์ชันปิด Modal
        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // ฟังก์ชันเปิดรูปภาพขนาดเต็ม
        function openFullImage(imageSrc) {
            window.open(imageSrc, '_blank');
        }

        // อัพเดทเวลาล่าสุด
        function updateLastUpdated() {
            const now = new Date();
            const timeString = now.toLocaleString('th-TH');
            const timeElement = document.getElementById('lastUpdated');
            if (timeElement) {
                timeElement.textContent = `อัพเดทล่าสุด: ${timeString}`;
            }
        }

        // เรียกใช้เมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            updateLastUpdated();
            
            // เพิ่มข้อมูลเวลาล่าสุด
            const header = document.querySelector('.header');
            const timeElement = document.createElement('p');
            timeElement.id = 'lastUpdated';
            timeElement.style.marginTop = '10px';
            timeElement.style.fontSize = '14px';
            timeElement.style.opacity = '0.8';
            header.appendChild(timeElement);
            updateLastUpdated();
        });
    </script>
</body>
</html>