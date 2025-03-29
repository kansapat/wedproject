<?php
// เชื่อมต่อกับฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "mydata");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// กำหนดตัวแปรเพื่อเก็บข้อความแจ้งข้อผิดพลาด
$error_message = "";
$reset_message = "";
$reset_status = "";

// ตรวจสอบการส่งข้อมูล reset password
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reset_submit'])) {
    $reset_email = $_POST['reset_email'];
    
    if (!empty($reset_email)) {
        // ตรวจสอบว่ามีอีเมลนี้ในระบบหรือไม่
        $sql = "SELECT * FROM user WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $reset_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // พบอีเมลในระบบ
            $user = $result->fetch_assoc();
            
            // สร้างข้อความที่จะส่ง
            $to = "kittipong.s@bmetech.co.th";
            $subject = "ขอรีเซ็ตรหัสผ่านระบบ";
            
            $message = "มีคำขอรีเซ็ตรหัสผ่านจาก:\n\n";
            $message .= "อีเมล: " . $reset_email . "\n";
            $message .= "ชื่อผู้ใช้: " . $user['username'] . "\n";
            $message .= "ชื่อ-นามสกุล: " . $user['name'] . " " . $user['lastname'] . "\n";
            $message .= "ตำแหน่ง: " . $user['position'] . "\n";
            $message .= "รหัสผ่านปัจจุบัน: " . $user['password'] . "\n";
            
            // แทนการใช้ mail() ด้วยการบันทึกข้อมูลลงไฟล์
            $log_file = "password_reset_requests.txt";
            $log_content = date("Y-m-d H:i:s") . " - ส่งคำขอรีเซ็ตรหัสผ่านจาก " . $reset_email . "\n";
            $log_content .= "รายละเอียด: " . $message . "\n";
            $log_content .= "--------------------------------------------------\n";
            file_put_contents($log_file, $log_content, FILE_APPEND);
            
            // แสดงผลว่าสำเร็จ
            $reset_status = "success";
            $reset_message = "ส่งคำขอรีเซ็ตรหัสผ่านเรียบร้อยแล้ว เจ้าหน้าที่จะติดต่อกลับทางอีเมลของคุณโดยเร็วที่สุด";
        } else {
            // ไม่พบอีเมลในระบบ
            $reset_status = "error";
            $reset_message = "ไม่พบอีเมลนี้ในระบบ กรุณาตรวจสอบอีกครั้ง";
        }
        
        $stmt->close();
    } else {
        $reset_status = "error";
        $reset_message = "กรุณากรอกอีเมล";
    }
}

// ตรวจสอบว่ามีการส่งข้อมูลฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['reset_submit'])) {
    // รับค่าจากฟอร์ม
    $email = $_POST['email'];
    $password = $_POST['password'];
    $position = $_POST['position'];
    
    // ตรวจสอบว่าไม่มีช่องว่างเปล่า
    if (!empty($email) && !empty($password) && !empty($position)) {
        // ป้องกัน SQL Injection
        $email = $conn->real_escape_string($email);
        $password = $conn->real_escape_string($password);
        $position = $conn->real_escape_string($position);
        
        // สร้าง query เพื่อตรวจสอบกับฐานข้อมูล
        $sql = "SELECT * FROM user WHERE email = '$email' AND password = '$password' AND position = '$position'";
        $result = $conn->query($sql);
        
        if ($result->num_rows == 1) {
            // ล็อกอินสำเร็จ
            $user = $result->fetch_assoc();
            
            // เริ่มต้น session
            session_start();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['name'] = $user['name'] . ' ' . $user['lastname'];
            $_SESSION['position'] = $user['position'];
            
            // จำรหัสผ่านหรือไม่
            if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
                // สร้าง cookies (หมดอายุใน 30 วัน)
                setcookie('email', $email, time() + (86400 * 30), "/");
            }
            
            // ไปยังหน้า menu.php
            header("Location: menu.php");
            exit();
        } else {
            // ล็อกอินล้มเหลว
            $error_message = "อีเมล รหัสผ่าน หรือตำแหน่งไม่ถูกต้อง!";
        }
    } else {
        // ข้อมูลไม่ครบ
        $error_message = "กรุณากรอกข้อมูลให้ครบทุกช่อง!";
    }
}

