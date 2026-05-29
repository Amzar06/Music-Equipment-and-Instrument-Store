<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    $_SESSION['cust_id'] = 1; 
}
$cust_id = $_SESSION['cust_id'];

$total_price = 0.00;
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container text-center">
    <div style="font-size: 4rem; color: var(--success); margin-bottom: 16px;">✓</div>
    <h2>Receipt Submitted!</h2>
    <p style="font-size: 1.25rem; font-weight: 600; margin-bottom: 8px; color: var(--text);">Total Paid: RM <?php echo number_format($total_price, 2); ?></p>
    <p class="mb-4">Your payment receipt has been received and will be verified by an admin shortly.<br>Once verified, your order will be processed for delivery.</p>
    <a href="payment history.php" style="padding: 12px 24px; background: var(--accent); color: white; border-radius: 8px; display: inline-block;">View Order History</a>
</div>

</body>
</html>
