<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $name = trim($_POST['name']);
    $role = trim($_POST['role']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("INSERT INTO employee (Name, Role, Phone) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $role, $phone);
    if ($stmt->execute()) {
        $msg = "<p style='color: #1dd1a1; font-weight: bold;'>Staff Added Successfully!</p>";
    } else {
        $msg = "<p style='color: #ff6b6b; font-weight: bold;'>Error adding staff!</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Staff Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .window-container { background: #fff; width: 100%; max-width: 1050px; height: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; overflow: hidden; }
        .sidebar { background: #dcdde1; width: 300px; padding: 25px 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #2c3e50; font-size: 22px; margin-bottom: 20px; }
        .form-control { width: 100%; padding: 12px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; }
        .btn-add { width: 100%; background: #1dd1a1; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 8px; }
        .btn-back { background: #ff6b6b; color: white; padding: 10px 20px; border: none; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
        .main-content { flex: 1; padding: 25px; display: flex; flex-direction: column; }
        .table-wrapper { flex: 1; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #e1e2e6; color: #2c3e50; padding: 12px; position: sticky; top: 0; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
    </style>
</head>
<body>

<div class="window-container">
    <div class="sidebar">
        <div>
            <h2>Add Staff Member</h2>
            <?php echo $msg; ?>
            <form method="POST">
                <input type="hidden" name="add_staff" value="1">
                <input type="text" name="name" class="form-control" placeholder="Staff Name" required>
                <input type="text" name="role" class="form-control" placeholder="Role (e.g. Pharmacist, Manager)" required>
                <input type="text" name="phone" class="form-control" placeholder="Phone Number" required>
                <button type="submit" class="btn-add"><i class="fa-solid fa-plus"></i> Add Staff</button>
            </form>
        </div>
        <a href="index.php" class="btn-back">&larr; Back</a>
    </div>

    <div class="main-content">
        <h2 style="margin-bottom: 15px; color: #2c3e50;">Staff / Employee List</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Role</th>
                        <th>Phone Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $res = $conn->query("SELECT * FROM employee ORDER BY EmployeeID DESC");
                    if ($res && $res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            // কলাম নেম চেক সাপোর্ট
                            $empName = isset($row['Name']) ? $row['Name'] : (isset($row['EmployeeName']) ? $row['EmployeeName'] : 'N/A');
                            $empRole = isset($row['Role']) ? $row['Role'] : 'N/A';
                            $empPhone = isset($row['Phone']) ? $row['Phone'] : (isset($row['ContactNo']) ? $row['ContactNo'] : 'N/A');

                            echo "<tr>
                                    <td>#{$row['EmployeeID']}</td>
                                    <td><b>{$empName}</b></td>
                                    <td>{$empRole}</td>
                                    <td>{$empPhone}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;'>No staff records found</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>