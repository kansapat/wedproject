<?php
// เชื่อมต่อฐานข้อมูล
$con = mysqli_connect("localhost", "root", "", "mydata");

// ตรวจสอบการเชื่อมต่อ
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// รับค่าจากฟอร์ม
$name = mysqli_real_escape_string($con, $_POST["name"]);
$number = mysqli_real_escape_string($con, $_POST["number"]);
$row = mysqli_real_escape_string($con, $_POST["row"]);
$shelf = mysqli_real_escape_string($con, $_POST["shelf"]);
$notes = mysqli_real_escape_string($con, $_POST["notes"]);

// คำสั่ง SQL
$sql = "INSERT INTO medicine (name, number, row, shelf, notes) 
        VALUES ('$name', '$number', '$row', '$shelf', '$notes')";

// รันคำสั่ง SQL
if (mysqli_query($con, $sql)) {
    echo "บันทึกข้อมูลสำเร็จ!";
} else {
    echo "Error: " . mysqli_error($con);
}

// ปิดการเชื่อมต่อฐานข้อมูล
mysqli_close($con);
?>






<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Untitled Document</title>
</head>

<body>
</body>
</html>