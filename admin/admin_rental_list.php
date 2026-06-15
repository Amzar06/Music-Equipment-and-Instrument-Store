<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$success = "";
$error = "";

// ==========================================
// HANDLE RENTAL STATUS UPDATE
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $rental_id = intval($_POST['rental_id']);
    $status    = mysqli_real_escape_string($conn, $_POST['status']);

    $sql  = "UPDATE rentals SET status = ? WHERE rental_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $rental_id);

    if (mysqli_stmt_execute($stmt)) {
        $success = "Rental status updated successfully.";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}

// ==========================================
// FETCH RENTALS
// ==========================================
$sql = "SELECT r.*, c.cust_name 
        FROM rentals r 
        LEFT JOIN customers c ON r.cust_id = c.cust_id 
        ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);

// This automatically feeds into admin_header.php to become the ONLY title
$page_title = "Rental Management";
$active = "rentals";
require_once('admin_header.php');
?>

<?php if ($success != ""): ?>
    <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;">
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;">
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="table-container" style="margin-top: 0;">
    <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-main);">All Customer Rentals</h3>
    
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Customer</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo $row['rental_id']; ?></td>
                        <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['cust_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['start_date'])); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                        <td style="font-weight: 500;">RM <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-pill <?php echo strtolower($row['status']); ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <form method="POST" action="admin_rental_list.php" style="display:flex; justify-content: flex-end; gap:8px; align-items:center; margin: 0;">
                                <input type="hidden" name="rental_id" value="<?php echo $row['rental_id']; ?>">
                                
                                <select name="status" style="padding:6px 10px; border-radius:6px; border:1px solid var(--border-color); font-size:13px; font-family:inherit; outline: none; background: white;">
                                    <option value="Active"     <?php echo ($row['status'] == 'Active')     ? 'selected' : ''; ?>>Active</option>
                                    <option value="Processing" <?php echo ($row['status'] == 'Processing') ? 'selected' : ''; ?>>Processing (Return Pending)</option>
                                    <option value="Returned"   <?php echo ($row['status'] == 'Returned')   ? 'selected' : ''; ?>>Returned</option>
                                    <option value="Overdue"    <?php echo ($row['status'] == 'Overdue')    ? 'selected' : ''; ?>>Overdue</option>
                                </select>
                                
                                <button type="submit" name="update_status" style="padding: 6px 12px; background: var(--accent-color); color: white; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: 0.2s;">
                                    Update
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding:40px; color:#9ca3af;">No rentals found in the system.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>