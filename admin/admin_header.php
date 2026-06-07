<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Admin Music Store</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>

<aside class="sidebar">
    <h2>Music Store</h2>
    <ul class="nav-links">
        <li><a href="admin_dashboard.php" class="<?php echo ($active == 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="admin_products.php" class="<?php echo ($active == 'products') ? 'active' : ''; ?>">Inventory</a></li>
        <li><a href="admin_categories.php" class="<?php echo ($active == 'categories') ? 'active' : ''; ?>">Categories</a></li>
        <li><a href="admin_order_list.php" class="<?php echo ($active == 'orders') ? 'active' : ''; ?>">Orders</a></li>
        <li><a href="admin_rental_list.php" class="<?php echo ($active == 'rentals') ? 'active' : ''; ?>">Rentals</a></li>
        <li><a href="manage_customer.php" class="<?php echo ($active == 'customers') ? 'active' : ''; ?>">Customers</a></li>
        <li><a href="manage_admin.php" class="<?php echo ($active == 'staff') ? 'active' : ''; ?>">Staff</a></li>
        <li><a href="admin_report.php" class="<?php echo ($active == 'reports') ? 'active' : ''; ?>">Reports</a></li>
    </ul>
    <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="admin_logout.php" style="color: #ef4444; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Logout</a>
    </div>
</aside>

<div class="main-content">
    <header>
        <div class="welcome-msg">
            <h1><?php echo $page_title; ?></h1>
            <p style="color: #6b7280;"><?php echo date('l, d F Y'); ?></p>
        </div>
        <div class="admin-profile">
            <span class="status-pill completed" style="text-transform: uppercase; letter-spacing: 1px;">
                <?php echo $_SESSION['staff_role'] ?? 'Admin'; ?>
            </span>
            <span style="margin-left: 10px; font-weight: 600; color: var(--text-main);">
                <?php echo $_SESSION['staff_name'] ?? 'Staff'; ?>
            </span>
        </div>
    </header>