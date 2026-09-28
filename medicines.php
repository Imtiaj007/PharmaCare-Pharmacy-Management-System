<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 

$msg = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_medicine'])) {
    $name = trim($_POST['name']);
    $cat = trim($_POST['category']);
    $stock = intval($_POST['stock']);
    $price = floatval($_POST['price']);

    // 1. Insert into Medicine Table
    $stmt = $conn->prepare("INSERT INTO medicine (MedicineName, Category) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $cat);
    
    if ($stmt->execute()) {
        $medId = $conn->insert_id;
        // 2. Insert into Batch Table for Stock and Selling Price
        $batchNum = "BAT-" . rand(100, 999);
        $expDate = date('Y-m-d', strtotime('+1 year'));
        $stmt2 = $conn->prepare("INSERT INTO batch (MedicineID, BatchNumber, ExpiryDate, SellingPrice, StockQuantity) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("issdi", $medId, $batchNum, $expDate, $price, $stock);
        $stmt2->execute();
        $msg = "<p style='color: #1dd1a1; font-weight: bold;'>Medicine Added Successfully!</p>";
    } else {
        $msg = "<p style='color: #ff6b6b; font-weight: bold;'>Error adding medicine!</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Medicine Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .window-container { background: #fff; width: 100%; max-width: 1050px; height: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; overflow: hidden; }
        
        .sidebar { background: #dcdde1; width: 300px; padding: 25px 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #2c3e50; font-size: 22px; margin-bottom: 20px; }
        .form-control { width: 100%; padding: 12px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; }
        .form-control:focus { border-color: #1dd1a1; }
        
        .btn-add { width: 100%; background: #1dd1a1; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-bottom: 8px; }
        .btn-clear { width: 100%; background: #576574; color: white; padding: 10px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .btn-back { background: #ff6b6b; color: white; padding: 10px 20px; border: none; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
        
        .main-content { flex: 1; padding: 25px; display: flex; flex-direction: column; }
        .search-box { width: 100%; padding: 12px 15px; border: 1px solid #ccc; border-radius: 20px; font-size: 14px; margin-bottom: 20px; outline: none; }
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
            <h2>Medicine Details</h2>
            <?php echo $msg; ?>
            <form method="POST" id="medForm">
                <input type="hidden" name="add_medicine" value="1">
                <input type="text" name="name" class="form-control" placeholder="Medicine Name" required>
                <input type="text" name="category" class="form-control" placeholder="Category (e.g. Syrup, Tablet)" required>
                <input type="number" name="stock" class="form-control" placeholder="Stock Quantity" required>
                <input type="number" step="0.01" name="price" class="form-control" placeholder="Price per unit" required>
                
                <button type="submit" class="btn-add"><i class="fa-solid fa-plus"></i> Add Medicine</button>
                <button type="button" onclick="document.getElementById('medForm').reset();" class="btn-clear">Clear Fields</button>
            </form>
        </div>
        <a href="index.php" class="btn-back">&larr; Back</a>
    </div>

    <div class="main-content">
        <input type="text" id="searchInput" onkeyup="filterTable()" class="search-box" placeholder="🔍 Search Medicine by Name or Category...">
        
        <div class="table-wrapper">
            <table id="medTable">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Category</th>
                        <th>Stock</th>
                        <th>Price (Tk)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sql = "SELECT M.MedicineName, M.Category, SUM(B.StockQuantity) as TotalStock, AVG(B.SellingPrice) as Price 
                            FROM medicine M 
                            LEFT JOIN batch B ON M.MedicineID = B.MedicineID 
                            GROUP BY M.MedicineID ORDER BY M.MedicineID DESC";
                    $res = $conn->query($sql);
                    if ($res && $res->num_rows > 0) {
                        while($row = $res->fetch_assoc()) {
                            $st = $row['TotalStock'] ? $row['TotalStock'] : 0;
                            $pr = $row['Price'] ? number_format($row['Price'], 2) : '0.00';
                            echo "<tr>
                                    <td><b>{$row['MedicineName']}</b></td>
                                    <td>{$row['Category']}</td>
                                    <td>{$st}</td>
                                    <td>৳ {$pr}</td>
                                  </tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center;'>No content in table</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function filterTable() {
    let input = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#medTable tbody tr");
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(input) ? "" : "none";
    });
}
</script>
</body>
</html>