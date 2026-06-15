<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}

$cust_id = $_SESSION['cust_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header("Location: payment history.php");
    exit();
}

// Verify order belongs to customer and is delivered within 2 days
$order_query = $conn->prepare("
    SELECT o.*, c.cust_name, c.cust_email, c.cust_phone_number
    FROM orders o
    JOIN customers c ON o.cust_id = c.cust_id
    WHERE o.order_id = ? AND o.cust_id = ? AND o.status = 'Delivered'
");
$order_query->bind_param("ii", $order_id, $cust_id);
$order_query->execute();
$order_result = $order_query->get_result();
$order_data = $order_result->fetch_assoc();
$order_query->close();

if (!$order_data) {
    die("Invalid order or order not eligible for return.");
}

// Check 2-day limit
$delivered_at = $order_data['delivered_at'] ? strtotime($order_data['delivered_at']) : null;
if (!$delivered_at || (time() - $delivered_at) > (2 * 24 * 60 * 60)) {
    die("Return period has expired for this order.");
}

// Fetch order items
$items_query = $conn->prepare("
    SELECT oi.*, p.prod_name 
    FROM order_items oi 
    JOIN products p ON oi.prod_id = p.prod_id 
    WHERE oi.order_id = ?
");
$items_query->bind_param("i", $order_id);
$items_query->execute();
$items_result = $items_result = $items_query->get_result();
$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$items_query->close();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_return'])) {
    $reason = $_POST['reason'] ?? '';
    $details = $_POST['details'] ?? '';
    
    // Handle Photo Upload
    $photo_name = '';
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $target_dir = "../uploads/returns/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_name = "return_" . $order_id . "_" . time() . "." . $file_ext;
        $target_file = $target_dir . $photo_name;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
            // Success
        } else {
            $message = "Error uploading photo.";
        }
    }

    if (empty($message)) {
        $conn->begin_transaction();
        try {
            $insert = $conn->prepare("INSERT INTO product_returns (order_id, cust_id, reason, details, photo) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("iisss", $order_id, $cust_id, $reason, $details, $photo_name);
            $insert->execute();
            $insert->close();

            // Update order status back to 'Processing' as requested
            $upd = $conn->prepare("UPDATE orders SET status = 'Processing' WHERE order_id = ?");
            $upd->bind_param("i", $order_id);
            $upd->execute();
            $upd->close();

            $conn->commit();
            header("Location: payment history.php?return_success=1");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error submitting return: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Product</title>
    <link rel="stylesheet" href="style.css?v=5.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .return-card { 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 40px auto;
            border: 1px solid #f1f5f9;
        }
        .form-label { font-weight: 700; color: #475569; font-size: 0.9rem; }
        .form-control, .form-select {
            padding: 12px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            background: white;
            border-color: #4f46e5;
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }
        .info-box {
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            border-left: 4px solid #4f46e5;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d3b8e; padding: 12px 0;">
    <div class="container-fluid px-5">
        <a class="navbar-brand" href="../customer/home_page.php" style="font-weight: 500;">Musical Instrument Store</a>
    </div>
</nav>

<div class="container pb-5">
    <div class="return-card">
        <h2 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">Return Product Request</h2>
        <p style="color: #64748b; margin-bottom: 32px;">Please provide the details below for your return request. Our team will review it shortly.</p>

        <?php if ($message): ?>
            <div class="alert alert-danger"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="info-box">
            <h5 style="font-weight: 700; margin-bottom: 12px;">Customer & Order Details</h5>
            <div style="font-size: 0.9rem; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div><strong>Name:</strong> <?php echo htmlspecialchars($order_data['cust_name']); ?></div>
                <div><strong>Email:</strong> <?php echo htmlspecialchars($order_data['cust_email']); ?></div>
                <div><strong>Order ID:</strong> #<?php echo $order_id; ?></div>
                <div><strong>Delivered On:</strong> <?php echo date('d M Y, h:i A', strtotime($order_data['delivered_at'])); ?></div>
            </div>
            <div style="margin-top: 12px; font-size: 0.9rem;">
                <strong>Items:</strong>
                <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                    <?php foreach ($items as $item): ?>
                        <li><?php echo $item['order_qty']; ?>x <?php echo htmlspecialchars($item['prod_name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label class="form-label">Reason for Return *</label>
                <select name="reason" class="form-select" required>
                    <option value="" disabled selected>Select a reason...</option>
                    <option value="Physically Damaged">Physically Damaged</option>
                    <option value="malfunction/faulty">Malfunction/Faulty</option>
                    <option value="wrong item">Wrong Item</option>
                    <option value="empty parcel">Empty Parcel</option>
                    <option value="any other reason">Any other reason</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="form-label">Photo of Product *</label>
                <p style="font-size: 0.75rem; color: #64748b; margin-bottom: 8px;">Please upload a clear photo of the issue/product for verification.</p>
                <input type="file" name="photo" class="form-control" accept="image/*" required>
            </div>

            <div class="mb-4">
                <label class="form-label">Additional Details / Description</label>
                <textarea name="details" class="form-control" rows="4" placeholder="Explain the issue in detail..."></textarea>
            </div>

            <div style="display: flex; gap: 16px; margin-top: 40px;">
                <a href="payment history.php" class="btn btn-outline-secondary px-4 py-2" style="flex: 1; border-radius: 10px; font-weight: 600;">Cancel</a>
                <button type="submit" name="submit_return" class="btn btn-primary px-4 py-2" style="flex: 2; border-radius: 10px; font-weight: 700; background: #4f46e5; border: none;">Submit Return Request</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
