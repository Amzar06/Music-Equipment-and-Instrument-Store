<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Handle Cancellation
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id']) && isset($_GET['type'])) {
    $cancel_id = intval($_GET['id']);
    $cancel_type = $_GET['type'];
    
    if ($cancel_type === 'Order') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ? AND cust_id = ? AND status = 'Pending'");
    } else {
        $stmt = $conn->prepare("UPDATE rentals SET status = 'Cancelled' WHERE rental_id = ? AND cust_id = ? AND status = 'Pending'");
    }
    
    if ($stmt) {
        $stmt->bind_param("ii", $cancel_id, $cust_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: payment history.php");
    exit();
}

$orders = [];
$db_error = null;
if (isset($conn)) {
    try {
        // Fetch both rentals and purchases grouped by date with product info
        $query = $conn->prepare("
            SELECT 'Order' as type, o.order_id as id, o.total_amount, o.status, o.order_date as date, a.city, a.state,
                   (SELECT p.prod_name FROM order_items oi JOIN products p ON oi.prod_id = p.prod_id WHERE oi.order_id = o.order_id LIMIT 1) as prod_name,
                   (SELECT p.prod_image FROM order_items oi JOIN products p ON oi.prod_id = p.prod_id WHERE oi.order_id = o.order_id LIMIT 1) as prod_image,
                   (SELECT SUM(oi.order_qty) FROM order_items oi WHERE oi.order_id = o.order_id) as total_qty,
                   NULL as start_date, NULL as end_date
            FROM orders o
            LEFT JOIN addresses a ON o.address_id = a.address_id
            WHERE o.cust_id = ?
            
            UNION ALL
            
            SELECT 'Rental' as type, r.rental_id as id, r.total_amount, r.status, r.created_at as date, a.city, a.state,
                   (SELECT p.prod_name FROM rental_items ri JOIN products p ON ri.prod_id = p.prod_id WHERE ri.rental_id = r.rental_id LIMIT 1) as prod_name,
                   (SELECT p.prod_image FROM rental_items ri JOIN products p ON ri.prod_id = p.prod_id WHERE ri.rental_id = r.rental_id LIMIT 1) as prod_image,
                   (SELECT SUM(ri.rental_qty) FROM rental_items ri WHERE ri.rental_id = r.rental_id) as total_qty,
                   r.start_date, r.end_date
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
                <th>Qty</th>
                <th>Total Price</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-secondary);">Your database currently has no past transactions explicitly linked to you.</td>
                </tr>
            <?php else: ?>
                <?php foreach($orders as $order): ?>
                <tr>
                    <td style="display: flex; align-items: center; gap: 12px;">
                        <img src="../uploads/<?php echo htmlspecialchars($order['prod_image'] ?: 'default.jpg'); ?>" 
                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; border: 1px solid var(--card-border);">
                        <div>
                            <span style="font-size: 0.75rem; padding: 2px 6px; background: <?php echo $order['type'] === 'Rental' ? '#7c3aed' : 'var(--accent)'; ?>; color: white; border-radius: 4px;"><?php echo htmlspecialchars($order['type']); ?></span>
                            <div style="font-weight: 600; font-size: 0.9rem; margin-top: 4px;"><?php echo htmlspecialchars($order['prod_name'] ?: 'Multiple Items'); ?></div>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">ID: #<?php echo htmlspecialchars($order['id']); ?></div>
                            <?php if ($order['type'] === 'Rental' && $order['start_date']): ?>
                                <div style="font-size: 0.75rem; color: #7c3aed; margin-top: 4px;">
                                    <strong>Rental Period:</strong><br>
                                    <?php echo date('d M Y', strtotime($order['start_date'])); ?> to <?php echo date('d M Y', strtotime($order['end_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($order['city']): ?>
                            <?php echo htmlspecialchars($order['city']); ?><br><?php echo htmlspecialchars($order['state']); ?>
                        <?php else: ?>
                            <span style="color: var(--text-secondary);">No Address</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 600; color: var(--text-primary);">
                        <?php echo htmlspecialchars($order['total_qty'] ?? 1); ?>
                    </td>
                    <td style="font-weight: 600; color: var(--success);">RM <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                    <td>
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 4px;">
                            <span style="font-weight: 600; color: <?php 
                                $status = strtolower($order['status'] ?? '');
                                if (in_array($status, ['completed', 'delivered', 'returned', 'shipped'])) echo 'var(--success)';
                                elseif (in_array($status, ['pending', 'processing', 'active'])) echo '#6366f1';
                                elseif (in_array($status, ['declined', 'cancelled'])) echo '#ef4444';
                                else echo 'var(--text-secondary)';
                            ?>;"><?php echo htmlspecialchars(ucfirst($order['status'] ?? 'Pending')); ?></span>

                            <?php if (strtolower($order['status']) === 'pending'): ?>
                                <a href="?action=cancel&id=<?php echo $order['id']; ?>&type=<?php echo $order['type']; ?>" 
                                   onclick="return confirm('Are you sure you want to cancel this order?')"
                                   style="font-size: 0.75rem; color: #ef4444; text-decoration: none; border: 1px solid #ef4444; padding: 2px 6px; border-radius: 4px; margin-top: 4px;">
                                   Cancel Product
                                </a>
                            <?php endif; ?>
                        </div>
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
