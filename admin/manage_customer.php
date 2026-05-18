<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once ('../database.php');

$sql = "SELECT * FROM customers ORDER BY cust_id ASC";
$result = mysqli_query($conn, $sql);
$page_title = "Customer List";
$active = "customer";

require_once('admin_header.php'); // Fixed missing semicolon
?>

<div class="main-header">
    <div>
        <h1>Customer List</h1>
        <div class="meta">View all registered customers</div>
    </div>
</div>
            
<div class="card">
    <div class="card-head">
        <span>All Customers</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Address</th>
                <th>Registered On</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['cust_id']; ?></td>
                    <td><?php echo $row['cust_name']; ?></td>
                    <td><?php echo $row['cust_email']; ?></td>
                    <td><?php echo ($row['cust_phone_number'] != "") ? $row['cust_phone_number'] : '-'; ?></td>
                    <td><?php echo ($row['cust_address'] != "") ? $row['cust_address'] : '-'; ?></td>
                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align:center; padding:24px; color:#888;">No customers registered yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>