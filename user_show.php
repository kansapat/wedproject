<?php
// เชื่อมต่อฐานข้อมูล (แก้ไขค่าตามเซิร์ฟเวอร์ของคุณ)
$conn = new mysqli("localhost", "root", "", "mydata");
// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// ค้นหาข้อมูล
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sql = "SELECT * FROM user WHERE name LIKE '%$search%' OR lastname LIKE '%$search%'";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title>HOME</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'DM Serif Display', serif;
            background-color: #f8f8f8;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            width: 90%;
            max-width: 1000px;
            background: #fff;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin: 0 auto;
            overflow: hidden;
        }
        
        h2 {
            color: #5a4221;
            font-size: 32px;
            margin-bottom: 25px;
            letter-spacing: 1px;
        }
        
        /* สไตล์สำหรับฟอร์มค้นหา */
        .search-form {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f4eb;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-form input {
            flex: 1;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #d5c7a9;
            border-radius: 8px;
            margin: 0 10px;
            font-family: Arial, sans-serif;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #b99b8c;
            box-shadow: 0 0 5px rgba(185, 155, 140, 0.5);
        }
        
        /* สไตล์สำหรับปุ่ม */
        .button {
            font-family: 'DM Serif Display', serif;
            font-size: 18px;
            padding: 12px 18px;
            min-width: 120px;
            border: 1px solid #b99b8c;
            background: #f8f4eb;
            color: #5a4221;
            cursor: pointer;
            text-align: center;
            margin: 5px;
            border-radius: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .button:hover {
            background: #e5dac8;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .button:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        
        .new-button {
            background-color: #b99b8c;
            color: white;
        }
        
        .new-button:hover {
            background-color: #a08778;
        }
        
        .edit-button {
            background-color: #f8f4eb;
            color: #5a4221;
        }
        
        .delete-button {
            background-color: #f8f4eb;
            color: #aa5555;
            border-color: #aa5555;
        }
        
        .delete-button:hover {
            background-color: #ffebeb;
        }
        
        /* สไตล์ตาราง */
        .table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .table th, .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background-color: #f8f4eb;
            color: #5a4221;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 16px;
        }
        
        .table tr:last-child td {
            border-bottom: none;
        }
        
        .table tr:hover {
            background-color: #f9f9f9;
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        
        /* ส่วนที่แสดงเมื่อไม่มีข้อมูล */
        .no-data {
            padding: 30px;
            text-align: center;
            color: #888;
            font-style: italic;
        }
        
        /* ปรับแต่งสำหรับหน้าจอขนาดเล็ก */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
            }
            
            .search-form {
                flex-direction: column;
                gap: 10px;
            }
            
            .search-form input {
                width: 100%;
                margin: 5px 0;
            }
            
            .button {
                min-width: 100px;
                font-size: 16px;
                padding: 10px 15px;
            }
            
            .table th, .table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>HOME</h2>
        
        <form method="GET" class="search-form">
            <button type="button" class="button new-button" onclick="window.location.href='resgister.php'">New</button>
            <input type="text" name="search" placeholder="Search by name or lastname..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="button">Search</button>
        </form>
        
        <?php if ($result && $result->num_rows > 0) { ?>
        <table class="table">
            <tr>
                <th width="60%">Name - Lastname</th>
                <th width="40%">Actions</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['name']) . " " . htmlspecialchars($row['lastname']); ?></td>
                <td class="actions">
                    <button class="button edit-button" onclick="window.location.href='edit.php?id=<?php echo $row['id']; ?>'">Edit</button>
                    <button class="button delete-button" onclick="if(confirm('Are you sure you want to delete this user?')) window.location.href='delete.php?id=<?php echo $row['id']; ?>'">Delete</button>
                </td>
            </tr>
            <?php } ?>
        </table>
        <?php } else { ?>
        <div class="no-data">
            <p>No users found. Try a different search term or add a new user.</p>
        </div>
        <?php } ?>
    </div>
</body>
</html>
<?php $conn->close(); ?>