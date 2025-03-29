<?php
// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "mydata");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตรวจสอบว่ามีการส่งค่า id มาหรือไม่
if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // แปลงค่าเป็นตัวเลข ป้องกัน SQL Injection

    // ใช้ Prepared Statement เพื่อความปลอดภัย
    $stmt = $conn->prepare("DELETE FROM user WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();

        // เมื่อทำการลบเสร็จแล้ว ให้กลับไปยังหน้า index.php
        header("Location: user_show.php");
        exit; // ป้องกันไม่ให้โค้ดอื่นทำงานต่อ
    } else {
        echo "Error deleting record: " . $conn->error;
    }
} else {
    echo "Invalid request.";
}

$conn->close();
?>

