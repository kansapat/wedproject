
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="icon" type="image/png" sizes="192x192" href="im/android-icon-192x192.png">
    <title></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f8f8f8;
        }
        .container {
            display: flex;
            width: 80%;
            max-width: 900px;
            background: #fff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        .left {
            flex: 1;
            background: url('im/background.png') no-repeat center center/cover;
            padding: 20px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        .right {
            flex: 1;
            padding: 30px;
        }
        form {
            display: flex;
            flex-wrap: wrap;
        }
        input, select {
            width: 48%;
            padding: 8px;
            margin: 5px 1%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .full-width {
            width: 98%;
        }
        .submit-btn {
            width: 100%;
            padding: 10px;
            background: #b99b8c;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="left">
  
    </div>
    <div class="right">
        <form id="form1" name="form1" method="post" action="user_add.php">
            <p>
              <input type="text" name="name" id="name" placeholder="Name">
              <input type="text" name="lastname" id="lastname" placeholder="Lastname">
              <input type="tel" name="tel" id="tel" placeholder="Tel" >
              <input type="email" name="email" id="email" placeholder="E-mail">
              <input type="text" name="address" class="full-width" id="address" placeholder="Address">
              <select name="position" >
                <option value="">Select Position</option>
                <option value="Board of Management">Board of Management</option>
				<option value="BME">BME</option>
				<option value="Pharmacist">Pharmacist</option>
                <option value="Inventory Officer">Inventory Officer</option>
              </select>
              <input type="text" name="username" id="username"placeholder="Username" >
              <input type="password" name="password" id="password" placeholder="Password" >
            </p>
 <input type="submit" name="submit" id="submit" class="submit-btn">
</form>
		
    </div>
</div>

</body>
</html>
