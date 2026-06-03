<<?php
session_start();
if (!isset($_SESSION['staff_id'])) { 
    header("Location: admin_login.php"); 
    exit(); 
}
require_once('../database.php');

$page_title = "Customer Directory";
$active = "customers";
require_once('admin_header.php');

$query = "SELECT * FROM customers ORDER BY cust_name ASC";
$result = mysqli_query($conn, $query);
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
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['cust_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['cust_email']); ?></td>
                    <td><?php echo htmlspecialchars($row['cust_phone_number']); ?></td>
                    <td><?php echo date('d M Y', strtotime($row['created_at'] ?? 'now')); ?></td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af;">No customers registered yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>