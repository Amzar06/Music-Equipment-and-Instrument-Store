<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

if (isset($_GET['action']) && isset($_GET['item_id'])) {
    $item_id = intval($_GET['item_id']);
    $action = $_GET['action'];
    
    if (isset($conn) && !$conn->connect_error) {
        if ($action === 'inc') {
            // Check stock before incrementing
            $stock_chk = $conn->prepare("SELECT ci.quantity, p.prod_sale_qty FROM cart_items ci JOIN products p ON ci.prod_id = p.prod_id WHERE ci.cart_item_id = ?");
            if ($stock_chk) {
                $stock_chk->bind_param("i", $item_id);
                $stock_chk->execute();
                $s_res = $stock_chk->get_result()->fetch_assoc();
                if ($s_res && $s_res['quantity'] < $s_res['prod_sale_qty']) {
                    $conn->query("UPDATE cart_items SET quantity = quantity + 1 WHERE cart_item_id = $item_id");
                }
                $stock_chk->close();
            }
        } elseif ($action === 'dec') {
            // Only decrement if current quantity is > 1
            $conn->query("UPDATE cart_items SET quantity = IF(quantity > 1, quantity - 1, 1) WHERE cart_item_id = $item_id");
        }
        header("Location: cart page.php");
        exit();
    }
}

if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    if (isset($conn) && !$conn->connect_error) {
        $del_query = $conn->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.cart_id
            WHERE ci.cart_item_id = ? AND c.cust_id = ?
        ");
        if ($del_query) {
            $del_query->bind_param("ii", $remove_id, $cust_id);
            $del_query->execute();
            $del_query->close();
        }
        header("Location: cart page.php");
        exit();
    }
}

$cart_items = [];
$total_price = 0.00;
$db_error = null;

