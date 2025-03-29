<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medicine Management System</title>
    <link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);
            --overlay-color: rgba(0, 0, 0, 0.5);
            --primary-spacing: 20px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Playfair Display', serif;
            overflow-x: hidden;
            line-height: 1.5;
            color: white;
        }
        
        .container {
            position: relative;
            width: 100%;
            height: 100vh;
            background: url('pic/log.png') no-repeat center center/cover;
            display: flex;
            flex-direction: column;
        }
        
        .overlay {
            position: absolute;
            inset: 0;
            background: var(--overlay-color);
            z-index: 1;
        }
        
        .info {
            position: relative;
            z-index: 2;
            display: flex;
            justify-content: space-around;
            padding: var(--primary-spacing) 0;
            margin-top: 5%;
        }
        
        .info div {
            font-size: clamp(14px, 1.6vw, 18px); /* ลดขนาดฟอนต์ลงจาก 18px เป็น 14px */
            text-align: center;
            text-shadow: var(--text-shadow);
            flex: 0 1 auto;
            max-width: 30%;
        }
        
        .content {
            position: relative;
            z-index: 2;
            margin: auto 0;
            padding: 0 8%;
            max-width: 1000px;
        }
        
        h1 {
            font-size: clamp(32px, 5vw, 60px); /* ลดขนาดฟอนต์ลงจาก 45px เป็น 32px */
            font-weight: bold;
            margin-bottom: 30px;
            line-height: 1.2;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.6);
        }
        
        p {
            font-size: clamp(16px, 2.2vw, 24px); /* ลดขนาดฟอนต์ลงจาก 22px เป็น 16px */
            max-width: 100%;
            text-shadow: var(--text-shadow);
        }
        
        .button-container {
            position: relative;
            z-index: 2;
            padding: 0 8%;
            margin-bottom: 10%;
        }
        
        .login-btn {
            padding: 12px 25px; /* ลดขนาด padding เล็กน้อย */
            border: 2px solid white;
            background-color: transparent;
            color: white;
            font-size: clamp(14px, 1.6vw, 18px); /* ลดขนาดฟอนต์ลงจาก 18px เป็น 14px */
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-shadow: var(--text-shadow);
        }
        
        .login-btn:hover, .login-btn:focus {
            background-color: white;
            color: black;
            text-shadow: none;
            outline: none;
        }
        
        @media (max-width: 700px) {
            .info {
                flex-direction: column;
                align-items: center;
            }
            
            .info div {
                margin-bottom: 5px;
                max-width: 90%;
            }
            
            .content, .button-container {
                padding: 0 2%;
            }
        }
        
        @media (max-height: 600px) {
            .container {
                height: auto;
                min-height: 100vh;
            }
            
            .content {
                margin: 20px 0;
            }
            
            .info {
                margin-top: 20px;
            }
            
            .button-container {
                margin: 15px 0 15px 0;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="overlay"></div>
        <div class="info">
            <div><strong>Date:</strong> <span id="event-date"></span></div>
            <div><strong>Time:</strong> <span id="event-time"></span></div>
            <div><strong>Hospital affiliation:</strong> Rangsit Hospital</div>
        </div>
        <div class="content">
            <h1>Medicine Management System</h1>
            <p>Welcome to the website that helps you procure your medicine. Hope we can help you find medicine easier than ever before.</p>
        </div>
        <div class="button-container">
            <a href="index.php" class="login-btn">LOG IN</a>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const dateElement = document.getElementById("event-date");
            const timeElement = document.getElementById("event-time");
            
            function updateDateTime() {
                const now = new Date();
                const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
                const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit' };
                
                dateElement.textContent = now.toLocaleDateString("en-US", dateOptions);
                timeElement.textContent = now.toLocaleTimeString("en-US", timeOptions);
            }
            
            updateDateTime();
            setInterval(updateDateTime, 1000);
        });
    </script>
</body>
</html>