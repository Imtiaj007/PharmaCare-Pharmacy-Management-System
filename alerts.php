<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Low Stock Alerts</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .window-container { background: #fff; width: 100%; max-width: 1050px; height: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; overflow: hidden; }
        
        .sidebar { background: #dcdde1; width: 320px; padding: 25px 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #d63031; font-size: 24px; margin-bottom: 15px; }
        .sidebar p { color: #636e72; font-size: 14px; line-height: 1.5; }
        
        .btn-back { background: #576574; color: white; padding: 10px 20px; border: none; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
        
        .main-content { flex: 1; padding: 25px; display: flex; flex-direction: column; }
        .main-content h2 { color: #2c3e50; font-size: 22px; margin-bottom: 20px; }
        .table-wrapper { flex: 1; overflow-y: auto; border: 1px solid #ff7675; border-radius: 6px; }
        
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #ffeaa7; color: #d63031; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        .stock-danger { color: #d63031; font-weight: bold; }
    </style>
</head>
<body>

<div class="window-container">
    <div class="sidebar">
        <div>
            <h2>Low Stock Alerts</h2>
            <p>The items listed here have a stock quantity of less than 10 units. Please restock immediately.</p>
        </div>
        <a href="index.php" class="btn-back">&larr; Back</a>
    </div>

    <div class="main-content">
        <h2>Inventory Warning List</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Category</th>
                        <th>Current Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT M.MedicineName, M.Category, B.StockQuantity 
                            FROM batch B 
                            JOIN medicine M ON B.MedicineID = M.MedicineID 
                            WHERE B.StockQuantity < 10";
                    $res = $conn->query($sql);
                    if ($res && $res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            echo "<tr>
                                    <td><b>{$row['MedicineName']}</b></td>
                                    <td>{$row['Category']}</td>
                                    <td class='stock-danger'>{$row['StockQuantity']} pcs</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='3' style='text-align:center;'>No content in table</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>