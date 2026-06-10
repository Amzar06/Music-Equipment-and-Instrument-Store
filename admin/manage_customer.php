<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Customer Directory";
$active = "customers"; 

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where_clause = "WHERE status != 'Deleted'"; // Hide deleted customers from the main list

if (!empty($search)) {
    $where_clause .= " AND (cust_name LIKE '%$search%' OR cust_email LIKE '%$search%' OR cust_phone_number LIKE '%$search%')";
}

$customer_query = "SELECT * FROM customers $where_clause ORDER BY cust_name ASC";
$customer_result = mysqli_query($conn, $customer_query);

require_once('admin_header.php');
?>

<div style="max-width: 1200px; margin: 0 auto; margin-top: 20px;">
    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); border: 1px solid #e5e7eb;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-weight: 700; color: #111827; font-size: 1.25rem;">Registered Customers</h3>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Customer Name</th>
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Contact Info</th>
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($customer_result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($customer_result)): 
                            $status = isset($row['status']) ? $row['status'] : 'Active';
                            if ($status == 'Active') { $bg = '#d1fae5'; $txt = '#065f46'; }
                            elseif ($status == 'Suspended') { $bg = '#fef3c7'; $txt = '#92400e'; }
                            elseif ($status == 'Blacklisted') { $bg = '#111827'; $txt = '#f9fafb'; }
                            else { $bg = '#fee2e2'; $txt = '#991b1b'; }
                        ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 16px 8px; font-weight: 600; color: #111827;">
                                <?php echo htmlspecialchars($row['cust_name']); ?>
                            </td>
                            <td style="padding: 16px 8px; font-size: 0.85rem; color: #4b5563;">
                                <?php echo htmlspecialchars($row['cust_email']); ?><br>
                                <?php echo !empty($row['cust_phone_number']) ? htmlspecialchars($row['cust_phone_number']) : '-'; ?>
                            </td>
                            <td style="padding: 16px 8px;">
                                <span style="background: <?php echo $bg; ?>; color: <?php echo $txt; ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($status); ?>
                                </span>
                            </td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <a href="admin_view_customer.php?id=<?php echo $row['cust_id']; ?>" 
                                   style="color: #4f46e5; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 16px; border-radius: 6px; background: #e0e7ff; transition: 0.2s;">
                                   View Full Profile
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af;">No customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once('admin_footer.php'); ?>