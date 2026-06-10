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
            $stock_chk = $conn->prepare("SELECT ci.quantity, p.prod_qty FROM cart_items ci JOIN products p ON ci.prod_id = p.prod_id WHERE ci.cart_item_id = ?");
            if ($stock_chk) {
                $stock_chk->bind_param("i", $item_id);
                $stock_chk->execute();
                $s_res = $stock_chk->get_result()->fetch_assoc();
                if ($s_res && $s_res['quantity'] < $s_res['prod_qty']) {
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
        SELECT ci.cart_item_id, p.prod_name, p.prod_sale_price, ci.quantity, p.prod_image
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
                $cart_items[] = $row;
                if (isset($row['prod_sale_price'])) {
                    $qty = $row['quantity'] ?? 1;
                    $total_price += ($row['prod_sale_price'] * $qty);
                }
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
    <title>Your Cart</title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
</head>
<body>

<div class="container">
    <h2>Your Cart</h2>

    <?php if ($db_error): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 16px;">
            <strong>Error:</strong> <?php echo htmlspecialchars($db_error); ?>
        </div>
    <?php endif; ?>

    <div class="cart-list">
        <?php if (empty($cart_items)): ?>
            <div style="text-align: center; padding: 32px; color: var(--text-secondary);">
                Your cart is completely empty.<br>
                Please add some products from the shop.
            </div>
        <?php else: ?>
            <?php foreach($cart_items as $item): ?>
                <div class='cart-item'>
                    <img src="../uploads/<?php echo htmlspecialchars($item['prod_image'] ?: 'default.jpg'); ?>" 
                         style="width: 70px; height: 70px; object-fit: cover; border-radius: 12px; border: 1px solid var(--card-border);">
                    <div class='cart-item-info' style="flex: 1;">
                        <strong><?php echo htmlspecialchars($item['prod_name']); ?></strong> <br> 
                        <div style="display: flex; align-items: center; gap: 10px; margin-top: 8px;">
                            <a href="?action=dec&item_id=<?php echo $item['cart_item_id']; ?>" style="margin-top:0; padding: 2px 10px; background: #e2e8f0; border-radius: 4px; color: #475569; font-weight: bold; text-decoration: none;">-</a>
                            <span style='font-size:1rem; font-weight: 600;'>Qty: <?php echo $item['quantity']; ?></span>
                            <?php if ($item['quantity'] < $item['prod_qty']): ?>
                                <a href="?action=inc&item_id=<?php echo $item['cart_item_id']; ?>" style="margin-top:0; padding: 2px 10px; background: #e2e8f0; border-radius: 4px; color: #475569; font-weight: bold; text-decoration: none;">+</a>
                            <?php else: ?>
                                <span style="margin-top:0; padding: 2px 10px; background: #f1f5f9; border-radius: 4px; color: #cbd5e1; font-weight: bold; cursor: not-allowed;" title="Max stock reached">+</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class='cart-item-price'>
                        <span>RM <?php echo number_format(($item['prod_sale_price'] ?? 0) * ($item['quantity'] ?? 1), 2); ?></span>
                        <a href="?remove_id=<?php echo $item['cart_item_id']; ?>" class="btn-remove">Remove</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="cart-total">
        Total: RM <?php echo number_format($total_price, 2); ?>
    </div>

    <div style="display: flex; gap: 16px; margin-top: 32px;">
        <a href="product page.php" style="flex: 1; text-align: center; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0;">Back to Shop</a>
        
        <?php if (!empty($cart_items)): ?>
            <a href="address page.php" style="flex: 1; text-align: center; padding: 12px; background: var(--accent); color: white; border-radius: 8px; margin: 0;">Proceed to Checkout</a>
        <?php else: ?>
            <span style="flex: 1; text-align: center; padding: 12px; background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.3); border-radius: 8px; margin: 0; cursor: not-allowed;">Proceed to Checkout</span>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
