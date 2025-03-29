<?php
// medicine-info.php - หน้าแสดงข้อมูลคลังยาและประวัติการจ่ายยา

// ตรวจสอบการเติมยา (ถ้ามีการส่งฟอร์มมา)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refill_submit'])) {
    try {
        // เชื่อมต่อกับฐานข้อมูล
        $conn = new PDO("mysql:host=localhost;dbname=mydata", "root", "");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->exec("SET NAMES utf8");
        
        $medicine_id = isset($_POST['medicine_id']) ? intval($_POST['medicine_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        
        if ($medicine_id > 0 && $quantity > 0) {
            // อัพเดทจำนวนยาในคลัง
            $stmt = $conn->prepare("UPDATE medicine SET number = number + :quantity WHERE id = :id");
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':id', $medicine_id);
            $stmt->execute();
            
            // บันทึกประวัติการเติมยา (ถ้ามีตาราง stock_refills)
            try {
                $stmt = $conn->prepare("INSERT INTO stock_refills (medicine_id, quantity, notes, created_at) VALUES (:medicine_id, :quantity, :notes, NOW())");
                $stmt->bindParam(':medicine_id', $medicine_id);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':notes', $notes);
                $stmt->execute();
            } catch (PDOException $e) {
                // ถ้าไม่มีตาราง stock_refills ให้ข้ามไป
            }
            
            $refill_success = true;
            $refill_message = "เติมยาสำเร็จ: เพิ่ม $quantity รายการ";
        } else {
            $refill_error = "กรุณาระบุยาและจำนวนที่ต้องการเติม";
        }
    } catch (PDOException $e) {
        $refill_error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// เชื่อมต่อกับฐานข้อมูล
$conn = null;
try {
    $conn = new PDO("mysql:host=localhost;dbname=mydata", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
}

// ค้นหายา (ถ้ามีการส่ง search_term มา)
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$where_clause = '';
if (!empty($search_term)) {
    $where_clause = " WHERE name LIKE :search OR notes LIKE :search";
}

// ดึงข้อมูลยาทั้งหมด
$medicines = [];
try {
    $sql = "SELECT * FROM medicine" . $where_clause . " ORDER BY name";
    $stmt = $conn->prepare($sql);
    
    if (!empty($search_term)) {
        $search_param = "%$search_term%";
        $stmt->bindParam(':search', $search_param);
    }
    
    $stmt->execute();
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ข้อผิดพลาดในการดึงข้อมูลยา: " . $e->getMessage());
}

// จำนวนยาที่ต้องเติมสต็อก (ยาที่มีจำนวนน้อยกว่า 10 ชิ้น)
$low_stock_count = 0;
$out_of_stock_count = 0;
foreach ($medicines as $medicine) {
    if ($medicine['number'] <= 0) {
        $out_of_stock_count++;
    } else if ($medicine['number'] < 10) {
        $low_stock_count++;
    }
}

// สถิติการจ่ายยาวันนี้
$today_dispensed = 0;
try {
    $sql = "SELECT COUNT(*) as count FROM medications WHERE DATE(created_at) = CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $today_dispensed = $result['count'];
} catch(PDOException $e) {
    // ไม่ต้องแสดงข้อผิดพลาด
}

// ดึงข้อมูลยาที่จ่ายบ่อยที่สุด
$top_medicines = [];
try {
    $sql = "SELECT medicine_name, COUNT(*) as dispense_count, SUM(quantity) as total_quantity 
            FROM medications 
            GROUP BY medicine_name 
            ORDER BY dispense_count DESC 
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $top_medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // ไม่ต้องแสดงข้อผิดพลาด
}

// ดูประวัติการเติมยา (ถ้ามีในฐานข้อมูล)
$recent_refills = [];
try {
    $sql = "SELECT r.*, m.name as medicine_name
            FROM stock_refills r
            JOIN medicine m ON r.medicine_id = m.id
            ORDER BY r.created_at DESC
            LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $recent_refills = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // ถ้าไม่มีตาราง stock_refills ให้ข้ามไป
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title>ข้อมูลคลังยา - ระบบตรวจสอบยา</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Prompt', 'Sarabun', sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            padding: 20px;
        }
        
        .page-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background-color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: #432208;
            font-size: 1.8rem;
            font-weight: 500;
        }
        
        .stats-cards {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            flex: 1;
            min-width: 200px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 600;
            color: #432208;
        }
        
        .stat-card.warning .value {
            color: #ff9800;
        }
        
        .stat-card.danger .value {
            color: #f44336;
        }
        
        .search-form {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px 0 0 5px;
            outline: none;
            font-size: 0.9rem;
        }
        
        .search-btn {
            padding: 10px 20px;
            background-color: #432208;
            color: white;
            border: none;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .search-btn:hover {
            background-color: #5a3110;
        }
        
        .add-new-btn {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-left: 10px;
        }
        
        .add-new-btn:hover {
            background-color: #45a049;
        }
        
        .table-container {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background-color: #432208;
            color: white;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        tbody tr:hover {
            background-color: #f9f9f9;
        }
        
        .low-stock {
            color: #ff9800;
            font-weight: 500;
        }
        
        .out-of-stock {
            color: #f44336;
            font-weight: 500;
        }
        
        .normal-stock {
            color: #4caf50;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-info {
            background-color: #2196f3;
            color: white;
        }
        
        .btn-info:hover {
            background-color: #0b7dda;
        }
        
        .btn-edit {
            background-color: #4caf50;
            color: white;
        }
        
        .btn-edit:hover {
            background-color: #46a049;
        }
        
        .btn-refill {
            background-color: #ff9800;
            color: white;
        }
        
        .btn-refill:hover {
            background-color: #e68a00;
        }
        
        .top-medicines {
            margin-bottom: 20px;
        }
        
        .top-medicines-title {
            font-size: 1.2rem;
            color: #432208;
            margin-bottom: 10px;
        }
        
        .top-medicine-item {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
        }
        
        .top-medicine-name {
            font-weight: 500;
            color: #333;
        }
        
        .top-medicine-count {
            color: #666;
            display: flex;
            gap: 15px;
        }
        
        .badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            background-color: #e9ecef;
        }
        
        .badge-primary {
            background-color: #cfe8ff;
            color: #0b5ed7;
        }
        
        .badge-success {
            background-color: #d1e7dd;
            color: #146c43;
        }
        
        .nav-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .nav-link {
            padding: 10px 15px;
            background-color: #432208;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .nav-link:hover {
            background-color: #5a3110;
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 80%;
            max-width: 500px;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
        }
        
        .modal-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 1.2rem;
            color: #432208;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-size: 0.9rem;
            color: #555;
        }
        
        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 0.9rem;
            min-height: 100px;
            resize: vertical;
        }
        
        .form-button {
            padding: 10px 20px;
            background-color: #432208;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .form-button:hover {
            background-color: #5a3110;
        }
        
        .form-button-cancel {
            padding: 10px 20px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9rem;
            margin-right: 10px;
        }
        
        .form-button-cancel:hover {
            background-color: #d32f2f;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .refill-history {
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .stats-cards {
                flex-direction: column;
            }
            
            .stat-card {
                min-width: 100%;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .top-medicine-item {
                flex-direction: column;
                gap: 10px;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
            
            .modal-content {
                width: 95%;
                margin: 20% auto;
            }
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <div class="header">
            <h1>ข้อมูลคลังยา</h1>
            <div>
                <a href="menu.php" class="nav-link">กลับหน้าสแกน</a>
            </div>
        </div>
        
        <?php if (isset($refill_success) && $refill_success): ?>
            <div class="alert alert-success"><?php echo $refill_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($refill_error)): ?>
            <div class="alert alert-danger"><?php echo $refill_error; ?></div>
        <?php endif; ?>
        
        <div class="stats-cards">
            <div class="stat-card">
                <h3>รายการยาทั้งหมด</h3>
                <div class="value"><?php echo count($medicines); ?></div>
            </div>
            
            <div class="stat-card warning">
                <h3>ยาที่เหลือน้อย</h3>
                <div class="value"><?php echo $low_stock_count; ?></div>
            </div>
            
            <div class="stat-card danger">
                <h3>ยาที่หมดสต็อก</h3>
                <div class="value"><?php echo $out_of_stock_count; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>จ่ายยาวันนี้</h3>
                <div class="value"><?php echo $today_dispensed; ?></div>
            </div>
        </div>
        
        <!-- แสดงประวัติการเติมยาล่าสุด -->
        <?php if (!empty($recent_refills)): ?>
        <div class="refill-history">
            <h2 class="top-medicines-title">ประวัติการเติมยาล่าสุด</h2>
            <?php foreach ($recent_refills as $refill): ?>
                <div class="top-medicine-item">
                    <div class="top-medicine-name"><?php echo htmlspecialchars($refill['medicine_name']); ?></div>
                    <div class="top-medicine-count">
                        <span class="badge badge-success">เติม <?php echo $refill['quantity']; ?> รายการ</span>
                        <span class="badge badge-primary">
                            <?php 
                                $date = new DateTime($refill['created_at']);
                                echo $date->format('d/m/Y H:i'); 
                            ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- แสดงยาที่จ่ายบ่อยที่สุด -->
        <?php if (!empty($top_medicines)): ?>
        <div class="top-medicines">
            <h2 class="top-medicines-title">ยาที่จ่ายบ่อยที่สุด</h2>
            <?php foreach ($top_medicines as $medicine): ?>
                <div class="top-medicine-item">
                    <div class="top-medicine-name"><?php echo htmlspecialchars($medicine['medicine_name']); ?></div>
                    <div class="top-medicine-count">
                        <span class="badge badge-primary">จ่าย <?php echo $medicine['dispense_count']; ?> ครั้ง</span>
                        <span class="badge badge-primary">จำนวน <?php echo $medicine['total_quantity']; ?> รายการ</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="display: flex; margin-bottom: 20px;">
            <form class="search-form" method="GET">
                <input type="text" name="search" class="search-input" placeholder="ค้นหายา..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit" class="search-btn">ค้นหา</button>
            </form>
            <button id="addNewMedicine" class="add-new-btn">เพิ่มยาใหม่</button>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ชื่อยา</th>
                        <th>จำนวนคงเหลือ</th>
                        <th>ตำแหน่ง</th>
                        <th>หมายเหตุ</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($medicines)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px;">ไม่พบข้อมูลยา</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($medicines as $medicine): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($medicine['name']); ?></td>
                                <td>
                                    <?php 
                                        $stock_class = '';
                                        if ($medicine['number'] <= 0) {
                                            $stock_class = 'out-of-stock';
                                            echo '<span class="' . $stock_class . '">หมด!</span>';
                                        } else if ($medicine['number'] < 10) {
                                            $stock_class = 'low-stock';
                                            echo '<span class="' . $stock_class . '">' . $medicine['number'] . '</span>';
                                        } else {
                                            $stock_class = 'normal-stock';
                                            echo '<span class="' . $stock_class . '">' . $medicine['number'] . '</span>';
                                        }
                                    ?>
                                </td>
                                <td>แถว <?php echo htmlspecialchars($medicine['row']); ?>, ชั้น <?php echo htmlspecialchars($medicine['shelf']); ?></td>
                                <td><?php echo htmlspecialchars($medicine['notes'] ?? '-'); ?></td>
                                <td class="actions">
                                    <button class="btn btn-refill refill-btn" 
                                            data-id="<?php echo $medicine['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($medicine['name']); ?>">
                                        เติมสต็อก
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="nav-links">
            <a href="qr.php" class="nav-link">กลับหน้าสแกน</a>
        </div>
    </div>
    
    <!-- Modal เติมยา -->
    <div id="refillModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeRefillModal">&times;</span>
            <h2 class="modal-title">เติมสต็อกยา <span id="refillMedicineName"></span></h2>
            <form id="refillForm" method="POST">
                <input type="hidden" id="medicine_id" name="medicine_id" value="">
                
                <div class="form-group">
                    <label for="quantity" class="form-label">จำนวนที่ต้องการเติม:</label>
                    <input type="number" id="quantity" name="quantity" class="form-input" min="1" required>
                </div>
                
                <div class="form-group">
                    <label for="notes" class="form-label">หมายเหตุ (ถ้ามี):</label>
                    <textarea id="notes" name="notes" class="form-textarea"></textarea>
                </div>
                
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" id="cancelRefill" class="form-button-cancel">ยกเลิก</button>
                    <button type="submit" name="refill_submit" class="form-button">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal เพิ่มยาใหม่ -->
    <div id="addMedicineModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeAddModal">&times;</span>
            <h2 class="modal-title">เพิ่มยาใหม่</h2>
            <form id="addMedicineForm" method="POST" action="add_medicine.php">
                <div class="form-group">
                    <label for="name" class="form-label">ชื่อยา:</label>
                    <input type="text" id="name" name="name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="initial_stock" class="form-label">จำนวนเริ่มต้น:</label>
                    <input type="number" id="initial_stock" name="initial_stock" class="form-input" min="0" required>
                </div>
                
                <div class="form-group">
                    <label for="row" class="form-label">แถว:</label>
                    <input type="text" id="row" name="row" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="shelf" class="form-label">ชั้น:</label>
                    <input type="text" id="shelf" name="shelf" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="med_notes" class="form-label">หมายเหตุ (ถ้ามี):</label>
                    <textarea id="med_notes" name="med_notes" class="form-textarea"></textarea>
                </div>
                
                <div style="display: flex; justify-content: flex-end;">
                    <button type="button" id="cancelAdd" class="form-button-cancel">ยกเลิก</button>
                    <button type="submit" name="add_medicine_submit" class="form-button">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // สคริปต์สำหรับ Modal เติมยา
        document.addEventListener('DOMContentLoaded', function() {
            const refillModal = document.getElementById('refillModal');
            const closeRefillModal = document.getElementById('closeRefillModal');
            const refillBtns = document.querySelectorAll('.refill-btn');
            const refillMedicineName = document.getElementById('refillMedicineName');
            const medicineIdInput = document.getElementById('medicine_id');
            const cancelRefill = document.getElementById('cancelRefill');
            
            const addMedicineModal = document.getElementById('addMedicineModal');
            const closeAddModal = document.getElementById('closeAddModal');
            const addNewMedicine = document.getElementById('addNewMedicine');
            const cancelAdd = document.getElementById('cancelAdd');
            
            // เปิด Modal เติมยา
            refillBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    
                    medicineIdInput.value = id;
                    refillMedicineName.textContent = name;
                    
                    refillModal.style.display = 'block';
                });
            });
            
            // ปิด Modal เติมยา
            closeRefillModal.addEventListener('click', function() {
                refillModal.style.display = 'none';
            });
            
            cancelRefill.addEventListener('click', function() {
                refillModal.style.display = 'none';
            });
            
            // เปิด Modal เพิ่มยาใหม่
            addNewMedicine.addEventListener('click', function() {
                addMedicineModal.style.display = 'block';
            });
            
            // ปิด Modal เพิ่มยาใหม่
            closeAddModal.addEventListener('click', function() {
                addMedicineModal.style.display = 'none';
            });
            
            cancelAdd.addEventListener('click', function() {
                addMedicineModal.style.display = 'none';
            });
            
            // ปิด Modal เมื่อคลิกพื้นที่ภายนอก
            window.addEventListener('click', function(event) {
                if (event.target == refillModal) {
                    refillModal.style.display = 'none';
                }
                
                if (event.target == addMedicineModal) {
                    addMedicineModal.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>