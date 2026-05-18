<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once ('../database.php');
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];

    $sql = "UPDATE orders SET status= ? WHERE order_id= ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    if (mysqli_stmt_execute($stmt)) {
        $success = "Order status updated successfully.";
    } else {
        $error = "Please try again.";
    }
}

$sql = "SELECT o.*, c.cust_name FROM orders o JOIN customers c ON o.cust_id= c.cust_id ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $sql);
$page_title = "Order List";
$active = "order";

require_once('admin_header.php');
?>

<div class="main-header">
    <div>
        <h1>Order List</h1>
        <div class="meta">View and manage orders</div>
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
        <span>All Orders</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Total Amount</th>
                <th>Order Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>

        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo $row['order_id']; ?></td>
                        <td><?php echo $row['cust_name']; ?></td>
                        <td>RM <?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo date('d M Y', strtotime($row['order_date'])); ?></td>
                        
                        <td>
                            <span class="status status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst($row['status']); ?>
                            </span>
                        </td>
                        
                        <td>
                            <form method="POST" action="" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                <select name="status" style="padding:6px 10px; border-radius:6px; border:1px solid #e0e0e0; font-size:13px; font-family: inherit;"> 
                                    <option value="pending" <?php if ($row['status'] == 'pending') echo 'selected'; ?>>Pending</option>
                                    <option value="authorized" <?php if ($row['status'] == 'authorized') echo 'selected'; ?>>Authorized</option>
                                    <option value="completed" <?php if ($row['status'] == 'completed') echo 'selected'; ?>>Completed</option>
                                    <option value="cancelled" <?php if ($row['status'] == 'cancelled') echo 'selected'; ?>>Cancelled</option>
                                </select>

                                <button type="submit" name="update_status" class="btn btn-green btn-sm">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:24px; color:#888;">No orders found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>