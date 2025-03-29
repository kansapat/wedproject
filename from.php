<!doctype html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <title>บันทึกข้อมูลยา</title>
</head>
<body>
    <h2>แบบฟอร์มบันทึกข้อมูลยา</h2>
    <a href="1.php">กลับหน้าแรก</a>
    
    <form action="save.php" method="post">
        <div>
            <label for="name">ชื่อยา</label>
            <input type="text" name="name" id="name" required>
        </div>
        <div>
            <label for="number">จำนวนยา</label>
            <input type="number" name="number" id="number" required>
        </div>
        <div>
            <label for="row">แถว</label>
            <input type="text" name="row" id="row" required>
        </div>
        <div>
            <label for="shelf">ชั้น</label>
            <input type="number" name="shelf" id="shelf" required>
        </div>
        <div>
            <label for="notes">หมายเหตุ</label>
            <textarea name="notes" id="notes"></textarea>
        </div>
        <input type="submit" value="บันทึกข้อมูล">
    </form>
</body>
</html>
