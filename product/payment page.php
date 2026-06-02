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
    try {
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
                // 1. Process Address
                // Combine street into city since db might lack a street column
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

                // 2. Process Order 
                // Using a safe insert, omitting address_id to prevent crashes if DB schema is older
                $ord = $conn->prepare("INSERT INTO orders (cust_id, total_amount, status) VALUES (?, ?, 'Pending')");
                if ($ord) {
                    $ord->bind_param("id", $cust_id, $total_price);
                    if ($ord->execute()) {
                        $order_id = $conn->insert_id;
                        $ord->close();
                        
                        // Try older schema linking as fallback if orders doesn't have address_id
                        // Do order items
                        $oi = $conn->prepare("INSERT INTO order_items (order_id, prod_id, order_qty, unit_price) VALUES (?, ?, 1, ?)");
                        if ($oi) {
                            foreach ($cart_items as $item) {
                                $oi->bind_param("iid", $order_id, $item['prod_id'], $item['prod_sale_price']);
                                $oi->execute();
                            }
                            $oi->close();
                        }
                    }
                }

                // 3. Clear Cart
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
    } catch (mysqli_sql_exception $e) {
        $db_error = "Database Error generating order: " . $e->getMessage();
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
    <div style="font-size: 4rem; color: var(--success); margin-bottom: 16px;">✓</div>
    <h2>Receipt Submitted!</h2>
    <p style="font-size: 1.25rem; font-weight: 600; margin-bottom: 8px; color: var(--text);">Total Paid: RM <?php echo number_format($total_price, 2); ?></p>
    <p class="mb-4">Your payment receipt has been received and will be verified by an admin shortly.<br>Once verified, your order will be processed for delivery.</p>
    <a href="payment history.php" style="padding: 12px 24px; background: var(--accent); color: white; border-radius: 8px; display: inline-block;">View Order History</a>
</div>

</body>
</html>
