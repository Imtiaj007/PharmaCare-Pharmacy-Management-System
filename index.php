<?php 
session_start();
if (!isset($_SESSION['emp_id'])) { header("Location: login.php"); exit(); }
include 'db.php'; 

$msg = "";
$error = "";
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'billing';

// 1. ADD MEDICINE & BATCH
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_medicine'])) {
    $name = trim($_POST['name']);
    $cat = trim($_POST['category']);
    $generic = trim($_POST['generic_name']);
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : NULL;
    $stock = intval($_POST['stock']);
    $price = floatval($_POST['price']);
    $purchase_price = isset($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : 0.00;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+1 year'));

    $stmt = $conn->prepare("INSERT INTO medicine (MedicineName, Category, GenericName, SupplierID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $cat, $generic, $supplier_id);
    if ($stmt->execute()) {
        $medId = $conn->insert_id;
        $batchNum = "BAT-" . rand(100, 999);
        
        $stmt2 = $conn->prepare("INSERT INTO batch (MedicineID, BatchNumber, ExpiryDate, PurchasePrice, SellingPrice, StockQuantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param("issddi", $medId, $batchNum, $expiry_date, $purchase_price, $price, $stock);
        $stmt2->execute();
        $msg = "Medicine added successfully!";
    } else {
        $error = "Failed to add medicine: " . $conn->error;
    }
}

// 2. UPDATE MEDICINE & STOCK
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_medicine'])) {
    $medId = intval($_POST['medicine_id']);
    $name = trim($_POST['name']);
    $cat = trim($_POST['category']);
    $generic = trim($_POST['generic_name']);
    $supplier_id = !empty($_POST['supplier_id']) ? intval($_POST['supplier_id']) : NULL;
    $add_stock = intval($_POST['add_stock']);
    $price = floatval($_POST['price']);
    $purchase_price = isset($_POST['purchase_price']) ? floatval($_POST['purchase_price']) : 0.00;
    $expiry_date = !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : date('Y-m-d', strtotime('+1 year'));

    $stmt = $conn->prepare("UPDATE medicine SET MedicineName=?, Category=?, GenericName=?, SupplierID=? WHERE MedicineID=?");
    $stmt->bind_param("sssii", $name, $cat, $generic, $supplier_id, $medId);
    $stmt->execute();

    $resBatch = $conn->query("SELECT BatchID, StockQuantity FROM batch WHERE MedicineID=$medId ORDER BY BatchID DESC LIMIT 1");
    if ($resBatch && $resBatch->num_rows > 0) {
        $bRow = $resBatch->fetch_assoc();
        $newStock = $bRow['StockQuantity'] + $add_stock;
        $batchId = $bRow['BatchID'];
        $stmtB = $conn->prepare("UPDATE batch SET StockQuantity=?, ExpiryDate=?, PurchasePrice=?, SellingPrice=? WHERE BatchID=?");
        $stmtB->bind_param("isddi", $newStock, $expiry_date, $purchase_price, $price, $batchId);
        $stmtB->execute();
    } else {
        $batchNum = "BAT-" . rand(100, 999);
        $stmtB = $conn->prepare("INSERT INTO batch (MedicineID, BatchNumber, ExpiryDate, PurchasePrice, SellingPrice, StockQuantity) VALUES (?, ?, ?, ?, ?, ?)");
        $stmtB->bind_param("issddi", $medId, $batchNum, $expiry_date, $purchase_price, $price, $add_stock);
        $stmtB->execute();
    }
    $msg = "Medicine updated successfully!";
}

// 3. DELETE MEDICINE
if (isset($_GET['delete_med'])) {
    $delId = intval($_GET['delete_med']);
    $conn->query("DELETE FROM batch WHERE MedicineID=$delId");
    $conn->query("DELETE FROM medicine WHERE MedicineID=$delId");
    header("Location: index.php?tab=medicines");
    exit();
}

// 4. ADD STAFF
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_staff'])) {
    $fullName = trim($_POST['name']);
    $parts = explode(' ', $fullName, 2);
    $firstName = $parts[0];
    $lastName = isset($parts[1]) ? $parts[1] : '';
    $role = trim($_POST['role']);
    $phone = trim($_POST['phone']);

    $stmt = $conn->prepare("INSERT INTO employee (FirstName, LastName, Role, Phone) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstName, $lastName, $role, $phone);
    if ($stmt->execute()) { $msg = "Staff added successfully!"; }
}

// 5. ADD SUPPLIER
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_supplier'])) {
    $supplier_name = trim($_POST['supplier_name']);
    $contact_info = trim($_POST['contact_info']);

    $stmt = $conn->prepare("INSERT INTO supplier (Name, ContactInfo) VALUES (?, ?)");
    $stmt->bind_param("ss", $supplier_name, $contact_info);
    
    if ($stmt->execute()) {
        $msg = "Supplier added successfully!";
    } else {
        $error = "Failed to add supplier: " . $conn->error;
    }
}

