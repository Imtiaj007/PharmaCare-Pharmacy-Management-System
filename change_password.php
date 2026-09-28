<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $currPass = trim($_POST['current_password']);
    $newPass = trim($_POST['new_password']);
    $empId = $_SESSION['emp_id'];

    // Check Current Password (Matched with Phone in database)
    $stmt = $conn->prepare("SELECT Phone FROM employee WHERE EmployeeID = ?");
    $stmt->bind_param("i", $empId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res && $row = $res->fetch_assoc()) {
        if ($row['Phone'] === $currPass) {
            $update = $conn->prepare("UPDATE employee SET Phone = ? WHERE EmployeeID = ?");
            $update->bind_param("si", $newPass, $empId);
            $update->execute();
            $msg = "<p style='color: #1dd1a1; font-weight: bold;'>Password Updated Successfully!</p>";
        } else {
            $msg = "<p style='color: #ff6b6b; font-weight: bold;'>Current password incorrect!</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { background: #fff; width: 100%; max-width: 420px; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; }
        h2 { color: #2c3e50; font-size: 26px; margin-bottom: 25px; }
        .form-group { text-align: left; margin-bottom: 15px; }
        label { font-size: 13px; color: #576574; font-weight: 600; display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; outline: none; font-size: 14px; }
        .btn-update { width: 100%; background: #5f27cd; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 10px; }
        .btn-cancel { background: #dcdde1; color: #2c3e50; padding: 10px 20px; border: none; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 15px; }
    </style>
</head>
<body>

<div class="card">
    <h2>Change Password</h2>
    <?php echo $msg; ?>
    <form method="POST">
        <div class="form-group">
            <label>Current Password:</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>New Password:</label>
            <input type="password" name="new_password" required>
        </div>
        <button type="submit" class="btn-update">Update Password</button>
        <a href="index.php" class="btn-cancel">Cancel</a>
    </form>
</div>

</body>
</html>