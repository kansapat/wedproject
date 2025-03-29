<?php
$conn = new mysqli("localhost", "root", "", "mydata");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$id_edit = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user = [];

// โหลดข้อมูลจากฐานข้อมูล
if ($id_edit > 0) {
    $stmt = $conn->prepare("SELECT * FROM user WHERE id = ?");
    $stmt->bind_param("i", $id_edit);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    } else {
        echo "<script>alert('User not found!'); window.location='index.php';</script>";
        exit;
    }
    $stmt->close();
}

// เช็คว่ามีการส่งฟอร์มหรือไม่
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $lastname = $_POST['lastname'];
    $tel = $_POST['tel'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $position = $_POST['position'];
    $username = $_POST['username'];
    $password = $_POST['password'];

    // อัปเดตข้อมูลลงฐานข้อมูล
    $stmt = $conn->prepare("UPDATE user SET name=?, lastname=?, tel=?, email=?, address=?, position=?, username=?, password=? WHERE id=?");
    $stmt->bind_param("ssssssssi", $name, $lastname, $tel, $email, $address, $position, $username, $password, $id_edit);
    
    if ($stmt->execute()) {
        echo "<script>alert('แก้ไขข้อมูลสำเร็จ!'); window.location='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('เกิดข้อผิดพลาด! กรุณาลองใหม่');</script>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title>Edit User</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f8f8;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            width: 90%;
            max-width: 600px;
            background: #fff;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        
        h2 {
            font-family: 'DM Serif Display', serif;
            text-align: center;
            color: #5a4221;
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: normal;
            letter-spacing: 1px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #666;
        }
        
        input, select {
            width: 100%;
            padding: 12px;
            margin-bottom: 5px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
            transition: border 0.3s ease;
            font-family: 'Roboto', Arial, sans-serif;
        }
        
        input:focus, select:focus {
            border-color: #b99b8c;
            outline: none;
            box-shadow: 0 0 5px rgba(185, 155, 140, 0.3);
        }
        
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: #b99b8c;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-family: 'DM Serif Display', serif;
            letter-spacing: 1px;
        }
        
        .submit-btn:hover {
            background: #a0877a;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .submit-btn:active {
            transform: translateY(0);
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #888;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            color: #b99b8c;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
        }
        
        .form-row .form-group {
            flex: 1;
        }
        
        .form-hint {
            font-size: 12px;
            color: #888;
            margin-top: 5px;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
            
            h2 {
                font-size: 28px;
            }
            
            input, select, .submit-btn {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Edit User</h2>
    <form method="post">
        <div class="form-row">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" placeholder="Enter name" value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="lastname">Lastname</label>
                <input type="text" name="lastname" id="lastname" placeholder="Enter lastname" value="<?php echo isset($user['lastname']) ? htmlspecialchars($user['lastname']) : ''; ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="tel">Telephone</label>
            <input type="tel" name="tel" id="tel" placeholder="Enter phone number" value="<?php echo isset($user['tel']) ? htmlspecialchars($user['tel']) : ''; ?>">
            <div class="form-hint">Format: 0XXXXXXXXX</div>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" placeholder="Enter email address" value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="address">Address</label>
            <input type="text" name="address" id="address" placeholder="Enter address" value="<?php echo isset($user['address']) ? htmlspecialchars($user['address']) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="position">Position</label>
            <select name="position" id="position" required>
                <option value="">Select Position</option>
                <option value="Board of Management" <?php echo isset($user['position']) && $user['position'] == 'Board of Management' ? 'selected' : ''; ?>>Board of Management</option>
                <option value="BME" <?php echo isset($user['position']) && $user['position'] == 'BME' ? 'selected' : ''; ?>>BME</option>
                <option value="Pharmacist" <?php echo isset($user['position']) && $user['position'] == 'Pharmacist' ? 'selected' : ''; ?>>Pharmacist</option>
                <option value="Inventory Officer" <?php echo isset($user['position']) && $user['position'] == 'Inventory Officer' ? 'selected' : ''; ?>>Inventory Officer</option>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Enter username" value="<?php echo isset($user['username']) ? htmlspecialchars($user['username']) : ''; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter password" value="<?php echo isset($user['password']) ? htmlspecialchars($user['password']) : ''; ?>" required>
            </div>
        </div>
        
        <input type="submit" name="submit" class="submit-btn" value="Save Changes">
        <a href="index.php" class="back-link">← Back to Users List</a>
    </form>
</div>

</body>
</html>