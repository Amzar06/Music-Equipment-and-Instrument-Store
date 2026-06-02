<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

$total_price = 0.00;
$db_error = null;

if (isset($conn) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_items = [];
        $query = $conn->prepare("
            SELECT p.prod_sale_price, p.prod_id 
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.id
            JOIN products p ON ci.instrument_id = p.prod_id
            WHERE c.user_id = ?
        ");
        if ($query) {
            $query->bind_param("i", $cust_id);
            $query->execute();
            $result = $query->get_result();
            while($row = $result->fetch_assoc()) {
                if (isset($row['prod_sale_price'])) {
                    $total_price += $row['prod_sale_price'];
                    $cart_items[] = $row;
                }
            }
            $query->close();
            
            if (!empty($cart_items) && $total_price > 0) {
                
                // Try to insert the new orders into older database schemas
                try {
                    // 1. Process Address
                    $street = $_POST['street'] ?? '';
                    $city = $_POST['city'] ?? '';
                    $postcode = $_POST['postcode'] ?? '';
                    $state = $_POST['state'] ?? '';
                    $combined_city = trim($street . ', ' . $city, ', ');
                    
                    $addr_id = null;
                    $addr = $conn->prepare("INSERT INTO addresses (user_id, city, state, postcode) VALUES (?, ?, ?, ?)");
                    if ($addr) {
                        $addr->bind_param("isss", $cust_id, $combined_city, $state, $postcode);
                        $addr->execute();
                        $addr_id = $conn->insert_id;
                        $addr->close();
                    }

                    // 2. Process Order 
                    $ord = $conn->prepare("INSERT INTO orders (cust_id, total_amount, status) VALUES (?, ?, 'Pending')");
                    if ($ord) {
                        $ord->bind_param("id", $cust_id, $total_price);
                        if ($ord->execute()) {
                            $order_id = $conn->insert_id;
                            $ord->close();
                            
                            // Do order items
                            $oi = $conn->prepare("INSERT INTO order_items (order_id, prod_id, quantity, unit_price) VALUES (?, ?, 1, ?)");
                            if ($oi) {
                                foreach ($cart_items as $item) {
                                    $oi->bind_param("iid", $order_id, $item['prod_id'], $item['prod_sale_price']);
                                    $oi->execute();
                                }
                                $oi->close();
                            }
                        }
                    }
                } catch (mysqli_sql_exception $e) {
                    $db_error = "Database Error generating order: " . $e->getMessage();
                }

                // 3. ALWAYS Clear Cart regardless of whether the active DB schema supports full order logging
                $del_query = $conn->prepare("
                    DELETE ci FROM cart_items ci
                    JOIN cart c ON ci.cart_id = c.id
                    WHERE c.user_id = ?
                ");
                if ($del_query) {
                    $del_query->bind_param("i", $cust_id);
                    $del_query->execute();
                    $del_query->close();
                }
            }
        }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container text-center">
    <?php if ($db_error): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 16px; text-align: left;">
            <strong>Error:</strong> <?php echo htmlspecialchars($db_error); ?>
        </div>
    <?php endif; ?>
    
    <div style="font-size: 4rem; color: var(--success); margin-bottom: 16px;">✓</div>
    <h2>Receipt Submitted!</h2>
    <p class="mb-4">Your payment receipt has been received and will be verified by an admin shortly.<br>Once verified, your order will be processed for delivery.</p>
    <a href="payment history.php" style="padding: 12px 24px; background: var(--accent); color: white; border-radius: 8px; display: inline-block;">View Order History</a>
</div>

</body>
</html>
