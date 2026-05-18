<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $rental_id = $_POST['rental_id'];
    $status    = $_POST['status'];

    $sql  = "UPDATE rentals SET status = ? WHERE rental_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $rental_id);

    if (mysqli_stmt_execute($stmt)) {
        $success = "Rental status updated successfully.";
    } else {
        $error = "Something went wrong. Please try again.";
    }
}

$sql = "SELECT r.*, c.cust_name 
        FROM rentals r 
        LEFT JOIN customers c ON r.cust_id = c.cust_id 
        ORDER BY r.created_at DESC";
$result = mysqli_query($conn, $sql);

$page_title = "Rental List";
$active = "rentals";
require_once('admin_header.php');
?>

<div class="main-header">
    <div>
        <h1>Rental List</h1>
        <div class="meta">View and manage customer rentals</div>
    </div>
</div>

<?php if ($success != ""): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <span>All Rentals</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Start Date</th>
                <th>End Date</th>
                <th>Total Amount</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td>#<?php echo $row['rental_id']; ?></td>
                    <td><?php echo $row['cust_name']; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['start_date'])); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['end_date'])); ?></td>
                    <td>RM <?php echo number_format($row['total_amount'], 2); ?></td>
                    <td>
                        <span class="status status-<?php echo $row['status']; ?>">
                            <?php echo ucfirst($row['status']); ?>
                        </span>
                    </td>
                    <td>
                        <form method="POST" action="" style="display:flex; gap:8px; align-items:center;">
                            <input type="hidden" name="rental_id" value="<?php echo $row['rental_id']; ?>">
                            <select name="status" style="padding:6px 10px; border-radius:6px; border:1px solid #e0e0e0; font-size:13px; font-family:inherit;">
                                <option value="pending"  <?php echo ($row['status'] == 'pending')  ? 'selected' : ''; ?>>Pending</option>
                                <option value="active"   <?php echo ($row['status'] == 'active')   ? 'selected' : ''; ?>>Active</option>
                                <option value="returned" <?php echo ($row['status'] == 'returned') ? 'selected' : ''; ?>>Returned</option>
                                <option value="rejected" <?php echo ($row['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-green btn-sm">Update</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:24px; color:#888;">No rentals found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>