<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }
require_once('../database.php');

$page_title = "Sales Orders";
$active = "orders";
require_once('admin_header.php');

$query = "SELECT o.*, c.cust_name FROM orders o JOIN customers c ON o.cust_id = c.cust_id ORDER BY o.order_date DESC";
$result = mysqli_query($conn, $query);
?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total (RM)</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td>#<?php echo $row['order_id']; ?></td>
                <td><?php echo $row['cust_name']; ?></td>
                <td><?php echo date('d M Y', strtotime($row['order_date'])); ?></td>
                <td style="font-weight: 600;"><?php echo number_format($row['total_amount'], 2); ?></td>
                <td>
                    <span class="status-pill <?php echo strtolower($row['status']); ?>">
                        <?php echo $row['status']; ?>
                    </span>
                </td>
                <td>
                    <a href="admin_order_details.php?id=<?php echo $row['order_id']; ?>" class="btn btn-outline btn-sm">Manage</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>