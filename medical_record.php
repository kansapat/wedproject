<?php
// ตรวจสอบการเชื่อมต่อฐานข้อมูล
$conn = mysqli_connect("localhost", "root", "", "mydata");

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("การเชื่อมต่อล้มเหลว: " . $conn->connect_error);
}

// ตั้งค่า charset เป็น utf8 เพื่อรองรับภาษาไทย
$conn->set_charset("utf8");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title>Medical Information</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.4/html5-qrcode.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            font-family: 'Prompt', 'Sarabun', sans-serif;
            height: 100vh;
            width: 100vw;
            margin: 0;
            padding: 0;
            overflow: hidden;
            background-color: #f5f5f5;
        }
        
        .container {
            display: flex;
            width: 100vw;
            height: 100vh;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .scan-section {
            flex: 0.6;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            background-image: url('im/111111.jpg'); /* รูปภาพพื้นหลัง */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
        }
        
        .scan-section::before {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background-color: rgba(0, 0, 0, 0.4); /* เพิ่มความเข้มให้ข้อความอ่านง่ายขึ้น */
            z-index: 1;
        }
        
        .scan-title {
            font-size: 4rem;
            font-weight: 300;
            letter-spacing: 8px;
            margin-bottom: 40px;
            color: #ffffff;
        }
        
        .qr-code-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            margin-top: 20px;
            text-align: center;
            display: none;
            max-width: 90%;
        }
        
        .qr-code-container h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .info-section {
            flex: 0.4;
            background-color: #e8e6e1; /* Light beige color from the image */
            padding: 40px;
            display: flex;
            flex-direction: column;
            overflow-y: auto; /* Allow scrolling if content doesn't fit */
        }
        
        .section-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 30px;
            font-weight: 500;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .info-label {
            min-width: 200px;
            font-size: 16px;
            color: #333;
        }
        
        .info-input {
            flex: 1;
            padding: 8px 10px;
            border: 1px solid #ddd;
            background-color: white;
            font-family: 'Prompt', 'Sarabun', sans-serif;
        }
        
        .divider {
            height: 1px;
            background-color: #333;
            margin: 30px 0;
            width: 100%;
        }
        
        .medicine-item {
            border: 1px solid #ddd;
            background-color: white;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 5px;
        }
        
        .medicine-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .medicine-row:last-child {
            margin-bottom: 0;
        }
        
        .medicine-label {
            min-width: 120px;
            font-size: 14px;
            color: #333;
        }
        
        .medicine-input {
            flex: 1;
            padding: 8px 10px;
            border: 1px solid #ddd;
            background-color: white;
            font-family: 'Prompt', 'Sarabun', sans-serif;
        }
        
        .add-medicine-btn {
            margin-top: 10px;
            padding: 8px 15px;
            background-color: #432208;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Prompt', 'Sarabun', sans-serif;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        
        .add-medicine-btn:hover {
            background-color: #432208;
        }
        
        .remove-medicine-btn {
            margin-left: 10px;
            padding: 5px 10px;
            background-color: #432208;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Prompt', 'Sarabun', sans-serif;
            font-size: 12px;
            transition: background-color 0.3s;
        }
        
        .remove-medicine-btn:hover {
            background-color: #432208;
        }
        
        .submit-btn {
            margin-top: 20px;
            padding: 8px 16px; /* ปรับขนาดของปุ่มให้เล็กลง */
            background-color: #432208;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Prompt', 'Sarabun', sans-serif;
            font-size: 14px; /* ลดขนาดตัวอักษร */
            transition: background-color 0.3s;
            width: auto; /* ไม่ให้ปุ่มกว้างเต็มหน้าจอ */
            display: inline-block; /* ทำให้ปุ่มมีขนาดพอดีกับเนื้อหา */
        }
        
        .submit-btn:hover {
            background-color: #432208;
        }
        
        .success-message {
            display: none;
            background-color: #dff0d8;
            color: #a59566;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }
        
        .download-btn, .view-history-btn {
            margin-top: 15px;
            padding: 12px 24px;
            background-color: #432208;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: 'Prompt', 'Sarabun', sans-serif;
            font-size: 16px;
            transition: background-color 0.3s;
            width: 100%;
        }
        
        .download-btn:hover, .view-history-btn:hover {
            background-color: #432208;
        }

        .view-history-btn {
            background-color: #432208;
            margin-top: 10px;
        }
        
        .view-history-btn:hover {
            background-color: #432208;
        }

        .btn-container {
            display: flex;
            flex-direction: column;
            margin-top: 15px;
        }
        
        /* ทำให้ปุ่มบันทึกข้อมูลอยู่ตรงกลาง */
        .submit-container {
            text-align: center;
        }
        
        @media (max-width: 992px) {
            .container {
                flex-direction: column;
            }
            
            .scan-section {
                min-height: 200px;
                flex: none;
            }
            
            .info-section {
                width: 100%;
                flex: 1;
            }
            
            .scan-title {
                font-size: 3rem;
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 576px) {
            .info-section {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .info-label {
                margin-bottom: 5px;
                min-width: unset;
            }
            
            .info-input {
                width: 100%;
            }
            
            .section-title {
                font-size: 22px;
                margin-bottom: 20px;
            }
            
            .scan-title {
                font-size: 2.5rem;
            }
            
            .medicine-row {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .medicine-label {
                margin-bottom: 5px;
                min-width: unset;
            }
            
            .medicine-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="scan-section">
            <h1 class="scan-title" style="position: relative; z-index: 2;">Medical Record</h1>
            <div class="qr-code-container" id="qrCodeContainer" style="position: relative; z-index: 2;">
                <h3>Patient QR Code</h3>
                <div id="qrCode"></div>
                <p id="patientInfo" style="margin-top: 10px; font-size: 12px; color: #333;"></p>
                <div class="btn-container">
                    <button id="downloadQrBtn" class="download-btn">ดาวน์โหลด QR Code</button>
                    <button id="viewHistoryBtn" class="view-history-btn">ดูประวัติผู้ป่วย</button>
                </div>
            </div>
        </div>
        
        <div class="info-section">
            <h2 class="section-title">General Information</h2>
            <form id="medicalForm" method="post">
                <div class="info-row">
                    <label class="info-label">- Patient name :</label>
                    <input type="text" class="info-input" id="patientName" name="patientName">
                </div>
                
                <div class="info-row">
                    <label class="info-label">- Hospital number :</label>
                    <input type="text" class="info-input" id="hospitalNumber" name="hospitalNumber">
                </div>
                
                <div class="info-row">
                    <label class="info-label">- Date of birth :</label>
                    <input type="date" class="info-input" id="dob" name="dob">
                </div>
                
                <div class="info-row">
                    <label class="info-label">- Chief complaint :</label>
                    <input type="text" class="info-input" id="chiefComplaint" name="chiefComplaint">
                </div>
                
                <div class="info-row">
                    <label class="info-label">- Gender :</label>
                    <select class="info-input" id="gender" name="gender">
                        <option value="">-- เลือก --</option>
                        <option value="male">ชาย</option>
                        <option value="female">หญิง</option>
                        <option value="other">อื่นๆ</option>
                    </select>
                </div>
                
                <div class="info-row">
                    <label class="info-label">- Drug allergies :</label>
                    <input type="text" class="info-input" id="drugAllergies" name="drugAllergies">
                </div>
                
                <div class="info-row">
                    <label class="info-label">- Height and weight :</label>
                    <input type="text" class="info-input" id="heightWeight" name="heightWeight" placeholder="สูง (ซม.) / น้ำหนัก (กก.)">
                </div>
                
                <div class="info-row">
                    <label class="info-label">- Present illness :</label>
                    <input type="text" class="info-input" id="presentIllness" name="presentIllness">
                </div>
                
                <div class="divider"></div>
                
                <!-- ส่วนของฟอร์มยาที่ปรับปรุงแล้ว -->
                <h2 class="section-title">Prescription</h2>
                <div id="medicineContainer">
                    <!-- Medicine items will be added here -->
                    <div class="medicine-item">
                        <div class="medicine-row">
                            <label class="medicine-label">ชื่อยา:</label>
                            <input type="text" class="medicine-input" name="medicineName[]" placeholder="พิมพ์เพื่อค้นหายา...">
                        </div>
                        <div class="medicine-row">
                            <label class="medicine-label">จำนวนยา:</label>
                            <input type="number" class="medicine-input" name="medicineQuantity[]" min="1">
                        </div>
                        <div class="medicine-row">
                            <label class="medicine-label">ตำแหน่งจัดเก็บ:</label>
                            <input type="text" class="medicine-input" name="medicineLocation[]" placeholder="ตำแหน่งจะแสดงอัตโนมัติเมื่อเลือกยา" readonly>
                        </div>
                        <button type="button" class="remove-medicine-btn">ลบรายการ</button>
                    </div>
                </div>
                
                <button type="button" id="addMedicineBtn" class="add-medicine-btn">+ เพิ่มรายการยา</button>
                
                <div class="submit-container">
                    <button type="submit" class="submit-btn">บันทึกข้อมูล</button>
                </div>
                
                <div class="success-message" id="successMessage">
                    บันทึกข้อมูลสำเร็จ! QR Code ได้ถูกสร้างขึ้นแล้ว
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('medicalForm');
            const successMessage = document.getElementById('successMessage');
            const qrCodeContainer = document.getElementById('qrCodeContainer');
            const patientInfoElement = document.getElementById('patientInfo');
            const downloadBtn = document.getElementById('downloadQrBtn');
            const viewHistoryBtn = document.getElementById('viewHistoryBtn');
            const addMedicineBtn = document.getElementById('addMedicineBtn');
            const medicineContainer = document.getElementById('medicineContainer');
            
            // แก้ไขข้อผิดพลาด: ต้องถอด event listener ออกและเพิ่มใหม่ทุกครั้ง
            // เพื่อป้องกันการทำงานซ้ำ
            let currentDownloadHandler = null;
            let currentViewHistoryHandler = null;
            
            // Add medicine item
            addMedicineBtn.addEventListener('click', function() {
                const newMedicineItem = document.createElement('div');
                newMedicineItem.className = 'medicine-item';
                newMedicineItem.innerHTML = `
                    <div class="medicine-row">
                        <label class="medicine-label">ชื่อยา:</label>
                        <input type="text" class="medicine-input" name="medicineName[]" placeholder="พิมพ์เพื่อค้นหายา...">
                    </div>
                    <div class="medicine-row">
                        <label class="medicine-label">จำนวนยา:</label>
                        <input type="number" class="medicine-input" name="medicineQuantity[]" min="1">
                    </div>
                    <div class="medicine-row">
                        <label class="medicine-label">ตำแหน่งจัดเก็บ:</label>
                        <input type="text" class="medicine-input" name="medicineLocation[]" placeholder="ตำแหน่งจะแสดงอัตโนมัติเมื่อเลือกยา" readonly>
                    </div>
                    <button type="button" class="remove-medicine-btn">ลบรายการ</button>
                `;
                medicineContainer.appendChild(newMedicineItem);
                
                // Add event listener to the new remove button
                newMedicineItem.querySelector('.remove-medicine-btn').addEventListener('click', function() {
                    medicineContainer.removeChild(newMedicineItem);
                });
            });
            
            // Initial remove button event
            document.querySelector('.remove-medicine-btn').addEventListener('click', function(e) {
                // Don't remove if it's the last medicine item
                if (medicineContainer.children.length > 1) {
                    medicineContainer.removeChild(e.target.parentElement);
                } else {
                    alert('ต้องมีรายการยาอย่างน้อย 1 รายการ');
                }
            });
            
            // Function to generate a unique ID
            function generateUniqueId() {
                return Date.now().toString(36) + Math.random().toString(36).substr(2, 5).toUpperCase();
            }
            
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Collect form data
                const formData = {
                    patientName: document.getElementById('patientName').value,
                    hospitalNumber: document.getElementById('hospitalNumber').value,
                    dob: document.getElementById('dob').value,
                    chiefComplaint: document.getElementById('chiefComplaint').value,
                    gender: document.getElementById('gender').value,
                    drugAllergies: document.getElementById('drugAllergies').value,
                    heightWeight: document.getElementById('heightWeight').value,
                    presentIllness: document.getElementById('presentIllness').value,
                    medications: []
                };
                
                // Collect medicine data
                const medicineItems = document.querySelectorAll('.medicine-item');
                medicineItems.forEach(item => {
                    const medicineName = item.querySelector('input[name="medicineName[]"]').value;
                    const medicineQuantity = item.querySelector('input[name="medicineQuantity[]"]').value;
                    const medicineLocation = item.querySelector('input[name="medicineLocation[]"]').value;
                    
                    if (medicineName || medicineQuantity || medicineLocation) {
                        formData.medications.push({
                            name: medicineName,
                            quantity: medicineQuantity,
                            location: medicineLocation
                        });
                    }
                });
                
                // Validate form (basic validation)
                if (!formData.patientName || !formData.hospitalNumber) {
                    alert('กรุณากรอกชื่อผู้ป่วยและหมายเลขโรงพยาบาล');
                    return;
                }
                
                // Add unique ID for this record
                formData.recordId = generateUniqueId();
                
                // Save to database using AJAX
                saveToDatabase(formData);
            });
            
            function saveToDatabase(data) {
                // สร้าง FormData object สำหรับส่งข้อมูลไปยังฐานข้อมูล
                const formDataToSend = new FormData();
                formDataToSend.append('formData', JSON.stringify(data));
                
                // ส่งข้อมูลไปยัง PHP script ด้วย Fetch API
                fetch('save_medical_data.php', {
                    method: 'POST',
                    body: formDataToSend
                })
                .then(response => response.json())
                .then(result => {
                    if (result.status === 'success') {
                        // สร้าง QR Code เมื่อบันทึกสำเร็จ
                        generateQRCode(data);
                        
                        // แสดงข้อความสำเร็จ
                        successMessage.style.display = 'block';
                        
                        // แสดงข้อมูลผู้ป่วยบน QR Code
                        const patientSummary = `ชื่อ: ${data.patientName} | HN: ${data.hospitalNumber} | ID: ${data.recordId}`;
                        patientInfoElement.textContent = patientSummary;
                        
                        setTimeout(function() {
                            successMessage.style.display = 'none';
                        }, 3000);
                    } else {
                        alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' + result.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                });
            }
            
function generateQRCode(data) {
    try {
        // เก็บข้อมูลผู้ป่วยแบบย่อ (เฉพาะข้อมูลสำคัญ)
        const recordId = data.recordId;
        
        // บันทึกข้อมูลเต็มในไฟล์ก่อน
        saveFullDataToFile(data);
        
        // สร้าง URL แบบสั้นที่มีเฉพาะ ID
        const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
        const patientUrl = `${baseUrl}view.php?id=${recordId}`;
        
        // ล้าง QR code เดิม (ถ้ามี)
        const qrCodeElement = document.getElementById('qrCode');
        qrCodeElement.innerHTML = '';
        
        // สร้าง QR code ที่ชี้ไปยัง URL สั้นๆ
        try {
            // ตั้งค่า QR Code
            new QRCode(qrCodeElement, {
                text: patientUrl,
                width: 300,
                height: 300,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H // ระดับการแก้ไขข้อผิดพลาดสูงสุด
            });
            
            // เก็บ URL ไว้ใช้งานภายหลัง
            if (!document.getElementById('hiddenPatientUrl')) {
                const hiddenUrl = document.createElement('input');
                hiddenUrl.type = 'hidden';
                hiddenUrl.id = 'hiddenPatientUrl';
                document.body.appendChild(hiddenUrl);
            }
            document.getElementById('hiddenPatientUrl').value = patientUrl;
            
            // แสดงข้อมูลสำคัญและ URL ด้านล่าง QR Code
            const urlDisplay = document.createElement('div');
            urlDisplay.innerHTML = `
                <p style="margin-top: 10px; font-size: 14px; color: #333; font-weight: 500;">
                    ชื่อ: ${data.patientName} | HN: ${data.hospitalNumber}
                </p>
                <p style="margin-top: 5px; font-size: 12px; color: #555;">
                    URL: <a href="${patientUrl}" target="_blank">${patientUrl}</a>
                </p>
                <button id="copyUrlBtn" style="margin-top: 8px; padding: 6px 12px; background-color: #432208; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                    คัดลอก URL
                </button>
            `;
            
            // ตรวจสอบและลบ URL เดิม (ถ้ามี)
            const existingUrlDisplay = document.getElementById('urlDisplayContainer');
            if (existingUrlDisplay) {
                existingUrlDisplay.remove();
            }
            
            // เพิ่ม URL แสดงผลลงในหน้าเว็บ
            urlDisplay.id = 'urlDisplayContainer';
            qrCodeElement.appendChild(urlDisplay);
            
            // เพิ่ม event listener สำหรับปุ่มคัดลอก URL
            document.getElementById('copyUrlBtn').addEventListener('click', function() {
                navigator.clipboard.writeText(patientUrl).then(function() {
                    this.textContent = 'คัดลอกแล้ว!';
                    setTimeout(() => {
                        this.textContent = 'คัดลอก URL';
                    }, 2000);
                }.bind(this)).catch(function(err) {
                    console.error('ไม่สามารถคัดลอก URL: ', err);
                    alert('ไม่สามารถคัดลอก URL อัตโนมัติได้ กรุณาคัดลอกด้วยตัวเอง');
                });
            });
            
        } catch (error) {
            console.error("Error generating QR code:", error);
            qrCodeElement.innerHTML = '<p>Cannot generate QR Code. Record ID: ' + data.recordId + '</p>';
        }
        
        // แสดง QR code container
        qrCodeContainer.style.display = 'block';
        
        // ตั้งค่าปุ่มดาวน์โหลด
        setupDownloadButton(data);
        
        // ตั้งค่าปุ่มดูประวัติ โดยใช้ URL ที่สร้างขึ้น
        setupViewHistoryButton(data, patientUrl);
        
        // เพิ่มข้อมูลผู้ป่วยใต้ QR Code
        const patientSummary = `ชื่อ: ${data.patientName} | HN: ${data.hospitalNumber}`;
        patientInfoElement.textContent = patientSummary;
    } catch (error) {
        console.error("Error in QR code generation process:", error);
        alert("เกิดข้อผิดพลาดในการสร้าง QR Code กรุณาลองใหม่อีกครั้ง");
    }
}

// ฟังก์ชันบันทึกข้อมูลเต็มลงในไฟล์
function saveFullDataToFile(data) {
    // สร้าง FormData object สำหรับส่งข้อมูลไปยังฐานข้อมูล
    const formDataToSend = new FormData();
    formDataToSend.append('recordId', data.recordId);
    formDataToSend.append('patientData', JSON.stringify(data));
    formDataToSend.append('action', 'save_patient_data_file');
    
    // ส่งข้อมูลไปที่ PHP script เพื่อบันทึกลงไฟล์
    fetch('save_patient_data.php', {
        method: 'POST',
        body: formDataToSend
    })
    .then(response => response.json())
    .then(result => {
        if (result.status === 'success') {
            console.log('Patient data saved successfully to file');
        } else {
            console.error('Error saving patient data file:', result.message);
            // ถ้าไม่สามารถบันทึกลงไฟล์ได้ ให้บันทึกลง localStorage
            saveToLocalStorage(data);
        }
    })
    .catch(error => {
        console.error('Error in save patient data request:', error);
        // ถ้าไม่สามารถบันทึกผ่าน AJAX ได้ ให้พยายามอีกวิธี
        saveToLocalStorage(data);
    });
}

// สำรองข้อมูลไว้ใน localStorage กรณีที่การบันทึกไฟล์ล้มเหลว
function saveToLocalStorage(data) {
    try {
        // เก็บข้อมูลใน localStorage (มีข้อจำกัดขนาด)
        localStorage.setItem(`patient_${data.recordId}`, JSON.stringify(data));
        console.log('Patient data saved to localStorage as backup');
    } catch (e) {
        console.error('Could not save to localStorage:', e);
    }
}          
            function setupDownloadButton(data) {
                // ลบ event listener เดิม (ถ้ามี)
                if (currentDownloadHandler) {
                    downloadBtn.removeEventListener('click', currentDownloadHandler);
                }
                
                // สร้าง handler ใหม่
                currentDownloadHandler = function() {
                    try {
                        // สร้าง link ชั่วคราว
                        const link = document.createElement('a');
                        
                        // รับรูปภาพ QR code
                        const qrCodeImg = document.querySelector('#qrCode img');
                        
                        if (!qrCodeImg) {
                            alert("ไม่พบรูปภาพ QR Code กรุณาสร้าง QR Code ใหม่อีกครั้ง");
                            return;
                        }
                        
                        // ตั้งค่า link attributes
                        link.href = qrCodeImg.src;
                        link.download = `patient_qr_${data.hospitalNumber || 'unknown'}.png`;
                        
                        // เพิ่มลงใน document, คลิก, และลบออก
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    } catch (error) {
                        console.error("Error downloading QR code:", error);
                        alert("เกิดข้อผิดพลาดในการดาวน์โหลด QR Code กรุณาลองใหม่อีกครั้ง");
                    }
                };
                
                // เพิ่ม event listener ใหม่
                downloadBtn.addEventListener('click', currentDownloadHandler);
            }
            
  function setupViewHistoryButton(data, patientUrl) {
    // ลบ event listener เดิม (ถ้ามี)
    if (currentViewHistoryHandler) {
        viewHistoryBtn.removeEventListener('click', currentViewHistoryHandler);
    }
    
    // สร้าง handler ใหม่
    currentViewHistoryHandler = function() {
        try {
            // กำหนด base URL ของระบบ
            const baseUrl = window.location.origin + window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/') + 1);
            
            // ตรวจสอบว่ามี recordId หรือไม่
            if (!data || !data.recordId) {
                throw new Error("ไม่พบข้อมูล ID ของผู้ป่วย");
            }
            
            // เชื่อมไปยังหน้า index1.php โดยตรง ตามที่ต้องการ
            const viewUrl = `${baseUrl}index1.php?id=${data.recordId}`;
            
            // เปิดหน้าใหม่
            window.open(viewUrl, '_blank');
        } catch (error) {
            console.error("Error navigating to patient data page:", error);
            alert("เกิดข้อผิดพลาดในการเปิดหน้าข้อมูลผู้ป่วย กรุณาลองใหม่อีกครั้ง");
        }
    };
    
    // เพิ่ม event listener ใหม่
    viewHistoryBtn.addEventListener('click', currentViewHistoryHandler);
}
            // ฟังก์ชันสำหรับการค้นหายา
            function setupMedicineSearch() {
                // รับองค์ประกอบ input ชื่อยาทั้งหมดที่มีอยู่
                const medicineInputs = document.querySelectorAll('input[name="medicineName[]"]');
                
                // ติดตั้ง event listener สำหรับแต่ละ input
                medicineInputs.forEach(input => {
                    setupSearchForInput(input);
                });
            }
            
            function setupSearchForInput(input) {
                // สร้าง datalist สำหรับ autocomplete
                const datalistId = 'medicine-list-' + Math.random().toString(36).substr(2, 9);
                const datalist = document.createElement('datalist');
                datalist.id = datalistId;
                input.setAttribute('list', datalistId);
                input.parentNode.appendChild(datalist);
                
                // เพิ่ม event สำหรับการพิมพ์เพื่อค้นหา
                input.addEventListener('input', function() {
                    const searchTerm = this.value.trim();
                    if (searchTerm.length < 2) return; // ไม่ค้นหาถ้าน้อยกว่า 2 ตัวอักษร
                    
                    // สร้าง FormData สำหรับส่งค่าไปค้นหา
                    const formData = new FormData();
                    formData.append('search', searchTerm);
                    
                    // ส่งคำขอไปยังเซิร์ฟเวอร์เพื่อค้นหายา
                    fetch('search_medicine.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            // ล้าง datalist เดิม
                            datalist.innerHTML = '';
                            
                            // เพิ่มตัวเลือกใหม่จากผลการค้นหา
                            data.medicines.forEach(medicine => {
                                const option = document.createElement('option');
                                option.value = medicine.name;
                                option.setAttribute('data-id', medicine.id);
                                option.setAttribute('data-location', medicine.location);
                                datalist.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error searching for medicines:', error);
                    });
                });
                
                // เพิ่ม event เมื่อเลือกยาเพื่อดึงข้อมูลตำแหน่ง
                input.addEventListener('change', function() {
                    const selectedMedicine = this.value;
                    
                    // หา option ที่เลือก
                    const options = datalist.querySelectorAll('option');
                    let selectedOption = null;
                    
                    for (let i = 0; i < options.length; i++) {
                        if (options[i].value === selectedMedicine) {
                            selectedOption = options[i];
                            break;
                        }
                    }
                    
                    if (selectedOption) {
                        // ดึงข้อมูลตำแหน่งและใส่ในช่องตำแหน่งจัดเก็บ
                        const location = selectedOption.getAttribute('data-location');
                        
                        // หา input ของตำแหน่งจัดเก็บในแถวเดียวกัน
                        const medicineItem = this.closest('.medicine-item');
                        const locationInput = medicineItem.querySelector('input[name="medicineLocation[]"]');
                        
                        if (locationInput) {
                            locationInput.value = location;
                        }
                    }
                });
            }
            
            // ดัดแปลง event listener สำหรับการเพิ่มรายการยาใหม่
            const originalAddMedicineBtnClick = addMedicineBtn.onclick;
            addMedicineBtn.onclick = function() {
                // เรียกฟังก์ชันเดิมก่อน (ถ้ามี)
                if (typeof originalAddMedicineBtnClick === 'function') {
                    originalAddMedicineBtnClick.call(this);
                }
                
                // รอให้ DOM อัปเดตเสร็จก่อน
                setTimeout(() => {
                    // รับ input ล่าสุดที่เพิ่มเข้ามา
                    const lastMedicineItem = document.querySelector('.medicine-item:last-child');
                    const lastMedicineInput = lastMedicineItem.querySelector('input[name="medicineName[]"]');
                    
                    // ติดตั้งการค้นหาสำหรับ input ใหม่
                    setupSearchForInput(lastMedicineInput);
                }, 100);
            };
            
            // เริ่มต้นการค้นหายาเมื่อโหลดหน้า
            setupMedicineSearch();
        });
    </script>
</body>
</html>