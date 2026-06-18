<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Admin Music Equipment & Instrument Store</title>
    <link rel="stylesheet" href="admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<aside class="sidebar">
    <h2>Music Equipment & Instrument Store</h2>
    <ul class="nav-links">
        <li><a href="admin_dashboard.php" class="<?php echo ($active == 'dashboard') ? 'active' : ''; ?>">Dashboard</a></li>
        <li><a href="admin_products.php" class="<?php echo ($active == 'products') ? 'active' : ''; ?>">Products</a></li>
        <li><a href="admin_return_products.php" class="<?php echo ($active == 'returns') ? 'active' : ''; ?>">Return Products</a></li>
        <li><a href="admin_categories.php" class="<?php echo ($active == 'categories') ? 'active' : ''; ?>">Categories</a></li>
        <li><a href="admin_order_list.php" class="<?php echo ($active == 'orders') ? 'active' : ''; ?>">Orders</a></li>
        <li><a href="admin_rental_list.php" class="<?php echo ($active == 'rentals') ? 'active' : ''; ?>">Rentals</a></li>
        <li><a href="manage_customer.php" class="<?php echo ($active == 'customers') ? 'active' : ''; ?>">Customers</a></li>
        
        <?php if (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Administrator'): ?>
            <li><a href="manage_admin.php" class="<?php echo ($active == 'staff') ? 'active' : ''; ?>">Staff</a></li>
            <li><a href="admin_report.php" class="<?php echo ($active == 'reports') ? 'active' : ''; ?>">Reports</a></li>
        <?php endif; ?>
    </ul>
    <div style="margin-top: auto; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
        <a href="admin_logout.php" onclick="return confirm('Are you sure you want to log out?');" style="color: #ef4444; text-decoration: none; font-size: 0.9rem; font-weight: 600;">Logout</a>
    </div>
</aside>

<div class="main-content">
    <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        
        <div class="welcome-msg">
            <h1 style="margin: 0; font-size: 1.5rem; color: #111827;"><?php echo $page_title; ?></h1>
            <p style="margin: 4px 0 0 0; color: #6b7280; font-size: 0.9rem;"><?php echo date('l, d F Y'); ?></p>
        </div>

        <?php if (!isset($hide_search) || !$hide_search): ?>
            <div style="width: 100%; max-width: 400px; margin: 0 20px;"></div>
        <?php else: ?>
            <div style="width: 100%; max-width: 400px; margin: 0 20px;"></div>
        <?php endif; ?>

        <div class="admin-profile" style="display: flex; align-items: center; gap: 15px;">
            <span style="text-transform: uppercase; letter-spacing: 1px; padding: 6px 12px; font-size: 0.75rem; background-color: #d1fae5; color: #065f46; border-radius: 12px; font-weight: 600;">
                <?php 
                    $db_role = $_SESSION['staff_role'] ?? 'staff';
                    echo ($db_role === 'Administrator') ? 'SUPERADMIN' : 'ADMIN';
                ?>
            </span>
            
            <a href="admin_profile.php" 
               style="font-weight: 600; color: #111827; text-decoration: none; padding: 6px 14px; background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 20px; transition: all 0.2s;"
               onmouseover="this.style.backgroundColor='#e5e7eb'; this.style.borderColor='#d1d5db';" 
               onmouseout="this.style.backgroundColor='#f3f4f6'; this.style.borderColor='#e5e7eb';">
               <?php echo htmlspecialchars($_SESSION['staff_name'] ?? 'Staff'); ?>
            </a>
        </div>
        
    </header>