// 6. PROCESS MULTIPLE MEDICINES SALE
$sale_success = false;
$invoice_data = null;
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['process_sale'])) {
    $cust_name = trim($_POST['cust_name']);
    $cust_phone = trim($_POST['cust_phone']);
    $doctor_name = trim($_POST['doctor_name']);
    $med_ids = $_POST['med_id'] ?? [];
    $qtys = $_POST['qty'] ?? [];
    $emp_id = $_SESSION['emp_id'];

    if (empty($med_ids)) {
        $error = "Please select at least one medicine!";
    } else {
        $grand_total = 0;
        $items_to_sell = [];
        $has_error = false;

        for ($i = 0; $i < count($med_ids); $i++) {
            $m_id = intval($med_ids[$i]);
            $q = intval($qtys[$i]);

            if ($m_id <= 0 || $q <= 0) continue;

            $bRes = $conn->query("SELECT * FROM batch WHERE MedicineID=$m_id AND StockQuantity >= $q ORDER BY BatchID ASC LIMIT 1");
            if ($bRes && $bRes->num_rows > 0) {
                $bData = $bRes->fetch_assoc();
                
                $mRes = $conn->query("SELECT MedicineName FROM medicine WHERE MedicineID=$m_id");
                $mName = ($mRes && $mRes->num_rows > 0) ? $mRes->fetch_assoc()['MedicineName'] : 'Medicine';

                $subtotal = $bData['SellingPrice'] * $q;
                $grand_total += $subtotal;

                $items_to_sell[] = [
                    'med_id' => $m_id,
                    'med_name' => $mName,
                    'batch_id' => $bData['BatchID'],
                    'qty' => $q,
                    'unit_price' => $bData['SellingPrice'],
                    'subtotal' => $subtotal,
                    'current_stock' => $bData['StockQuantity']
                ];
            } else {
                $mRes = $conn->query("SELECT MedicineName FROM medicine WHERE MedicineID=$m_id");
                $mName = ($mRes && $mRes->num_rows > 0) ? $mRes->fetch_assoc()['MedicineName'] : 'Medicine';
                $error = "Insufficient stock for: " . $mName;
                $has_error = true;
                break;
            }
        }

        if (!$has_error && !empty($items_to_sell)) {
            $cust_id = null;
            $cRes = $conn->query("SELECT CustomerID FROM customer WHERE Phone='$cust_phone' LIMIT 1");
            if ($cRes && $cRes->num_rows > 0) {
                $cRow = $cRes->fetch_assoc();
                $cust_id = $cRow['CustomerID'];
            } else {
                $checkCols = $conn->query("SHOW COLUMNS FROM customer LIKE 'CustomerName'");
                if ($checkCols && $checkCols->num_rows > 0) {
                    $stmtC = $conn->prepare("INSERT INTO customer (CustomerName, Phone) VALUES (?, ?)");
                    $stmtC->bind_param("ss", $cust_name, $cust_phone);
                } else {
                    $stmtC = $conn->prepare("INSERT INTO customer (Phone) VALUES (?)");
                    $stmtC->bind_param("s", $cust_phone);
                }
                $stmtC->execute();
                $cust_id = $conn->insert_id;
            }
            if (!$cust_id) { $cust_id = 1; }

            $sale_date = date('Y-m-d H:i:s');
            $stmtS = $conn->prepare("INSERT INTO sale (CustomerID, EmployeeID, SaleDate, TotalAmount) VALUES (?, ?, ?, ?)");
            $stmtS->bind_param("iisd", $cust_id, $emp_id, $sale_date, $grand_total);
            
            if ($stmtS->execute()) {
                $sale_id = $conn->insert_id;

                foreach ($items_to_sell as $item) {
                    $stmtSD = $conn->prepare("INSERT INTO saledetails (SaleID, BatchID, Quantity, Subtotal) VALUES (?, ?, ?, ?)");
                    $stmtSD->bind_param("iiid", $sale_id, $item['batch_id'], $item['qty'], $item['subtotal']);
                    $stmtSD->execute();

                    $new_stock = $item['current_stock'] - $item['qty'];
                    $conn->query("UPDATE batch SET StockQuantity=$new_stock WHERE BatchID=" . $item['batch_id']);
                }

                if (!empty($doctor_name)) {
                    $p_date = date('Y-m-d');
                    $stmtP = $conn->prepare("INSERT INTO prescription (CustomerID, DoctorName, PrescriptionDate) VALUES (?, ?, ?)");
                    $stmtP->bind_param("iss", $cust_id, $doctor_name, $p_date);
                    $stmtP->execute();
                }

                $sale_success = true;
                $invoice_data = [
                    'sale_id' => $sale_id,
                    'cust_name' => $cust_name,
                    'cust_phone' => $cust_phone,
                    'doctor_name' => !empty($doctor_name) ? $doctor_name : 'N/A',
                    'items' => $items_to_sell,
                    'grand_total' => $grand_total,
                    'date' => $sale_date
                ];
                $msg = "Sale completed successfully!";
            }
        }
    }
}

