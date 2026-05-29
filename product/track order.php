<?php
session_start();
include '../database.php';

$tracking_number = isset($_GET['tracking']) ? trim($_GET['tracking']) : '';
$tracking_data = null;
$error = null;

if ($tracking_number) {
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT * FROM order_tracking WHERE tracking_number = ?");
        if ($stmt) {
            $stmt->bind_param("s", $tracking_number);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $tracking_data = $result->fetch_assoc();
            } else {
                $error = "Tracking number not found in the database.";
            }
            $stmt->close();
        } else {
            $error = "Database query failed.";
        }
    } else {
         $error = "Database connection not established.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Track Order</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container text-center" style="max-width: 600px;">
    <h2>Track Your Order</h2>
    
    <form action="track order.php" method="GET" style="margin-bottom: 32px; flex-direction: row; gap: 12px; margin-top: 0;">
        <input type="text" name="tracking" placeholder="Enter Tracking Number" value="<?php echo htmlspecialchars($tracking_number ? $tracking_number : 'TRK-992384A'); ?>" required style="flex: 1;">
        <button type="submit" style="width: auto; padding: 12px 24px; flex-shrink: 0; margin-top: 0;">Track</button>
    </form>
    
    <?php if ($error): ?>
        <div style="text-align: left; background: #fef2f2; border: 1px solid #fca5a5; padding: 16px; border-radius: 12px; margin-bottom: 24px; color: #b91c1c;">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($tracking_data): ?>
    <div style="text-align: left; background: #f8fafc; border: 1px solid var(--card-border); padding: 24px; border-radius: 12px; margin-bottom: 24px;">
        <h3 style="margin-bottom: 8px;">Order #<?php echo htmlspecialchars($tracking_data['order_id']); ?></h3>
        <p style="margin-bottom: 24px;">Courier: <strong><?php echo htmlspecialchars($tracking_data['courier']); ?></strong> | Status: <strong style="color: var(--accent);"><?php echo htmlspecialchars($tracking_data['status']); ?></strong></p>
        
        <div style="border-left: 2px solid var(--accent); padding-left: 24px; margin-left: 12px; position: relative;">
            
            <div style="margin-bottom: 24px; position: relative;">
                <div style="width: 14px; height: 14px; background: var(--accent); border-radius: 50%; position: absolute; left: -32px; top: 4px; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3);"></div>
                <p style="margin: 0; font-weight: 600; color: var(--text-primary);"><?php echo htmlspecialchars($tracking_data['status']); ?></p>
                <p style="font-size: 0.85rem; margin: 0;">Last updated: <?php echo htmlspecialchars($tracking_data['last_updated']); ?></p>
            </div>
            
            <?php if (in_array($tracking_data['status'], ['Shipped', 'In Transit', 'Delivered'])): ?>
            <div style="margin-bottom: 24px; position: relative;">
                <div style="width: 14px; height: 14px; background: var(--success); border-radius: 50%; position: absolute; left: -32px; top: 4px;"></div>
                <p style="margin: 0; font-weight: 600; color: var(--text-primary);">Processing completed</p>
            </div>
            <?php endif; ?>
            
            <div style="position: relative;">
                <div style="width: 14px; height: 14px; background: var(--success); border-radius: 50%; position: absolute; left: -32px; top: 4px;"></div>
                <p style="margin: 0; font-weight: 600; color: var(--text-primary);">Order Placed</p>
            </div>

        </div>
    </div>
    <?php elseif (!$tracking_number): ?>
    <div style="text-align: center; color: var(--text-secondary); padding: 48px;">
        <h3>Track Your Instrument</h3>
        <p>Enter your tracking number above to see the live status from the database.</p>
    </div>
    <?php endif; ?>

    <div style="display: flex; gap: 16px; justify-content: center;">
        <a href="payment history.php" style="padding: 12px 24px; background: #f8fafc; border: 1px solid var(--card-border); border-radius: 8px; display: inline-block;">Back to Order History</a>
        <a href="product page.php" style="padding: 12px 24px; background: var(--accent); color: white; border-radius: 8px; display: inline-block; margin-top: 0;">Continue Shopping</a>
    </div>
</div>

</body>
</html>
