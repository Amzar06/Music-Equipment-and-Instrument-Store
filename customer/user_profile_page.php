<?php
session_start();
include '../database.php';
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../product/cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Check if the user is logged in (for navbar dynamic profile display context)
$is_logged_in = isset($_SESSION['cust_id']);

// Default values safely assigned
$user_data = [
    'cust_name' => 'N/A',
    'cust_email' => 'N/A',
    'cust_phone_number' => 'N/A',
    'cust_street' => 'N/A',
    'cust_city' => 'N/A',
    'cust_state' => 'N/A',
    'cust_postcode' => 'N/A'
];

$orders = []; // Array to store order history

if (isset($conn)) {
    // 1. Fetch Customer Information
    $stmt = $conn->prepare("SELECT cust_name, cust_email, cust_phone_number, cust_street, cust_city, cust_state, cust_postcode FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_data['cust_name'] = $row['cust_name'] ?? 'N/A';
            $_SESSION['cust_name'] = $user_data['cust_name']; // Ensure session name is populated
            $user_data['cust_email'] = $row['cust_email'] ?? 'N/A';
            $user_data['cust_phone_number'] = $row['cust_phone_number'] ?? 'N/A';
            $user_data['cust_street'] = $row['cust_street'] ?? '';
            $user_data['cust_city'] = $row['cust_city'] ?? '';
            $user_data['cust_state'] = $row['cust_state'] ?? '';
            $user_data['cust_postcode'] = $row['cust_postcode'] ?? '';
        }
        $stmt->close();
    }

    // 2. Fetch Customer Order History
    $order_stmt = $conn->prepare("SELECT order_id, order_date, total_amount, status FROM orders WHERE cust_id = ? ORDER BY order_date DESC LIMIT 5");
    if ($order_stmt) {
        $order_stmt->bind_param("i", $cust_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        while ($order_row = $order_result->fetch_assoc()) {
            $orders[] = $order_row;
        }
        $order_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Musical Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="customer.css?v=3.0">
    <style>
        body { background-color: #f8fafc; color: #1e293b; }
        .hero-section {
            background: linear-gradient(135deg, #0d3b8e 0%, #2563eb 100%);
            padding: 60px 0;
            color: white;
            margin-bottom: -50px;
        }
        .profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: none;
            padding: 30px;
            height: 100%;
        }
        .avatar-circle {
            width: 80px;
            height: 80px;
            background: #e2e8f0;
            color: #0d3b8e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 20px;
        }
        .info-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 5px;
            display: block;
        }
        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            display: block;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        .status-pending { background: #fee2e2; color: #991b1b; }
        .status-completed { background: #dcfce7; color: #166534; }
        .btn-edit {
            background: #0d3b8e;
            color: white;
            border-radius: 10px;
            padding: 10px 24px;
            font-weight: 600;
            transition: all 0.2s;
            border: none;
        }
        .btn-edit:hover { background: #082c6c; color: white; transform: translateY(-2px); }
        .order-table th {
            border: none;
            color: #64748b;
            font-size: 0.8rem;
            text-transform: uppercase;
        }
        .order-table td {
            vertical-align: middle;
            padding: 16px 8px;
        }

        /* Integrated Custom Profile Icon Dropdown Styles from Homepage */
        .user-dropdown-toggle {
            color: rgba(255, 255, 255, 0.85) !important;
            font-size: 1.35rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .user-dropdown-toggle:hover {
            color: #20c997 !important;
        }
        .dropdown-menu-end {
            right: 0;
            left: auto;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d3b8e; padding: 12px 0;">
    <div class="container-fluid px-5">
        <a class="navbar-brand" href="home_page.php" style="font-weight: 500;">Musical Instrument Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLogged">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navLogged">
            <ul class="navbar-nav ms-auto align-items-center" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link" href="home_page.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../product/product page.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="../product/payment history.php">My Orders</a></li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-dropdown-toggle" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-circle-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2" aria-labelledby="userMenu">
                        <?php if ($is_logged_in): ?>
                            <li class="px-3 py-1 text-muted small fw-bold text-uppercase">
                                Hi, <?php echo htmlspecialchars($_SESSION['cust_name'] ?? 'Customer'); ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item active" href="user_profile_page.php"><i class="fa-regular fa-id-card me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="../product/payment history.php"><i class="fa-solid fa-clock-history me-2"></i> Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout_page.php" onclick="return confirmLogout(event);"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="../product/cust login.php"><i class="fa-solid fa-right-to-bracket me-2"></i> Customer Login</a></li>
                            <li><a class="dropdown-item" href="register_page.php"><i class="fa-solid fa-user-plus me-2"></i> Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="hero-section">
    <div class="container">
        <div class="d-flex align-items-center gap-4">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user_data['cust_name'], 0, 1)); ?>
            </div>
            <div>
                <h1 style="margin: 0; font-weight: 800; font-size: 2.5rem;"><?php echo htmlspecialchars($user_data['cust_name']); ?></h1>
                <p style="margin: 0; color: rgba(255,255,255,0.7); font-weight: 500;">Musical Enthusiast since 2026</p>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5" style="margin-top: 30px;">
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="profile-card">
                <h3 style="font-weight: 800; margin-bottom: 24px;">Account Details</h3>
                
                <span class="info-label">Email Address</span>
                <span class="info-value"><?php echo htmlspecialchars($user_data['cust_email']); ?></span>
                
                <span class="info-label">Phone Number</span>
                <span class="info-value"><?php echo htmlspecialchars($user_data['cust_phone_number'] ?: 'Not Provided'); ?></span>
                
                <span class="info-label">Address</span>
                <span class="info-value" style="line-height: 1.6;">
                    <?php 
                    if($user_data['cust_street']) {
                        echo htmlspecialchars($user_data['cust_street']) . "<br>" . 
                             htmlspecialchars($user_data['cust_postcode']) . " " . 
                             htmlspecialchars($user_data['cust_city']) . "<br>" . 
                             htmlspecialchars($user_data['cust_state']);
                    } else {
                        echo "Address not set";
                    }
                    ?>
                </span>
                
                <div class="mt-4">
                    <a href="edit_profile_page.php" class="btn btn-edit w-100">Edit Profile Information</a>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="profile-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 style="font-weight: 800; margin: 0;">Recent Orders</h3>
                    <a href="../product/payment history.php" style="font-weight: 700; font-size: 0.9rem; text-decoration: none; color: #2563eb;">View All History →</a>
                </div>

                <div class="table-responsive">
                    <table class="table order-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5">
                                        <p style="color: #94a3b8; font-weight: 500;">No recent orders found.</p>
                                        <a href="../product/product page.php" class="btn btn-outline-primary btn-sm mt-2 rounded-pill px-4">Start Shopping</a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td style="font-weight: 700;">#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td style="color: #64748b;"><?php echo htmlspecialchars(date('d M Y', strtotime($order['order_date']))); ?></td>
                                        <td style="font-weight: 700; color: #10b981;">RM <?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo strtolower($order['status']) === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmLogout(event) {
    const confirmation = confirm("Are you sure you want to log out of your account?");
    if (!confirmation) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>