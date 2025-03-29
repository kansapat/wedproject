<?php
// เชื่อมต่อฐานข้อมูล
$conn = mysqli_connect("localhost", "root", "", "mydata");
// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die(json_encode([
        'status' => 'error',
        'message' => 'การเชื่อมต่อฐานข้อมูลล้มเหลว: ' . $conn->connect_error
    ]));
}
// ตั้งค่า charset เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8");

// ตรวจสอบว่ามีข้อมูลส่งมาหรือไม่
if (!isset($_POST['formData']) || empty($_POST['formData'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'ไม่พบข้อมูลที่ส่งมา'
    ]);
    exit;
}
// รับข้อมูลและแปลงเป็น PHP array
$formData = json_decode($_POST['formData'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error',
        'message' => 'รูปแบบข้อมูลไม่ถูกต้อง: ' . json_last_error_msg()
    ]);
    exit;
}
// เริ่ม transaction
$conn->begin_transaction();
try {
    // เตรียมข้อมูลผู้ป่วย
    $record_id = $formData['recordId'];
    $patient_name = $conn->real_escape_string($formData['patientName']);
    $hospital_number = $conn->real_escape_string($formData['hospitalNumber']);
    $dob = $conn->real_escape_string($formData['dob']);
    $chief_complaint = $conn->real_escape_string($formData['chiefComplaint']);
    $gender = $conn->real_escape_string($formData['gender']);
    $drug_allergies = $conn->real_escape_string($formData['drugAllergies']);
    $height_weight = $conn->real_escape_string($formData['heightWeight']);
    $present_illness = $conn->real_escape_string($formData['presentIllness']);
    $current_time = date('Y-m-d H:i:s');
    
    // ตรวจสอบว่ามีข้อมูลผู้ป่วยนี้อยู่แล้วหรือไม่
    $checkSql = "SELECT id FROM patients WHERE record_id = '$record_id'";
    $checkResult = $conn->query($checkSql);
    
    if ($checkResult->num_rows > 0) {
        // อัปเดตข้อมูลผู้ป่วยที่มีอยู่แล้ว
        $updateSql = "UPDATE patients SET 
                     patient_name = '$patient_name',
                     hospital_number = '$hospital_number',
                     dob = '$dob',
                     chief_complaint = '$chief_complaint',
                     gender = '$gender',
                     drug_allergies = '$drug_allergies',
                     height_weight = '$height_weight',
                     present_illness = '$present_illness',
                     updated_at = '$current_time'
                     WHERE record_id = '$record_id'";
        
        if (!$conn->query($updateSql)) {
            throw new Exception("ไม่สามารถอัปเดตข้อมูลผู้ป่วย: " . $conn->error);
        }
    } else {
        // เพิ่มข้อมูลผู้ป่วยใหม่
        $insertSql = "INSERT INTO patients (
                     record_id, patient_name, hospital_number, dob, 
                     chief_complaint, gender, drug_allergies, 
                     height_weight, present_illness, created_at, updated_at
                     ) VALUES (
                     '$record_id', '$patient_name', '$hospital_number', '$dob', 
                     '$chief_complaint', '$gender', '$drug_allergies', 
                     '$height_weight', '$present_illness', '$current_time', '$current_time'
                     )";
        
        if (!$conn->query($insertSql)) {
            throw new Exception("ไม่สามารถเพิ่มข้อมูลผู้ป่วย: " . $conn->error);
        }
    }
    
    // สร้างตาราง medicine_prescriptions ถ้ายังไม่มี
    // ตารางนี้จะใช้เก็บข้อมูลการสั่งยาให้กับผู้ป่วย
    $createTableSql = "CREATE TABLE IF NOT EXISTS medicine_prescriptions (
                      id INT AUTO_INCREMENT PRIMARY KEY,
                      patient_record_id VARCHAR(100),
                      medicine_id INT,
                      quantity INT,
                      medicine_name VARCHAR(255),
                      location VARCHAR(255),
                      created_at DATETIME,
                      notes TEXT
                      )";
                      
    if (!$conn->query($createTableSql)) {
        throw new Exception("ไม่สามารถสร้างตารางข้อมูลยา: " . $conn->error);
    }
    
    // จัดการกับข้อมูลยา
    if (!empty($formData['medications'])) {
        foreach ($formData['medications'] as $medication) {
            $medicine_name = $conn->real_escape_string($medication['name']);
            $quantity = intval($medication['quantity']);
            $location = $conn->real_escape_string($medication['location']);
            
            // เพิ่มข้อมูลการสั่งยาในตาราง medicine_prescriptions
            $prescriptionSql = "INSERT INTO medicine_prescriptions (
                              patient_record_id, medicine_name, quantity, location, created_at
                              ) VALUES (
                              '$record_id', '$medicine_name', $quantity, '$location', '$current_time'
                              )";
            
            if (!$conn->query($prescriptionSql)) {
                throw new Exception("ไม่สามารถบันทึกข้อมูลการสั่งยา: " . $conn->error);
            }
        }
    }
    
    // ยืนยัน transaction
    $conn->commit();
    
    // ส่งการตอบกลับเมื่อสำเร็จ
    echo json_encode([
        'status' => 'success',
        'message' => 'บันทึกข้อมูลสำเร็จ',
        'recordId' => $record_id
    ]);
} catch (Exception $e) {
    // ยกเลิก transaction หากเกิดข้อผิดพลาด
    $conn->rollback();
    
    // ส่งข้อความผิดพลาดกลับไป
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
// ปิดการเชื่อมต่อฐานข้อมูล
$conn->close();
?>