if (!isset($conn) || $conn->connect_error) {
    $db_error = "Database connection failed";
} else {
    $query = $conn->prepare("
        SELECT ci.cart_item_id, p.prod_name, p.prod_sale_price, p.prod_rental_price, ci.quantity, p.prod_image, p.prod_sale_qty, ci.start_date, ci.end_date
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.cart_id
        JOIN products p ON ci.prod_id = p.prod_id
        WHERE c.cust_id = ?
    ");
    if (!$query) {
        $db_error = "Query preparation failed: " . $conn->error;
    } else {
        $query->bind_param("i", $cust_id);
        if (!$query->execute()) {
            $db_error = "Query execution failed: " . $query->error;
        } else {
            $result = $query->get_result();
            while($row = $result->fetch_assoc()) {
                if ($row['start_date'] && $row['end_date']) {
                    // Rental item
                    $start = new DateTime($row['start_date']);
                    $end = new DateTime($row['end_date']);
                    $days = $start->diff($end)->days;
                    if ($days < 1) $days = 1;
                    $row['price'] = $row['prod_rental_price'] * $days;
                    $row['is_rental'] = true;
                    $row['duration'] = $days;
                } else {
                    // Sale item
                    $row['price'] = $row['prod_sale_price'];
                    $row['is_rental'] = false;
                }
                $cart_items[] = $row;
                $total_price += ($row['price'] * ($row['quantity'] ?? 1));
            }
        }
        $query->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Shopping Cart</title>
    <link rel="stylesheet" href="style.css?v=4.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .cart-card { 
            background: white; 
            border-radius: 16px; 
            padding: 32px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid rgba(0,0,0,0.05);
            margin-bottom: 40px;
        }
        .cart-item {
            background: #fff;
            border-bottom: 1px solid #f1f5f9;
            padding: 20px 0;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .cart-item:last-child { border-bottom: none; }
        .cart-total-box {
            background: #f8fafc;
            padding: 24px;
            border-radius: 12px;
            text-align: right;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d3b8e; padding: 12px 0;">
    <div class="container-fluid px-5">
        <a class="navbar-brand" href="../customer/home_page.php" style="font-weight: 500;">Musical Instrument Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLogged">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navLogged">
            <ul class="navbar-nav ms-auto" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link" href="../customer/home_page.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="product page.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="payment history.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="../customer/user_profile_page.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="../customer/logout_page.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80') center/cover; padding: 40px 0; margin-bottom: 40px; border-bottom: 4px solid #3b82f6;">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h2 style="margin: 0; font-size: 2.2rem; font-weight: 800; color: white;">Shopping Cart</h2>
        <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0 0;">Review your items before checkout</p>
    </div>
</div>

<div class="container pb-5">
    <div class="cart-card">
        <?php if ($db_error): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong> <?php echo htmlspecialchars($db_error); ?>
            </div>
        <?php endif; ?>

        <div class="cart-list">
            <?php if (empty($cart_items)): ?>
                <div style="text-align: center; padding: 60px 0; color: #64748b;">
                    <span style="font-size: 3rem; display: block; margin-bottom: 10px;">🛒</span>
                    <h3>Your cart is empty</h3>
                    <p>Looks like you haven't added any instruments yet.</p>
                    <a href="product page.php" class="btn btn-primary mt-3 px-4 py-2">Start Shopping</a>
                </div>
            <?php else: ?>
                <form action="address page.php" method="GET" id="cartForm">
                    <div style="padding: 10px 0; border-bottom: 2px solid #f1f5f9; margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" id="selectAll" style="width: 20px; height: 20px; cursor: pointer;">
                        <label for="selectAll" style="font-weight: 700; color: #64748b; cursor: pointer; user-select: none;">Select All Items</label>
                    </div>

                    <?php foreach($cart_items as $item): ?>
                        <div class='cart-item'>
                            <div style="padding: 0 10px;">
                                <input type="checkbox" name="selected_items[]" value="<?php echo $item['cart_item_id']; ?>" 
                                       class="item-checkbox" 
                                       data-price="<?php echo $item['price'] * ($item['quantity'] ?? 1); ?>"
                                       style="width: 20px; height: 20px; cursor: pointer;">
                            </div>

                            <img src="../uploads/<?php echo htmlspecialchars($item['prod_image'] ?: 'default.jpg'); ?>" 
                                 style="width: 100px; height: 100px; object-fit: cover; border-radius: 12px; border: 1px solid #e2e8f0;">
                            
                            <div style="flex: 1;">
                                <h5 style="margin: 0; font-weight: 700; color: #1e293b;"><?php echo htmlspecialchars($item['prod_name']); ?></h5>
                                <?php if ($item['is_rental']): ?>
                                    <div style="font-size: 0.85rem; color: #64748b; margin-top: 5px;">
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-weight: 700; font-size: 0.75rem; text-transform: uppercase; margin-right: 8px;">Rental</span>
                                        📅 <strong><?php echo htmlspecialchars($item['start_date']); ?></strong> to <strong><?php echo htmlspecialchars($item['end_date']); ?></strong>
                                        (<?php echo $item['duration']; ?> days)
                                    </div>
                                <?php endif; ?>
                                
                                <div style="display: flex; align-items: center; gap: 12px; margin-top: 12px;">
                                    <?php if (!$item['is_rental']): ?>
                                        <div style="display: flex; align-items: center; background: #f1f5f9; border-radius: 8px; padding: 5px;">
                                            <a href="?action=dec&item_id=<?php echo $item['cart_item_id']; ?>" style="padding: 0 12px; color: #475569; font-weight: 800; text-decoration: none; font-size: 1.2rem;">−</a>
                                            <span style='font-size:1rem; font-weight: 700; padding: 0 5px; color: #1e293b;'> <?php echo $item['quantity']; ?> </span>
                                            <?php if ($item['quantity'] < $item['prod_sale_qty']): ?>
                                                <a href="?action=inc&item_id=<?php echo $item['cart_item_id']; ?>" style="padding: 0 12px; color: #475569; font-weight: 800; text-decoration: none; font-size: 1.2rem;">+</a>
                                            <?php else: ?>
                                                <span style="padding: 0 12px; color: #cbd5e1; font-weight: 800; cursor: not-allowed; font-size: 1.2rem;">+</span>
                                            <?php endif; ?>
                                        </div>
                                        <span style="font-size: 0.85rem; color: #94a3b8;">Price: RM <?php echo number_format($item['price'], 2); ?> each</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.85rem; color: #94a3b8;">Rental Rate: RM <?php echo number_format($item['prod_rental_price'], 2); ?> / day</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div style="text-align: right;">
                                <div style='font-size:1.25rem; font-weight: 800; color: #10b981; margin-bottom: 8px;'>RM <?php echo number_format($item['price'] * ($item['quantity'] ?? 1), 2); ?></div>
                                <a href="?remove_id=<?php echo $item['cart_item_id']; ?>" style="color: #ef4444; font-size: 0.85rem; font-weight: 600; text-decoration: none;">🗑️ Remove</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-total-box">
                        <span style="font-size: 1.1rem; color: #64748b; font-weight: 600;">Selected Total:</span> <br>
                        <span style="font-size: 2rem; font-weight: 800; color: #1e293b;">RM <span id="displayTotal">0.00</span></span>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 40px; gap: 20px;">
                        <a href="product page.php" style="color: #475569; font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                            <span>←</span> Continue Shopping
                        </a>
                        <button type="submit" id="checkoutBtn" disabled class="btn btn-primary px-5 py-3" style="border-radius: 12px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(37,99,235,0.3);">
                            Secure Checkout (<span id="selectedCount">0</span>)
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.item-checkbox');
    const displayTotal = document.getElementById('displayTotal');
    const selectedCount = document.getElementById('selectedCount');
    const checkoutBtn = document.getElementById('checkoutBtn');

    function updateTotal() {
        let total = 0;
        let count = 0;
        checkboxes.forEach(cb => {
            if (cb.checked) {
                total += parseFloat(cb.dataset.price);
                count++;
            }
        });
        displayTotal.textContent = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        selectedCount.textContent = count;
        checkoutBtn.disabled = count === 0;
        
        // Update checkout button style when disabled
        if (count === 0) {
            checkoutBtn.style.opacity = '0.6';
            checkoutBtn.style.cursor = 'not-allowed';
            checkoutBtn.style.boxShadow = 'none';
        } else {
            checkoutBtn.style.opacity = '1';
            checkoutBtn.style.cursor = 'pointer';
            checkoutBtn.style.boxShadow = '0 4px 15px rgba(37,99,235,0.3)';
        }
    }

    selectAll.addEventListener('change', function() {
        checkboxes.forEach(cb => {
            cb.checked = selectAll.checked;
        });
        updateTotal();
    });

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            updateTotal();
            // Update selectAll state
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            selectAll.checked = allChecked;
        });
    });

    // Initial check (disabled by default as nothing is selected)
    updateTotal();
});
</script>

</body>
</html>
