<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }
require_once('../database.php');

$page_title = "Sales Orders";
$active = "orders";

// ==========================================
// HANDLE STATUS UPDATES
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_order_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    if ($new_status === 'Delivered') {
        $update_query = "UPDATE orders SET status = '$new_status', delivered_at = CURRENT_TIMESTAMP WHERE order_id = $order_id";
    } else {
        $update_query = "UPDATE orders SET status = '$new_status' WHERE order_id = $order_id";
    }
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['flash_message'] = "Order #$order_id status updated to $new_status.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error updating order: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
    }
    header("Location: admin_order_list.php");
    exit();
}

// ==========================================
// FETCH ORDERS + CUSTOMER DATA + ADDRESS DATA
// ==========================================
// Note: o.* will automatically fetch the new collection_method column!
$query = "SELECT o.*, 
                 c.cust_name, c.cust_email, c.cust_phone_number,
                 a.full_name AS ship_name, a.phone_number AS ship_phone, 
                 a.street, a.city, a.state, a.postcode, a.country
          FROM orders o 
          JOIN customers c ON o.cust_id = c.cust_id 
          LEFT JOIN addresses a ON o.address_id = a.address_id
          ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $query);

require_once('admin_header.php');
?>

<div style="max-width: 1200px; margin: 0 auto; margin-top: 20px;">
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($_SESSION['flash_type'] == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">Order Management</h3>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Order ID</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Customer</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Date</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Total (RM)</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): 
                            $order_id = $row['order_id'];
                            
                            $status = $row['status'];
                            if ($status == 'Pending' || $status == 'Processing') { $bg = '#fef3c7'; $txt = '#92400e'; }
                            elseif ($status == 'Shipped' || $status == 'Completed' || $status == 'Delivered') { $bg = '#d1fae5'; $txt = '#065f46'; }
                            elseif ($status == 'Cancelled' || $status == 'Refunded') { $bg = '#fee2e2'; $txt = '#991b1b'; }
                            else { $bg = '#e5e7eb'; $txt = '#374151'; }
                        ?>
                        
                        <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" id="row-<?php echo $order_id; ?>">
                            <td style="padding: 16px; font-weight: 700; color: #4f46e5;">#<?php echo $order_id; ?></td>
                            <td style="padding: 16px; font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['cust_name']); ?></td>
                            <td style="padding: 16px; color: #4b5563; font-size: 0.9rem;"><?php echo date('d M Y, h:i A', strtotime($row['order_date'])); ?></td>
                            <td style="padding: 16px; font-weight: 700; color: #111827;">RM <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td style="padding: 16px;">
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $txt; ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: right;">
                                <button onclick="toggleDetails(<?php echo $order_id; ?>)" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                                    View Details ⮟
                                </button>
                            </td>
                        </tr>

                        <tr id="details-<?php echo $order_id; ?>" style="display: none; background: #f8fafc; border-bottom: 2px solid #e5e7eb;">
                            <td colspan="6" style="padding: 0;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; padding: 24px; border-left: 4px solid #4f46e5;">
                                    
                                    <div>
                                        <h4 style="margin: 0 0 12px 0; color: #111827; font-size: 0.95rem; border-bottom: 1px solid #d1d5db; padding-bottom: 8px;">Customer & Fulfillment</h4>
                                        <div style="font-size: 0.85rem; color: #4b5563; line-height: 1.6;">
                                            
                                            <?php $method = $row['collection_method'] ?? 'Self-Pickup'; ?>
                                            <div style="margin-bottom: 12px; background: <?php echo ($method == 'Self-Pickup') ? '#f0fdf4' : '#e0e7ff'; ?>; padding: 8px 12px; border-radius: 6px; border: 1px solid <?php echo ($method == 'Self-Pickup') ? '#bbf7d0' : '#c7d2fe'; ?>; display: inline-block;">
                                                <strong style="color: <?php echo ($method == 'Self-Pickup') ? '#166534' : '#3730a3'; ?>;">Method:</strong> 
                                                <span style="color: <?php echo ($method == 'Self-Pickup') ? '#15803d' : '#312e81'; ?>; font-weight: 600;"><?php echo htmlspecialchars($method); ?></span>
                                            </div>
                                            <br>

                                            <strong>Email:</strong> <?php echo htmlspecialchars($row['cust_email']); ?><br>
                                            <strong>Account Phone:</strong> <?php echo !empty($row['cust_phone_number']) ? htmlspecialchars($row['cust_phone_number']) : 'N/A'; ?><br>
                                            
                                            <div style="margin-top: 14px; background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                                <?php if ($method == 'Self-Pickup'): ?>
                                                    <span style="color: #166534; font-weight: 700; display: block;">🏪 Self Collection at Store</span>
                                                    <span style="font-size: 0.78rem; color: #15803d; display: block; margin-top: 2px;">Customer will pick up at store location. No shipping required.</span>
                                                <?php elseif (!empty($row['street'])): ?>
                                                    <strong style="color: #111827; display: block; margin-bottom: 4px;">Delivery Address:</strong>
                                                    <span style="display: block; font-weight: 600; color: #374151;">
                                                        <?php echo htmlspecialchars($row['ship_name']); ?> 
                                                        <?php if(!empty($row['ship_phone'])) echo '<span style="font-weight: normal; color: #6b7280;">(' . htmlspecialchars($row['ship_phone']) . ')</span>'; ?>
                                                    </span>
                                                    <span style="color: #6b7280; display: block; margin-top: 4px;">
                                                        <?php 
                                                            $parts = array_filter([$row['street'], $row['city'], $row['postcode'], $row['state'], $row['country']]);
                                                            echo htmlspecialchars(implode(', ', $parts));
                                                        ?>
                                                    </span>
                                                <?php else: ?>
                                                    <em style="color:#ef4444;">Delivery requested, but address data is missing.</em>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 style="margin: 0 0 12px 0; color: #111827; font-size: 0.95rem; border-bottom: 1px solid #d1d5db; padding-bottom: 8px;">Purchased Instruments</h4>
                                        <div style="font-size: 0.85rem; color: #4b5563;">
                                            <?php
                                                $items_query = @mysqli_query($conn, "SELECT oi.*, p.prod_name FROM order_items oi JOIN products p ON oi.prod_id = p.prod_id WHERE oi.order_id = $order_id");
                                                if($items_query && mysqli_num_rows($items_query) > 0) {
                                                    echo '<ul style="margin: 0; padding-left: 16px; line-height: 1.6;">';
                                                    while($item = mysqli_fetch_assoc($items_query)) {
                                                        // Fallbacks to handle different possible column names for unit price
                                                        $price = $item['unit_price'] ?? $item['price'] ?? 0;
                                                        echo '<li><strong>' . $item['order_qty'] . 'x</strong> ' . htmlspecialchars($item['prod_name']) . ' <span style="color:#9ca3af;">(RM ' . number_format($price, 2) . ' each)</span></li>';
                                                    }
                                                    echo '</ul>';
                                                } else {
                                                    echo '<em style="color:#9ca3af;">Item details not found.</em>';
                                                }
                                            ?>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 style="margin: 0 0 12px 0; color: #111827; font-size: 0.95rem; border-bottom: 1px solid #d1d5db; padding-bottom: 8px;">Update Order Status</h4>
                                        <form action="admin_order_list.php" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                            <select name="new_status" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; outline: none;">
                                                <option value="Pending" <?php echo ($status == 'Pending') ? 'selected' : ''; ?>>Pending (Awaiting Processing)</option>
                                                <option value="Processing" <?php echo ($status == 'Processing') ? 'selected' : ''; ?>>Processing (Preparing Item)</option>
                                                <option value="Shipped" <?php echo ($status == 'Shipped') ? 'selected' : ''; ?>>Shipped (Out for Delivery)</option>
                                                <option value="Delivered" <?php echo ($status == 'Delivered') ? 'selected' : ''; ?>>Delivered (Completed)</option>
                                                <option value="Cancelled" <?php echo ($status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_order_status" style="background: #4f46e5; color: white; border: none; padding: 8px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">Save Status</button>
                                        </form>
                                    </div>

                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">No orders found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleDetails(id) {
    var detailsRow = document.getElementById('details-' + id);
    var mainRow = document.getElementById('row-' + id);
    if (detailsRow.style.display === 'none') {
        detailsRow.style.display = 'table-row';
        mainRow.style.background = '#f8fafc';
    } else {
        detailsRow.style.display = 'none';
        mainRow.style.background = 'white';
    }
}
</script>

<?php require_once('admin_footer.php'); ?>