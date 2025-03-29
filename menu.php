<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="192x192"  href="im/android-icon-192x192.png">
    <title>Rangsit Hospital</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Old Standard TT', serif;
        }
        
        body {
            color: #333;
            font-size: 14px;
            overflow-x: hidden;
        }
        
        /* Navigation Bar */
        nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 2%;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .logo-container {
            display: flex;
            justify-content: flex-end;
            width: 180px;
            margin-right: 10px;
        }
        
        .logo {
            font-size: 22px;
            font-weight: 600;
            color: #333;
            white-space: nowrap;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
            flex-grow: 1;
            justify-content: center;
        }
        
        .nav-item {
            text-decoration: none;
            color: #5D4037;
            font-size: 14px;
            font-weight: 500;
            position: relative;
            white-space: nowrap;
            transition: color 0.3s;
        }
        
        .nav-item:hover {
            color: #8D6E63;
        }
        
        .user-dropdown {
            display: flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            position: relative;
        }
        
        .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 4px;
            min-width: 200px;
            display: none;
            z-index: 100;
        }
        
        .user-dropdown:hover .dropdown-menu {
            display: block;
        }
        
        .dropdown-item {
            padding: 8px 12px;
            display: block;
            text-decoration: none;
            color: #5D4037;
            transition: background 0.3s, color 0.3s;
            font-size: 13px;
        }
        
        .dropdown-item:hover {
            background: #f5f5f5;
            color: #8D6E63;
        }
        
        .logout-btn {
            padding: 5px 15px;
            border: 1.5px solid #5D4037;
            border-radius: 50px;
            text-decoration: none;
            color: #5D4037;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 14px;
            margin-left: 10px;
        }
        
        .logout-btn:hover {
            background-color: #5D4037;
            color: white;
        }
        
        /* Hero Section */
        .hero {
            height: calc(100vh - 64px);
            width: 100%;
            background: url(im/log\ in\ \(1\)\ \(1\).png) no-repeat center center;
            background-size: cover;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            color: white;
            position: relative;
        }
        
        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.15);
        }
        
        .hero-content {
            padding-left: 10%;
            max-width: 95%;
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 55px;
            margin-bottom: 30px;
            font-weight: 400;
            line-height: 1.1;
            
        }

        .hero-description {
            font-size: 24px;
            line-height: 1.4;
            font-weight: 400;
            max-width: 100%;
            
        }

        .line1, .line2 {
            display: block;
            margin-bottom: 8px;
            padding-left: 0;
            padding-right: 0;
        }

        .line1 {
            text-indent: 30px;
        }
        /* Media Queries */
        @media (max-width: 1400px) {
            .hero-title {
                font-size: 42px;
            }
            
            .hero-description {
                font-size: 22px;
            }
            
            .line1, .line2 {
                padding-left: 25px;
            }
            
            .nav-links {
                gap: 18px;
            }
        }
        
        @media (max-width: 1200px) {
            .hero-title {
                font-size: 38px;
            }
            
            .hero-description {
                font-size: 20px;
            }
            
            .nav-item {
                font-size: 13px;
            }
            
            .logout-btn {
                font-size: 13px;
                padding: 5px 12px;
            }
            
            .line1, .line2 {
                padding-left: 20px;
            }
            
            .nav-links {
                gap: 15px;
            }
            
            .logo-container {
                width: 160px;
            }
            
            .logo {
                font-size: 20px;
            }
        }
        
        @media (max-width: 992px) {
            .nav-links {
                gap: 12px;
            }
            
            .hero-content {
                max-width: 100%;
                padding-left: 4%;
            }
            
            .hero-title {
                font-size: 34px;
            }
            
            .hero-description {
                font-size: 18px;
            }
            
            .line1, .line2 {
                padding-right: 100px;
            }
            
            .nav-item {
                font-size: 12px;
            }
            
            .logo-container {
                width: 140px;
            }
            
            .logo {
                font-size: 18px;
            }
        }
        
        @media (max-width: 768px) {
            nav {
                padding: 15px 2%;
            }
            
            .nav-links {
                gap: 8px;
            }
            
            .nav-item {
                font-size: 11px;
            }
            
            .logout-btn {
                font-size: 11px;
                padding: 4px 10px;
                margin-left: 5px;
            }
            
            .hero-title {
                font-size: 30px;
                margin-bottom: 20px;
            }
            
            .hero-description {
                font-size: 16px;
            }
            
            .line1, .line2 {
                padding-left: 10px;
            }
            
            .logo-container {
                width: 120px;
            }
            
            .logo {
                font-size: 16px;
            }
            
            .hero {
                height: calc(100vh - 54px);
            }
        }
        
        @media (max-width: 480px) {
            .nav-links {
                display: none;
            }
            
            .hero-title {
                font-size: 26px;
                margin-bottom: 15px;
            }
            
            .hero-description {
                font-size: 14px;
            }
            
            .line1, .line2 {
                padding-left: 5px;
            }
            
            .logo {
                font-size: 14px;
            }
            
            .logout-btn {
                font-size: 10px;
                padding: 4px 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav>
        <div class="logo-container">
            <span class="logo">Rangsit Hospital</span>
        </div>
        <div class="nav-links">
            <div class="user-dropdown">
                <a href="#" class="nav-item">User</a>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M6 9L12 15L18 9" stroke="#5D4037" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <div class="dropdown-menu">
                    <a href="bme.html" class="dropdown-item">BIOMEDICAI ENGNEERING</a>
                    <a href="pharmacist.html" class="dropdown-item">PHARMACIST</a>
                    <a href="inventory.html" class="dropdown-item">INVENTORY OFFICER</a>
                    <a href="executive.html" class="dropdown-item">EXECUTIVE BOARD</a>
                </div>
            </div>
            <a href="medical_record.php" class="nav-item"> Ordering</a>
            <a href="qr.php" class="nav-item">Mobile Ordering</a>
            <a href="Configuration.php" class="nav-item">Control Center</a>
            <a href="statement.php" class="nav-item">FinanceBudgeting</a>
        </div>
        <a href="login.php" class="logout-btn">Logout</a>
    </nav>
    
    <!-- Hero Section -->
    <section class="hero">
        <div class="overlay"></div>
        <div class="hero-content">
            <h1 class="hero-title">Why Choose Our System?</h1>
            <p class="hero-description">
                <span class="line1">The system will display the exact storage location,including</span>
                <span class="line2">the shelf number and bin position.</span>
            </p>
        </div>
    </section>
</body>
</html>