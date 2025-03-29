<?php
// การกำหนดค่าฐานข้อมูล
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mydata";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าชุดอักขระเพื่อรองรับตัวอักษรภาษาไทย
$conn->set_charset("utf8mb4");

// ฟังก์ชันสร้างไฟล์ ZIP ของ QR code ทั้งหมด
function create_zip_of_qrcodes() {
    // ตรวจสอบว่ามีคลาส ZipArchive หรือไม่
    if (!class_exists('ZipArchive')) {
        return false;
    }
    
    $zip = new ZipArchive();
    $zipname = 'qrcodes/all_qrcodes.zip';
    
    if ($zip->open($zipname, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return false;
    }
    
    // สร้างตัวนำทางไดเรกทอรีแบบเรียกซ้ำ
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator('qrcodes'),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $name => $file) {
        // ข้ามไดเรกทอรีและไฟล์ zip เอง
        if (!$file->isDir() && pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'png') {
            $filePath = $file->getRealPath();
            $relativePath = 'qrcodes/' . basename($filePath);
            $zip->addFile($filePath, basename($filePath));
        }
    }
    
    $zip->close();
    return $zipname;
}

// ตรวจสอบคำขอดาวน์โหลด
if (isset($_GET['download'])) {
    $file = 'qrcodes/med_' . $_GET['download'] . '.png';
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}

// ตรวจสอบคำขอดาวน์โหลดทั้งหมด
if (isset($_GET['download_all'])) {
    $zipfile = create_zip_of_qrcodes();
    if ($zipfile && file_exists($zipfile)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="all_qrcodes.zip"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($zipfile));
        readfile($zipfile);
        exit;
    }
}

// ตรวจสอบรายการซ้ำในฐานข้อมูล
function checkForDuplicateMedications($conn) {
    $sql = "SELECT name, COUNT(*) as count FROM medicine GROUP BY name HAVING COUNT(*) > 1";
    $result = $conn->query($sql);
    
    $duplicates = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $duplicates[] = $row["name"];
        }
    }
    
    return $duplicates;
}

// ดึงข้อมูลยาทั้งหมดจากฐานข้อมูลพร้อมการตรวจสอบเพื่อให้แน่ใจว่าไม่ซ้ำกัน
$sql = "SELECT * FROM medicine GROUP BY name";  // จัดกลุ่มตามชื่อเพื่อให้แน่ใจว่าไม่ซ้ำกัน
$query_result = $conn->query($sql);

// ตรวจสอบรายการซ้ำ
$duplicates = checkForDuplicateMedications($conn);

// รวมไลบรารี QR code
require 'vendor/autoload.php';
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;

// สร้างไดเรกทอรีสำหรับ QR code หากยังไม่มี
if (!file_exists('qrcodes')) {
    mkdir('qrcodes', 0777, true);
}

// สไตล์ CSS
echo '
<style>
    body {
        font-family: Arial, sans-serif;
        margin: 20px;
    }
    h1 {
        color: #333;
        text-align: center;
    }
    .qr-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
    }
    .qr-item {
        margin: 20px;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        width: 320px;
    }
    .qr-item img {
        max-width: 100%;
        height: auto;
    }
    .download-btn {
        background-color: #4CAF50;
        color: white;
        padding: 8px 16px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        border-radius: 4px;
        margin-top: 10px;
        cursor: pointer;
    }
    .download-all-btn {
        background-color: #2196F3;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        border-radius: 4px;
        margin: 20px auto;
        cursor: pointer;
        font-size: 16px;
    }
    .btn-container {
        text-align: center;
        margin-bottom: 30px;
    }
    .error-message {
        color: #f44336;
        text-align: center;
        margin: 20px;
        padding: 15px;
        border: 1px solid #f44336;
        border-radius: 4px;
    }
</style>
';

// แสดงคำเตือนรายการซ้ำหากมี
if (!empty($duplicates)) {
    echo "<div class='error-message'>";
    echo "<h2>พบรายการยาซ้ำในฐานข้อมูล:</h2>";
    echo "<ul>";
    foreach ($duplicates as $med) {
        echo "<li>$med</li>";
    }
    echo "</ul>";
    echo "<p>กรุณาแก้ไขข้อมูลในฐานข้อมูลเพื่อให้แน่ใจว่าไม่มีรายการซ้ำกัน</p>";
    echo "</div>";
}

// ตรวจสอบว่ามีผลลัพธ์หรือไม่
if ($query_result && $query_result->num_rows > 0) {
    // แสดงข้อมูลของยาแต่ละรายการ
    echo "<h1>QR Codes for Medications</h1>";
    
    echo "<div class='btn-container'>";
    echo "<a href='?download_all=1' class='download-all-btn'>ดาวน์โหลด QR Code ทั้งหมด (ZIP)</a>";
    echo "</div>";
    
    echo "<div class='qr-container'>";
    
    // ติดตามชื่อยาที่ประมวลผลแล้วเพื่อหลีกเลี่ยงการซ้ำซ้อน
    $processed_meds = [];
    
    while($row = $query_result->fetch_assoc()) {
        // รับข้อมูลยา
        $med_id = $row["id"];
        $med_name = $row["name"];
        
        // ข้ามหากชื่อยานี้ได้รับการประมวลผลแล้ว
        if (in_array($med_name, $processed_meds)) {
            continue;
        }
        
        // เพิ่มลงในรายการที่ประมวลผลแล้ว
        $processed_meds[] = $med_name;
        
        // ตรวจสอบว่ามีฟิลด์รายละเอียดหรือไม่
        $med_details = isset($row["details"]) ? $row["details"] : "";
        
        // สร้างเนื้อหา QR code
        $qrContent = json_encode([
            'id' => $med_id,
            'name' => $med_name,
            'details' => $med_details
        ], JSON_UNESCAPED_UNICODE);
        
        // สร้าง QR code
        $qrCode = new QrCode($qrContent);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $qrCode->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh());
        
        // สร้างตัวเขียนและสร้างภาพ QR code
        $writer = new PngWriter();
        $qr_result = $writer->write($qrCode);
        
        // บันทึก QR code ลงในไฟล์
        $filename = 'qrcodes/med_' . $med_id . '.png';
        $qr_result->saveToFile($filename);
        
        // แสดง QR code พร้อมข้อมูลยา
        echo "<div class='qr-item'>";
        echo "<img src='" . $filename . "' alt='QR Code for " . $med_name . "'>";
        echo "<p><strong>" . $med_name . "</strong></p>";
        echo "<a href='?download=" . $med_id . "' class='download-btn'>ดาวน์โหลด QR Code</a>";
        echo "</div>";
    }
    
    echo "</div>";
} else {
    echo "<h1>ไม่พบข้อมูลยาในฐานข้อมูล</h1>";
}

// ปิดการเชื่อมต่อ
$conn->close();
?>