<?php
// การตั้งค่าฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mydata";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่าการเข้ารหัส UTF-8
mysqli_set_charset($conn, "utf8");

// คำนวณยอดขายรวมทั้งหมด
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

// ข้อมูลจากงบการเงินเดิม
$costOfSales = 210830; // ต้นทุนขาย
$previousYearRevenue = 292761; // รายได้ปีก่อน
$previousYearGrossProfit = 57245; // กำไรขั้นต้นปีก่อน
$previousYearProfitBeforeExpenses = 235516; // กำไรก่อนค่าใช้จ่ายปีก่อน
$sellingExpenses = 52930; // ค่าใช้จ่ายในการขาย
$adminExpenses = 67165; // ค่าใช้จ่ายในการบริหาร
$previousYearTotalExpenses = 119715; // รวมค่าใช้จ่ายปีก่อน

// คำนวณข้อมูลงบการเงินใหม่
$currentYearRevenue = $grandTotal; // รายได้จากการขายปีปัจจุบัน = ยอดขายยา
$grossProfit = $currentYearRevenue - $costOfSales; // กำไรขั้นต้น
$profitBeforeExpenses = $grossProfit; // กำไรก่อนค่าใช้จ่าย
$totalExpenses = $sellingExpenses + $adminExpenses; // รวมค่าใช้จ่าย
$operatingProfit = $profitBeforeExpenses - $totalExpenses; // กำไรจากกิจกรรมดำเนินงาน

// แสดงผลลัพธ์
echo "<!DOCTYPE html>
<html lang=\"th\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <link rel=\"icon\" type=\"image/png\" sizes=\"192x192\" href=\"im/android-icon-192x192.png\">
    <title>งบการเงิน - โรงพยาบาลรังสิต</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', sans-serif;
        }
        
        body {
            display: flex;
            min-height: 100vh;
            background-color: #f5f5f5;
        }
        
        .left-image {
            flex: 1;
            background-image: url('im/12555.png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            filter: grayscale(100%);
        }
        
        .right-content {
            flex: 1;
            padding: 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .hospital-name {
            font-size: 2.2rem;
            color: #333;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 500;
        }
        
        .statement-title {
            font-size: 1.4rem;
            color: #444;
            text-align: center;
            margin-bottom: 10px;
        }
        
        .section-header {
            text-align: right;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 20px;
            width: 60%;
            margin-left: auto;
            padding-right: 50px;
        }
        
        .financial-summary {
            width: 100%;
            border-spacing: 0;
            border-collapse: collapse;
        }
        
        .financial-summary tr td {
            padding: 12px 10px;
            font-size: 1.05rem;
            color: #333;
            position: relative;
        }
        
        .financial-summary tr td:first-child {
            text-align: left;
            width: 50%;
            padding-left: 0;
        }
        
        .financial-summary tr td:nth-child(2),
        .financial-summary tr td:nth-child(3) {
            text-align: right;
            width: 25%;
        }
        
        .divider-container {
            width: 60%;
            margin-left: auto;
            border-bottom: 1px solid #ccc;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        
        .strong-divider-container {
            width: 60%;
            margin-left: auto;
            border-bottom: 2px solid #999;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        
        .revenue-source {
            font-size: 0.85rem;
            color: #666;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            body {
                flex-direction: column;
            }
            
            .left-image {
                height: 300px;
            }
            
            .right-content {
                padding: 30px 20px;
            }
            
            .hospital-name {
                font-size: 1.8rem;
                margin-bottom: 30px;
            }
            
            .statement-title {
                font-size: 1.2rem;
            }
            
            .financial-summary tr td {
                padding: 10px 5px;
                font-size: 0.9rem;
            }
            
            .section-header {
                padding-right: 20px;
            }
        }
    </style>
    <link href=\"https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap\" rel=\"stylesheet\">
</head>
<body>
    <div class=\"left-image\"></div>
    <div class=\"right-content\">
        <h1 class=\"hospital-name\">Rangsit Hospital</h1>
        
        <h2 class=\"statement-title\">งบกำไรขาดทุนเบ็ดเสร็จสำหรับรายเดือน</h2>
        
        <div class=\"section-header\">งบการเงินรวมเฉพาะกิจการ</div>
        
        <table class=\"financial-summary\">
            <tr class=\"highlight\">
                <td>รายได้จากการขาย<br><span class=\"revenue-source\">(รายได้จากการจำหน่ายยา)</span></td>
                <td>" . number_format($currentYearRevenue, 2) . "</td>
                <td>" . number_format($previousYearRevenue, 2) . "</td>
            </tr>
            <tr>
                <td>ต้นทุนขาย</td>
                <td>" . number_format($costOfSales, 2) . "</td>
                <td>" . number_format($costOfSales, 2) . "</td>
            </tr>
            <tr>
                <td>กำไรขั้นต้น</td>
                <td>" . number_format($grossProfit, 2) . "</td>
                <td>" . number_format($previousYearGrossProfit, 2) . "</td>
            </tr>
        </table>
        
        <div class=\"divider-container\"></div>
        
        <table class=\"financial-summary\">
            <tr>
                <td>กำไรก่อนค่าใช้จ่าย</td>
                <td>" . number_format($profitBeforeExpenses, 2) . "</td>
                <td>" . number_format($previousYearProfitBeforeExpenses, 2) . "</td>
            </tr>
            <tr>
                <td>ค่าใช้จ่ายในการขาย</td>
                <td>" . number_format($sellingExpenses, 2) . "</td>
                <td>" . number_format($sellingExpenses, 2) . "</td>
            </tr>
            <tr>
                <td>ค่าใช้จ่ายในการบริหาร</td>
                <td>" . number_format($adminExpenses, 2) . "</td>
                <td>" . number_format($adminExpenses, 2) . "</td>
            </tr>
            <tr>
                <td>รวมค่าใช้จ่าย</td>
                <td>" . number_format($totalExpenses, 2) . "</td>
                <td>" . number_format($totalExpenses, 2) . "</td>
            </tr>
        </table>
        
        <div class=\"divider-container\"></div>
        
        <table class=\"financial-summary\">
            <tr>
                <td>กำไรจากกิจกรรมดำเนินงาน</td>
                <td>" . number_format($operatingProfit, 2) . "</td>
                <td>" . number_format($previousYearTotalExpenses, 2) . "</td>
            </tr>
        </table>
        
        <div class=\"strong-divider-container\"></div>
        
        <div style=\"margin-top: 20px; font-size: 0.9rem; color: #666;\">
            <p>หมายเหตุ: งบการเงินปีปัจจุบันแสดงรายได้จากการขายตามยอดขายยาจากระบบ medicine_prescriptions</p>
            <p>วันที่ออกรายงาน: " . date('d/m/Y H:i:s') . "</p>
        </div>
    </div>
</body>
</html>";

// ปิดการเชื่อมต่อ
$conn->close();
?>