// 7. LOW STOCK & EXPIRY ALERTS FETCH
$low_stock_alerts = [];
$expiry_alerts = [];

// Low Stock Alert Query (< 20)
$lowStockQuery = "SELECT M.MedicineName, B.BatchNumber, B.StockQuantity 
                  FROM batch B JOIN medicine M ON B.MedicineID = M.MedicineID 
                  WHERE B.StockQuantity < 20 ORDER BY B.StockQuantity ASC";
$resLow = $conn->query($lowStockQuery);
if ($resLow && $resLow->num_rows > 0) {
    while($row = $resLow->fetch_assoc()) {
        $low_stock_alerts[] = $row;
    }
}

// Expiry Alert Query (Expiry Date within 10 days or already expired)
$expiryQuery = "SELECT M.MedicineName, B.BatchNumber, B.ExpiryDate, DATEDIFF(B.ExpiryDate, CURDATE()) as DaysLeft 
                FROM batch B JOIN medicine M ON B.MedicineID = M.MedicineID 
                WHERE DATEDIFF(B.ExpiryDate, CURDATE()) <= 10 ORDER BY B.ExpiryDate ASC";
$resExp = $conn->query($expiryQuery);
if ($resExp && $resExp->num_rows > 0) {
    while($row = $resExp->fetch_assoc()) {
        $expiry_alerts[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaCare - Pharmacy Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --bg-light: #f8fafc;
            --text-dark: #0f172a;
            --text-muted: #64748b;
            --sidebar-bg: #1e293b;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; }
        body { background-color: var(--bg-light); color: var(--text-dark); display: flex; height: 100vh; overflow: hidden; }

        .sidebar { width: 260px; background: var(--sidebar-bg); color: #fff; display: flex; flex-direction: column; justify-content: space-between; padding: 20px 0; }
        .brand { padding: 0 20px 25px; display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; color: #38bdf8; border-bottom: 1px solid #334155; }
        .nav-menu { list-style: none; margin-top: 20px; flex: 1; }
        .nav-item a { display: flex; align-items: center; gap: 14px; padding: 14px 24px; color: #94a3b8; text-decoration: none; font-size: 15px; font-weight: 500; transition: all 0.2s ease; }
        .nav-item a:hover, .nav-item.active a { background: #334155; color: #fff; border-left: 4px solid var(--primary); }
        .logout-btn { display: flex; align-items: center; gap: 12px; padding: 14px 24px; color: #f87171; text-decoration: none; font-weight: 600; transition: 0.2s; border-top: 1px solid #334155; }
        .logout-btn:hover { background: #7f1d1d; color: #fff; }

        .main-wrapper { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        .header { background: var(--card-bg); height: 65px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 30px; }
        .header h1 { font-size: 20px; font-weight: 600; color: var(--text-dark); }
        .user-profile { display: flex; align-items: center; gap: 10px; font-size: 14px; font-weight: 600; color: var(--text-muted); }

        .content-body { flex: 1; padding: 25px 30px; overflow-y: auto; }
        .alert-msg { background: #d1fae5; color: #065f46; padding: 12px 18px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; color: #991b1b; padding: 12px 18px; border-radius: 8px; margin-bottom: 15px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
        .alert-warning-box { background: #fffbe3; color: #b45309; border: 1px solid #fde68a; padding: 12px 18px; border-radius: 8px; margin-bottom: 15px; font-size: 13px; }

        .layout-grid { display: grid; grid-template-columns: 420px 1fr; gap: 25px; }
        .full-grid { display: grid; grid-template-columns: 1fr; }
        
        .card { background: var(--card-bg); border-radius: 12px; border: 1px solid var(--border); padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .card-header-flex { display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
        .card-title { font-size: 17px; font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 8px; }

        .search-box { position: relative; width: 280px; }
        .search-box input { width: 100%; padding: 8px 12px 8px 35px; border: 1px solid var(--border); border-radius: 6px; font-size: 13px; outline: none; }
        .search-box i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }

        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .btn-submit { width: 100%; background: var(--primary); color: #fff; border: none; padding: 11px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: var(--primary-dark); }
        .btn-action { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
        .btn-edit { background: #e0e7ff; color: #3730a3; }
        .btn-delete { background: #fee2e2; color: #991b1b; }

        .med-row { display: flex; gap: 8px; margin-bottom: 10px; align-items: center; }
        .med-row select { flex: 2; }
        .med-row input { flex: 1; }
        .btn-remove-row { background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 10px; border-radius: 8px; cursor: pointer; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { background: #f1f5f9; color: var(--text-muted); font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; border-bottom: 1px solid var(--border); }
        td { padding: 14px 16px; border-bottom: 1px solid var(--border); font-size: 14px; color: var(--text-dark); }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .badge-warning { background: #fef3c7; color: #d97706; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-danger { background: #fee2e2; color: #dc2626; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; }

        .receipt-box { background: #fff; border: 1px solid var(--border); padding: 20px; border-radius: 8px; margin-top: 20px; }
        .receipt-header { text-align: center; border-bottom: 1px dashed #ccc; padding-bottom: 10px; margin-bottom: 10px; }
        @media print {
            body * { visibility: hidden; }
            #printable-receipt, #printable-receipt * { visibility: visible; }
            #printable-receipt { position: absolute; left: 0; top: 0; width: 100%; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="brand"><i class="fa-solid fa-square-plus"></i> PharmaCare</div>
            <ul class="nav-menu">
                <li class="nav-item <?php if($active_tab=='billing') echo 'active'; ?>"><a href="index.php?tab=billing"><i class="fa-solid fa-cart-shopping"></i> POS / Billing</a></li>
                <li class="nav-item <?php if($active_tab=='medicines') echo 'active'; ?>"><a href="index.php?tab=medicines"><i class="fa-solid fa-pills"></i> Inventory / Stock</a></li>
                <li class="nav-item <?php if($active_tab=='staff') echo 'active'; ?>"><a href="index.php?tab=staff"><i class="fa-solid fa-user-nurse"></i> Staff List</a></li>
                <li class="nav-item <?php if($active_tab=='customers') echo 'active'; ?>"><a href="index.php?tab=customers"><i class="fa-solid fa-users"></i> Customers</a></li>
                <li class="nav-item <?php if($active_tab=='suppliers') echo 'active'; ?>"><a href="index.php?tab=suppliers"><i class="fa-solid fa-truck-field"></i> Suppliers</a></li>
                <li class="nav-item <?php if($active_tab=='prescriptions') echo 'active'; ?>"><a href="index.php?tab=prescriptions"><i class="fa-solid fa-file-prescription"></i> Prescriptions</a></li>
            </ul>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
    </div>

    <div class="main-wrapper">
        <div class="header">
            <h1><?php echo strtoupper($active_tab); ?> Management</h1>
            <div class="user-profile"><i class="fa-solid fa-circle-user fa-lg"></i> Employee ID: #<?php echo $_SESSION['emp_id']; ?></div>
        </div>

        <div class="content-body">
            <?php if($msg): ?>
                <div class="alert-msg"><i class="fa-solid fa-circle-check"></i> <?php echo $msg; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $error; ?></div>
            <?php endif; ?>

            <!-- ALERTS FOR LOW STOCK AND EXPIRY DATE -->
            <?php if(!empty($low_stock_alerts) || !empty($expiry_alerts)): ?>
                <div class="alert-warning-box">
                    <strong style="font-size:14px;"><i class="fa-solid fa-bell"></i> System Alerts & Warnings:</strong>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <?php foreach($low_stock_alerts as $l): ?>
                            <li><b><?php echo $l['MedicineName']; ?></b> (Batch: <?php echo $l['BatchNumber']; ?>) — Stock Low: <span style="color:#dc2626; font-weight:bold;"><?php echo $l['StockQuantity']; ?> pcs remaining</span> (Less than 20)</li>
                        <?php endforeach; ?>

                        <?php foreach($expiry_alerts as $e): ?>
                            <li>
                                <b><?php echo $e['MedicineName']; ?></b> (Batch: <?php echo $e['BatchNumber']; ?>) — 
                                <?php if($e['DaysLeft'] < 0): ?>
                                    <span style="color:#dc2626; font-weight:bold;">Expired <?php echo abs($e['DaysLeft']); ?> days ago!</span> (Date: <?php echo $e['ExpiryDate']; ?>)
                                <?php elseif($e['DaysLeft'] == 0): ?>
                                    <span style="color:#dc2626; font-weight:bold;">Expiring TODAY!</span>
                                <?php else: ?>
                                    <span style="color:#d97706; font-weight:bold;">Expiring in <?php echo $e['DaysLeft']; ?> day(s)!</span> (Date: <?php echo $e['ExpiryDate']; ?>)
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- TAB 0: POS / BILLING -->
            <?php if($active_tab == 'billing'): ?>
            <div class="layout-grid">
                <div class="card">
                    <div class="card-title" style="margin-bottom: 18px;"><i class="fa-solid fa-receipt"></i> Create New Sale</div>
                    <form method="POST">
                        <input type="hidden" name="process_sale" value="1">
                        <div class="form-group">
                            <label>Customer Name</label>
                            <input type="text" name="cust_name" class="form-control" required placeholder="Customer Name">
                        </div>
                        <div class="form-group">
                            <label>Customer Phone</label>
                            <input type="text" name="cust_phone" class="form-control" required placeholder="017xxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>Doctor Name (Prescription)</label>
                            <input type="text" name="doctor_name" class="form-control" placeholder="Dr. Name (Optional)">
                        </div>

                        <div class="form-group">
                            <label>Select Medicines & Quantity</label>
                            <div id="medicine-container">
                                <div class="med-row">
                                    <select name="med_id[]" class="form-control" required>
                                        <option value="">-- Choose Medicine --</option>
                                        <?php
                                        $mQuery = "SELECT M.MedicineID, M.MedicineName, SUM(B.StockQuantity) as Stock, AVG(B.SellingPrice) as Price 
                                                   FROM medicine M JOIN batch B ON M.MedicineID = B.MedicineID 
                                                   WHERE B.StockQuantity > 0 GROUP BY M.MedicineID";
                                        $mRes = $conn->query($mQuery);
                                        $optionsHtml = "";
                                        while($m = $mRes->fetch_assoc()) {
                                            $opt = "<option value='{$m['MedicineID']}'>{$m['MedicineName']} (Stock: {$m['Stock']} | ৳{$m['Price']})</option>";
                                            $optionsHtml .= $opt;
                                            echo $opt;
                                        }
                                        ?>
                                    </select>
                                    <input type="number" name="qty[]" min="1" value="1" class="form-control" style="width:70px;" placeholder="Qty" required>
                                </div>
                            </div>
                            <button type="button" onclick="addMedicineRow()" style="background:#e0e7ff; color:#3730a3; border:none; padding:8px 12px; border-radius:6px; font-weight:600; cursor:pointer; margin-top:5px;">
                                <i class="fa-solid fa-plus"></i> Add More Medicine
                            </button>
                        </div>

                        <button type="submit" class="btn-submit" style="background:#10b981; margin-top:10px;"><i class="fa-solid fa-print"></i> Complete Sale & Print</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-title" style="margin-bottom: 18px;"><i class="fa-solid fa-clock-rotate-left"></i> Recent Sales / Invoice Print</div>
                    
                    <?php if($sale_success && $invoice_data): ?>
                        <div id="printable-receipt" class="receipt-box">
                            <div class="receipt-header">
                                <h3>PharmaCare Pharmacy</h3>
                                <p style="font-size: 12px; color: #666;">Sales Receipt / Tax Invoice</p>
                            </div>
                            <p><strong>Invoice ID:</strong> #<?php echo $invoice_data['sale_id']; ?></p>
                            <p><strong>Date:</strong> <?php echo $invoice_data['date']; ?></p>
                            <p><strong>Customer:</strong> <?php echo $invoice_data['cust_name']; ?> (<?php echo $invoice_data['cust_phone']; ?>)</p>
                            <p><strong>Prescribed By:</strong> <?php echo $invoice_data['doctor_name']; ?></p>
                            <hr style="margin: 10px 0; border: none; border-top: 1px dashed #ccc;">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Qty</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($invoice_data['items'] as $item): ?>
                                    <tr>
                                        <td><?php echo $item['med_name']; ?></td>
                                        <td><?php echo $item['qty']; ?></td>
                                        <td>৳<?php echo number_format($item['unit_price'], 2); ?></td>
                                        <td>৳<?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <h3 style="text-align: right; margin-top: 15px;">Grand Total: ৳<?php echo number_format($invoice_data['grand_total'], 2); ?></h3>
                        </div>
                        <button onclick="window.print()" class="btn-submit" style="margin-top: 15px;"><i class="fa-solid fa-print"></i> Print Receipt</button>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Customer ID</th>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sRes = $conn->query("SELECT * FROM sale ORDER BY SaleID DESC LIMIT 8");
                                    if ($sRes && $sRes->num_rows > 0) {
                                        while($s = $sRes->fetch_assoc()) {
                                            echo "<tr>
                                                    <td>#{$s['SaleID']}</td>
                                                    <td>#{$s['CustomerID']}</td>
                                                    <td>{$s['SaleDate']}</td>
                                                    <td><b>৳ " . number_format($s['TotalAmount'], 2) . "</b></td>
                                                  </tr>";
                                        }
                                    } else { echo "<tr><td colspan='4'>No sales completed yet</td></tr>"; }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <script>
            function addMedicineRow() {
                var container = document.getElementById('medicine-container');
                var div = document.createElement('div');
                div.className = 'med-row';
                div.innerHTML = `
                    <select name="med_id[]" class="form-control" required>
                        <option value="">-- Choose Medicine --</option>
                        <?php echo $optionsHtml; ?>
                    </select>
                    <input type="number" name="qty[]" min="1" value="1" class="form-control" style="width:70px;" placeholder="Qty" required>
                    <button type="button" class="btn-remove-row" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
                `;
                container.appendChild(div);
            }
            </script>

            <!-- TAB 1: MEDICINES (INVENTORY WITH BATCH DETAILS) -->
            <?php elseif($active_tab == 'medicines'): ?>
            <div class="layout-grid">
                <?php
                $edit_med = null;
                if (isset($_GET['edit_med'])) {
                    $eId = intval($_GET['edit_med']);
                    $eRes = $conn->query("SELECT M.*, B.StockQuantity, B.SellingPrice, B.PurchasePrice, B.ExpiryDate FROM medicine M LEFT JOIN batch B ON M.MedicineID = B.MedicineID WHERE M.MedicineID=$eId ORDER BY B.BatchID DESC LIMIT 1");
                    if ($eRes && $eRes->num_rows > 0) { $edit_med = $eRes->fetch_assoc(); }
                }
                ?>

                <div class="card">
                    <div class="card-title" style="margin-bottom: 18px;">
                        <i class="fa-solid fa-plus-circle"></i> <?php echo $edit_med ? "Update Medicine & Stock" : "Add / Update Stock"; ?>
                    </div>
                    <form method="POST" action="index.php?tab=medicines">
                        <?php if($edit_med): ?>
                            <input type="hidden" name="update_medicine" value="1">
                            <input type="hidden" name="medicine_id" value="<?php echo $edit_med['MedicineID']; ?>">
                        <?php else: ?>
                            <input type="hidden" name="add_medicine" value="1">
                        <?php endif; ?>

                        <div class="form-group">
                            <label>Medicine Name</label>
                            <input type="text" name="name" class="form-control" required value="<?php echo $edit_med ? $edit_med['MedicineName'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" class="form-control" required value="<?php echo $edit_med ? $edit_med['Category'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Generic Name</label>
                            <input type="text" name="generic_name" class="form-control" placeholder="e.g. Paracetamol" value="<?php echo $edit_med ? ($edit_med['GenericName'] ?? '') : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Supplier ID (Optional)</label>
                            <input type="number" name="supplier_id" class="form-control" placeholder="e.g. 1" value="<?php echo $edit_med ? ($edit_med['SupplierID'] ?? '') : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label><?php echo $edit_med ? "Add Extra Stock Quantity" : "Stock Quantity"; ?></label>
                            <input type="number" name="<?php echo $edit_med ? 'add_stock' : 'stock'; ?>" class="form-control" required value="<?php echo $edit_med ? '0' : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Purchase Price (৳)</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control" placeholder="0.00" value="<?php echo $edit_med ? ($edit_med['PurchasePrice'] ?? '0.00') : '0.00'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Selling Price (৳)</label>
                            <input type="number" step="0.01" name="price" class="form-control" required value="<?php echo $edit_med ? $edit_med['SellingPrice'] : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Expiry Date</label>
                            <input type="date" name="expiry_date" class="form-control" value="<?php echo $edit_med ? $edit_med['ExpiryDate'] : ''; ?>">
                        </div>
                        <button type="submit" class="btn-submit"><?php echo $edit_med ? "Update Stock" : "Save Medicine"; ?></button>
                        <?php if($edit_med): ?>
                            <a href="index.php?tab=medicines" style="display:block; text-align:center; margin-top:10px; color:#666;">Cancel Edit</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-title"><i class="fa-solid fa-list"></i> All Medicines & Batches</div>
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="medicineSearch" onkeyup="filterTable('medicineSearch', 'medicineTable')" placeholder="Search medicine, batch...">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="medicineTable">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Batch No</th>
                                    <th>Expiry Date</th>
                                    <th>Purchase</th>
                                    <th>Selling</th>
                                    <th>Stock</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $sql = "SELECT M.MedicineID, M.MedicineName, B.BatchNumber, B.ExpiryDate, B.PurchasePrice, B.SellingPrice, B.StockQuantity, DATEDIFF(B.ExpiryDate, CURDATE()) as DaysLeft 
                                        FROM medicine M JOIN batch B ON M.MedicineID = B.MedicineID 
                                        ORDER BY B.BatchID DESC";
                                $res = $conn->query($sql);
                                if ($res && $res->num_rows > 0) {
                                    while($row = $res->fetch_assoc()) {
                                        $st = $row['StockQuantity'] ? $row['StockQuantity'] : 0;
                                        $pPr = number_format($row['PurchasePrice'], 2);
                                        $sPr = number_format($row['SellingPrice'], 2);
                                        
                                        // Highlight status
                                        $stockBadge = ($st < 20) ? "<br><span class='badge-danger'>Low Stock</span>" : "";
                                        $expBadge = "";
                                        if ($row['DaysLeft'] <= 10) {
                                            $expBadge = ($row['DaysLeft'] < 0) ? "<br><span class='badge-danger'>Expired</span>" : "<br><span class='badge-warning'>Expiring Soon</span>";
                                        }

                                        echo "<tr>
                                                <td><b>{$row['MedicineName']}</b></td>
                                                <td>{$row['BatchNumber']}</td>
                                                <td>{$row['ExpiryDate']} {$expBadge}</td>
                                                <td>৳{$pPr}</td>
                                                <td>৳{$sPr}</td>
                                                <td><b>{$st}</b> {$stockBadge}</td>
                                                <td>
                                                    <a href='index.php?tab=medicines&edit_med={$row['MedicineID']}' class='btn-action btn-edit'><i class='fa-solid fa-pen-to-square'></i> Edit</a>
                                                    <a href='index.php?delete_med={$row['MedicineID']}' onclick='return confirm(\"Are you sure?\");' class='btn-action btn-delete'><i class='fa-solid fa-trash'></i> Delete</a>
                                                </td>
                                              </tr>";
                                    }
                                } else { echo "<tr><td colspan='7'>No records found</td></tr>"; }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 2: STAFF LIST -->
            <?php elseif($active_tab == 'staff'): ?>
            <div class="layout-grid">
                <div class="card">
                    <div class="card-title" style="margin-bottom: 18px;"><i class="fa-solid fa-user-plus"></i> Add Employee</div>
                    <form method="POST">
                        <input type="hidden" name="add_staff" value="1">
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Abdur Rahman">
                        </div>
                        <div class="form-group">
                            <label>Role</label>
                            <input type="text" name="role" class="form-control" required placeholder="e.g. Manager, Pharmacist">
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" name="phone" class="form-control" required placeholder="01910000000">
                        </div>
                        <button type="submit" class="btn-submit">Add Staff</button>
                    </form>
                </div>

                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-title"><i class="fa-solid fa-id-card"></i> Staff Directory</div>
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="staffSearch" onkeyup="filterTable('staffSearch', 'staffTable')" placeholder="Search name or phone number...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="staffTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM employee ORDER BY EmployeeID DESC");
                                if ($res && $res->num_rows > 0) {
                                    while($row = $res->fetch_assoc()) {
                                        $empName = trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''));
                                        if (empty($empName)) { $empName = $row['Name'] ?? 'N/A'; }
                                        $empRole = $row['Role'] ?? 'N/A';
                                        $empPhone = $row['Phone'] ?? 'N/A';

                                        echo "<tr>
                                                <td>#{$row['EmployeeID']}</td>
                                                <td><b>{$empName}</b></td>
                                                <td>{$empRole}</td>
                                                <td>{$empPhone}</td>
                                              </tr>";
                                    }
                                } else { echo "<tr><td colspan='4'>No staff members found</td></tr>"; }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: CUSTOMERS -->
            <?php elseif($active_tab == 'customers'): ?>
            <div class="card full-grid">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-users"></i> Registered Customer List</div>
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="customerSearch" onkeyup="filterTable('customerSearch', 'customerTable')" placeholder="Search mobile number...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="customerTable">
                        <thead>
                            <tr>
                                <th>Customer ID</th>
                                <th>Phone Number</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = $conn->query("SELECT * FROM customer ORDER BY CustomerID DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) {
                                    $cPhone = isset($row['Phone']) ? $row['Phone'] : 'N/A';
                                    echo "<tr>
                                            <td>#{$row['CustomerID']}</td>
                                            <td>{$cPhone}</td>
                                          </tr>";
                                }
                            } else { echo "<tr><td colspan='2'>No customers found</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: SUPPLIERS -->
            <?php elseif($active_tab == 'suppliers'): ?>
            <div class="layout-grid">
                <!-- Add Supplier Form -->
                <div class="card">
                    <div class="card-title" style="margin-bottom: 18px;"><i class="fa-solid fa-truck-field"></i> Add New Supplier</div>
                    <form method="POST" action="index.php?tab=suppliers">
                        <input type="hidden" name="add_supplier" value="1">
                        <div class="form-group">
                            <label>Company / Supplier Name</label>
                            <input type="text" name="supplier_name" class="form-control" required placeholder="e.g. Square Pharmaceuticals">
                        </div>
                        <div class="form-group">
                            <label>Contact / Phone / Address</label>
                            <input type="text" name="contact_info" class="form-control" required placeholder="01711000000 / Dhaka">
                        </div>
                        <button type="submit" class="btn-submit">Add Supplier</button>
                    </form>
                </div>

                <!-- Supplier List Table -->
                <div class="card">
                    <div class="card-header-flex">
                        <div class="card-title"><i class="fa-solid fa-list"></i> Supplier Directory</div>
                        <div class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" id="supplierSearch" onkeyup="filterTable('supplierSearch', 'supplierTable')" placeholder="Search phone or name...">
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table id="supplierTable">
                            <thead>
                                <tr>
                                    <th>Supplier ID</th>
                                    <th>Company / Name</th>
                                    <th>Contact / Phone</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $res = $conn->query("SELECT * FROM supplier ORDER BY SupplierID DESC");
                                if ($res && $res->num_rows > 0) {
                                    while($row = $res->fetch_assoc()) {
                                        $sName = isset($row['Name']) ? $row['Name'] : ($row['SupplierName'] ?? 'N/A');
                                        $sContact = isset($row['ContactInfo']) ? $row['ContactInfo'] : ($row['Phone'] ?? 'N/A');
                                        echo "<tr>
                                                <td>#{$row['SupplierID']}</td>
                                                <td><b>{$sName}</b></td>
                                                <td>{$sContact}</td>
                                              </tr>";
                                    }
                                } else { echo "<tr><td colspan='3'>No suppliers found</td></tr>"; }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 5: PRESCRIPTIONS -->
            <?php elseif($active_tab == 'prescriptions'): ?>
            <div class="card full-grid">
                <div class="card-header-flex">
                    <div class="card-title"><i class="fa-solid fa-file-prescription"></i> Prescription Database</div>
                    <div class="search-box">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="prescriptionSearch" onkeyup="filterTable('prescriptionSearch', 'prescriptionTable')" placeholder="Search Customer ID or Doctor...">
                    </div>
                </div>
                <div class="table-responsive">
                    <table id="prescriptionTable">
                        <thead>
                            <tr>
                                <th>Prescription ID</th>
                                <th>Customer ID</th>
                                <th>Doctor Name</th>
                                <th>Prescription Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $res = $conn->query("SELECT * FROM prescription ORDER BY PrescriptionID DESC");
                            if ($res && $res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) {
                                    $doc = $row['DoctorName'] ?? 'N/A';
                                    $pDate = $row['PrescriptionDate'] ?? 'N/A';
                                    echo "<tr>
                                            <td>#{$row['PrescriptionID']}</td>
                                            <td>#{$row['CustomerID']}</td>
                                            <td><b>{$doc}</b></td>
                                            <td>{$pDate}</td>
                                          </tr>";
                                }
                            } else { echo "<tr><td colspan='4'>No prescription records found</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Live Search Script -->
    <script>
    function filterTable(inputId, tableId) {
        var input = document.getElementById(inputId);
        var filter = input.value.toUpperCase();
        var table = document.getElementById(tableId);
        var tr = table.getElementsByTagName("tr");

        for (var i = 1; i < tr.length; i++) {
            var show = false;
            var tds = tr[i].getElementsByTagName("td");
            for (var j = 0; j < tds.length; j++) {
                if (tds[j]) {
                    var txtValue = tds[j].textContent || tds[j].innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        show = true;
                        break;
                    }
                }
            }
            tr[i].style.display = show ? "" : "none";
        }
    }
    </script>

</body>
</html>