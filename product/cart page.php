<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    $_SESSION['cust_id'] = 1; 
}
$cust_id = $_SESSION['cust_id'];

if (isset($_GET['remove_id'])) {
    $remove_id = intval($_GET['remove_id']);
    if (isset($conn) && !$conn->connect_error) {
        $del_query = $conn->prepare("
            DELETE ci FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            WHERE ci.id = ? AND c.user_id = ?
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
        SELECT ci.id, p.prod_name, p.prod_sale_price 
        FROM cart_items ci
        JOIN cart c ON ci.cart_id = c.id
        JOIN products p ON ci.instrument_id = p.prod_id
        WHERE c.user_id = ?
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
                    $total_price += $row['prod_sale_price'];
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
    <link rel="stylesheet" href="style.css">
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
                    <div class='cart-item-info'>
                        <strong><?php echo htmlspecialchars($item['prod_name']); ?></strong> <br> 
                        <span style='font-size:0.9em;color:var(--text-secondary);'>(Buy)</span>
                    </div>
                    <div class='cart-item-price'>
                        RM <?php echo number_format($item['prod_sale_price'] ?? 0, 2); ?>
                        <br>
                        <a href="?remove_id=<?php echo $item['id']; ?>" style="color: #fca5a5; font-size: 0.85em; text-decoration: none;">Cancel</a>
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
