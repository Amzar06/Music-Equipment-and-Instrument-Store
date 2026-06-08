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
    <header style="display: flex; justify-content: space-between; align-items: center;">
        <div class="welcome-msg">
            <h1><?php echo $page_title; ?></h1>
            <p style="color: #6b7280;"><?php echo date('l, d F Y'); ?></p>
        </div>

        <!-- CONDITIONAL GLOBAL SEARCH BAR -->
        <?php if (!isset($hide_search) || !$hide_search): ?>
            <div style="width: 100%; max-width: 400px; margin: 0 20px;">
                <form action="" method="GET" style="display: flex; align-items: center; background: white; border: 1px solid #d1d5db; border-radius: 20px; padding: 4px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    
                    <?php if(isset($_GET['sort'])): ?>
                        <input type="hidden" name="sort" value="<?php echo htmlspecialchars($_GET['sort']); ?>">
                    <?php endif; ?>

                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#9ca3af" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" name="search" placeholder="Search..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" 
                           style="border: none; outline: none; padding: 8px 12px; width: 100%; font-size: 0.9rem; background: transparent;">
                    <button type="submit" style="display: none;"></button>
                </form>
            </div>
        <?php else: ?>
            <!-- Invisible placeholder so the header layout doesn't break when search is hidden -->
            <div style="width: 100%; max-width: 400px; margin: 0 20px;"></div>
        <?php endif; ?>

        <div class="admin-profile">
            <span class="status-pill completed" style="text-transform: uppercase; letter-spacing: 1px;">
                <?php echo $_SESSION['staff_role'] ?? 'Admin'; ?>
            </span>
            <span style="margin-left: 10px; font-weight: 600; color: var(--text-main);">
                <?php echo $_SESSION['staff_name'] ?? 'Staff'; ?>
            </span>
        </div>
    </header>