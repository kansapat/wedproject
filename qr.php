<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title>QR CODE SCANNER - ข้อมูลผู้ป่วย</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            width: 100%;
            max-width: 1200px;
            background-color: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .page-title {
            text-align: center;
            color: #555;
            margin-bottom: 20px;
            padding-bottom: 10px;
            position: relative;
            font-size: 2rem;
            font-weight: 400;
        }
        
        .page-title:before, .page-title:after {
            content: "";
            height: 1px;
            position: absolute;
            bottom: 0;
            background-color: #ccc;
            width: 40%;
        }
        
        .page-title:before {
            left: 0;
        }
        
        .page-title:after {
            right: 0;
        }
        
        .scanner-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-top: 20px;
        }
        
        .scan-section {
            flex: 1;
            min-width: 300px;
            background-color: #432208;
            color: white;
            padding: 20px;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
        }
        
        .result-section {
            flex: 1;
            min-width: 300px;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
        }
        
        .section-title {
            font-size: 1.2rem;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 400;
            letter-spacing: 0.5px;
        }
        
        .result-section .section-title {
            border-bottom-color: #eee;
            color: #555;
        }
        
        #reader {
            width: 100%;
            margin-top: 15px;
            margin-bottom: 15px;
            max-height: 300px;
            overflow: hidden;
            border-radius: 8px;
        }
        
        #reader video {
            border-radius: 8px;
        }
        
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: auto;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .scan-btn-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-top: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: 2px solid white;
            background-color: transparent;
            color: white;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 400;
            transition: all 0.3s ease;
            min-width: 130px;
            text-align: center;
        }
        
        .btn:hover {
            background-color: white;
            color: #432208;
        }
        
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .result-btn {
            background-color: white;
            color: #555;
            border: 2px solid #432208;
        }
        
        .result-btn:hover {
            background-color: #432208;
            color: white;
        }
        
        .qr-image-container {
            text-align: center;
            margin: 20px 0;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        #qrImage {
            max-width: 100%;
            max-height: 300px;
            border-radius: 4px;
        }
        
        #uploadResult {
            text-align: center;
            margin: 10px 0;
            color: #555;
        }
        
        .status-message {
            border-top: 1px solid #eee;
            margin-top: 15px;
            padding-top: 15px;
            text-align: center;
        }
        
        .loading-indicator {
            text-align: center;
            margin: 10px 0;
            display: none;
        }
        
        .loading-indicator svg {
            animation: rotate 1.5s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .status-box {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .status-box h3 {
            font-size: 1rem;
            margin-bottom: 10px;
            color: #555;
            font-weight: 500;
        }
        
        .status-box p {
            font-size: 0.9rem;
            margin-bottom: 8px;
            color: #666;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #432208;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        }
        
        .notification.show {
            opacity: 1;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .scanner-container {
                flex-direction: column;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .page-title:before, .page-title:after {
                width: 30%;
            }
            
            .scan-btn-container, .btn-container {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="page-title">ระบบสแกน QR Code ข้อมูลผู้ป่วย</h1>
        
        <div class="scanner-container">
            <div class="scan-section">
                <h2 class="section-title">สแกนด้วยกล้อง</h2>
                
                <div class="scan-btn-container">
                    <button class="btn" id="startButton" onclick="startScanner()">เริ่มการสแกน</button>
                    <button class="btn" id="stopButton" onclick="stopScanner()" disabled>หยุดสแกน</button>
                    <button class="btn" id="switchCameraBtn" onclick="switchCamera()" disabled>สลับกล้อง</button>
                </div>
                
                <!-- สร้าง element สำหรับกล้อง html5-qrcode -->
                <div id="reader"></div>
                
                <div class="loading-indicator" id="loadingIndicator">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="white" stroke-width="4" stroke-dasharray="31.4 31.4" stroke-dashoffset="0"></circle>
                    </svg>
                    <p>กำลังประมวลผล...</p>
                </div>
            </div>
            
            <div class="result-section">
                <h2 class="section-title">วิธีใช้งาน</h2>
                
                <!-- QR Code display area -->
                <div class="qr-image-container">
                    <img id="qrImage" src="#" alt="QR Code Image" style="display: none;">
                    <p id="uploadResult"></p>
                </div>
                
                <div class="status-box">
                    <h3>คำแนะนำการใช้งาน</h3>
                    <p>1. กดปุ่ม "เริ่มการสแกน" เพื่อเปิดกล้อง</p>
                    <p>2. ส่องกล้องไปที่ QR Code ข้อมูลผู้ป่วย</p>
                    <p>3. เมื่อสแกนสำเร็จ ระบบจะนำไปยังหน้าแสดงข้อมูลผู้ป่วยโดยอัตโนมัติ</p>
                    <p>4. หากไม่สามารถสแกนด้วยกล้องได้ ให้ใช้การอัปโหลดรูปภาพ QR Code แทน</p>
                </div>
                
                <div class="btn-container">
                    <input type="file" id="qrFileInput" style="display: none;" accept="image/*" onchange="handleFileSelect(event)">
                    <button class="btn result-btn" onclick="document.getElementById('qrFileInput').click()">เลือกไฟล์รูปภาพ</button>
                </div>
                
                <div class="status-message" id="statusMessage"></div>
                
                <a href="menu.php" class="back-link">← กลับไปยังหน้าหลัก</a>
            </div>
        </div>
    </div>

    <div class="notification" id="notification"></div>

    <script>
        let html5QrCode;
        let isScanning = false;
        let scannedData = null;
        let currentCamera = 'environment'; // default to rear camera
        
        // เริ่มการสแกน QR Code
        function startScanner() {
            if (isScanning) return;
            
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('uploadResult').textContent = '';
            document.getElementById('qrImage').style.display = 'none';
            
            html5QrCode = new Html5Qrcode("reader");
            const config = { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                showTorchButtonIfSupported: true,
                showZoomSliderIfSupported: true
            };
            
            html5QrCode.start(
                { facingMode: currentCamera }, 
                config,
                (decodedText, decodedResult) => {
                    // เมื่อสแกนสำเร็จ
                    stopScanner();
                    document.getElementById('loadingIndicator').style.display = 'block';
                    document.getElementById('statusMessage').innerHTML = `<p style="color: #4CAF50">สแกนสำเร็จ กำลังนำไปยังหน้าข้อมูลผู้ป่วย...</p>`;
                    
                    // ประมวลผล QR Code และเด้งไปยังหน้า view.php
                    processQrCodeAndRedirect(decodedText);
                },
                (errorMessage) => {
                    // ไม่ต้องทำอะไรกับข้อผิดพลาดระหว่างสแกน
                }
            ).then(() => {
                document.getElementById('switchCameraBtn').disabled = false;
                isScanning = true;
                document.getElementById('startButton').disabled = true;
                document.getElementById('stopButton').disabled = false;
            }).catch((err) => {
                console.error("Scanner error:", err);
                showNotification("ไม่สามารถเริ่มการสแกนได้ กรุณาตรวจสอบว่าอนุญาตให้ใช้กล้องแล้ว", "error");
            });
        }
        
        // สลับกล้อง
        function switchCamera() {
            if (!isScanning || !html5QrCode) return;
            
            stopScanner();
            
            // สลับระหว่างกล้องหน้าและกล้องหลัง
            currentCamera = (currentCamera === 'environment') ? 'user' : 'environment';
            
            // เริ่มสแกนใหม่ด้วยกล้องที่เปลี่ยน
            setTimeout(() => {
                startScanner();
            }, 500);
        }
        
        // หยุดการสแกน
        function stopScanner() {
            if (!isScanning || !html5QrCode) return;
            
            html5QrCode.stop().then(() => {
                isScanning = false;
                document.getElementById('startButton').disabled = false;
                document.getElementById('stopButton').disabled = true;
                document.getElementById('switchCameraBtn').disabled = true;
            }).catch((err) => {
                console.error("Error stopping scanner:", err);
            });
        }
        
        // ฟังก์ชันดึงพารามิเตอร์จาก URL
        function getParameterByName(url, name) {
            name = name.replace(/[\[\]]/g, '\\$&');
            const regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)');
            const results = regex.exec(url);
            if (!results) return null;
            if (!results[2]) return '';
            return decodeURIComponent(results[2].replace(/\+/g, ' '));
        }
        
        // ฟังก์ชันช่วยถอดรหัส Base64 เป็น UTF-8 (รองรับภาษาไทย)
        function base64ToUtf8(str) {
            try {
                // ใช้ approach เดียวกันกับที่ใช้ในการสร้าง QR code
                return decodeURIComponent(Array.prototype.map.call(atob(str), function(c) {
                    return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
                }).join(''));
            } catch (e) {
                console.error('Error decoding base64:', e);
                return null;
            }
        }
        
        // ฟังก์ชันแสดงการแจ้งเตือน
        function showNotification(message, type = 'info') {
            const notification = document.getElementById('notification');
            
            // กำหนดสีตามประเภทการแจ้งเตือน
            if (type === 'success') {
                notification.style.backgroundColor = 'rgba(40, 167, 69, 0.9)';
            } else if (type === 'error') {
                notification.style.backgroundColor = 'rgba(220, 53, 69, 0.9)';
            } else if (type === 'warning') {
                notification.style.backgroundColor = 'rgba(255, 193, 7, 0.9)';
            } else {
                notification.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
            }
            
            notification.textContent = message;
            notification.classList.add('show');
            
            // ซ่อนการแจ้งเตือนหลังจาก 3 วินาที
            setTimeout(() => {
                notification.classList.remove('show');
            }, 3000);
        }
        
        // ฟังก์ชันประมวลผล QR Code และเด้งไปหน้า view.php
        function processQrCodeAndRedirect(decodedText) {
            try {
                console.log("Scanned text:", decodedText);
                scannedData = decodedText;
                
                // กรณีสแกนได้เป็น URL
                if (decodedText.startsWith("http://") || decodedText.startsWith("https://")) {
                    console.log("Detected URL QR code");
                    
                    // ตรวจสอบว่าเป็น URL ของ view.php หรือไม่
                    if (decodedText.includes("view.php")) {
                        // นำทางไปยัง URL โดยตรง
                        window.location.href = decodedText;
                        return;
                    }
                    
                    // ตรวจสอบว่ามีพารามิเตอร์ id หรือไม่
                    const idParam = getParameterByName(decodedText, 'id');
                    
                    if (idParam) {
                        // นำทางไปยังหน้า view.php พร้อมส่ง ID
                        window.location.href = `view.php?id=${idParam}`;
                        return;
                    }
                    
                    // กรณีเป็น URL ที่มีพารามิเตอร์ data
                    const dataParam = getParameterByName(decodedText, 'data');
                    if (dataParam) {
                        // นำทางไปยังหน้า view.php พร้อมส่งข้อมูล
                        window.location.href = `view.php?data=${encodeURIComponent(dataParam)}`;
                        return;
                    }
                    
                    // ถ้าเป็น URL อื่นๆ ให้ไปที่ URL นั้นตรงๆ
                    window.location.href = decodedText;
                    return;
                }
                // กรณีสแกนได้เป็น Base64 หรือข้อมูลอื่นๆ
                else {
                    console.log("Attempting to decode as Base64 or raw data");
                    
                    // กรณีที่เป็น Base64 encoded JSON
                    try {
                        const jsonString = base64ToUtf8(decodedText);
                        if (jsonString) {
                            try {
                                // แปลง JSON string เป็น object
                                const jsonData = JSON.parse(jsonString);
                                
                                // ถ้ามี recordId ให้ใช้ recordId
                                if (jsonData.recordId) {
                                    // บันทึกข้อมูลลงใน localStorage
                                    localStorage.setItem(`patient_${jsonData.recordId}`, JSON.stringify(jsonData));
                                    
                                    // นำทางไปยังหน้า view.php พร้อมส่ง ID
                                    window.location.href = `view.php?id=${jsonData.recordId}`;
                                    return;
                                }
                                
                                // ถ้าไม่มี recordId ให้ใช้ data parameter
                                const encodedData = encodeURIComponent(JSON.stringify(jsonData));
                                window.location.href = `view.php?data=${encodedData}`;
                                return;
                            } catch (jsonError) {
                                console.error("Error parsing JSON from Base64:", jsonError);
                            }
                        }
                    } catch (base64Error) {
                        console.log("Not a Base64 encoded JSON");
                    }
                    
                    // กรณีที่เป็น JSON โดยตรง
                    try {
                        const jsonData = JSON.parse(decodedText);
                        
                        // ถ้ามี recordId ให้ใช้ recordId
                        if (jsonData.recordId) {
                            // บันทึกข้อมูลลงใน localStorage
                            localStorage.setItem(`patient_${jsonData.recordId}`, JSON.stringify(jsonData));
                            
                            // นำทางไปยังหน้า view.php พร้อมส่ง ID
                            window.location.href = `view.php?id=${jsonData.recordId}`;
                            return;
                        }
                        
                        // ถ้าไม่มี recordId ให้ใช้ data parameter
                        const encodedData = encodeURIComponent(JSON.stringify(jsonData));
                        window.location.href = `view.php?data=${encodedData}`;
                        return;
                    } catch (jsonError) {
                        console.log("Not a direct JSON");
                    }
                    
                    // ถ้าเป็นแค่ ID เพียงอย่างเดียว (เช่น sample123)
                    if (/^[a-zA-Z0-9]{6,20}$/.test(decodedText)) {
                        // นำทางไปยังหน้า view.php พร้อมส่ง ID
                        window.location.href = `view.php?id=${decodedText}`;
                        return;
                    }
                    
                    // ถ้าไม่ตรงกับรูปแบบใดเลย ส่ง raw data ไปหน้า view.php
                    window.location.href = `view.php?raw_data=${encodeURIComponent(decodedText)}`;
                }
                
            } catch (error) {
                console.error("Error processing QR code:", error);
                document.getElementById('statusMessage').innerHTML = `<p style="color: #F44336">เกิดข้อผิดพลาดในการประมวลผล QR Code</p>`;
                document.getElementById('loadingIndicator').style.display = 'none';
                showNotification("เกิดข้อผิดพลาดในการประมวลผล QR Code", "error");
            }
        }
        
        // สำหรับอัปโหลดไฟล์ QR Code
        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            document.getElementById('loadingIndicator').style.display = 'none';
            document.getElementById('uploadResult').textContent = '';
            
            // แสดงรูปภาพที่เลือก
            const reader = new FileReader();
            reader.onload = function(e) {
                const qrImage = document.getElementById('qrImage');
                qrImage.src = e.target.result;
                qrImage.style.display = 'block';
                
                document.getElementById('uploadResult').textContent = "กำลังวิเคราะห์ QR Code...";
                document.getElementById('loadingIndicator').style.display = 'block';
                
                // สแกน QR code จากไฟล์
                const scanner = new Html5Qrcode("reader");
                scanner.scanFile(file, true)
                    .then(decodedText => {
                        document.getElementById('statusMessage').innerHTML = `<p style="color: #4CAF50">สแกนสำเร็จ กำลังนำไปยังหน้าข้อมูลผู้ป่วย...</p>`;
                        processQrCodeAndRedirect(decodedText);
                    })
                    .catch(err => {
                        console.error("ไม่พบ QR Code ในรูปภาพ หรือ QR Code ไม่ถูกต้อง", err);
                        document.getElementById('uploadResult').innerHTML = `<span style="color: #F44336">&#10008;</span> ไม่พบ QR Code ในรูปภาพนี้ กรุณาลองรูปอื่น`;
                        document.getElementById('loadingIndicator').style.display = 'none';
                        document.getElementById('statusMessage').innerHTML = `<p style="color: #F44336">ไม่พบ QR Code ในรูปภาพนี้ กรุณาลองรูปอื่น</p>`;
                        showNotification('ไม่พบ QR Code ในรูปภาพนี้', 'error');
                    });
            };
            reader.readAsDataURL(file);
        }
        
        // สร้างข้อมูลตัวอย่างสำหรับการทดสอบ (ถ้าไม่มีข้อมูลในระบบ)
        function createSampleData() {
            // ตรวจสอบว่ามีข้อมูลตัวอย่างอยู่แล้วหรือไม่
            if (!localStorage.getItem('patient_sample123')) {
                // สร้างข้อมูลตัวอย่าง
                const sampleData = {
                    recordId: "sample123",
                    patientName: "ทดสอบ สาธิต",
                    hospitalNumber: "HN12345",
                    dob: "1990-05-15",
                    gender: "male",
                    chiefComplaint: "ปวดศีรษะ มีไข้",
                    heightWeight: "170 ซม. / 65 กก.",
                    drugAllergies: "พาราเซตามอล",
                    presentIllness: "มีอาการไข้ ปวดศีรษะมา 2 วัน",
                    medications: [
                        {
                            name: "แก้ปวดลดไข้",
                            quantity: "10",
                            location: "ชั้น A ตู้ 3"
                        },
                        {
                            name: "ยาแก้แพ้",
                            quantity: "20",
                            location: "ชั้น B ตู้ 2"
                        }
                    ]
                };
                
                // บันทึกลงใน localStorage
                localStorage.setItem('patient_sample123', JSON.stringify(sampleData));
                console.log("สร้างข้อมูลตัวอย่างเรียบร้อย");
                
                return true;
            }
            return false;
        }
        
        // กำหนดค่าเริ่มต้นเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('stopButton').disabled = true;
            document.getElementById('switchCameraBtn').disabled = true;
            
            // สร้างข้อมูลตัวอย่าง
            if (createSampleData()) {
                console.log("สร้างข้อมูลตัวอย่างสำเร็จ ใช้ ID: sample123 เพื่อทดสอบ");
            }
        });
    </script>
</body>
</html>