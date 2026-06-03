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
    $is_rent = isset($_POST['type']) && $_POST['type'] === 'rent';
    $rent_product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $rent_days = isset($_POST['days']) ? intval($_POST['days']) : 1;
    
    if ($is_rent && $rent_product_id > 0) {
        $query = $conn->prepare("SELECT prod_rental_price, prod_id FROM products WHERE prod_id = ?");
        if ($query) {
            $query->bind_param("i", $rent_product_id);
            $query->execute();
            $result = $query->get_result();
            if ($row = $result->fetch_assoc()) {
                $total_price = $row['prod_rental_price'] * $rent_days;
                $cart_items[] = [
                    'prod_id' => $row['prod_id'],
                    'prod_sale_price' => $row['prod_rental_price'],
                    'quantity' => $rent_days
                ];
            }
            $query->close();
        }
    } else {
        $query = $conn->prepare("
            SELECT p.prod_sale_price, p.prod_id, ci.quantity as ci_quantity
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
                    $qty = isset($row['ci_quantity']) ? $row['ci_quantity'] : 1;
                    $total_price += ($row['prod_sale_price'] * $qty);
                    // Use quantity 1 for cart items buy
                    $cart_items[] = [
                        'prod_id' => $row['prod_id'],
                        'prod_sale_price' => $row['prod_sale_price'],
                        'quantity' => $qty
                    ];
                }
            }
            $query->close();
        }
    }
            
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
            $addr = $conn->prepare("INSERT INTO addresses (cust_id, city, state, postcode) VALUES (?, ?, ?, ?)");
            if ($addr) {
                $addr->bind_param("isss", $cust_id, $combined_city, $state, $postcode);
                $addr->execute();
                $addr_id = $conn->insert_id;
                $addr->close();
            }

            if ($is_rent) {
                // 2a. Process Rental
                $start_date = date('Y-m-d');
                $end_date = date('Y-m-d', strtotime("+$rent_days days"));
                
                $rent = $conn->prepare("INSERT INTO rentals (cust_id, address_id, start_date, end_date, status, total_amount) VALUES (?, ?, ?, ?, 'active', ?)");
                if ($rent) {
                    $rent->bind_param("iissd", $cust_id, $addr_id, $start_date, $end_date, $total_price);
                    if ($rent->execute()) {
                        $rental_id = $conn->insert_id;
                        $rent->close();
                        
                        $ri = $conn->prepare("INSERT INTO rental_items (rental_id, prod_id, rental_qty, rental_rate, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
                        if ($ri) {
                            foreach ($cart_items as $item) {
                                $ri->bind_param("iiidss", $rental_id, $item['prod_id'], $item['quantity'], $item['prod_sale_price'], $start_date, $end_date);
                                $ri->execute();
                            }
                            $ri->close();
                        }
                    } else {
                        $rent->close();
                    }
                }
            } else {
                // 2b. Process Order 
                $ord = $conn->prepare("INSERT INTO orders (cust_id, address_id, total_amount, status) VALUES (?, ?, ?, 'pending')");
                if ($ord) {
                    $ord->bind_param("iid", $cust_id, $addr_id, $total_price);
                    if ($ord->execute()) {
                        $order_id = $conn->insert_id;
                        $ord->close();
                        
                        // Do order items
                        $oi = $conn->prepare("INSERT INTO order_items (order_id, prod_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                        if ($oi) {
                            foreach ($cart_items as $item) {
                                $oi->bind_param("iiid", $order_id, $item['prod_id'], $item['quantity'], $item['prod_sale_price']);
                                $oi->execute();
                            }
                            $oi->close();
                        }
                    } else {
                        $ord->close();
                    }
                }
            }
        } catch (mysqli_sql_exception $e) {
            $db_error = "Database Error generating order or rental: " . $e->getMessage();
        }

        if (!$is_rent) {
            // 3. ALWAYS Clear Cart if it was a cart checkout
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
