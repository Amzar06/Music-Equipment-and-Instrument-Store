<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

$total_price = 0.00;

if (isset($_GET['amount'])) {
    $total_price = floatval($_GET['amount']);
} else {
    if (isset($conn)) {
        $query = $conn->prepare("
            SELECT p.prod_sale_price 
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN products p ON ci.instrument_id = p.prod_id
            WHERE c.user_id = ?
        ");
        if ($query) {
            $query->bind_param("i", $cust_id);
            $query->execute();
            $result = $query->get_result();
            while($row = $result->fetch_assoc()) {
                if (isset($row['prod_sale_price'])) {
                    $total_price += $row['prod_sale_price'];
                }
            }
            $query->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Make Payment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container text-center" style="max-width: 500px;">
    <h2>Payment Options</h2>
    <p class="mb-4">Please scan the QR code below to complete your payment.</p>
    
    <div style="font-size: 1.25rem; font-weight: 600; margin-bottom: 24px; color: var(--text);">
        Total Amount: RM <?php echo number_format($total_price, 2); ?>
    </div>

    <!-- QR Code Section -->
    <div style="background: white; padding: 24px; border-radius: 12px; display: inline-block; margin-bottom: 24px;">
        <img src="qr_placeholder.png" alt="Scan to Pay" style="width: 200px; height: 200px; object-fit: contain;">
        <p style="color: #333; font-size: 14px; margin-top: 12px; font-weight: 500;">DuitNow / Touch 'n Go</p>
    </div>

    <form action="payment page.php" method="POST" enctype="multipart/form-data">
        <?php if (isset($_GET['type']) && $_GET['type'] === 'rent'): ?>
            <input type="hidden" name="type" value="rent">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($_GET['product_id'] ?? 0); ?>">
            <input type="hidden" name="days" value="<?php echo htmlspecialchars($_GET['days'] ?? 1); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($_GET['amount'] ?? 0); ?>">
        <?php endif; ?>
        
        <!-- Capture Address Details -->
        <input type="hidden" name="street" value="<?php echo htmlspecialchars($_GET['street'] ?? ''); ?>">
        <input type="hidden" name="city" value="<?php echo htmlspecialchars($_GET['city'] ?? ''); ?>">
        <input type="hidden" name="postcode" value="<?php echo htmlspecialchars($_GET['postcode'] ?? ''); ?>">
        <input type="hidden" name="state" value="<?php echo htmlspecialchars($_GET['state'] ?? ''); ?>">

        <!-- Receipt Upload -->
        <div style="text-align: left; margin-bottom: 24px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 500;">Upload Payment Receipt *</label>
            <input type="file" name="receipt" accept="image/*,application/pdf" required style="padding: 12px; width: 100%; box-sizing: border-box; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: var(--text);">
        </div>

        <div style="display: flex; gap: 16px; margin-top: 16px;">
            <a href="address page.php" style="flex: 1; text-align: center; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0; display: inline-block;">Back</a>
            <button type="submit" style="flex: 1; margin: 0;">Submit Receipt</button>
        </div>
    </form>
</div>

</body>
</html>
