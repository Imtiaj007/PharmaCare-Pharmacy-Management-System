<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <title>Billing System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background-color: #f0f2f5; height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .window-container { background: #fff; width: 100%; max-width: 1050px; height: 600px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); display: flex; overflow: hidden; }
        
        .sidebar { background: #dcdde1; width: 320px; padding: 25px 20px; display: flex; flex-direction: column; justify-content: space-between; }
        .sidebar h2 { color: #2c3e50; font-size: 22px; margin-bottom: 20px; }
        .form-group { margin-bottom: 12px; }
        .form-group label { font-size: 12px; font-weight: bold; color: #333; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 6px; font-size: 14px; outline: none; margin-top: 4px; }
        
        .btn-cart { width: 100%; background: #1dd1a1; color: white; padding: 12px; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; margin-top: 10px; }
        .btn-back { background: #576574; color: white; padding: 10px 20px; border: none; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
        
        .main-content { flex: 1; padding: 25px; display: flex; flex-direction: column; justify-content: space-between; }
        .table-wrapper { flex: 1; overflow-y: auto; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #e1e2e6; color: #2c3e50; padding: 12px; }
        td { padding: 12px; border-bottom: 1px solid #eee; font-size: 14px; }
        
        .checkout-bar { display: flex; justify-content: space-between; align-items: center; border-top: 2px solid #eee; padding-top: 15px; }
        .grand-total { font-size: 22px; font-weight: bold; color: #2c3e50; }
        .grand-total span { color: #ff6b6b; }
        .btn-confirm { background: #ff6b6b; color: white; padding: 12px 25px; border: none; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; }

        @media print {
            body * { visibility: hidden; }
            #printableArea, #printableArea * { visibility: visible; }
            #printableArea { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body>

<div class="window-container">
    <div class="sidebar">
        <div>
            <h2>Billing System</h2>
            <div class="form-group">
                <label>Customer Name:</label>
                <input type="text" id="custName" class="form-control" placeholder="Enter Customer Name">
            </div>
            <div class="form-group">
                <label>Select Medicine:</label>
                <select id="medSelect" class="form-control" onchange="updatePrice()">
                    <option value="">Choose Medicine</option>
                    <?php
                    $sql = "SELECT M.MedicineID, M.MedicineName, B.SellingPrice 
                            FROM medicine M 
                            JOIN batch B ON M.MedicineID = B.MedicineID 
                            WHERE B.StockQuantity > 0";
                    $res = $conn->query($sql);
                    while($row = $res->fetch_assoc()) {
                        echo "<option value='{$row['MedicineID']}' data-name='{$row['MedicineName']}' data-price='{$row['SellingPrice']}'>{$row['MedicineName']} (৳{$row['SellingPrice']})</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <label>Quantity:</label>
                <input type="number" id="qtyInput" class="form-control" placeholder="Enter Quantity" value="1" min="1">
            </div>
            <div class="form-group">
                <label>Price per unit:</label>
                <input type="text" id="unitPrice" class="form-control" readonly placeholder="0.00">
            </div>
            
            <button onclick="addToCart()" class="btn-cart"><i class="fa-solid fa-cart-plus"></i> Add to Cart</button>
        </div>
        <a href="index.php" class="btn-back">&larr; Back</a>
    </div>

    <div class="main-content">
        <div class="table-wrapper" id="printableArea">
            <h3 id="printCustHeader" style="display:none; margin-bottom: 10px;">Pharmacy Invoice</h3>
            <table id="cartTable">
                <thead>
                    <tr>
                        <th>Medicine Name</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="emptyRow"><td colspan="4" style="text-align:center;">No content in table</td></tr>
                </tbody>
            </table>
        </div>

        <div class="checkout-bar">
            <div class="grand-total">Grand Total: <span id="grandTotalText">0.00 Tk</span></div>
            <button onclick="window.print()" class="btn-confirm"><i class="fa-solid fa-print"></i> Confirm & Print Bill</button>
        </div>
    </div>
</div>

<script>
let cart = [];

function updatePrice() {
    let select = document.getElementById("medSelect");
    let selectedOption = select.options[select.selectedIndex];
    let price = selectedOption.getAttribute("data-price") || "0.00";
    document.getElementById("unitPrice").value = price;
}

function addToCart() {
    let select = document.getElementById("medSelect");
    let medId = select.value;
    let name = select.options[select.selectedIndex].getAttribute("data-name");
    let price = parseFloat(document.getElementById("unitPrice").value);
    let qty = intval = parseInt(document.getElementById("qtyInput").value);

    if (!medId || qty <= 0) { alert("Please select a medicine and valid quantity!"); return; }

    let total = price * qty;
    cart.push({ name, qty, price, total });

    renderCart();
}

function renderCart() {
    let tbody = document.querySelector("#cartTable tbody");
    tbody.innerHTML = "";
    let grandTotal = 0;

    cart.forEach(item => {
        grandTotal += item.total;
        tbody.innerHTML += `<tr>
            <td><b>${item.name}</b></td>
            <td>${item.qty}</td>
            <td>৳ ${item.price.toFixed(2)}</td>
            <td>৳ ${item.total.toFixed(2)}</td>
        </tr>`;
    });

    document.getElementById("grandTotalText").innerText = grandTotal.toFixed(2) + " Tk";
}
</script>

</body>
</html>