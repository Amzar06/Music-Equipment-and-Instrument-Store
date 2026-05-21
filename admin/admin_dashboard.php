<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

// Fetching all counts
$total_products  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM products"))['total'];
$total_orders    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM orders"))['total'];
$total_rentals   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM rentals"))['total'];
$total_customers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM customers"))['total'];

// Fetching 5 most recent orders
$recent_orders = mysqli_query($conn, "
    SELECT o.order_id, c.cust_name, o.total_amount, o.status 
    FROM orders o 
    JOIN customers c ON o.cust_id = c.cust_id 
    ORDER BY o.order_date DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard | Music Store</title>
    <link rel="stylesheet" href="admin.css">
</head>
<body>

<aside class="sidebar">
    <h2>Music Store Admin</h2>
    <ul class="nav-links">
        <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
        <li><a href="admin_products.php">Inventory</a></li>
        <li><a href="admin_order_list.php">Orders</a></li>
        <li><a href="admin_rental_list.php">Rentals</a></li>
        <li><a href="manage_customer.php">Customers</a></li>
        <li><a href="admin_report.php">Reports</a></li>
    </ul>
    <div style="margin-top: auto;">
        <a href="admin_logout.php" style="color: #ef4444; text-decoration: none; font-size: 0.9rem;">Logout</a>
    </div>
</aside>

<div class="main-content">
    <header>
        <div class="welcome-msg">
            <h1>Dashboard Overview</h1>
            <p style="color: #6b7280;"><?php echo date('l, d F Y'); ?></p>
        </div>
        <div class="admin-profile">
            <span style="font-weight: 600;"><?php echo $_SESSION['staff_name']; ?></span>
        </div>
    </header>

    <div class="stats-grid">
        <div class="stat-card">
            <span>Total Products</span>
            <h3><?php echo $total_products; ?></h3>
        </div>
        <div class="stat-card">
            <span>Total Orders</span>
            <h3><?php echo $total_orders; ?></h3>
        </div>
        <div class="stat-card">
            <span>Active Rentals</span>
            <h3><?php echo $total_rentals; ?></h3>
        </div>
        <div class="stat-card">
            <span>Customers</span>
            <h3><?php echo $total_customers; ?></h3>
        </div>
    </div>

    <div class="table-container">
        <h3 style="margin-bottom: 20px;">Recent Sales Orders</h3>
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer Name</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                <tr>
                    <td>#<?php echo $order['order_id']; ?></td>
                    <td><?php echo $order['cust_name']; ?></td>
                    <td>RM <?php echo number_format($order['total_amount'], 2); ?></td>
                    <td>
                        <span class="status-pill <?php echo strtolower($order['status']); ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>