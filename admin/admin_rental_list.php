<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }
require_once('../database.php');

$page_title = "Rental Management";
$active = "rentals";

// ==========================================
// HANDLE STATUS UPDATES
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_rental_status'])) {
    $rental_id = intval($_POST['rental_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    
    $update_query = "UPDATE rentals SET status = '$new_status' WHERE rental_id = $rental_id";
    
    // If status is Active, we should also make sure items are marked as 'Out'
    if ($new_status === 'Active') {
        mysqli_query($conn, "UPDATE rental_items SET return_status = 'Out' WHERE rental_id = $rental_id AND return_status = 'Pending'");
    }

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['flash_message'] = "Rental #$rental_id status updated successfully.";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error updating rental: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
    }
    header("Location: admin_rental_list.php");
    exit();
}

// ==========================================
// FETCH RENTALS + CUSTOMER DATA + ADDRESS DATA
// ==========================================
$query = "SELECT r.*, 
                 c.cust_name, c.cust_email, c.cust_phone_number,
                 a.full_name AS ship_name, a.phone_number AS ship_phone, 
                 a.street, a.city, a.state, a.postcode, a.country
          FROM rentals r 
          JOIN customers c ON r.cust_id = c.cust_id 
          LEFT JOIN addresses a ON r.address_id = a.address_id
          ORDER BY r.created_at DESC";
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
        <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">All Customer Rentals</h3>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Rental ID</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Customer</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Duration</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Total (RM)</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($result)): 
                            $rental_id = $row['rental_id'];
                            
                            $status = $row['status'];
                            if ($status == 'Pending' || $status == 'Processing') { $bg = '#fef3c7'; $txt = '#92400e'; }
                            elseif ($status == 'Active') { $bg = '#dbeafe'; $txt = '#1e40af'; }
                            elseif ($status == 'Returned' || $status == 'Completed') { $bg = '#d1fae5'; $txt = '#065f46'; }
                            elseif ($status == 'Cancelled' || $status == 'Overdue') { $bg = '#fee2e2'; $txt = '#991b1b'; }
                            else { $bg = '#e5e7eb'; $txt = '#374151'; $status = 'Unknown'; } // Catch-all for blank statuses
                        ?>
                        
                        <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" id="row-<?php echo $rental_id; ?>">
                            <td style="padding: 16px; font-weight: 700; color: #4f46e5;">#<?php echo $rental_id; ?></td>
                            <td style="padding: 16px; font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['cust_name']); ?></td>
                            <td style="padding: 16px; color: #4b5563; font-size: 0.85rem;">
                                <strong><?php echo date('d M Y', strtotime($row['start_date'])); ?></strong><br>
                                to <strong><?php echo date('d M Y', strtotime($row['end_date'])); ?></strong>
                            </td>
                            <td style="padding: 16px; font-weight: 700; color: #111827;">RM <?php echo number_format($row['total_amount'], 2); ?></td>
                            <td style="padding: 16px;">
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $txt; ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td style="padding: 16px; text-align: right;">
                                <button onclick="toggleDetails(<?php echo $rental_id; ?>)" style="background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; padding: 6px 12px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">
                                    View Details ⮟
                                </button>
                            </td>
                        </tr>

                        <tr id="details-<?php echo $rental_id; ?>" style="display: none; background: #f8fafc; border-bottom: 2px solid #e5e7eb;">
                            <td colspan="6" style="padding: 0;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; padding: 24px; border-left: 4px solid #4f46e5;">
                                    
                                    <div>
                                        <h4 style="margin: 0 0 12px 0; color: #111827; font-size: 0.95rem; border-bottom: 1px solid #d1d5db; padding-bottom: 8px;">Customer & Delivery</h4>
                                        <div style="font-size: 0.85rem; color: #4b5563; line-height: 1.6;">
                                            
                                            <strong>Email:</strong> <?php echo htmlspecialchars($row['cust_email']); ?><br>
                                            <strong>Account Phone:</strong> <?php echo !empty($row['cust_phone_number']) ? htmlspecialchars($row['cust_phone_number']) : 'N/A'; ?><br>
                                            
                                            <div style="margin-top: 14px; background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
                                                <?php if (!empty($row['street'])): ?>
                                                    <strong style="color: #111827; display: block; margin-bottom: 4px;">🚚 Delivery/Pickup Address:</strong>
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
                                                    <span style="color: #166534; font-weight: 700; display: block;">🏪 Self Collection at Store</span>
                                                    <span style="font-size: 0.78rem; color: #15803d; display: block; margin-top: 2px;">Customer will pick up and return at store location.</span>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>

                                    <div>
                                        <h4 style="margin: 0 0 12px 0; color: #111827; font-size: 0.95rem; border-bottom: 1px solid #d1d5db; padding-bottom: 8px;">Rented Instruments</h4>
                                        <div style="font-size: 0.85rem; color: #4b5563;">
                                            <?php
                                                $items_query = @mysqli_query($conn, "SELECT ri.*, p.prod_name FROM rental_items ri JOIN products p ON ri.prod_id = p.prod_id WHERE ri.rental_id = $rental_id");
                                                if($items_query && mysqli_num_rows($items_query) > 0) {
                                                    echo '<ul style="margin: 0; padding-left: 16px; line-height: 1.6;">';
                                                    while($item = mysqli_fetch_assoc($items_query)) {
                                                        echo '<li style="margin-bottom: 8px;">';
                                                        echo '<strong>' . $item['rental_qty'] . 'x</strong> ' . htmlspecialchars($item['prod_name']);
                                                        echo '<br><span style="color:#9ca3af; font-size: 0.75rem;">(RM ' . number_format($item['rental_rate'], 2) . ' /day)</span>';
                                                        echo '</li>';
                                                    }
                                                    echo '</ul>';
                                                } else {
                                                    echo '<em style="color:#9ca3af;">Item details not found.</em>';
                                                }
                                            ?>
                                        </div>
                                    </div>

                                    <div>
                                        <h4 style="margin: 0 0 12px 0; color: #111827; font-size: 0.95rem; border-bottom: 1px solid #d1d5db; padding-bottom: 8px;">Update Rental Status</h4>
                                        <form action="admin_rental_list.php" method="POST" style="display: flex; flex-direction: column; gap: 10px;">
                                            <input type="hidden" name="rental_id" value="<?php echo $rental_id; ?>">
                                            <select name="new_status" style="padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.85rem; outline: none;">
                                                <option value="Pending" <?php echo ($status == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="Processing" <?php echo ($status == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                                <option value="Active" <?php echo ($status == 'Active') ? 'selected' : ''; ?>>Active</option>
                                                <option value="Returned" <?php echo ($status == 'Returned' || $status == 'Unknown') ? 'selected' : ''; ?>>Returned</option>
                                                <option value="Overdue" <?php echo ($status == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                                                <option value="Cancelled" <?php echo ($status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_rental_status" style="background: #4f46e5; color: white; border: none; padding: 8px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 0.85rem;">Update Status</button>
                                        </form>
                                    </div>

                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">No rentals found.</td></tr>
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