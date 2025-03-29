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

// สร้างตาราง medicine_prices หากยังไม่มี
$createTableSql = "CREATE TABLE IF NOT EXISTS medicine_prices (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT(11) NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY (medicine_id)
)";

if ($conn->query($createTableSql) !== TRUE) {
    echo "เกิดข้อผิดพลาดในการสร้างตาราง: " . $conn->error;
}

// ดึงข้อมูลยาทั้งหมด
$sql = "SELECT * FROM medicine ORDER BY id";
$result = $conn->query($sql);

// กำหนดราคาตามประเภทหรือชนิดยา
if ($result->num_rows > 0) {
    $updatedCount = 0;
    $insertedCount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $medicineId = $row["id"];
        $price = 0;
        
        // กำหนดราคาตามประเภทยา (row)
        switch ($row["row"]) {
            case "ยาปฏิชีวนะ":
            case "ยาลดการอักเสบ":
                $price = 120 + ($row["number"] * 5); // ราคาขึ้นอยู่กับปริมาณยา
                break;
                
            case "ยาแก้ปวด":
            case "ยาบรรเทาอาการปวดและมีไข้":
                $price = 85 + ($row["number"] * 3);
                break;
                
            case "ยาลดกรด":
            case "ยาลดแก๊ส":
                $price = 75 + ($row["number"] * 2);
                break;
                
            case "ยาแก้แพ้":
            case "ยาแก้เมารถ":
                $price = 65 + ($row["number"] * 2.5);
                break;
                
            case "ยารักษาโรคผิวหนัง":
                $price = 95 + ($row["number"] * 4);
                break;
                
            default:
                // กำหนดราคาตามหมวดหมู่ยา (shelf)
                switch ($row["shelf"]) {
                    case "A3":
                    case "A4":
                    case "A5":
                        $price = 150 + (intval($row["number"]) * 10);
                        break;
                        
                    case "B1":
                    case "B2":
                    case "B3":
                    case "B4":
                        $price = 100 + (intval($row["number"]) * 7);
                        break;
                        
                    case "C2":
                    case "C3":
                        $price = 80 + (intval($row["number"]) * 5);
                        break;
                        
                    case "D1":
                    case "D3":
                    case "D4":
                        $price = 120 + (intval($row["number"]) * 8);
                        break;
                        
                    default:
                        // ถ้าไม่มีกฎเฉพาะ ให้กำหนดราคาพื้นฐาน
                        $price = 50 + (intval($row["number"]) * 5);
                        break;
                }
                break;
        }
        
        // อนุโลมปัดเศษให้เป็นเลขกลม
        $price = ceil($price / 5) * 5;
        
        // ตรวจสอบว่ามีราคายานี้อยู่แล้วหรือไม่
        $checkSql = "SELECT * FROM medicine_prices WHERE medicine_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $medicineId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // อัปเดตราคาที่มีอยู่
            $updateSql = "UPDATE medicine_prices SET price = ?, updated_at = NOW() WHERE medicine_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("di", $price, $medicineId);
            if ($updateStmt->execute()) {
                $updatedCount++;
            }
        } else {
            // เพิ่มราคาใหม่
            $insertSql = "INSERT INTO medicine_prices (medicine_id, price, created_at, updated_at) 
                         VALUES (?, ?, NOW(), NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("id", $medicineId, $price);
            if ($insertStmt->execute()) {
                $insertedCount++;
            }
        }
    }
    
    echo "<h2>กำหนดราคายาเรียบร้อยแล้ว</h2>";
    echo "<p>อัปเดตราคา: $updatedCount รายการ</p>";
    echo "<p>เพิ่มราคาใหม่: $insertedCount รายการ</p>";
    
    // แสดงรายการยาพร้อมราคาที่กำหนด
    $listSql = "SELECT m.*, mp.price 
                FROM medicine m 
                LEFT JOIN medicine_prices mp ON m.id = mp.medicine_id 
                ORDER BY m.id";
    $listResult = $conn->query($listSql);
    
    if ($listResult->num_rows > 0) {
        echo "<h3>รายการยาและราคา</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th>รหัสยา</th>";
        echo "<th>ชื่อยา (ภาษาไทย)</th>";
        echo "<th>ชื่อยา (ภาษาอังกฤษ)</th>";
        echo "<th>ประเภท</th>";
        echo "<th>หมวดหมู่</th>";
        echo "<th>คงเหลือ</th>";
        echo "<th>ราคา (บาท)</th>";
        echo "</tr>";
        
        while ($row = $listResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row["id"] . "</td>";
            echo "<td>" . $row["notes"] . "</td>";
            echo "<td>" . $row["name"] . "</td>";
            echo "<td>" . $row["row"] . "</td>";
            echo "<td>" . $row["shelf"] . "</td>";
            echo "<td>" . $row["number"] . "</td>";
            echo "<td align='right'>" . number_format($row["price"], 2) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
} else {
    echo "ไม่พบข้อมูลยาในฐานข้อมูล";
}

// ปิดการเชื่อมต่อ
$conn->close();
?>