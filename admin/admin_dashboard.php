<?php 
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Dashboard";
$active = "dashboard";


// fetching statistics

$total_products  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"))['total'];
$total_orders    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders"))['total'];

// FIXED: Now counts both 'Pending' and 'Processing' orders
$pending_orders  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders WHERE status IN ('Pending', 'Processing')"))['total'];

$total_rentals   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM rentals"))['total'];
$active_rentals  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM rentals WHERE status = 'Active'"))['total'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers"))['total'];

// Fetching recent activities

// Fetching 5 recent sales

$recent_orders = mysqli_query($conn, "
    SELECT o.order_id, c.cust_name, o.total_amount, o.status 
    FROM orders o 
    JOIN customers c ON o.cust_id = c.cust_id 
    ORDER BY o.order_date DESC LIMIT 5
");

// Fetching 5 recent rentals

$recent_rentals = mysqli_query($conn, "
    SELECT r.rental_id, c.cust_name, r.end_date, r.status 
    FROM rentals r 
    JOIN customers c ON r.cust_id = c.cust_id 
    ORDER BY r.created_at DESC LIMIT 5
");

// Load the layout frame

require_once('admin_header.php');
?>

<!--stat cards -->

<div class="stats-grid">
    <div class="stat-card">
        <span>Total Products</span>
        <h3><?php echo $total_products; ?></h3>
    </div>
    <div class="stat-card">
        <span>Total Orders</span>
        <h3><?php echo $total_orders; ?></h3>
        <div style="font-size: 0.85rem; color: #ef4444; font-weight: 500; margin-top: 4px;">
            <?php echo $pending_orders; ?> processing
        </div>
    </div>
    <div class="stat-card">
        <span>Lifetime Rentals</span>
        <h3><?php echo $total_rentals; ?></h3>
        <div style="font-size: 0.85rem; color: #f59e0b; font-weight: 500; margin-top: 4px;">
            <?php echo $active_rentals; ?> currently active
        </div>
    </div>
    <div class="stat-card">
        <span>Customers</span>
        <h3><?php echo $total_customers; ?></h3>
    </div>
</div>

<!-- recent sales + rentals -->
 
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    
    <div class="table-container" style="margin-top: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin-bottom: 0;">Recent Sales Orders</h3>
            <a href="admin_order_list.php" style="font-size: 0.85rem; color: var(--accent-color); text-decoration: none; font-weight: 600;">View All</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                    <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                    <tr>
                        <td>#<?php echo $order['order_id']; ?></td>
                        <td style="font-weight: 500;"><?php echo $order['cust_name']; ?></td>
                        <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <span class="status-pill <?php echo strtolower($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: #9ca3af;">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="table-container" style="margin-top: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin-bottom: 0;">Recent Rentals</h3>
            <a href="admin_rental_list.php" style="font-size: 0.85rem; color: var(--accent-color); text-decoration: none; font-weight: 600;">View All</a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Rental ID</th>
                    <th>Customer</th>
                    <th>Due Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($recent_rentals) > 0): ?>
                    <?php while($rental = mysqli_fetch_assoc($recent_rentals)): ?>
                    <tr>
                        <td>#<?php echo $rental['rental_id']; ?></td>
                        <td style="font-weight: 500;"><?php echo $rental['cust_name']; ?></td>
                        <td><?php echo date('d M Y', strtotime($rental['end_date'])); ?></td>
                        <td>
                            <span class="status-pill <?php echo strtolower($rental['status']); ?>">
                                <?php echo $rental['status']; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 20px; color: #9ca3af;">No rentals found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<?php require_once('admin_footer.php'); ?>