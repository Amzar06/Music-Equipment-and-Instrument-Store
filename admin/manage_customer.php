<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }
require_once('../database.php');

$page_title = "Customer Directory";
$active = "customers";
require_once('admin_header.php');

$result = mysqli_query($conn, "SELECT * FROM customers ORDER BY cust_name ASC");
?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Account Created</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td style="font-weight: 600;"><?php echo $row['cust_name']; ?></td>
                <td><?php echo $row['cust_email']; ?></td>
                <td><?php echo $row['cust_phone']; ?></td>
                <td><?php echo date('d M Y', strtotime($row['created_at'] ?? 'now')); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>