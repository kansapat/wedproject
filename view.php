<?php
// ไฟล์ view.php สำหรับแสดงข้อมูลผู้ป่วยและสแกนยา
// ตรวจสอบว่ามีพารามิเตอร์ id หรือ data หรือ raw_data หรือไม่
$id = isset($_GET['id']) ? $_GET['id'] : null;
$data = isset($_GET['data']) ? $_GET['data'] : null;
$raw_data = isset($_GET['raw_data']) ? $_GET['raw_data'] : null;

// เชื่อมต่อกับฐานข้อมูล
$conn = null;
try {
    $conn = new PDO("mysql:host=localhost;dbname=mydata", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("SET NAMES utf8");
} catch(PDOException $e) {
    // กรณีเชื่อมต่อฐานข้อมูลไม่สำเร็จ ไม่ต้องแสดงข้อผิดพลาด เพียงเก็บ $conn เป็น null
}

// ฟังก์ชันเพื่อแปลงวันที่เป็นรูปแบบไทย
function formatThaiDate($dateStr) {
    if (!$dateStr) return '-';
    
    try {
        $dateParts = explode('-', $dateStr);
        if (count($dateParts) !== 3) return $dateStr;
        
        $monthNames = [
            'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
            'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
        ];
        
        $day = intval($dateParts[2]);
        $month = intval($dateParts[1]);
        $year = intval($dateParts[0]) + 543; // แปลงเป็น พ.ศ.
        
        return $day . ' ' . $monthNames[$month-1] . ' ' . $year;
    } catch (Exception $e) {
        return $dateStr;
    }
}

// ฟังก์ชันสำหรับตรวจสอบจำนวนยาคงเหลือ
function getMedicineStock($conn, $medicineName) {
    if (!$conn) return null;
    
    try {
        $stmt = $conn->prepare("SELECT number FROM medicine WHERE name = :name");
        $stmt->bindParam(':name', $medicineName);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['number'];
        }
        return null;
    } catch(PDOException $e) {
        return null;
    }
}

// ฟังก์ชันสำหรับตรวจสอบข้อมูลคลังยาเพิ่มเติม
function getMedicineDetails($conn, $medicineName) {
    if (!$conn) return null;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM medicine WHERE name = :name");
        $stmt->bindParam(':name', $medicineName);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result;
    } catch(PDOException $e) {
        return null;
    }
}
function getMedicineStockStatus($stock, $requiredQuantity) {
    $stock = intval($stock);
    $requiredQuantity = intval($requiredQuantity);
    
    if ($stock <= 0) {
        return 'out'; // ยาหมด
    } elseif ($stock < $requiredQuantity) {
        return 'low'; // ยาไม่พอจ่าย
    } else {
        return 'normal'; // ยาเพียงพอ
    }
}



// ฟังก์ชันสำหรับบันทึกการจ่ายยา
function recordMedicationDispensed($conn, $patientId, $medicineName, $quantity, $location = '') {
    if (!$conn) return false;
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ตรวจสอบจำนวนยาในคลัง
        $stmt = $conn->prepare("SELECT number FROM medicine WHERE name = :name FOR UPDATE");
        $stmt->bindParam(':name', $medicineName);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$result || $result['number'] < $quantity) {
            // ยาไม่พอจ่าย
            $conn->rollBack();
            return false;
        }
        
        // บันทึกการจ่ายยา
        $stmt = $conn->prepare("INSERT INTO medications (patient_id, medicine_name, quantity, location, created_at) 
                               VALUES (:patient_id, :medicine_name, :quantity, :location, NOW())");
        $stmt->bindParam(':patient_id', $patientId);
        $stmt->bindParam(':medicine_name', $medicineName);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':location', $location);
        $stmt->execute();
        
        // อัพเดทจำนวนยาในคลัง
        $stmt = $conn->prepare("UPDATE medicine SET number = number - :quantity WHERE name = :name");
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':name', $medicineName);
        $stmt->execute();
        
        // จบ transaction
        $conn->commit();
        return true;
    } catch(PDOException $e) {
        // มีข้อผิดพลาด ยกเลิก transaction
        $conn->rollBack();
        return false;
    }
}

// ฟังก์ชันสำหรับยกเลิกการจ่ายยา (คืนยาเข้าคลัง)
function cancelMedicationDispensed($conn, $patientId, $medicineName, $quantity) {
    if (!$conn) return false;
    
    try {
        // เริ่ม transaction
        $conn->beginTransaction();
        
        // ลบบันทึกการจ่ายยาล่าสุดของผู้ป่วย
        $stmt = $conn->prepare("DELETE FROM medications 
                               WHERE patient_id = :patient_id AND medicine_name = :medicine_name 
                               ORDER BY created_at DESC LIMIT 1");
        $stmt->bindParam(':patient_id', $patientId);
        $stmt->bindParam(':medicine_name', $medicineName);
        $stmt->execute();
        
        // อัพเดทจำนวนยาในคลัง (เพิ่มขึ้นตามที่คืน)
        $stmt = $conn->prepare("UPDATE medicine SET number = number + :quantity WHERE name = :name");
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':name', $medicineName);
        $stmt->execute();
        
        // จบ transaction
        $conn->commit();
        return true;
    } catch(PDOException $e) {
        // มีข้อผิดพลาด ยกเลิก transaction
        $conn->rollBack();
        return false;
    }
}

// ตัวแปรสำหรับเก็บข้อมูลผู้ป่วย
$patientData = null;
$errorMessage = '';

