<?php
// รับค่าจากฟอร์ม
$name = $_POST['name'];
$lastname = $_POST['lastname'];
$tel = $_POST['tel'];
$email = $_POST['email'];
$address = $_POST['address'];
$position = $_POST['position'];
$username = $_POST['username'];
$password = $_POST['password'];

// ดึงฟังชั่นการเชื่อมต่อฐานข้อมูล
include("connect.php");

// เพิ่มข้อมูลลงฐานข้อมูล
$sql = "INSERT INTO user VALUES('', '$name', '$lastname', '$tel', '$email', '$address', '$username', '$password', '$position')";
$result = $conn->query($sql);

if ($result) {
    echo "<script>
            alert('ลงทะเบียนสำเร็จ!');
            window.location.href='user_show.php';
          </script>";
} else {
    echo "<script>
            alert('เกิดข้อผิดพลาด! กรุณาลองใหม่อีกครั้ง');
            window.history.back();
          </script>";
}
?>
