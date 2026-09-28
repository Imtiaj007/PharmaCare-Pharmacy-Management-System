<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Customer List</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .window-container { background: #fff; width: 100%; max-width: 900px; height: 550px; border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .table-wrapper { flex: 1; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #5f27cd; color: white; padding: 12px; position: sticky; top: 0; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .btn-back { background: #576574; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; align-self: flex-start; }
    </style>
</head>
<body>

<div class="window-container">
    <div style="display:flex; justify-content: space-between; align-items:center;">
        <h2>Registered Customers</h2>
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Customer ID</th>
                    <th>Name</th>
                    <th>Phone</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM customer ORDER BY CustomerID DESC");
                if ($res && $res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        $cName = isset($row['Name']) ? $row['Name'] : (isset($row['CustomerName']) ? $row['CustomerName'] : 'N/A');
                        $cPhone = isset($row['Phone']) ? $row['Phone'] : (isset($row['ContactNo']) ? $row['ContactNo'] : 'N/A');

                        echo "<tr>
                                <td>#{$row['CustomerID']}</td>
                                <td><b>{$cName}</b></td>
                                <td>{$cPhone}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center;'>No customers found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>