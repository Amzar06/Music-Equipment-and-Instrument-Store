<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Check if the user is logged in (for navbar profile dynamic context)
$is_logged_in = isset($_SESSION['cust_id']);

// Handle Cancellation
if (isset($_GET['action']) && $_GET['action'] === 'cancel' && isset($_GET['id']) && isset($_GET['type'])) {
    $cancel_id = intval($_GET['id']);
    $cancel_type = $_GET['type'];
    
    if ($cancel_type === 'Order') {
        $stmt = $conn->prepare("UPDATE orders SET status = 'Cancelled' WHERE order_id = ? AND cust_id = ? AND status IN ('Pending', 'Processing')");
    } else {
        $stmt = $conn->prepare("UPDATE rentals SET status = 'Cancelled' WHERE rental_id = ? AND cust_id = ? AND status = 'Pending'");
    }
    
    if ($stmt) {
        $stmt->bind_param("ii", $cancel_id, $cust_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: payment history.php");
    exit();
}

$orders = [];
$db_error = null;
if (isset($conn)) {
    try {
        // Fetch both rentals and purchases grouped by date with product info
        $query = $conn->prepare("
             SELECT 'Order' as type, o.order_id as id, o.total_amount, o.status, o.order_date as date, a.street, a.city, a.state, a.postcode,
                   (SELECT p.prod_name FROM order_items oi JOIN products p ON oi.prod_id = p.prod_id WHERE oi.order_id = o.order_id LIMIT 1) as prod_name,
                   (SELECT p.prod_image FROM order_items oi JOIN products p ON oi.prod_id = p.prod_id WHERE oi.order_id = o.order_id LIMIT 1) as prod_image,
                   (SELECT SUM(oi.order_qty) FROM order_items oi WHERE oi.order_id = o.order_id) as total_qty,
                   NULL as start_date, NULL as end_date, o.delivered_at
            FROM orders o
            LEFT JOIN addresses a ON o.address_id = a.address_id
            WHERE o.cust_id = ?
            
            UNION ALL
                        SELECT 'Rental' as type, r.rental_id as id, r.total_amount, r.status, r.created_at as date, a.street, a.city, a.state, a.postcode,
                   (SELECT p.prod_name FROM rental_items ri JOIN products p ON ri.prod_id = p.prod_id WHERE ri.rental_id = r.rental_id LIMIT 1) as prod_name,
                   (SELECT p.prod_image FROM rental_items ri JOIN products p ON ri.prod_id = p.prod_id WHERE ri.rental_id = r.rental_id LIMIT 1) as prod_image,
                   (SELECT SUM(ri.rental_qty) FROM rental_items ri WHERE ri.rental_id = r.rental_id) as total_qty,
                   r.start_date, r.end_date, NULL as delivered_at
            FROM rentals r
            LEFT JOIN addresses a ON r.address_id = a.address_id
            WHERE r.cust_id = ?
            
            ORDER BY date DESC
        ");
        if ($query) {
            $query->bind_param("ii", $cust_id, $cust_id);
            if ($query->execute()) {
                $result = $query->get_result();
                while($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }
            } else {
                $db_error = "Execution failed: " . $query->error;
            }
            $query->close();
        } else {
            $db_error = "Preparation failed: " . $conn->error;
        }
    } catch (mysqli_sql_exception $e) {
        $db_error = "Database Error: " . $e->getMessage();
    }
} else {
    $db_error = "Database connection not established.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Order History</title>
    <link rel="stylesheet" href="style.css?v=5.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome CDN Added for the Profile User Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .history-card { 
            background: white; 
            border-radius: 16px; 
            padding: 32px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }
        .type-badge {
            font-size: 0.7rem;
            padding: 2px 8px;
            border-radius: 4px;
            color: white;
            font-weight: 700;
        }
        table { border-collapse: separate; border-spacing: 0 10px; width: 100%; }
        th { background: #f8fafc; border: none; padding: 15px; font-weight: 700; color: #64748b; font-size: 0.85rem; text-transform: uppercase; }
        td { background: white; border-top: 1px solid #f1f5f9; border-bottom: 1px solid #f1f5f9; padding: 20px 15px; }
        td:first-child { border-left: 1px solid #f1f5f9; border-radius: 12px 0 0 12px; }
        td:last-child { border-right: 1px solid #f1f5f9; border-radius: 0 12px 12px 0; }

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

<!-- UPDATED NAVIGATION BAR -->
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d3b8e; padding: 12px 0;">
    <div class="container-fluid px-5">
        <a class="navbar-brand" href="../customer/home_page.php" style="font-weight: 500;">Musical Instrument Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLogged">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navLogged">
            <ul class="navbar-nav ms-auto align-items-center" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link" href="../customer/home_page.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="product page.php">Products</a></li>
                <li class="nav-item"><a class="nav-link active" href="payment history.php">My Orders</a></li>
                
                <!-- CUSTOMER PROFILE ICON ACCORDION DROPDOWN -->
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
                            <li><a class="dropdown-item" href="../customer/user_profile_page.php"><i class="fa-regular fa-id-card me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item active" href="payment history.php"><i class="fa-solid fa-clock-history me-2"></i> Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../customer/logout_page.php" onclick="return confirmLogout(event);"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="cust login.php"><i class="fa-solid fa-right-to-bracket me-2"></i> Customer Login</a></li>
                            <li><a class="dropdown-item" href="../customer/register_page.php"><i class="fa-solid fa-user-plus me-2"></i> Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80') center/cover; padding: 40px 0; margin-bottom: 0; border-bottom: 4px solid #10b981;">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h2 style="margin: 0; font-size: 2.2rem; font-weight: 800; color: white;">Order History</h2>
        <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0 0;">Track your purchases and rentals</p>
    </div>
</div>

<div class="container pb-5 mt-4">
    <div class="history-card">
        <?php if (isset($_GET['return_success'])): ?>
            <div style="background: #d1fae5; border: 1px solid #a7f3d0; color: #065f46; padding: 16px; border-radius: 12px; margin-bottom: 24px;">
                <strong>Success!</strong> Your return request has been submitted and is under review.
            </div>
        <?php endif; ?>

        <?php if ($db_error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($db_error); ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Instrument Details</th>
                        <th>Address / Method</th>
                        <th>Qty</th>
                        <th>Total Price</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 60px; color: #94a3b8;">
                                <div style="font-size: 3rem; margin-bottom: 10px;">📋</div>
                                <h5>No transactions found</h5>
                                <p>You haven't made any purchases or rentals yet.</p>
                                <a href="product page.php" class="btn btn-outline-primary mt-3">Start Shopping</a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($orders as $order): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="../uploads/<?php echo htmlspecialchars($order['prod_image'] ?: 'default.jpg'); ?>" 
                                         style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0;">
                                    <div>
                                        <span class="type-badge" style="background: <?php echo $order['type'] === 'Rental' ? '#7c3aed' : '#2563eb'; ?>;"><?php echo htmlspecialchars($order['type']); ?></span>
                                        <div style="font-weight: 700; color: #1e293b; margin-top: 2px;"><?php echo htmlspecialchars($order['prod_name'] ?: 'Multiple Items'); ?></div>
                                        <div style="font-size: 0.75rem; color: #94a3b8;">#<?php echo htmlspecialchars($order['id']); ?> • <?php echo date('d M Y', strtotime($order['date'])); ?></div>
                                        
                                        <?php if ($order['type'] === 'Rental' && $order['start_date']): ?>
                                            <div style="font-size: 0.75rem; color: #7c3aed; font-weight: 600; margin-top: 4px; background: rgba(124, 58, 237, 0.05); padding: 2px 8px; border-radius: 4px; display: inline-block;">
                                                📅 <?php echo date('d M', strtotime($order['start_date'])); ?> - <?php echo date('d M', strtotime($order['end_date'])); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($order['street']): ?>
                                    <div style="font-size: 0.85rem; color: #64748b; line-height: 1.4;">
                                        <span style="font-weight:600; color:#1e293b; display:block;">🚚 Delivery</span>
                                        <?php echo htmlspecialchars($order['street']); ?>, <?php echo htmlspecialchars($order['city']); ?>
                                    </div>
                                <?php else: ?>
                                    <span style="font-size: 0.8rem; padding: 4px 10px; background: #f0fdf4; color: #16a34a; border-radius: 20px; font-weight: 700; border: 1px solid #bbf7d0;">🏪 Self Collect</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($order['total_qty'] ?? 1); ?></td>
                            <td style="font-weight: 800; color: #10b981; font-size: 1.1rem;">RM <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                            <td>
                                <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 6px;">
                                    <span class="status-badge" style="background: <?php 
                                        $status = strtolower($order['status'] ?? '');
                                        if (in_array($status, ['completed', 'delivered', 'returned', 'shipped'])) echo '#dcfce7; color: #166534;';
                                        elseif (in_array($status, ['pending', 'processing', 'active'])) echo '#e0e7ff; color: #3730a3;';
                                        elseif (in_array($status, ['declined', 'cancelled'])) echo '#fee2e2; color: #991b1b;';
                                        else echo '#f1f5f9; color: #475569;';
                                    ?>"><?php echo htmlspecialchars(ucfirst($order['status'] ?? 'Pending')); ?></span>

                                    <?php 
                                        $s = strtolower($order['status'] ?? '');
                                        if (in_array($s, ['pending', 'processing'])): 
                                    ?>
                                        <a href="?action=cancel&id=<?php echo $order['id']; ?>&type=<?php echo $order['type']; ?>" 
                                           onclick="return confirm('Are you sure you want to cancel?')"
<<<<<<< HEAD
                                           style="font-size: 0.7rem; color: #ef4444; font-weight: 700; text-decoration: none; border: 1px solid #fee2e2; padding: 2px 8px; border-radius: 4px; background: #fff5f5; margin-top: 5px;">
                                           Cancel Item
=======
                                           style="font-size: 0.7rem; color: #ef4444; font-weight: 700; text-decoration: none; border-bottom: 1px dashed #ef4444; margin-left: 5px;">
                                            Cancel Item
>>>>>>> e59ae58dc569070a7eee0c1fe7de29a54d5fa692
                                        </a>
                                    <?php elseif ($order['type'] === 'Rental' && in_array($s, ['active', 'overdue'])): ?>
                                        <a href="return_rental.php?rental_id=<?php echo $order['id']; ?>" 
                                           style="font-size: 0.7rem; color: #7c3aed; font-weight: 700; text-decoration: none; border: 1px solid #f5d0fe; padding: 2px 8px; border-radius: 4px; background: #fdf4ff; margin-top: 5px;">
                                           Return Instrument
                                        </a>
                                    <?php elseif ($s === 'delivered' && $order['type'] === 'Order'): 
                                        $delivered_at = !empty($order['delivered_at']) ? strtotime($order['delivered_at']) : null;
                                        $can_return = false;
                                        if ($delivered_at) {
                                            $diff = time() - $delivered_at;
                                            if ($diff <= (2 * 24 * 60 * 60)) { // 2 days
                                                $can_return = true;
                                            }
                                        }
                                        
                                        if ($can_return):
                                    ?>
                                        <a href="return_product.php?order_id=<?php echo $order['id']; ?>" 
                                           style="font-size: 0.7rem; color: #0891b2; font-weight: 700; text-decoration: none; border: 1px solid #cffafe; padding: 2px 8px; border-radius: 4px; background: #f0fdfa; margin-top: 5px;">
                                           Return Item
                                        </a>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="text-align: center; margin-top: 40px;">
            <a href="product page.php" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 10px; font-weight: 600;">← Back to Shop</a>
        </div>
    </div>
</div>

<!-- Scripts & Event Observers -->
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