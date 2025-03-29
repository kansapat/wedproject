<?php
// ตรวจสอบการเชื่อมต่อฐานข้อมูล
$conn = mysqli_connect("localhost", "root", "", "mydata");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า charset เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8");

// ดึงข้อมูลผู้ป่วยทั้งหมด
$sql = "SELECT * FROM patients ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบันทึกข้อมูลทางการแพทย์</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Prompt', 'Sarabun', sans-serif;
            background-color: #f5f5f5;
            background-image: url('im/111111.jpg'); /* เพิ่มรูปพื้นหลัง */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: rgba(255, 255, 255, 0.9); /* ปรับความโปร่งใสของพื้นหลัง */
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #432208;
            border-top: 2px solid #432208;
            padding-top: 10px;
        }
        
        h2 {
            margin-top: 20px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .action-buttons {
            margin-bottom: 30px;
            text-align: right;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #432208;
            color: white;
            text-decoration: none;
            border-radius: 30px; /* ปรับให้ปุ่มมีความโค้งมากขึ้น */
            margin: 0 10px;
            font-weight: 500;
            transition: background-color 0.3s;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background-color: #2e1805;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
            background-color: white;
        }
        
        th, td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        
        tr:hover {
            background-color: #f5f5f5;
        }
        
        .empty-message {
            text-align: center;
            padding: 30px;
            font-size: 18px;
            color: #666;
        }
        
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        /* สไตล์สำหรับการแสดงผลในมือถือ */
        @media (max-width: 992px) {
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px 10px;
            }
        }
        
        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>ระบบบันทึกข้อมูลทางการแพทย์</h1>
        
        <h2>รายชื่อผู้ป่วยทั้งหมด</h2>
        
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Patient name</th>
                            <th>Hospital number</th>
                            <th>Date of birth</th>
                            <th>Chief complaint</th>
                            <th>Gender</th>
                            <th>Drug allergies</th>
                            <th>Height and weight</th>
                            <th>Present illness</th>
                            <th>วันที่บันทึก</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['patient_name'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($row['hospital_number'] ?? ''); ?></td>
                                <td><?php echo !empty($row['dob']) ? date('d/m/Y', strtotime($row['dob'])) : '-'; ?></td>
                                <td><?php echo !empty($row['chief_complaint']) ? htmlspecialchars($row['chief_complaint']) : '-'; ?></td>
                                <td>
                                    <?php
                                    $genderMap = [
                                        'male' => 'ชาย',
                                        'female' => 'หญิง',
                                        'other' => 'อื่นๆ'
                                    ];
                                    echo isset($row['gender']) && isset($genderMap[$row['gender']]) ? $genderMap[$row['gender']] : '-';
                                    ?>
                                </td>
                                <td><?php echo !empty($row['drug_allergies']) ? htmlspecialchars($row['drug_allergies']) : '-'; ?></td>
                                <td><?php echo !empty($row['height_weight']) ? htmlspecialchars($row['height_weight']) : '-'; ?></td>
                                <td><?php echo !empty($row['present_illness']) ? htmlspecialchars($row['present_illness']) : '-'; ?></td>
                                <td><?php echo isset($row['created_at']) ? date('d/m/Y H:i', strtotime($row['created_at'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-message">ยังไม่มีข้อมูลผู้ป่วยในระบบ</div>
        <?php endif; ?>
        
        <div class="nav-buttons">
            <a href="menu.php" class="btn">ย้อนกลับ</a>
            <a href="medical_record.php" class="btn">บันทึกข้อมูลใหม่</a>
        </div>
    </div>
</body>
</html>

<?php
// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>