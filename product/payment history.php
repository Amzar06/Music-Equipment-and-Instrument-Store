<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    $_SESSION['cust_id'] = 1; 
}
$cust_id = $_SESSION['cust_id'];

$orders = [];
$db_error = null;
if (isset($conn)) {
    try {
        // Fetch only columns that exist in the old 'orders' table schema 
        // to prevent 'Unknown column' errors if the DB is un-updated.
        $query = $conn->prepare("
            SELECT order_id, total_amount, status, order_date
            FROM orders 
            WHERE cust_id = ?
            ORDER BY order_date DESC
        ");
        if ($query) {
            $query->bind_param("i", $cust_id);
            if ($query->execute()) {
                $result = $query->get_result();
                while($row = $result->fetch_assoc()) {
                    // Set default nulls for missing columns so HTML gracefully falls back
                    $row['city'] = null;
                    $row['state'] = null;
                    $row['tracking_number'] = null; 
                    
                    $orders[] = $row;
                }
            } else {
                $db_error = "Execution failed: " . $query->error;
            }
            $query->close();
        } else {
            $db_error = "Preparation failed: " . $conn->error;
        }
    } catch (mysqli_sql_exception $e) {
        $db_error = "Database Error: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection not established.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order History</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container" style="max-width: 800px;">
    <h2>Order History</h2>
    
    <?php if ($db_error): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
            <strong>Error:</strong> <?php echo htmlspecialchars($db_error); ?>
        </div>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Delivery Address</th>
                <th>Total Price</th>
                <th>Tracking Number</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 24px; color: var(--text-secondary);">Your database currently has no past orders explicitly linked to you.</td>
                </tr>
            <?php else: ?>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                    <td>
                        <?php if ($order['city']): ?>
                            <?php echo htmlspecialchars($order['city'] ?? ''); ?><br><?php echo htmlspecialchars($order['state'] ?? ''); ?>
                        <?php else: ?>
                            <span style="color: var(--text-secondary);">No Address</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 600; color: var(--success);">RM <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                    <td>
                        <?php if ($order['tracking_number']): ?>
                            <a href="track order.php?tracking=<?php echo urlencode($order['tracking_number'] ?? ''); ?>" style="margin-top: 0; font-weight: 600; text-decoration: underline; color: var(--accent);"><?php echo htmlspecialchars($order['tracking_number'] ?? ''); ?></a>
                        <?php else: ?>
                            <span style="color: var(--text-secondary);"><?php echo htmlspecialchars($order['status'] ?? ''); ?>...</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="display: flex; gap: 16px; justify-content: center; margin-top: 32px;">
        <a href="product page.php" style="padding: 12px 24px; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0;">Continue Shopping</a>
        <a href="track order.php" style="padding: 12px 24px; background: var(--accent); color: white; border-radius: 8px; margin: 0;">Track Order</a>
    </div>
</div>

</body>
</html>
