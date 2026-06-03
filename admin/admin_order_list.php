<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Sales Orders";
$active = "orders";

$message = "";
$message_type = "";

// ==========================================
// 1. HANDLE STATUS UPDATE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    // Update the status in the orders table
    $update_query = "UPDATE orders SET status = '$new_status' WHERE order_id = $order_id";
    if (mysqli_query($conn, $update_query)) {
        $message = "Order #$order_id status updated to $new_status.";
        $message_type = "success";
    } else {
        $message = "Error updating status: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// ==========================================
// 2. FETCH ALL ORDERS
// ==========================================
// Joining with the customers table to get the customer name
$orders_query = "SELECT o.*, c.cust_name 
                 FROM orders o 
                 LEFT JOIN customers c ON o.cust_id = c.cust_id 
                 ORDER BY o.order_id DESC";
$orders_result = mysqli_query($conn, $orders_query);

require_once('admin_header.php');
?>

<?php if (!empty($message)): ?>
    <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div class="table-container">
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid var(--border-color); text-align: left;">
                    <th style="padding: 16px; color: #6b7280; font-weight: 600;">Order ID</th>
                    <th style="padding: 16px; color: #6b7280; font-weight: 600;">Customer</th>
                    <th style="padding: 16px; color: #6b7280; font-weight: 600;">Date</th>
                    <th style="padding: 16px; color: #6b7280; font-weight: 600;">Total (RM)</th>
                    <th style="padding: 16px; color: #6b7280; font-weight: 600;">Current Status</th>
                    <th style="padding: 16px; color: #6b7280; font-weight: 600; text-align: left;">Order Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($orders_result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($orders_result)): ?>
                    
                    <?php 
                        // Format the date nicely
                        $formatted_date = date("d M Y", strtotime($row['order_date']));
                        
                        // Set pill colors based on status
                        $pill_bg = "#fef3c7"; $pill_text = "#92400e"; // Default Yellow (Pending)
                        if ($row['status'] == 'Processing') { $pill_bg = "#e0e7ff"; $pill_text = "#3730a3"; } // Blue
                        if ($row['status'] == 'Shipped') { $pill_bg = "#dbeafe"; $pill_text = "#1e40af"; } // Lighter Blue
                        if ($row['status'] == 'Delivered') { $pill_bg = "#d1fae5"; $pill_text = "#065f46"; } // Green
                        if ($row['status'] == 'Cancelled') { $pill_bg = "#fee2e2"; $pill_text = "#991b1b"; } // Red
                    ?>
                    
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 16px; font-weight: 600; color: var(--text-main);">#<?php echo $row['order_id']; ?></td>
                        <td style="padding: 16px; color: var(--text-main);"><?php echo htmlspecialchars($row['cust_name'] ?? 'Unknown'); ?></td>
                        <td style="padding: 16px; color: #4b5563;"><?php echo $formatted_date; ?></td>
                        <td style="padding: 16px; font-weight: 600; color: var(--text-main);"><?php echo number_format($row['total_amount'], 2); ?></td>
                        <td style="padding: 16px;">
                            <span style="background: <?php echo $pill_bg; ?>; color: <?php echo $pill_text; ?>; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                <?php echo htmlspecialchars($row['status']); ?>
                            </span>
                        </td>
                        <td style="padding: 16px; text-align: right;">
                            
                            <form action="" method="POST" style="display: flex; gap: 8px; align-items: center; justify-content: left; margin: 0;">
                                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                
                                <select name="new_status" style="padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border-color); outline: none; background: white; font-family: inherit; font-size: 0.85rem; cursor: pointer;">
                                    <option value="Pending" <?php if($row['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Shipped" <?php if($row['status'] == 'Shipped') echo 'selected'; ?>>Shipped</option>
                                    <option value="Delivered" <?php if($row['status'] == 'Delivered') echo 'selected'; ?>>Delivered</option>
                                    <option value="Cancelled" <?php if($row['status'] == 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>
                                
                                <button type="submit" name="update_status" style="padding: 6px 12px; background: #111827; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600; transition: 0.2s;" onmouseover="this.style.background='#000000'" onmouseout="this.style.background='#111827'">
                                     Update
                                </button>
                            </form>

                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>