<?php
// เริ่ม session เพื่อตรวจสอบการเข้าสู่ระบบ
session_start();

// ตรวจสอบว่ามีการเข้าสู่ระบบหรือไม่
if (!isset($_SESSION['user_id']) || !isset($_SESSION['position'])) {
    // ถ้าไม่มีข้อมูล session ให้กลับไปที่หน้า login
    header("Location: index.php");
    exit();
}

// ดึงตำแหน่งของผู้ใช้จาก session
$user_position = $_SESSION['position'];
$user_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'User';

// ตรวจสอบว่าผู้ใช้มีสิทธิ์เข้าถึงเมนูหรือไม่
$is_bme = ($user_position === 'BME');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title>Configuration</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            height: 100vh;
            overflow: hidden;
        }
        
        .container {
            position: relative;
            width: 100%;
            height: 100%;
            background-image: url('im/11112222.png');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }
        
        .content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            width: 100%;
            padding: 20px;
        }
        
        h1 {
            font-size: 5rem;
            margin-bottom: 80px;
            color: white;
            font-weight: 300;
            letter-spacing: 10px;
        }
        
        .button {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 15px auto;
            padding: 15px 20px;
            background-color: transparent;
            color: white;
            border: 1px solid white;
            text-decoration: none;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .button:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .disabled-button {
            display: block;
            width: 100%;
            max-width: 400px;
            margin: 15px auto;
            padding: 15px 20px;
            background-color: rgba(100, 100, 100, 0.2);
            color: rgba(255, 255, 255, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-decoration: none;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: not-allowed;
        }
        
        .user-info {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 1rem;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }
        
        .user-info span {
            margin-bottom: 5px;
        }
        
        .logout-btn {
            background-color: transparent;
            color: white;
            border: 1px solid white;
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .message {
            color: rgba(255, 255, 255, 0.7);
            font-size: 1rem;
            margin-top: 20px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="overlay"></div>
        
        <div class="content">
            <h1>CONFIGURATION</h1>
            
            <?php if ($is_bme): ?>
                <!-- เมนูสำหรับ BME เท่านั้น -->
                <a href="resgister.php" class="button">CREATE ACCOUNT</a>
                <a href="user_show.php" class="button">UPDATE PROFILE</a>
            <?php else: ?>
                <!-- สำหรับตำแหน่งอื่นๆ แสดงปุ่มที่ไม่สามารถคลิกได้ -->
                <div class="disabled-button">CREATE ACCOUNT</div>
                <div class="disabled-button">UPDATE PROFILE</div>
            <?php endif; ?>
            
            <!-- เมนูนี้ทุกตำแหน่งเข้าถึงได้ -->
            <a href="medicine-info.php" class="button">MEDICATION INVENTORY</a>
        </div>
    </div>
</body>
</html>