// ปิดการเชื่อมต่อ
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LOGIN</title>
    <link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Old+Standard+TT:wght@400;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Old Standard TT", serif;
        }

        body {
            width: 100%;
            height: 100vh;
            overflow: hidden;
            position: relative;
        }

        /* พื้นหลังเต็มหน้าจอปรับให้คมชัดและเพิ่มคอนทราสต์ */
        .login-background {
            position: absolute;
            width: 100%;
            height: 100%;
            background-color: #d19558; /* เพิ่มสีพื้นหลังเข้มเพื่อเพิ่มคอนทราสต์ */
            background-image: url('pic/log in.png');
            background-size: 100%;
            background-position: left center;
            background-repeat: no-repeat;
            z-index: 1;
            
            /* เพิ่ม filters เพื่อปรับความคมชัดและคอนทราสต์ */
            filter: contrast(100%) brightness(90%) saturate(100%);
            
            /* เพิ่มคุณภาพการแสดงผลภาพ */
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                to right,
                rgba(0, 0, 0, 0.6) 0%,
                rgba(0, 0, 0, 0.4) 60%,
                rgba(0, 0, 0, 0.2) 100%
            );
            z-index: 2;
            
        }

        .welcome-text {
            font-family: "Old Standard TT", serif;
            font-size: 60px;
            font-weight: 400;
            margin-bottom: 20px;
            letter-spacing: -0.3px;
            line-height: 1.1;
            /* เพิ่มเงาข้อความ */
            text-shadow: 
                0 3px 6px rgba(0, 0, 0, 0.5),
                0 6px 18px rgba(0, 0, 0, 0.3),
                1px 1px 2px rgba(0, 0, 0, 0.8);
            color: rgba(255, 255, 255, 0.95);
        }

        .login-instruction {
            font-family: "Old Standard TT", serif;
            font-size: 16px;
            font-weight: 400;
            letter-spacing: 0.8px;
            line-height: 1.2;
            margin-left: 15px;
            padding-right: 15px;
            text-align: left;
            /* เพิ่มเงาข้อความ */
            text-shadow: 
                0 1px 3px rgba(0, 0, 0, 0.5),
                0 3px 8px rgba(0, 0, 0, 0.3);
            color: rgba(255, 255, 255, 0.9);
        }

        /* ฟอร์มล็อกอินวางอยู่ด้านหน้า */
        .container {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            z-index: 3;
            left: -5%;
        }

        .login-form {
            position: absolute;
            width: 400px;
            height: auto;
            min-height: 550px;
            background-color: rgb(255, 255, 255);
            left: 79%;
            top: 50%;
            transform: translate(-50%, -50%);
            border-radius: 8px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4);
            padding: 60px 40px;
            
            /* จัดวางเนื้อหาตรงกลาง */
            display: flex;
            justify-content: center;
            align-items: center;
        }
		
		 /* แก้ไขเพื่อให้ข้อความอยู่ตรงกับฟอร์มและขยับขึ้น */
        .welcome-container {
            position: absolute;
            top: 25%; /* ปรับจาก 50% เพื่อขยับขึ้น */
            transform: translateY(-50%);
            left: 200px;
            z-index: 3;
        }

        .form-container {
            width: 100%;
            max-width: 450px;
            
            /* จัดศูนย์กลางทั้งแนวตั้งและแนวนอน */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100%;
            gap: 8px;
        }

        .input-group {
            width: 100%;
            margin-bottom: 25px;
            text-align: center;
        }

        .form-input {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            font-family: 'Merriweather', serif;
            border: 1.5px solid #e0e0e0;
            border-radius: 6px;
            outline: none;
            text-align: center;
            transition: all 0.3s ease;
        }

        .form-input:focus {
            border-color: #c17d55;
            box-shadow: 0 0 10px rgba(193, 125, 85, 0.35);
        }

        .remember-me {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 30px;
            padding-left: 10px;
        }

        .remember-me input {
            margin-right: 8px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            accent-color: #c17d55;
        }

        .remember-me label {
            font-family: 'Merriweather', serif;
            color: #8a7b70;
            font-size: 14px;
            font-weight: 400;
        }

        .login-button {
            width: 100%;
            padding: 12px;
            background-color: white;
            color: #c17d55;
            border: 2px solid #c17d55;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Merriweather', serif;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            letter-spacing: 0.8px;
        }

        .login-button:hover {
            background-color: #c17d55;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(193, 125, 85, 0.4);
        }

        .form-footer {
            width: 100%;
            margin-top: 45px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }

        .forgot-password {
            font-family: 'Merriweather', serif;
            color: #8a7b70;
            text-decoration: none;
            font-size: 14px;
            font-weight: 400;
            transition: color 0.3s ease;
            padding: 6px 0;
            cursor: pointer;
        }

        .forgot-password:hover {
            color: #c17d55;
            text-decoration: underline;
        }

        /* สไตล์สำหรับ select box */
        select {
            width: 100%;
            padding: 8px;
            font-size: 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 6px;
            outline: none;
            transition: all 0.3s ease;
            font-family: 'Merriweather', serif;
            margin-top: 15px;
            text-align: center;
            background-color: white;
            color: #666;
        }

        select:focus {
            border-color: #c17d55;
            box-shadow: 0 0 10px rgba(193, 125, 85, 0.35);
        }

       

        /* ข้อความแจ้งเตือนข้อผิดพลาด */
        .error-message {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-radius: 6px;
            text-align: center;
            font-family: 'Merriweather', serif;
            font-size: 14px;
            display: <?php echo !empty($error_message) ? 'block' : 'none'; ?>;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 30px;
            width: 90%;
            max-width: 400px;
            border-radius: 8px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalAppear 0.3s;
        }

        @keyframes modalAppear {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .close {
            position: absolute;
            right: 20px;
            top: 15px;
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.2s;
        }

        .close:hover,
        .close:focus {
            color: #c17d55;
            text-decoration: none;
        }

        .modal-title {
            font-family: 'Old Standard TT', serif;
            color: #c17d55;
            font-size: 24px;
            margin-bottom: 20px;
            font-weight: 400;
        }

        .modal-message {
            font-family: 'Merriweather', serif;
            font-size: 14px;
            margin-bottom: 20px;
            color: #666;
            line-height: 1.5;
        }

        .modal-message.success {
            color: #28a745;
        }

        .modal-message.error {
            color: #dc3545;
        }

        .modal-input {
            width: 100%;
            padding: 12px 15px;
            font-size: 14px;
            font-family: 'Merriweather', serif;
            border: 1.5px solid #e0e0e0;
            border-radius: 6px;
            outline: none;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .modal-input:focus {
            border-color: #c17d55;
            box-shadow: 0 0 10px rgba(193, 125, 85, 0.35);
        }

        .modal-button {
            width: 100%;
            padding: 12px;
            background-color: white;
            color: #c17d55;
            border: 2px solid #c17d55;
            border-radius: 6px;
            font-size: 14px;
            font-family: 'Merriweather', serif;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            letter-spacing: 0.8px;
        }

        .modal-button:hover {
            background-color: #c17d55;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(193, 125, 85, 0.4);
        }

        /* รองรับหน้าจอขนาดเล็ก */
        @media (max-width: 768px) {
            .welcome-container {
                left: 30px;
                top: 30%; /* ปรับให้ขยับขึ้นมากกว่าเดิมบนหน้าจอขนาดเล็ก */
            }

            .welcome-text {
                font-size: 40px;
            }

            .login-instruction {
                font-size: 14px;
            }

            .login-form {
                width: 85%;
                left: 50%;
                min-height: auto;
                padding: 40px 25px;
            }

            .modal-content {
                margin: 30% auto;
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <!-- พื้นหลังรูปภาพ -->
    <div class="login-background">
        <div class="overlay"></div>
    </div>
    
    <!-- ข้อความ welcome แยกออกมาเพื่อจัดตำแหน่งได้อิสระ -->
    <div class="welcome-container">
        <h1 class="welcome-text">Welcome Back!</h1>
        <p class="login-instruction">ENTER YOUR EMAIL ADDRESS AND PASSWORD</p>
    </div>
    
    <!-- ส่วนฟอร์มล็อกอิน -->
    <div class="container">
        <div class="login-form">
            <div class="form-container">
                <div class="error-message"><?php echo $error_message; ?></div>
                
                <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="input-group">
                        <input type="email" placeholder="Enter Email Address" class="form-input" id="email" name="email" required value="<?php echo isset($_COOKIE['email']) ? $_COOKIE['email'] : ''; ?>">
                    </div>
                    
                    <div class="input-group">
                        <input type="password" placeholder="Password" class="form-input" id="password" name="password" required>
                        
                        <select name="position" id="position" required>
                            <option value="" disabled selected>Select Position</option>
                            <option value="Pharmacist">Pharmacist</option>
                            <option value="Inventory officer">Inventory Officer</option>
                            <option value="Board of Management">Board of Management</option>
                            <option value="BME">BME</option>
                        </select>
                    </div>
                    
                    <div class="remember-me">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Remember Me</label>
                    </div>
                    
                    <button type="submit" class="login-button">LOG IN</button>
                </form>
                
                <div class="form-footer">
                    <a id="forgotPasswordLink" class="forgot-password">Forget Password?</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับลืมรหัสผ่าน -->
    <div id="forgotPasswordModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Forget Password</h2>
            
            <?php if (!empty($reset_message) && $reset_status == 'success'): ?>
                <p class="modal-message success"><?php echo $reset_message; ?></p>
            <?php elseif (!empty($reset_message) && $reset_status == 'error'): ?>
                <p class="modal-message error"><?php echo $reset_message; ?></p>
            <?php else: ?>
                <p class="modal-message">กรุณากรอกอีเมลของคุณเพื่อขอรีเซ็ตรหัสผ่าน</p>
                <form id="resetForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="email" placeholder="Enter your email address" name="reset_email" class="modal-input" required>
                    <button type="submit" name="reset_submit" class="modal-button">ส่งคำขอ</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const modal = document.getElementById('forgotPasswordModal');
            const forgotLink = document.getElementById('forgotPasswordLink');
            const closeBtn = document.querySelector('.close');
            
            // เปิด Modal เมื่อคลิกที่ Forget Password
            forgotLink.addEventListener('click', function() {
                modal.style.display = "block";
            });
            
            // ปิด Modal เมื่อคลิกที่ X
            closeBtn.addEventListener('click', function() {
                modal.style.display = "none";
            });
            
            // ปิด Modal เมื่อคลิกนอกพื้นที่ Modal
            window.addEventListener('click', function(event) {
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
            
            // แสดง Modal อัตโนมัติถ้ามีการส่งข้อมูล reset password
            <?php if (!empty($reset_message)): ?>
                modal.style.display = "block";
            <?php endif; ?>
            
            // ตรวจสอบความถูกต้องของข้อมูลก่อนส่งฟอร์ม
            loginForm.addEventListener('submit', function(event) {
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                const position = document.getElementById('position').value;
                
                if (!email || !password || !position) {
                    alert('กรุณากรอกข้อมูลให้ครบทุกช่อง');
                    event.preventDefault();
                }
            });
        });
    </script>
</body>
</html>