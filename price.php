<?php
// การตั้งค่าฐานข้อมูล
$servername = "localhost";
$username = "root";  // เปลี่ยนเป็นชื่อผู้ใช้ฐานข้อมูลของคุณ
$password = "";      // เปลี่ยนเป็นรหัสผ่านฐานข้อมูลของคุณ
$dbname = "mydata";  // ชื่อฐานข้อมูลของคุณ

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่าการเข้ารหัส UTF-8
mysqli_set_charset($conn, "utf8");

// ตรวจสอบว่ามีตาราง medicine_prices หรือไม่
$checkTableSql = "SHOW TABLES LIKE 'medicine_prices'";
$tableResult = $conn->query($checkTableSql);

if ($tableResult->num_rows == 0) {
    // ถ้าไม่มีตาราง medicine_prices ให้แสดงข้อความแจ้งเตือน
    echo "<h2>ไม่พบตาราง medicine_prices</h2>";
    echo "<p>กรุณาสร้างตารางและกำหนดราคายาก่อนคำนวณยอดขาย</p>";
    exit;
}

// คำนวณยอดขายจากข้อมูลในตาราง medicine_prescriptions
$salesQuery = "
SELECT 
    mp.medicine_name,
    COUNT(mp.id) AS prescription_count,
    SUM(mp.quantity) AS total_quantity,
    m_prices.price AS unit_price,
    SUM(mp.quantity * IFNULL(m_prices.price, 0)) AS total_sales
FROM 
    medicine_prescriptions mp
LEFT JOIN 
    medicine m ON mp.medicine_name = m.name
LEFT JOIN 
    medicine_prices m_prices ON m.id = m_prices.medicine_id
GROUP BY 
    mp.medicine_name, m_prices.price
ORDER BY 
    total_sales DESC
";

$salesResult = $conn->query($salesQuery);

// แสดงผลลัพธ์
echo "<!DOCTYPE html>
<html lang='th'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>รายงานยอดขายยา</title>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            padding: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        h1, h2 {
            color: #333;
        }
        .summary {
            margin-top: 20px;
            padding: 10px;
            background-color: #f0f0f0;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <h1>รายงานยอดขายยา</h1>";

if ($salesResult->num_rows > 0) {
    // คำนวณยอดรวมทั้งหมด
    $totalSalesQuery = "
    SELECT 
        SUM(mp.quantity * IFNULL(m_prices.price, 0)) AS grand_total
    FROM 
        medicine_prescriptions mp
    LEFT JOIN 
        medicine m ON mp.medicine_name = m.name
    LEFT JOIN 
        medicine_prices m_prices ON m.id = m_prices.medicine_id
    ";
    
    $totalResult = $conn->query($totalSalesQuery);
    $totalRow = $totalResult->fetch_assoc();
    $grandTotal = $totalRow['grand_total'] ?? 0;
    
    // ตารางแสดงยอดขายตามรายการยา
    echo "<table>
        <thead>
            <tr>
                <th>ลำดับ</th>
                <th>ชื่อยา</th>
                <th class='text-center'>จำนวนใบสั่งยา</th>
                <th class='text-center'>จำนวนที่จ่าย</th>
                <th class='text-right'>ราคาต่อหน่วย (บาท)</th>
                <th class='text-right'>ยอดขาย (บาท)</th>
            </tr>
        </thead>
        <tbody>";
        
    $i = 1;
    while ($row = $salesResult->fetch_assoc()) {
        echo "<tr>
            <td>" . $i . "</td>
            <td>" . $row['medicine_name'] . "</td>
            <td class='text-center'>" . $row['prescription_count'] . "</td>
            <td class='text-center'>" . ($row['total_quantity'] ?? 0) . "</td>
            <td class='text-right'>" . number_format($row['unit_price'] ?? 0, 2) . "</td>
            <td class='text-right'>" . number_format($row['total_sales'] ?? 0, 2) . "</td>
        </tr>";
        $i++;
    }
    
    echo "</tbody>
        <tfoot>
            <tr>
                <th colspan='5' class='text-right'>ยอดขายรวมทั้งสิ้น:</th>
                <th class='text-right'>" . number_format($grandTotal, 2) . " บาท</th>
            </tr>
        </tfoot>
    </table>";
    
    // สรุปข้อมูลเพิ่มเติม
    $infoQuery = "
    SELECT 
        COUNT(DISTINCT patient_record_id) AS total_patients,
        COUNT(DISTINCT DATE(created_at)) AS total_days,
        COUNT(*) AS total_prescriptions
    FROM 
        medicine_prescriptions
    ";
    
    $infoResult = $conn->query($infoQuery);
    $infoRow = $infoResult->fetch_assoc();
    
    echo "<div class='summary'>
        <h2>สรุปข้อมูล</h2>
        <p>จำนวนผู้ป่วยทั้งหมด: " . $infoRow['total_patients'] . " คน</p>
        <p>จำนวนวันที่มีการจ่ายยา: " . $infoRow['total_days'] . " วัน</p>
        <p>จำนวนใบสั่งยาทั้งหมด: " . $infoRow['total_prescriptions'] . " รายการ</p>
        <p>ยอดขายเฉลี่ยต่อวัน: " . number_format($grandTotal / ($infoRow['total_days'] > 0 ? $infoRow['total_days'] : 1), 2) . " บาท</p>
        <p>ยอดขายเฉลี่ยต่อผู้ป่วย: " . number_format($grandTotal / ($infoRow['total_patients'] > 0 ? $infoRow['total_patients'] : 1), 2) . " บาท</p>
    </div>";
    
} else {
    echo "<p>ไม่พบข้อมูลการจ่ายยาในระบบ</p>";
}

echo "</body>
</html>";

// ปิดการเชื่อมต่อ
$conn->close();
?>