// กรณีมี ID
if ($id) {
    // ลองค้นหาข้อมูลจากฐานข้อมูล (จำลองการค้นหา)
    // ในสภาพแวดล้อมจริง คุณจะเชื่อมต่อกับฐานข้อมูลและดึงข้อมูลจากตาราง patients
    // ตัวอย่างเช่น: $patientData = fetchPatientFromDatabase($id);
    
    // สำหรับตัวอย่างนี้ เราจะใช้ข้อมูลจำลอง
    if ($id === 'sample123') {
        $patientData = [
            'recordId' => 'sample123',
            'patientName' => 'ทดสอบ สาธิต',
            'hospitalNumber' => 'HN12345',
            'dob' => '1990-05-15',
            'gender' => 'male',
            'chiefComplaint' => 'ปวดศีรษะ มีไข้',
            'heightWeight' => '170 ซม. / 65 กก.',
            'drugAllergies' => 'พาราเซตามอล',
            'presentIllness' => 'มีอาการไข้ ปวดศีรษะมา 2 วัน',
            'medications' => [
                [
                    'id' => '1',
                    'name' => 'พาราเซตามอล (Paracetamol)',
                    'quantity' => '10',
                    'location' => 'ชั้น A ตู้ 3',
                    'dispensed' => false
                ],
                [
                    'id' => '2',
                    'name' => 'แก้ไอน้ำดำ (Cough Syrup)',
                    'quantity' => '1',
                    'location' => 'ชั้น C ตู้ 1',
                    'dispensed' => false
                ]
            ]
        ];
    } else {
        // ในกรณีจริง คุณจะเชื่อมต่อกับฐานข้อมูลและค้นหาข้อมูลจาก ID
        // $patientData = fetchPatientFromDatabase($id);
        
        // หากไม่พบข้อมูลในฐานข้อมูล ให้แสดงข้อความแจ้งเตือน
        $errorMessage = 'ไม่พบข้อมูลผู้ป่วยจาก ID ที่ระบุ: ' . htmlspecialchars($id);
    }
}
// กรณีมีข้อมูล JSON ใน data parameter
else if ($data) {
    try {
        // พยายามแปลงข้อมูล JSON เป็น associative array
        $patientData = json_decode(urldecode($data), true);
        
        // ตรวจสอบว่าข้อมูลถูกต้องหรือไม่
        if (!$patientData || !isset($patientData['patientName'])) {
            throw new Exception('ข้อมูลไม่ถูกต้องหรือไม่สมบูรณ์');
        }
        
        // เพิ่ม id ให้ยาแต่ละตัว (ถ้ายังไม่มี) และสถานะการให้ยา
        if (isset($patientData['medications']) && is_array($patientData['medications'])) {
            foreach ($patientData['medications'] as $key => $med) {
                if (!isset($med['id'])) {
                    $patientData['medications'][$key]['id'] = ($key + 1);
                }
                if (!isset($med['dispensed'])) {
                    $patientData['medications'][$key]['dispensed'] = false;
                }
                
                // ตรวจสอบสต็อกคงเหลือถ้าเชื่อมต่อ DB ได้
                if ($conn && isset($med['name'])) {
                    // ดึงข้อมูลยาจากฐานข้อมูล
                    $medicineDetails = getMedicineDetails($conn, $med['name']);
                    
                    if ($medicineDetails) {
                        $patientData['medications'][$key]['stock'] = $medicineDetails['number'];
                        $patientData['medications'][$key]['location'] = $patientData['medications'][$key]['location'] ?? 
                            "แถว {$medicineDetails['row']} ชั้น {$medicineDetails['shelf']}";
                    }
                }
            }
        }
    } catch (Exception $e) {
        $errorMessage = 'ไม่สามารถแปลงข้อมูลได้: ' . $e->getMessage();
    }
}
// กรณีมีข้อมูลดิบ (raw data)
else if ($raw_data) {
    // พยายามทำความเข้าใจข้อมูลดิบ (อาจเป็น Base64, plain text, หรืออื่นๆ)
    $decoded = false;
    
    // ลองถอดรหัส Base64
    try {
        $decodedText = base64_decode($raw_data, true);
        if ($decodedText !== false) {
            // ลองแปลงเป็น UTF-8
            $jsonString = urldecode(implode('', array_map(function($c) {
                return '%' . sprintf('%02x', ord($c));
            }, str_split($decodedText))));
            
            // ลองแปลงเป็น JSON
            $jsonData = json_decode($jsonString, true);
            if ($jsonData && isset($jsonData['patientName'])) {
                $patientData = $jsonData;
                $decoded = true;
                
                // เพิ่ม id ให้ยาแต่ละตัว (ถ้ายังไม่มี) และสถานะการให้ยา
                if (isset($patientData['medications']) && is_array($patientData['medications'])) {
                    foreach ($patientData['medications'] as $key => $med) {
                        if (!isset($med['id'])) {
                            $patientData['medications'][$key]['id'] = ($key + 1);
                        }
                        if (!isset($med['dispensed'])) {
                            $patientData['medications'][$key]['dispensed'] = false;
                        }
                        
                        // ตรวจสอบสต็อกคงเหลือถ้าเชื่อมต่อ DB ได้
                        if ($conn && isset($med['name'])) {
                            // ดึงข้อมูลยาจากฐานข้อมูล
                            $medicineDetails = getMedicineDetails($conn, $med['name']);
                            
                            if ($medicineDetails) {
                                $patientData['medications'][$key]['stock'] = $medicineDetails['number'];
                                $patientData['medications'][$key]['location'] = $patientData['medications'][$key]['location'] ?? 
                                    "แถว {$medicineDetails['row']} ชั้น {$medicineDetails['shelf']}";
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // ไม่สามารถถอดรหัส Base64 ได้
    }
    
    // ถ้ายังไม่สามารถถอดรหัสได้ ลองแปลงโดยตรงเป็น JSON
    if (!$decoded) {
        try {
            $jsonData = json_decode($raw_data, true);
            if ($jsonData && isset($jsonData['patientName'])) {
                $patientData = $jsonData;
                $decoded = true;
                
                // เพิ่ม id ให้ยาแต่ละตัว (ถ้ายังไม่มี) และสถานะการให้ยา
                if (isset($patientData['medications']) && is_array($patientData['medications'])) {
                    foreach ($patientData['medications'] as $key => $med) {
                        if (!isset($med['id'])) {
                            $patientData['medications'][$key]['id'] = ($key + 1);
                        }
                        if (!isset($med['dispensed'])) {
                            $patientData['medications'][$key]['dispensed'] = false;
                        }
                        
                        // ตรวจสอบสต็อกคงเหลือถ้าเชื่อมต่อ DB ได้
                        if ($conn && isset($med['name'])) {
                            // ดึงข้อมูลยาจากฐานข้อมูล
                            $medicineDetails = getMedicineDetails($conn, $med['name']);
                            
                            if ($medicineDetails) {
                                $patientData['medications'][$key]['stock'] = $medicineDetails['number'];
                                $patientData['medications'][$key]['location'] = $patientData['medications'][$key]['location'] ?? 
                                    "แถว {$medicineDetails['row']} ชั้น {$medicineDetails['shelf']}";
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // ไม่สามารถแปลงเป็น JSON ได้
        }
    }
    
    // ถ้าไม่สามารถถอดรหัสได้ทั้งหมด
    if (!$decoded) {
        $errorMessage = 'ไม่สามารถถอดรหัสหรือเข้าใจข้อมูลดิบที่ได้รับ';
    }
}
// ถ้าไม่มีพารามิเตอร์ใดเลย
else {
    $errorMessage = 'ไม่ได้ระบุพารามิเตอร์ id, data หรือ raw_data';
}

// สร้าง unique ID สำหรับคำขอนี้ถ้ายังไม่มี recordId
if ($patientData && !isset($patientData['recordId'])) {
    $patientData['recordId'] = uniqid('med_');
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title>ข้อมูลผู้ป่วย - ระบบตรวจสอบยา</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
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
            background-image: url('im/log in (9).png');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            min-height: 100vh;
            padding: 20px;
            overflow-x: hidden;
        }
        
        .page-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .scan-section {
            width: 340px; /* ลดความกว้างลงเล็กน้อย */
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            position: sticky;
            top: 20px;
            align-self: flex-start;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .main-section {
            flex: 1;
            min-width: 300px;
            max-width: 900px; /* เพิ่มความกว้างสูงสุด */
            background-color: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .page-title {
            text-align: center;
            color: #555;
            margin-bottom: 25px;
            padding-bottom: 15px;
            position: relative;
            font-size: 1.8rem;
            font-weight: 400;
            border-bottom: 1px solid #eee;
        }
        
        .scan-title {
            text-align: center;
            color: #555;
            margin-bottom: 15px;
            padding-bottom: 10px;
            position: relative;
            font-size: 1.2rem;
            font-weight: 400;
            border-bottom: 1px solid #eee;
        }
        
        #reader {
            width: 100%;
            margin: 0 auto;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .scan-buttons {
            display: flex;
            gap: 10px;
            margin: 15px 0;
        }
        
        .scan-btn {
            padding: 8px 15px;
            background-color: #432208;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            flex: 1;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .scan-btn:hover {
            background-color: #5a3110;
        }
        
        .scan-btn:disabled {
            background-color: #9e9e9e;
            cursor: not-allowed;
        }
        
        .scan-result {
            margin-top: 15px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 10px;
            display: none;
        }
        
        .result-title {
            font-weight: 500;
            margin-bottom: 5px;
            color: #432208;
        }
        
        .result-content {
            font-size: 0.9rem;
            color: #555;
        }
        
        .result-success {
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .result-error {
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 5px;
            font-size: 0.9rem;
        }
        
        .file-upload {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px dashed #ccc;
        }
        
        .file-upload-title {
            font-size: 1rem;
            color: #555;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .upload-btn-wrapper {
            position: relative;
            overflow: hidden;
            display: block;
            text-align: center;
        }
        
        .upload-btn {
            padding: 8px 15px;
            background-color: #6b6b6b;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .upload-btn:hover {
            background-color: #555;
        }
        
        .upload-btn-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .uploaded-image-container {
            margin-top: 15px;
            text-align: center;
            display: none;
        }
        
        .uploaded-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .patient-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 10px;
        }
        
        .patient-name {
            font-size: 1.4rem;
            color: #432208;
            font-weight: 500;
        }
        
        .patient-hn {
            font-size: 1rem;
            color: #666;
            background-color: #f0f0f0;
            padding: 5px 12px;
            border-radius: 15px;
            font-weight: 500;
        }
        
        .info-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: #432208;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item {
            display: flex;
            margin-bottom: 12px;
        }
        
        .info-label {
            font-weight: 500;
            width: 140px;
            color: #666;
            flex-shrink: 0;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .med-items {
            margin-top: 15px;
        }
        
        .med-item {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            border-left: 3px solid #432208;
            position: relative;
            transition: background-color 0.3s, border-color 0.3s;
        }
        
        .med-item.dispensed {
            background-color: #e8f4e8;
            border-left: 3px solid #4CAF50;
        }
        
        .med-item.highlight {
            background-color: #fff3cd;
            border-left: 3px solid #ffc107;
            animation: highlight 2s ease;
        }
        
        .med-item.low-stock {
            background-color: #fff3e0;
            border-left: 3px solid #ff9800;
        }
        
        .med-item.out-of-stock {
            background-color: #ffebee;
            border-left: 3px solid #f44336;
        }
        
        @keyframes highlight {
            0% { background-color: #fff3cd; }
            50% { background-color: #ffe082; }
            100% { background-color: #fff3cd; }
        }
        
        .med-checkbox {
            position: absolute;
            right: 15px;
            top: 15px;
            transform: scale(1.3);
            cursor: pointer;
        }
        
        .med-item-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding-right: 40px; /* Space for checkbox */
        }
        
        .med-name {
            font-weight: 500;
            color: #432208;
            font-size: 1.1rem;
        }
        
        .med-qty {
            color: #555;
        }
        
        .med-location {
            font-size: 0.9rem;
            color: #777;
            margin-top: 5px;
        }
        
        .med-stock {
            font-size: 0.9rem;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 8px;
        }
        
        .med-stock.normal {
            background-color: #e8f5e9;
            color: #2e7d32;
        }
        
        .med-stock.low {
            background-color: #fff8e1;
            color: #ff8f00;
        }
        
        .med-stock.out {
            background-color: #ffebee;
            color: #c62828;
        }
        
        .med-status {
            font-size: 0.8rem;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
            color: #4CAF50;
            display: none;
        }
        
        .med-status.visible {
            display: block;
        }
        
        .error-container {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .error-title {
            font-weight: 500;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }
        
        .error-message {
            font-size: 0.9rem;
        }
        
        .btn-container {
            display: flex;
            justify-content: center;
            margin-top: 30px;
            gap: 15px;
        }
        
        .btn {
            padding: 10px 20px;
            border: 2px solid #432208;
            background-color: #432208;
            color: white;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 400;
            transition: all 0.3s ease;
            min-width: 130px;
            text-align: center;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: white;
            color: #432208;
        }
        
        .btn-outline {
            background-color: white;
            color: #432208;
        }
        
        .btn-outline:hover {
            background-color: #432208;
            color: white;
        }
        
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: rgba(76, 175, 80, 0.9);
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            transform: translateX(200%);
            transition: transform 0.3s ease;
            z-index: 9999;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        @media print {
            .scan-section, .notification, .scan-btn, .btn-container {
                display: none !important;
            }
            
            .page-wrapper {
                display: block;
            }
            
            .main-section {
                box-shadow: none;
                padding: 0;
            }
            
            .med-checkbox {
                display: none !important;
            }
            
            .med-status.visible {
                display: block !important;
                color: #000 !important;
            }
            
            body {
                background: none;
                padding: 0;
            }
        }
        
        @media (max-width: 768px) {
            .page-wrapper {
                flex-direction: column;
            }
            
            .scan-section {
                position: relative;
                top: 0;
                width: 100%;
                margin-bottom: 20px;
            }
            
            .main-section {
                padding: 20px;
            }
            
            .patient-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .patient-name {
                margin-bottom: 10px;
            }
            
            .info-item {
                flex-direction: column;
            }
            
            .info-label {
                margin-bottom: 5px;
                width: 100%;
            }
            
            .btn-container {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
		.modal {
    display: none;
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from {opacity: 0}
    to {opacity: 1}
}

.modal-content {
    position: relative;
    background-color: #fefefe;
    margin: 10% auto;
    padding: 0;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    width: 500px;
    max-width: 90%;
    animation: slideDown 0.3s;
}

@keyframes slideDown {
    from {transform: translateY(-50px); opacity: 0;}
    to {transform: translateY(0); opacity: 1;}
}

.modal-header {
    padding: 15px 20px;
    background-color: #f44336;
    color: white;
    border-radius: 10px 10px 0 0;
    display: flex;
    align-items: center;
}

.modal-icon {
    font-size: 24px;
    margin-right: 10px;
}

.close-modal {
    color: white;
    float: right;
    font-size: 28px;
    font-weight: bold;
    margin-left: auto;
}

.close-modal:hover,
.close-modal:focus {
    color: #f8f8f8;
    text-decoration: none;
    cursor: pointer;
}

.modal-body {
    padding: 20px;
    font-size: 16px;
    line-height: 1.5;
}

.modal-footer {
    padding: 15px 20px;
    background-color: #f9f9f9;
    border-top: 1px solid #e9e9e9;
    border-radius: 0 0 10px 10px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

#alertMedicationName {
    font-size: 18px;
    font-weight: 500;
    margin-bottom: 15px;
    color: #f44336;
}

/* ปรับแต่งปุ่มในโมดัล */
.modal-footer .btn {
    padding: 8px 15px;
    min-width: 120px;
}

#goToInventoryBtn {
    background-color: #f44336;
    border-color: #f44336;
}

#goToInventoryBtn:hover {
    background-color: white;
    color: #f44336;
}

/* ปรับปรุง CSS สำหรับรายการยาที่หมดสต็อก */
.med-item.out-of-stock {
    position: relative;
    transition: all 0.3s ease;
    cursor: pointer;
}

.med-item.out-of-stock:hover {
    box-shadow: 0 2px 8px rgba(244, 67, 54, 0.3);
    transform: translateY(-2px);
}

.med-item.out-of-stock .med-checkbox {
    pointer-events: none;
    opacity: 0.5;
    cursor: not-allowed;
}

/* เพิ่ม tooltip สำหรับรายการยาที่หมดสต็อก */
.item-tooltip {
    visibility: hidden;
    position: absolute;
    background-color: rgba(97, 97, 97, 0.9);
    color: white;
    text-align: center;
    border-radius: 6px;
    padding: 5px 10px;
    z-index: 10;
    font-size: 12px;
    bottom: -30px;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.3s;
    white-space: nowrap;
}

.med-item.out-of-stock:hover .item-tooltip {
    visibility: visible;
    opacity: 1;
}

/* ไอคอนแจ้งเตือนสำหรับยาที่หมด */
.out-of-stock-icon {
    display: inline-block;
    margin-left: 8px;
    color: #f44336;
    font-size: 16px;
}
    </style>
</head>
<body>
    <div class="notification" id="notification">บันทึกข้อมูลสำเร็จ</div>
    
    <div class="page-wrapper">
        <!-- ส่วนสแกน QR Code ด้านซ้าย -->
        <div class="scan-section">
            <h2 class="scan-title">สแกน QR Code ยา</h2>
            
            <div id="reader"></div>
            
            <div class="scan-buttons">
                <button class="scan-btn" id="startButton">เริ่มสแกน</button>
                <button class="scan-btn" id="stopButton" disabled>หยุดสแกน</button>
            </div>
            
            <!-- เพิ่มส่วนอัปโหลดรูปภาพ -->
            <div class="file-upload">
                <div class="file-upload-title">หรือ อัปโหลดรูปภาพ QR Code</div>
                
                <div class="upload-btn-wrapper">
                    <button class="upload-btn">เลือกไฟล์รูปภาพ</button>
                    <input type="file" id="qrFileInput" accept="image/*" />
                </div>
                
                <div class="uploaded-image-container" id="uploadedImageContainer">
                    <img src="#" alt="Uploaded QR Code" class="uploaded-image" id="uploadedImage">
                </div>
            </div>
            
            <div class="scan-result" id="scanResult">
                <div class="result-title">ผลการสแกน</div>
                <div class="result-content" id="resultContent"></div>
                <div class="result-success" id="resultSuccess" style="display: none;"></div>
                <div class="result-error" id="resultError" style="display: none;"></div>
            </div>


            
            <div style="margin-top: 15px;">
                <p style="font-size: 14px; color: #666;">
                    คำแนะนำ: สแกน QR Code ของยาแต่ละรายการเพื่อติกเครื่องหมายถูกที่รายการยาโดยอัตโนมัติ
                </p>
            </div>
            
            <!-- เพิ่มลิงค์ไปหน้าดูข้อมูลคลังยา -->
            <div style="margin-top: 20px; text-align: center;">
                <a href="medicine-info.php" style="color: #432208; text-decoration: none; font-weight: 500;">
                    ดูข้อมูลคลังยา
                </a>
            </div>
        </div>
        
        <!-- ส่วนแสดงข้อมูลผู้ป่วยด้านขวา -->
        <div class="main-section">
            <?php if ($errorMessage): ?>
                <!-- แสดงข้อความแจ้งเตือนกรณีเกิดข้อผิดพลาด -->
                <div class="error-container">
                    <div class="error-title">ไม่สามารถแสดงข้อมูลผู้ป่วยได้</div>
                    <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
                </div>
                
                <div class="btn-container">
                    <a href="qr.php" class="btn">กลับไปยังหน้าสแกน</a>
                </div>
            <?php else: ?>
                <!-- แสดงข้อมูลผู้ป่วย -->
                <h1 class="page-title">ข้อมูลผู้ป่วยและรายการยา</h1>
                
                <div class="patient-header">
                    <div class="patient-name"><?php echo htmlspecialchars($patientData['patientName'] ?? '-'); ?></div>
                    <div class="patient-hn">HN: <?php echo htmlspecialchars($patientData['hospitalNumber'] ?? '-'); ?></div>
                </div>
                
                <div class="info-section">
                    <h2 class="section-title">ข้อมูลทั่วไป</h2>
                    
                    <div class="info-item">
                        <div class="info-label">วันเกิด:</div>
                        <div class="info-value">
                            <?php
                                echo isset($patientData['dob']) ? formatThaiDate($patientData['dob']) : '-';
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">เพศ:</div>
                        <div class="info-value">
                            <?php
                                if (isset($patientData['gender'])) {
                                    if ($patientData['gender'] === 'male') {
                                        echo 'ชาย';
                                    } else if ($patientData['gender'] === 'female') {
                                        echo 'หญิง';
                                    } else {
                                        echo htmlspecialchars($patientData['gender']);
                                    }
                                } else {
                                    echo '-';
                                }
                            ?>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">อาการสำคัญ:</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['chiefComplaint'] ?? '-'); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">ส่วนสูง/น้ำหนัก:</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['heightWeight'] ?? '-'); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">แพ้ยา:</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['drugAllergies'] ?? 'ไม่มี'); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">อาการปัจจุบัน:</div>
                        <div class="info-value"><?php echo htmlspecialchars($patientData['presentIllness'] ?? '-'); ?></div>
                    </div>
                </div>
                
                <div class="info-section">
                    <h2 class="section-title">รายการยา</h2>
                    
                    <div class="med-items" id="medicationsList">
                        <?php foreach ($patientData['medications'] as $index => $med): ?>
                            <?php 
                                $stockClass = '';
                                $stockText = '';
                                $stockStatus = 'unknown';
                                $isDisabled = false;
                                
                                if (isset($med['stock'])) {
                                    $stock = intval($med['stock']);
                                    $requestQty = intval($med['quantity'] ?? 1);
                                    
                                    // ตรวจสอบระดับสต็อก
                                    $stockStatus = getMedicineStockStatus($stock, $requestQty);
                                    
                                    if ($stockStatus === 'out') {
                                        $stockClass = 'out-of-stock';
                                        $stockText = '<span class="med-stock out">ยาหมด!</span>';
                                        $isDisabled = true; // Disable checkbox ถ้ายาหมด
                                    } elseif ($stockStatus === 'low') {
                                        $stockClass = 'low-stock';
                                        $stockText = '<span class="med-stock low">ยาเหลือน้อย ('.$stock.'/'.$requestQty.')</span>';
                                    } else {
                                        $stockText = '<span class="med-stock normal">ยาคงเหลือ: '.$stock.'</span>';
                                    }
                                }
                            ?>
                            <div class="med-item <?php echo ($med['dispensed'] ?? false) ? 'dispensed' : $stockClass; ?>" 
                                data-id="<?php echo htmlspecialchars($med['id'] ?? $index); ?>" 
                                data-name="<?php echo htmlspecialchars($med['name'] ?? ''); ?>"
                                data-stock="<?php echo isset($med['stock']) ? intval($med['stock']) : ''; ?>"
                                data-quantity="<?php echo intval($med['quantity'] ?? 1); ?>"
                                data-status="<?php echo $stockStatus; ?>">
                                
                                <input type="checkbox" class="med-checkbox" id="med-cb-<?php echo $index; ?>" 
                                    <?php echo ($med['dispensed'] ?? false) ? 'checked' : ''; ?>
                                    <?php echo $isDisabled ? 'disabled' : ''; ?>>
                                
                                <div class="med-item-header">
                                    <span class="med-name"><?php echo htmlspecialchars($med['name'] ?? 'ไม่ระบุชื่อยา'); ?></span>
                                    <span class="med-qty">จำนวน: <?php echo htmlspecialchars($med['quantity'] ?? '-'); ?></span>
                                </div>
                                
                                <?php if (isset($med['location']) && !empty($med['location'])): ?>
                                    <div class="med-location">ตำแหน่งจัดเก็บ: <?php echo htmlspecialchars($med['location']); ?></div>
                                <?php endif; ?>
                                
                                <?php if ($stockText): ?>
                                    <div class="med-inventory"><?php echo $stockText; ?></div>
                                <?php endif; ?>
                                
                                <div class="med-status <?php echo ($med['dispensed'] ?? false) ? 'visible' : ''; ?>" id="status-<?php echo $index; ?>">
                                    <?php if ($med['dispensed'] ?? false): ?>
                                        ✓ จ่ายยาแล้ว
                                        <?php if (isset($med['dispensedBy'])): ?>
                                            โดย: <?php echo htmlspecialchars($med['dispensedBy']); ?>
                                        <?php endif; ?>
                                        <?php if (isset($med['dispensedTime'])): ?>
                                            เมื่อ: <?php echo htmlspecialchars($med['dispensedTime']); ?>
                                        <?php endif; ?>
                                        <?php if (isset($med['isPartial']) && $med['isPartial'] && isset($med['dispensedQuantity'])): ?>
                                            <span class="partial-label">(จ่ายบางส่วน: <?php echo $med['dispensedQuantity']; ?>/<?php echo $med['quantity']; ?>)</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ตัวแปรสำหรับเก็บข้อมูลผู้ป่วยและรายการยา
            const patientData = <?php echo $patientData ? json_encode($patientData, JSON_UNESCAPED_UNICODE) : 'null'; ?>;
            const medsCheckboxes = document.querySelectorAll('.med-checkbox');
            
            // ตัวแปรสำหรับ QR Scanner
            let html5QrCode;
            let isScanning = false;
            
            // ปุ่มควบคุมการสแกน
            const startButton = document.getElementById('startButton');
            const stopButton = document.getElementById('stopButton');
            
            // ส่วนแสดงผลการสแกน
            const scanResult = document.getElementById('scanResult');
            const resultContent = document.getElementById('resultContent');
            const resultSuccess = document.getElementById('resultSuccess');
            const resultError = document.getElementById('resultError');
            
            // ส่วนอัปโหลดรูปภาพ
            const qrFileInput = document.getElementById('qrFileInput');
            const uploadedImageContainer = document.getElementById('uploadedImageContainer');
            const uploadedImage = document.getElementById('uploadedImage');
            
            // สำหรับ localStorage
            <?php if ($id && $errorMessage): ?>
                // ถ้ามี ID แต่ไม่พบในฐานข้อมูล ลองค้นหาใน localStorage
                const savedData = localStorage.getItem(`patient_<?php echo addslashes($id); ?>`);
                if (savedData) {
                    try {
                        // ถ้าพบข้อมูลใน localStorage ให้โหลดหน้าใหม่พร้อมส่งข้อมูล
                        const patientData = JSON.parse(savedData);
                        const encodedData = encodeURIComponent(JSON.stringify(patientData));
                        window.location.href = `view.php?data=${encodedData}`;
                    } catch (e) {
                        console.error("Error loading patient data from localStorage:", e);
                    }
                }
            <?php endif; ?>
            
            // ตรวจสอบการเปลี่ยนแปลงของการติก checkbox ยา
            medsCheckboxes.forEach((checkbox, index) => {
                checkbox.addEventListener('change', function() {
                    const medItem = this.closest('.med-item');
                    const medName = medItem.getAttribute('data-name');
                    const stock = parseInt(medItem.getAttribute('data-stock'));
                    const qtyNeeded = parseInt(medItem.querySelector('.med-qty').textContent.replace(/[^\d]/g, '') || 1);
                    const patientId = patientData.hospitalNumber || 'unknown';
                    const location = medItem.querySelector('.med-location') ? 
                                    medItem.querySelector('.med-location').textContent.replace('ตำแหน่งจัดเก็บ: ', '') : '';
                    
                    if (this.checked) {
                        // ตรวจสอบสต็อกก่อนจ่ายยา
                        if (!isNaN(stock) && stock <= 0) {
                            // กรณียาหมด
                            showNotification('ไม่สามารถจ่ายยาได้: ยาหมดสต็อก', 'error');
                            this.checked = false;
                            
                            // แสดง Modal แจ้งเตือนยาหมดสต็อก
                            showStockAlertModal(medName);
                            return;
                        } else if (!isNaN(stock) && stock < qtyNeeded) {
                            // กรณียาไม่พอ
                            if (confirm(`ยาเหลือไม่พอจ่าย (เหลือ ${stock} รายการ แต่ต้องการ ${qtyNeeded} รายการ) ต้องการจ่ายยาบางส่วนหรือไม่?`)) {
                                // ผู้ใช้ยืนยันจ่ายยาบางส่วน
                                showNotification(`จ่ายยาบางส่วน: ${stock}/${qtyNeeded} รายการ`, 'warning');
                                
                                // บันทึกการจ่ายยาบางส่วนลงฐานข้อมูล
                                fetch('api-dispense.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                    },
                                    body: `action=dispense&patient_id=${patientId}&medicine_name=${encodeURIComponent(medName)}&quantity=${stock}&location=${encodeURIComponent(location)}`
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (!data.success) {
                                        showNotification(data.message, 'error');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    showNotification('เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
                                });
                            } else {
                                // ผู้ใช้ยกเลิกการจ่ายยา
                                this.checked = false;
                                return;
                            }
                        } else {
                            // กรณียาเพียงพอ - บันทึกการจ่ายยาลงฐานข้อมูล
                            fetch('api-dispense.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `action=dispense&patient_id=${patientId}&medicine_name=${encodeURIComponent(medName)}&quantity=${qtyNeeded}&location=${encodeURIComponent(location)}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (!data.success) {
                                    // หากไม่สามารถจ่ายยาได้ ให้ยกเลิกการติกเช็คบ็อกซ์
                                    this.checked = false;
                                    showNotification(data.message, 'error');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                this.checked = false;
                                showNotification('เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
                            });
                        }
                        
                        medItem.classList.add('dispensed');
                        medItem.classList.remove('low-stock', 'out-of-stock');
                        
                        // แสดงสถานะการจ่ายยา
                        const statusElement = medItem.querySelector('.med-status');
                        const now = new Date();
                        const formattedTime = `${now.getDate()}/${now.getMonth()+1}/${now.getFullYear() + 543} ${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
                        
                        if (!isNaN(stock) && stock < qtyNeeded) {
                            // กรณีจ่ายยาบางส่วน
                            statusElement.innerHTML = `✓ จ่ายยาแล้ว เมื่อ: ${formattedTime} <span class="partial-label">(จ่ายบางส่วน: ${stock}/${qtyNeeded})</span>`;
                        } else {
                            statusElement.textContent = `✓ จ่ายยาแล้ว เมื่อ: ${formattedTime}`;
                        }
                        
                        statusElement.classList.add('visible');
                        
                        // บันทึกการเปลี่ยนแปลงใน localStorage
                        if (!isNaN(stock) && stock < qtyNeeded) {
                            updateMedicationStatus(index, true, stock, qtyNeeded);
                        } else {
                            updateMedicationStatus(index, true);
                        }
                        
                        // แสดงการแจ้งเตือน
                        showNotification(`บันทึกการจ่ายยา ${medName || 'รายการที่ ' + (index + 1)} เรียบร้อยแล้ว`);
                    } else {
                        // ยกเลิกการจ่ายยา (คืนยาเข้าคลัง)
                        fetch('api-dispense.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `action=cancel&patient_id=${patientId}&medicine_name=${encodeURIComponent(medName)}&quantity=${qtyNeeded}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (!data.success) {
                                // หากไม่สามารถยกเลิกการจ่ายยาได้ ให้ติกเช็คบ็อกซ์กลับ
                                this.checked = true;
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            this.checked = true;
                            showNotification('เกิดข้อผิดพลาดในการยกเลิกการจ่ายยา', 'error');
                        });
                        
                        medItem.classList.remove('dispensed');
                        
                        // คืนสถานะการแสดงผลสต็อกกลับไป
                        if (!isNaN(stock)) {
                            if (stock <= 0) {
                                medItem.classList.add('out-of-stock');
                            } else if (stock < qtyNeeded) {
                                medItem.classList.add('low-stock');
                            }
                        }
                        
                        // ซ่อนสถานะการจ่ายยา
                        const statusElement = medItem.querySelector('.med-status');
                        statusElement.textContent = '';
                        statusElement.classList.remove('visible');
                        
                        // บันทึกการเปลี่ยนแปลงใน localStorage
                        updateMedicationStatus(index, false);
                        
                        // แสดงการแจ้งเตือน
                        showNotification(`ยกเลิกการจ่ายยา ${medName || 'รายการที่ ' + (index + 1)} แล้ว`, 'warning');
                    }
                });
            });
            
            // ฟังก์ชันอัปเดตสถานะของยา
            window.updateMedicationStatus = function(index, isDispensed, dispensedQty = null, totalQty = null) {
                if (!patientData || !patientData.recordId) return;
                
                // ดึงข้อมูลปัจจุบัน
                let currentData = JSON.parse(localStorage.getItem(`patient_${patientData.recordId}`)) || patientData;
                
                // อัปเดตสถานะการจ่ายยา
                if (currentData.medications && currentData.medications[index]) {
                    currentData.medications[index].dispensed = isDispensed;
                    
                    if (isDispensed) {
                        const now = new Date();
                        const formattedTime = `${now.getDate()}/${now.getMonth()+1}/${now.getFullYear() + 543} ${now.getHours()}:${now.getMinutes().toString().padStart(2, '0')}`;
                        currentData.medications[index].dispensedTime = formattedTime;
                        
                        // บันทึกข้อมูลการจ่ายยาบางส่วน (ถ้ามี)
                        if (dispensedQty !== null && totalQty !== null) {
                            currentData.medications[index].dispensedQuantity = dispensedQty;
                            currentData.medications[index].totalQuantity = totalQty;
                            currentData.medications[index].isPartial = true;
                        }
                    } else {
                        delete currentData.medications[index].dispensedTime;
                        delete currentData.medications[index].dispensedQuantity;
                        delete currentData.medications[index].totalQuantity;
                        delete currentData.medications[index].isPartial;
                    }
                }
                
                // บันทึกกลับลงใน localStorage
                localStorage.setItem(`patient_${patientData.recordId}`, JSON.stringify(currentData));
            }
            
            // ฟังก์ชันแสดงการแจ้งเตือน
            function showNotification(message, type = 'success') {
                const notification = document.getElementById('notification');
                notification.textContent = message;
                
                // เปลี่ยนสีตามประเภทการแจ้งเตือน
                if (type === 'error') {
                    notification.style.backgroundColor = 'rgba(244, 67, 54, 0.9)';
                } else if (type === 'warning') {
                    notification.style.backgroundColor = 'rgba(255, 152, 0, 0.9)';
                } else {
                    notification.style.backgroundColor = 'rgba(76, 175, 80, 0.9)';
                }
                
                notification.classList.add('show');
                
                setTimeout(() => {
                    notification.classList.remove('show');
                }, 3000);
            }
            
            // เริ่มต้นการสแกน QR Code
            startButton.addEventListener('click', startScanner);
            stopButton.addEventListener('click', stopScanner);
            
            // ฟังก์ชันเริ่มการสแกน
            function startScanner() {
                if (isScanning) return;
                
                html5QrCode = new Html5Qrcode("reader");
                const config = { 
                    fps: 10, 
                    qrbox: { width: 200, height: 200 },
                    experimentalFeatures: {
                        useBarCodeDetectorIfSupported: true
                    }
                };
                
                html5QrCode.start(
                    { facingMode: "environment" }, 
                    config,
                    onScanSuccess,
                    onScanFailure
                ).then(() => {
                    isScanning = true;
                    startButton.disabled = true;
                    stopButton.disabled = false;
                }).catch((err) => {
                    console.error("Error starting scanner:", err);
                    scanResult.style.display = 'block';
                    resultContent.textContent = "ไม่สามารถเริ่มการสแกนได้";
                    resultError.textContent = "กรุณาตรวจสอบว่าอนุญาตให้ใช้กล้องแล้ว";
                    resultError.style.display = 'block';
                    resultSuccess.style.display = 'none';
                });
            }
            
            // ฟังก์ชันหยุดการสแกน
            function stopScanner() {
                if (!isScanning || !html5QrCode) return;
                
                html5QrCode.stop().then(() => {
                    isScanning = false;
                    startButton.disabled = false;
                    stopButton.disabled = true;
                }).catch((err) => {
                    console.error("Error stopping scanner:", err);
                });
            }
            
            // ฟังก์ชันเมื่อสแกนสำเร็จ
            function onScanSuccess(decodedText, decodedResult) {
                // หยุดการสแกนชั่วคราว
                stopScanner();
                
                processQrCodeResult(decodedText);
            }
            
            // ฟังก์ชันเมื่อสแกนไม่สำเร็จ
            function onScanFailure(error) {
                // ไม่ต้องทำอะไร - เป็นปกติระหว่างการสแกน
            }
            
            // อัปโหลดรูปภาพ QR Code
            qrFileInput.addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const file = e.target.files[0];
                    const fileReader = new FileReader();
                    
                    fileReader.onload = function(e) {
                        // แสดงรูปภาพที่อัปโหลด
                        uploadedImage.src = e.target.result;
                        uploadedImageContainer.style.display = 'block';
                        
                        // แสดงผลการสแกน
                        scanResult.style.display = 'block';
                        resultContent.textContent = "กำลังประมวลผล QR Code จากรูปภาพ...";
                        resultSuccess.style.display = 'none';
                        resultError.style.display = 'none';
                        
                        // สแกน QR code จากไฟล์
                        const html5QrCodeFile = new Html5Qrcode("reader");
                        html5QrCodeFile.scanFile(file, true)
                            .then(decodedText => {
                                processQrCodeResult(decodedText);
                            })
                            .catch(err => {
                                console.error("Error scanning QR code from file:", err);
                                resultContent.textContent = "ไม่พบ QR Code ในรูปภาพ";
                                resultError.textContent = "กรุณาอัปโหลดรูปที่มี QR Code ที่ชัดเจน";
                                resultError.style.display = 'block';
                                resultSuccess.style.display = 'none';
                            });
                    };
                    
                    fileReader.readAsDataURL(file);
                }
            });

            // ตั้งค่ารายการยาที่หมดสต็อก
            setupOutOfStockItems();
            
            // ฟังก์ชันสำหรับตั้งค่ารายการยาที่หมดสต็อก
            function setupOutOfStockItems() {
                // เลือกรายการยาที่หมดสต็อกทั้งหมด
                const outOfStockItems = document.querySelectorAll('.med-item.out-of-stock');
                
                // เพิ่ม event listener และ UI elements สำหรับรายการยาที่หมดสต็อก
                outOfStockItems.forEach(item => {
                    // เพิ่มไอคอนแจ้งเตือนที่ชื่อยา
                    const medNameElement = item.querySelector('.med-name');
                    if (medNameElement && !medNameElement.querySelector('.out-of-stock-icon')) {
                        const icon = document.createElement('span');
                        icon.className = 'out-of-stock-icon';
                        icon.innerHTML = '&#9888;'; // ไอคอนเตือนภัย
                        medNameElement.appendChild(icon);
                    }
                    
                    // เพิ่ม tooltip สำหรับรายการยาที่หมดสต็อก
                    if (!item.querySelector('.item-tooltip')) {
                        const tooltip = document.createElement('div');
                        tooltip.className = 'item-tooltip';
                        tooltip.textContent = 'คลิกเพื่อดูข้อมูลยาหมดสต็อก';
                        item.appendChild(tooltip);
                    }
                    
                    // เพิ่ม event listener สำหรับคลิกที่รายการยา
                    item.addEventListener('click', function(e) {
                        // ยกเว้นการคลิกที่ checkbox
                        if (e.target.type === 'checkbox' || e.target.closest('.med-checkbox')) {
                            return;
                        }
                        
                        // ดึงชื่อยาจากข้อมูลของ element
                        const medName = this.getAttribute('data-name');
                        
                        // แสดง Modal แจ้งเตือนยาหมดสต็อก
                        showStockAlertModal(medName);
                    });
                });
            }
            
            // ฟังก์ชันแสดง Modal แจ้งเตือนยาหมดสต็อก
            window.showStockAlertModal = function(medicineName) {
                const modal = document.getElementById('stockAlertModal');
                const alertMedicationName = document.getElementById('alertMedicationName');
                
                // ตั้งค่าข้อความแจ้งเตือน
                alertMedicationName.textContent = `ยา ${medicineName} หมดสต็อกแล้ว`;
                
                // แสดง Modal
                modal.style.display = 'block';
                
                // จัดการเหตุการณ์ปิด Modal
                const closeBtn = document.querySelector('.close-modal');
                const closeModalBtn = document.getElementById('closeModalBtn');
                const goToInventoryBtn = document.getElementById('goToInventoryBtn');
                
                // ปิด Modal เมื่อคลิกที่ปุ่มปิด
                closeBtn.onclick = function() {
                    modal.style.display = 'none';
                }
                
                // ปิด Modal เมื่อคลิกที่ปุ่ม "ปิด"
                closeModalBtn.onclick = function() {
                    modal.style.display = 'none';
                }
                
                // ไปที่หน้าจัดการคลังยา
                goToInventoryBtn.onclick = function() {
                    window.location.href = 'medicine-info.php';
                }
                
                // ปิด Modal เมื่อคลิกพื้นหลัง
                window.onclick = function(event) {
                    if (event.target == modal) {
                        modal.style.display = 'none';
                    }
                }
            }
            
            // ฟังก์ชันประมวลผล QR Code
            function processQrCodeResult(decodedText) {
                // แสดงผลการสแกน
                scanResult.style.display = 'block';
                resultContent.textContent = "กำลังประมวลผล QR Code...";
                resultSuccess.style.display = 'none';
                resultError.style.display = 'none';
                
                // ลองแปลงข้อมูลที่สแกนได้
                try {
                    // ถ้าเป็น JSON โดยตรง
                    let medicationData;
                    try {
                        medicationData = JSON.parse(decodedText);
                    } catch (e) {
                        // ถ้าไม่ใช่ JSON โดยตรง อาจเป็น URL หรืออื่นๆ
                        medicationData = { id: decodedText, name: decodedText };
                    }
                    
                    // ตรวจสอบว่าเป็นข้อมูลยาหรือไม่ (มี id)
                    if (medicationData && medicationData.id) {
                        resultContent.textContent = `สแกนยา: ${medicationData.name || medicationData.id}`;
                        
                        // ค้นหายาที่ตรงกันในรายการยาของผู้ป่วย
                        let found = false;
                        const medItems = document.querySelectorAll('.med-item');
                        
                        medItems.forEach((item, index) => {
                            const medId = item.getAttribute('data-id');
                            const medName = item.getAttribute('data-name');
                            const stock = parseInt(item.getAttribute('data-stock'));
                            
                            // ตรวจสอบว่าตรงกับยาที่มีในรายการหรือไม่
                            if (medId === String(medicationData.id) || 
                                medName.toLowerCase() === (medicationData.name || '').toLowerCase()) {
                                
                                found = true;
                                
                                // ตรวจสอบว่ายาหมดสต็อกหรือไม่
                                if (item.classList.contains('out-of-stock') || (!isNaN(stock) && stock <= 0)) {
                                    resultError.textContent = 'ยานี้หมดสต็อก ไม่สามารถจ่ายได้';
                                    resultError.style.display = 'block';
                                    resultSuccess.style.display = 'none';
                                    
                                    // แสดง Modal แจ้งเตือนยาหมดสต็อก
                                    showStockAlertModal(medName);
                                    return;
                                }
                                
                                // ติกเครื่องหมายถูกที่ยารายการนี้ (ถ้ายังไม่หมดสต็อก)
                                const checkbox = item.querySelector('.med-checkbox');
                                
                                // ตรวจสอบว่า checkbox ไม่ได้ถูก disabled
                                if (!checkbox.disabled) {
                                    checkbox.checked = true;
                                    
                                    // เรียกใช้ event change เพื่อให้ทำงานเหมือนถูกคลิก
                                    const event = new Event('change', { bubbles: true });
                                    checkbox.dispatchEvent(event);
                                    
                                    // ไฮไลท์รายการยา
                                    item.classList.add('highlight');
                                    setTimeout(() => {
                                        item.classList.remove('highlight');
                                    }, 3000);
                                    
                                    // เลื่อนไปที่รายการยาที่เลือก
                                    item.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    
                                    // แสดงข้อความสำเร็จ
                                    resultSuccess.textContent = `ยา ${medName} ถูกติกเครื่องหมายถูกแล้ว`;
                                    resultSuccess.style.display = 'block';
                                    resultError.style.display = 'none';
                                } else {
                                    resultError.textContent = 'ยานี้หมดสต็อก ไม่สามารถจ่ายได้';
                                    resultError.style.display = 'block';
                                    resultSuccess.style.display = 'none';
                                    
                                    // แสดง Modal แจ้งเตือนยาหมดสต็อก
                                    showStockAlertModal(medName);
                                }
                            }
                        });
                        
                        if (!found) {
                            resultError.textContent = 'ไม่พบยานี้ในรายการยาของผู้ป่วย';
                            resultError.style.display = 'block';
                            resultSuccess.style.display = 'none';
                        }
                    } else {
                        resultContent.textContent = 'อ่าน QR Code สำเร็จ';
                        resultError.textContent = 'แต่ไม่ใช่ QR Code ของยา';
                        resultError.style.display = 'block';
                        resultSuccess.style.display = 'none';
                    }
                } catch (error) {
                    console.error("Error processing QR code:", error);
                    resultContent.textContent = 'อ่าน QR Code สำเร็จ';
                    resultError.textContent = 'แต่ไม่สามารถประมวลผลได้';
                    resultError.style.display = 'block';
                    resultSuccess.style.display = 'none';
                }
            }
        });
    </script>
    
    <!-- Modal แจ้งเตือนยาหมดสต็อก -->
    <div id="stockAlertModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <span class="close-modal">&times;</span>
                <h2><i class="modal-icon">&#9888;</i> แจ้งเตือนยาหมดสต็อก</h2>
            </div>
            <div class="modal-body">
                <p id="alertMedicationName">ยา [ชื่อยา] หมดสต็อกแล้ว</p>
                <p>กรุณาเติมยาในคลังเพื่อให้สามารถจ่ายยาได้</p>
            </div>
            <div class="modal-footer">
                <button id="goToInventoryBtn" class="btn">ไปหน้าจัดการคลังยา</button>
                <button id="closeModalBtn" class="btn btn-outline">ปิด</button>
            </div>
        </div>
    </div>
</body>
</html>