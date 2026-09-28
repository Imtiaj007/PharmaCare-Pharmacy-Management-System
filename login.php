<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (!empty($username) && !empty($password)) {
        // Query employee table matching FirstName and Phone
        $sql = "SELECT * FROM employee WHERE LOWER(TRIM(FirstName)) = LOWER('$username') AND TRIM(Phone) = '$password'";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $emp = $result->fetch_assoc();
            $_SESSION['emp_id'] = isset($emp['EmployeeID']) ? $emp['EmployeeID'] : $emp['Employee_ID'];
            $_SESSION['emp_name'] = $emp['FirstName'] . ' ' . $emp['LastName'];
            $_SESSION['emp_role'] = isset($emp['Role']) ? $emp['Role'] : 'Employee';
            
            // Redirect to dashboard page
            header("Location: index.php");
            exit();
        } else {
            $error = "ইউজারনেম (First Name) বা পাসওয়ার্ড (Phone) ভুল হয়েছে!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Login | Pharmacy Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        body { 
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); 
            height: 100vh; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
        }

        .login-card { 
            background: rgba(255, 255, 255, 0.96); 
            padding: 40px 35px; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(0,0,0,0.3); 
            width: 100%; 
            max-width: 420px; 
            text-align: center;
        }

        .brand-logo {
            width: 70px;
            height: 70px;
            background: #e74c3c;
            color: #fff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 15px auto;
            box-shadow: 0 4px 10px rgba(231, 76, 60, 0.4);
        }

        .login-card h2 { color: #2c3e50; margin-bottom: 5px; font-size: 24px; }
        .login-card p { color: #7f8c8d; font-size: 14px; margin-bottom: 25px; }

        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; color: #34495e; font-size: 13px; }

        .input-box { position: relative; display: flex; align-items: center; }
        .input-box i.left-icon { position: absolute; left: 14px; color: #95a5a6; font-size: 15px; }
        .input-box input { 
            width: 100%; 
            padding: 12px 42px 12px 40px; 
            border: 1px solid #dcdde1; 
            border-radius: 8px; 
            font-size: 14px; 
            outline: none;
            transition: 0.3s;
        }

        .input-box input:focus { 
            border-color: #3498db; 
            box-shadow: 0 0 8px rgba(52, 152, 219, 0.3);
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            cursor: pointer;
            color: #7f8c8d;
            font-size: 15px;
            user-select: none;
        }

        .toggle-password:hover { color: #2c3e50; }

        .btn { 
            width: 100%; 
            background: linear-gradient(135deg, #3498db, #2980b9); 
            color: #fff; 
            padding: 13px; 
            border: none; 
            border-radius: 8px; 
            font-size: 16px; 
            font-weight: bold; 
            cursor: pointer; 
            margin-top: 10px;
        }

        .btn:hover { background: linear-gradient(135deg, #2980b9, #1c5980); }

        .error-msg { 
            background: #f8d7da; 
            color: #721c24; 
            padding: 12px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            font-size: 13px; 
            border-left: 4px solid #e74c3c;
            text-align: left;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-logo">
        <i class="fa-solid fa-prescription-bottle-medical"></i>
    </div>
    <h2>Pharmacy Admin</h2>
    <p>Employee Account Login</p>
    
    <?php if (!empty($error)) { echo "<div class='error-msg'><i class='fa-solid fa-circle-exclamation'></i> $error</div>"; } ?>

    <form method="POST" action="login.php">
        <div class="form-group">
            <label>Username (First Name)</label>
            <div class="input-box">
                <i class="fa-solid fa-user left-icon"></i>
                <input type="text" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" placeholder="e.g. Abdur" required autocomplete="off">
            </div>
        </div>

        <div class="form-group">
            <label>Password (Mobile Phone)</label>
            <div class="input-box">
                <i class="fa-solid fa-lock left-icon"></i>
                <input type="password" id="passwordInput" name="password" placeholder="e.g. 01910000001" required>
                <i class="fa-solid fa-eye toggle-password" id="toggleEye" onclick="togglePasswordVisibility()"></i>
            </div>
        </div>

        <button type="submit" class="btn">Login to Portal</button>
    </form>
</div>

<script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('passwordInput');
        const toggleEye = document.getElementById('toggleEye');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleEye.classList.remove('fa-eye');
            toggleEye.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleEye.classList.remove('fa-eye-slash');
            toggleEye.classList.add('fa-eye');
        }
    }
</script>

</body>
</html>