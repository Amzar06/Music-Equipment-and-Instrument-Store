<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$page_title = "Customer Profile";
$active = "customers"; 

// Get the customer id from the url
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "No customer selected.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_customer.php");
    exit();
}

$cust_id = intval($_GET['id']);

// Fetch customer details
$customer_query = "SELECT * FROM customers WHERE cust_id = $cust_id";
$customer_result = mysqli_query($conn, $customer_query);

if (mysqli_num_rows($customer_result) == 0) {
    $_SESSION['flash_message'] = "Customer not found.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_customer.php");
    exit();
}
$customer = mysqli_fetch_assoc($customer_result);

// Handle admin actions for Suspend/Blacklist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $update_query = "UPDATE customers SET status = '$new_status' WHERE cust_id = $cust_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['flash_message'] = "Customer account status updated to $new_status.";
        $_SESSION['flash_type'] = "success";
        // Refresh page to show the new status
        header("Location: admin_view_customer.php?id=$cust_id");
        exit();
    }
}

require_once('admin_header.php');
?>

<div style="max-width: 1000px; margin: 0 auto; margin-top: 20px;">
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($_SESSION['flash_type'] == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <a href="manage_customer.php" style="color: #4b5563; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
            ← Back to Directory
        </a>
    </div>

    <!-- Customer Info Card -->
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px; position: relative;">
        
        <div style="position: absolute; top: 30px; right: 30px; text-align: right;">
            <?php 
                $status = $customer['status'] ?? 'Active';
                $bg = ($status == 'Active') ? '#d1fae5' : '#fee2e2';
                $color = ($status == 'Active') ? '#065f46' : '#991b1b';
            ?>
            <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">
                <?php echo htmlspecialchars($status); ?>
            </span>
            <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 8px;">
                Joined: <?php echo date('F d, Y', strtotime($customer['created_at'])); ?>
            </div>
        </div>

        <h2 style="margin: 0 0 16px 0; color: #111827; font-size: 1.8rem;"><?php echo htmlspecialchars($customer['cust_name']); ?></h2>
        
        <div style="display: flex; gap: 24px; color: #4b5563; font-size: 0.95rem;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-envelope" style="color: #4f46e5;"></i> 
                <?php echo htmlspecialchars($customer['cust_email']); ?>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-phone" style="color: #e11d48;"></i> 
                <?php echo htmlspecialchars($customer['cust_phone_number'] ?? 'No phone provided'); ?>
            </div>
        </div>
    </div>

    <!-- Grid for Addresses & Actions -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        
        <!-- Saved Addresses Box -->
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827;">Saved Addresses</h3>
            
            <?php 
                $addr_query = "SELECT * FROM addresses WHERE cust_id = $cust_id AND street IS NOT NULL AND TRIM(street) != '' ORDER BY created_at DESC";
                $addr_result = mysqli_query($conn, $addr_query);

                if (mysqli_num_rows($addr_result) > 0): 
                    while($addr = mysqli_fetch_assoc($addr_result)):
            ?>
                <div style="padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px; background: #f9fafb;">
                    <strong style="color: #111827; display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($addr['full_name']); ?></strong>
                    <p style="margin: 0; font-size: 0.85rem; color: #6b7280; line-height: 1.5;">
                        <?php 
                            $parts = array_filter([$addr['street'], $addr['city'], $addr['postcode'], $addr['state']]);
                            echo htmlspecialchars(implode(', ', $parts));
                        ?>
                    </p>
                </div>
            <?php 
                    endwhile; 
                else: 
            ?>
                <div style="padding: 20px; text-align: center; color: #9ca3af; border: 1px dashed #d1d5db; border-radius: 8px;">
                    No valid saved addresses on file.
                </div>
            <?php endif; ?>
        </div>

        <!-- Administrative Actions Box -->
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827;">Administrative Actions</h3>
            
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                <?php if ($status == 'Active'): ?>
                    <input type="hidden" name="new_status" value="Suspended">
                    <button type="submit" name="update_status" style="width: 100%; padding: 14px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#d97706'" onmouseout="this.style.backgroundColor='#f59e0b'">
                         Suspend Account
                    </button>
                <?php else: ?>
                    <input type="hidden" name="new_status" value="Active">
                    <button type="submit" name="update_status" style="width: 100%; padding: 14px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10b981'">
                         Reactivate Account
                    </button>
                <?php endif; ?>
            </form>

            <form action="" method="POST" style="margin-top: 12px;" onsubmit="return confirm('Are you sure you want to blacklist this user? They will not be able to log in.');">
                <input type="hidden" name="new_status" value="Blacklisted">
                <button type="submit" name="update_status" style="width: 100%; padding: 14px; background: #111827; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#000000'" onmouseout="this.style.backgroundColor='#111827'">
                     Blacklist Customer
                </button>
            </form>
        </div>

    </div> 

    <!-- Orders & Rentals History  -->
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px;">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827;">Orders & Rentals History</h3>
        
        <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
            <thead>
                <tr style="background-color: #f9fafb;">
                    <th style="text-align: left; padding: 14px 12px; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600; width: 10%;">ID</th>
                    <th style="text-align: left; padding: 14px 12px; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600; width: 18%;">Date</th>
                    <th style="text-align: left; padding: 14px 12px; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600; width: 15%;">Type</th>
                    <th style="text-align: left; padding: 14px 12px; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600; width: 32%;">Items Ordered / Rented</th>
                    <th style="text-align: left; padding: 14px 12px; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600; width: 13%;">Total</th>
                    <th style="text-align: left; padding: 14px 12px; border-bottom: 2px solid #e5e7eb; color: #4b5563; font-weight: 600; width: 12%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // fetch all items for each transaction 
                $history_query = "
                    (SELECT 
                        o.order_id AS trans_id, 
                        o.order_date AS trans_date, 
                        'Direct Sale' AS trans_type, 
                        o.total_amount, 
                        o.status,
                        GROUP_CONCAT(CONCAT(oi.order_qty, 'x ', p.prod_name) SEPARATOR ', ') AS item_list
                     FROM orders o
                     LEFT JOIN order_items oi ON o.order_id = oi.order_id
                     LEFT JOIN products p ON oi.prod_id = p.prod_id
                     WHERE o.cust_id = $cust_id
                     GROUP BY o.order_id)
                    
                    UNION ALL
                    
                    (SELECT 
                        r.rental_id AS trans_id, 
                        r.created_at AS trans_date, 
                        'Instrument Rental' AS trans_type, 
                        r.total_amount, 
                        r.status,
                        GROUP_CONCAT(CONCAT(ri.rental_qty, 'x ', p.prod_name) SEPARATOR ', ') AS item_list
                     FROM rentals r
                     LEFT JOIN rental_items ri ON r.rental_id = ri.rental_id
                     LEFT JOIN products p ON ri.prod_id = p.prod_id
                     WHERE r.cust_id = $cust_id
                     GROUP BY r.rental_id)
                    
                    ORDER BY trans_date DESC";

                $history_result = mysqli_query($conn, $history_query);

                if (mysqli_num_rows($history_result) > 0):
                    while ($row = mysqli_fetch_assoc($history_result)):
                        $status_label = htmlspecialchars($row['status']);
                        
                        if ($status_label == 'Delivered' || $status_label == 'Returned') {
                            $bg_color = '#d1fae5'; $txt_color = '#065f46';
                        } elseif ($status_label == 'Processing' || $status_label == 'Active') {
                            $bg_color = '#fef3c7'; $txt_color = '#92400e';
                        } else {
                            $bg_color = '#fee2e2'; $txt_color = '#991b1b';
                        }
                ?>
                    <tr style="border-bottom: 1px solid #e5e7eb; vertical-align: top;">
                        <td style="padding: 14px 12px; color: #111827; font-weight: 600;">#<?php echo htmlspecialchars($row['trans_id']); ?></td>
                        <td style="padding: 14px 12px; color: #4b5563;"><?php echo date('M d, Y H:i', strtotime($row['trans_date'])); ?></td>
                        <td style="padding: 14px 12px; color: #4b5563;"><?php echo htmlspecialchars($row['trans_type']); ?></td>
                        
                        <!-- Items list Column -->
                        <td style="padding: 14px 12px; color: #111827; line-height: 1.4;">
                            <?php 
                                if (!empty($row['item_list'])) {
                                    echo htmlspecialchars($row['item_list']);
                                } else {
                                    echo '<span style="color: #9ca3af; font-style: italic;">No items details recorded</span>';
                                }
                            ?>
                        </td>
                        
                        <td style="padding: 14px 12px; color: #111827; font-weight: 700;">RM <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td style="padding: 14px 12px;">
                            <span style="background: <?php echo $bg_color; ?>; color: <?php echo $txt_color; ?>; padding: 4px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; text-transform: uppercase; display: inline-block;">
                                <?php echo $status_label; ?>
                            </span>
                        </td>
                    </tr>
                <?php 
                    endwhile;
                else: 
                ?>
                    <tr>
                        <td colspan="6" style="padding: 30px; text-align: center; color: #9ca3af; border: 1px dashed #d1d5db; border-radius: 8px;">
                            No past transaction records.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div> 

<?php require_once('admin_footer.php'); ?>