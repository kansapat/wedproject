<?php
// api-dispense.php - API สำหรับบันทึกการจ่ายยาและยกเลิกการจ่ายยา

// รับข้อมูลจาก POST request
$action = isset($_POST['action']) ? $_POST['action'] : null;
$patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : null;
$medicine_name = isset($_POST['medicine_name']) ? $_POST['medicine_name'] : null;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
$location = isset($_POST['location']) ? $_POST['location'] : '';

// เตรียมข้อมูลสำหรับตอบกลับ
$response = [
    'success' => false,
    'message' => 'ไม่สามารถดำเนินการได้',
    'data' => null
];

// ตรวจสอบข้อมูลที่จำเป็น
if (!$action || !$patient_id || !$medicine_name || $quantity <= 0) {
    $response['message'] = 'ข้อมูลไม่ครบถ้วนหรือไม่ถูกต้อง';
    echo json_encode($response);
    exit;
}

// เชื่อมต่อกับฐานข้อมูล
try {
    $conn = new PDO("mysql:host=localhost;dbname=mydata", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    $response['message'] = 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้';
    echo json_encode($response);
    exit;
}

// เก็บค่า HN (hospital_number) ไว้
$patientHN = $patient_id;

// ค้นหา ID ที่ถูกต้องจาก HN หรือสร้างผู้ป่วยใหม่ถ้าไม่มีในระบบ
try {
    // ค้นหาผู้ป่วยจาก hospital_number
    $stmt = $conn->prepare("SELECT id FROM patients WHERE hospital_number = :hn LIMIT 1");
    $stmt->bindParam(':hn', $patientHN);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // ถ้าพบผู้ป่วย ใช้ ID ที่ถูกต้อง
        $patient_id = $result['id'];
    } else {
        // ถ้าไม่พบผู้ป่วย สร้างข้อมูลผู้ป่วยใหม่
        $stmt = $conn->prepare("INSERT INTO patients (hospital_number, name, created_at) VALUES (:hn, :hn, NOW())");
        $stmt->bindParam(':hn', $patientHN);
        $stmt->execute();
        $patient_id = $conn->lastInsertId();
    }
} catch(PDOException $e) {
    $response['message'] = 'ไม่สามารถค้นหาหรือสร้างข้อมูลผู้ป่วย: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}

// ดำเนินการตาม action ที่ได้รับ
if ($action === 'dispense') {
    // จ่ายยา
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ตรวจสอบจำนวนยาในคลัง
        $stmt = $conn->prepare("SELECT number FROM medicine WHERE name = :name FOR UPDATE");
        $stmt->bindParam(':name', $medicine_name);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result) {
            // ไม่พบยาในระบบ
            $conn->rollBack();
            $response['message'] = "ไม่พบยา '{$medicine_name}' ในระบบ";
            echo json_encode($response);
            exit;
        }
        
        $current_stock = intval($result['number']);
        
        if ($current_stock < $quantity) {
            // ยาไม่พอจ่าย
            $conn->rollBack();
            $response['message'] = "ยาเหลือไม่พอจ่าย (เหลือ {$current_stock}, ต้องการ {$quantity})";
            echo json_encode($response);
            exit;
        }
        
        // บันทึกการจ่ายยา
        $stmt = $conn->prepare("INSERT INTO medications (patient_id, medicine_name, quantity, location, created_at) 
                               VALUES (:patient_id, :medicine_name, :quantity, :location, NOW())");
        $stmt->bindParam(':patient_id', $patient_id);
        $stmt->bindParam(':medicine_name', $medicine_name);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':location', $location);
        $stmt->execute();
        
        // อัพเดทจำนวนยาในคลัง
        $stmt = $conn->prepare("UPDATE medicine SET number = number - :quantity WHERE name = :name");
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':name', $medicine_name);
        $stmt->execute();
        
        // จบ transaction
        $conn->commit();
        
        // ส่งผลลัพธ์กลับ
        $response['success'] = true;
        $response['message'] = "จ่ายยา '{$medicine_name}' จำนวน {$quantity} รายการเรียบร้อยแล้ว";
        $response['data'] = [
            'medicine_name' => $medicine_name,
            'quantity' => $quantity,
            'stock_remaining' => $current_stock - $quantity
        ];
    } catch(PDOException $e) {
        // มีข้อผิดพลาด ยกเลิก transaction
        $conn->rollBack();
        $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
} else if ($action === 'cancel') {
    // ยกเลิกการจ่ายยา
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ตรวจสอบประวัติการจ่ายยาล่าสุด
        $stmt = $conn->prepare("SELECT id, quantity FROM medications 
                               WHERE patient_id = :patient_id AND medicine_name = :medicine_name 
                               ORDER BY created_at DESC LIMIT 1");
        $stmt->bindParam(':patient_id', $patient_id);
        $stmt->bindParam(':medicine_name', $medicine_name);
        $stmt->execute();
        $last_dispensed = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$last_dispensed) {
            // ไม่พบประวัติการจ่ายยา
            $conn->rollBack();
            $response['message'] = "ไม่พบประวัติการจ่ายยา '{$medicine_name}' ให้ผู้ป่วยรหัส {$patientHN}";
            echo json_encode($response);
            exit;
        }
        
        // จำนวนยาที่จะคืนเข้าคลัง (ใช้จำนวนจากประวัติการจ่ายล่าสุด หรือจำนวนที่ระบุ)
        $return_quantity = isset($last_dispensed['quantity']) ? $last_dispensed['quantity'] : $quantity;
        
        // ลบบันทึกการจ่ายยาล่าสุด
        $stmt = $conn->prepare("DELETE FROM medications WHERE id = :id");
        $stmt->bindParam(':id', $last_dispensed['id']);
        $stmt->execute();
        
        // อัพเดทจำนวนยาในคลัง (เพิ่มขึ้นตามที่คืน)
        $stmt = $conn->prepare("UPDATE medicine SET number = number + :quantity WHERE name = :name");
        $stmt->bindParam(':quantity', $return_quantity);
        $stmt->bindParam(':name', $medicine_name);
        $stmt->execute();
        
        // จบ transaction
        $conn->commit();
        
        // ส่งผลลัพธ์กลับ
        $response['success'] = true;
        $response['message'] = "ยกเลิกการจ่ายยา '{$medicine_name}' จำนวน {$return_quantity} รายการเรียบร้อยแล้ว";
        $response['data'] = [
            'medicine_name' => $medicine_name,
            'quantity_returned' => $return_quantity
        ];
    } catch(PDOException $e) {
        // มีข้อผิดพลาด ยกเลิก transaction
        $conn->rollBack();
        $response['message'] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
} else {
    // Action ไม่ถูกต้อง
    $response['message'] = "คำสั่ง '{$action}' ไม่ถูกต้อง กรุณาระบุ 'dispense' หรือ 'cancel'";
}

// ส่งผลลัพธ์กลับเป็น JSON
header('Content-Type: application/json');
echo json_encode($response);