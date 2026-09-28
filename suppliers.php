<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Supplier List</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .window-container { background: #fff; width: 100%; max-width: 900px; height: 550px; border-radius: 12px; padding: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; flex-direction: column; }
        .table-wrapper { flex: 1; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; margin-top: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #00d2d3; color: white; padding: 12px; position: sticky; top: 0; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .btn-back { background: #576574; color: white; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: bold; align-self: flex-start; }
    </style>
</head>
<body>

<div class="window-container">
    <div style="display:flex; justify-content: space-between; align-items:center;">
        <h2>Medicine Suppliers</h2>
        <a href="index.php" class="btn-back">&larr; Back to Dashboard</a>
    </div>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>Supplier ID</th>
                    <th>Supplier Name</th>
                    <th>Contact Info</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $res = $conn->query("SELECT * FROM supplier ORDER BY SupplierID DESC");
                if ($res && $res->num_rows > 0) {
                    while($row = $res->fetch_assoc()) {
                        $sName = isset($row['Name']) ? $row['Name'] : (isset($row['SupplierName']) ? $row['SupplierName'] : 'N/A');
                        $sContact = isset($row['ContactInfo']) ? $row['ContactInfo'] : (isset($row['Phone']) ? $row['Phone'] : 'N/A');

                        echo "<tr>
                                <td>#{$row['SupplierID']}</td>
                                <td><b>{$sName}</b></td>
                                <td>{$sContact}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='3' style='text-align:center;'>No suppliers found</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>