<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

$orders = [];
$db_error = null;
if (isset($conn)) {
    try {
        // Fetch both rentals and purchases grouped by date
        $query = $conn->prepare("
            SELECT 'Order' as type, o.order_id as id, o.total_amount, o.status, o.order_date as date, a.city, a.state 
            FROM orders o
            LEFT JOIN addresses a ON o.address_id = a.address_id
            WHERE o.cust_id = ?
            UNION ALL
            SELECT 'Rental' as type, r.rental_id as id, r.total_amount, r.status, r.created_at as date, a.city, a.state
            FROM rentals r
            LEFT JOIN addresses a ON r.address_id = a.address_id
            WHERE r.cust_id = ?
            ORDER BY date DESC
        ");
        if ($query) {
            $query->bind_param("ii", $cust_id, $cust_id);
            if ($query->execute()) {
                $result = $query->get_result();
                while($row = $result->fetch_assoc()) {
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
                <th>Order Type & ID</th>
                <th>Delivery Address</th>
                <th>Total Price</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 24px; color: var(--text-secondary);">Your database currently has no past transactions explicitly linked to you.</td>
                </tr>
            <?php else: ?>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td>
                        <span style="font-size: 0.8rem; padding: 2px 6px; background: <?php echo $order['type'] === 'Rental' ? 'var(--secondary)' : 'var(--accent)'; ?>; color: white; border-radius: 4px; margin-right: 8px;"><?php echo htmlspecialchars($order['type']); ?></span>
                        #<?php echo htmlspecialchars($order['id']); ?>
                    </td>
                    <td>
                        <?php if ($order['city']): ?>
                            <?php echo htmlspecialchars($order['city']); ?><br><?php echo htmlspecialchars($order['state']); ?>
                        <?php else: ?>
                            <span style="color: var(--text-secondary);">No Address</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 600; color: var(--success);">RM <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                    <td>
                        <span style="font-weight: 600; color: <?php 
                            $status = strtolower($order['status'] ?? '');
                            if (in_array($status, ['completed', 'delivered', 'returned', 'shipped'])) echo 'var(--success)';
                            elseif (in_array($status, ['pending', 'processing', 'active'])) echo 'var(--secondary)';
                            elseif (in_array($status, ['declined', 'cancelled'])) echo '#ef4444';
                            else echo 'var(--text-secondary)';
                        ?>;"><?php echo htmlspecialchars(ucfirst($order['status'] ?? 'Pending')); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div style="display: flex; gap: 16px; justify-content: center; margin-top: 32px;">
        <a href="product page.php" style="padding: 12px 24px; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0;">Continue Shopping</a>
    </div>
</div>

</body>
</html>
