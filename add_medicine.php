<?php
// add_medicine.php - ไฟล์สำหรับเพิ่มยาใหม่เข้าระบบ

// ตรวจสอบว่าข้อมูลถูกส่งมาหรือไม่
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_medicine_submit'])) {
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $initial_stock = isset($_POST['initial_stock']) ? intval($_POST['initial_stock']) : 0;
    $row = isset($_POST['row']) ? $_POST['row'] : '';
    $shelf = isset($_POST['shelf']) ? $_POST['shelf'] : '';
    $notes = isset($_POST['med_notes']) ? $_POST['med_notes'] : '';
    
    // เช็คข้อมูลที่จำเป็น
    if (empty($name) || $row === '' || $shelf === '') {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        try {
            // เชื่อมต่อกับฐานข้อมูล
            $conn = new PDO("mysql:host=localhost;dbname=mydata", "root", "");
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $conn->exec("SET NAMES utf8");
            
            // ตรวจสอบว่ามียาชื่อนี้ในระบบแล้วหรือไม่
            $stmt = $conn->prepare("SELECT id FROM medicine WHERE name = :name");
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $error = "มียาชื่อ '$name' ในระบบแล้ว";
            } else {
                // เพิ่มยาใหม่
                $stmt = $conn->prepare("INSERT INTO medicine (name, number, row, shelf, notes) VALUES (:name, :stock, :row, :shelf, :notes)");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':stock', $initial_stock);
                $stmt->bindParam(':row', $row);
                $stmt->bindParam(':shelf', $shelf);
                $stmt->bindParam(':notes', $notes);
                $stmt->execute();
                
                // บันทึกการเพิ่มยาใหม่ในตาราง stock_refills (ถ้ามี)
                try {
                    if ($initial_stock > 0) {
                        // ดึง ID ที่เพิ่มล่าสุด
                        $medicine_id = $conn->lastInsertId();
                        
                        $stmt = $conn->prepare("INSERT INTO stock_refills (medicine_id, quantity, notes, created_at) VALUES (:medicine_id, :quantity, :notes, NOW())");
                        $stmt->bindParam(':medicine_id', $medicine_id);
                        $stmt->bindParam(':quantity', $initial_stock);
                        $refill_note = "เพิ่มยาใหม่เข้าระบบ";
                        $stmt->bindParam(':notes', $refill_note);
                        $stmt->execute();
                    }
                } catch (PDOException $e) {
                    // ถ้าไม่มีตาราง stock_refills ให้ข้ามไป
                }
                
                // กลับไปที่หน้ารายการยาพร้อมข้อความสำเร็จ
                header("Location: medicine-info.php?success=1&message=" . urlencode("เพิ่มยา '$name' เข้าระบบเรียบร้อยแล้ว"));
                exit;
            }
        } catch (PDOException $e) {
            $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
        }
    }
    
    // มีข้อผิดพลาด กลับไปที่หน้ารายการยาพร้อมข้อความผิดพลาด
    header("Location: medicine-info.php?error=1&message=" . urlencode($error));
    exit;
}

// ถ้าเข้าถึงหน้านี้โดยตรง ให้กลับไปที่หน้ารายการยา
header("Location: medicine-info.php");
exit;