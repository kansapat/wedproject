<?php
// search_medicine.php - ไฟล์สำหรับค้นหายาจากฐานข้อมูล

// เชื่อมต่อกับฐานข้อมูล
$conn = mysqli_connect("localhost", "root", "", "mydata");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'การเชื่อมต่อล้มเหลว: ' . $conn->connect_error
    ]));
}

// ตั้งค่า charset เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8");

// ตรวจสอบว่ามีการส่งข้อมูลมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
    $searchTerm = $conn->real_escape_string($_POST['search']);
    
    // คำสั่ง SQL สำหรับค้นหา - แก้ไขให้ตรงกับชื่อฟิลด์จริงในฐานข้อมูล
    $sql = "SELECT id, name, row, shelf, number,
            CONCAT('แถว ', row, ' ชั้น ', shelf) as location 
            FROM medicine 
            WHERE name LIKE ?
            ORDER BY name
            LIMIT 10";
    
    $stmt = $conn->prepare($sql);
    $searchParam = "%{$searchTerm}%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    
    // ส่งผลลัพธ์กลับเป็น JSON
    echo json_encode([
        'status' => 'success',
        'medicines' => $medicines
    ]);
    
    $stmt->close();
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่มีข้อมูลคำค้นหา'
    ]);
}

// ปิดการเชื่อมต่อกับฐานข้อมูล
$conn->close();
?>