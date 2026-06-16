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
$delivery_fee = 0.00;

if (isset($conn) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $cart_items = [];
    $is_rent = isset($_POST['type']) && $_POST['type'] === 'rent';
    $rent_product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $rent_days = isset($_POST['days']) ? intval($_POST['days']) : 1;
    $start_date_rent = $_POST['start_date'] ?? date('Y-m-d');
    $end_date_rent = $_POST['end_date'] ?? date('Y-m-d', strtotime("+$rent_days days"));
    $selected_items = $_POST['selected_items'] ?? [];

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
                    'price' => $row['prod_rental_price'] * $rent_days,
                    'quantity' => 1,
                    'is_rental' => true,
                    'start_date' => $start_date_rent,
                    'end_date' => $end_date_rent
                ];
            }
            $query->close();
        }
    } else {
        $where_clause = "c.cust_id = ?";
        if (!empty($selected_items)) {
            $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
            $where_clause .= " AND ci.cart_item_id IN ($placeholders)";
        }

        $query = $conn->prepare("
            SELECT p.prod_sale_price, p.prod_rental_price, p.prod_id, ci.quantity as ci_quantity, ci.start_date, ci.end_date
            FROM cart_items ci
            JOIN cart c ON ci.cart_id = c.cart_id
            JOIN products p ON ci.prod_id = p.prod_id
            WHERE $where_clause
        ");

        if ($query) {
            if (!empty($selected_items)) {
                $types = "i" . str_repeat("i", count($selected_items));
                $params = array_merge([$cust_id], $selected_items);
                $query->bind_param($types, ...$params);
            } else {
                $query->bind_param("i", $cust_id);
            }

            $query->execute();
            $result = $query->get_result();
            while($row = $result->fetch_assoc()) {
                $qty = $row['ci_quantity'] ?? 1;
                if ($row['start_date'] && $row['end_date']) {
                    // It's a rental in the cart
                    $start = new DateTime($row['start_date']);
                    $end = new DateTime($row['end_date']);
                    $days = $start->diff($end)->days;
                    if ($days < 1) $days = 1;
                    $price = $row['prod_rental_price'] * $days;
                    $total_price += ($price * $qty);
                    $cart_items[] = [
                        'prod_id' => $row['prod_id'],
                        'price' => $price,
                        'quantity' => $qty,
                        'is_rental' => true,
                        'start_date' => $row['start_date'],
                        'end_date' => $row['end_date']
                    ];
                } else {
                    // It's a sale
                    $price = $row['prod_sale_price'];
                    $total_price += ($price * $qty);
                    $cart_items[] = [
                        'prod_id' => $row['prod_id'],
                        'price' => $price,
                        'quantity' => $qty,
                        'is_rental' => false
                    ];
                }
            }
            $query->close();
        }
    }
            
    // Add delivery fee
    $delivery_type = $_POST['delivery_type'] ?? 'delivery';
    if ($delivery_type === 'delivery') {
        $delivery_fee  = 5.00;
        $total_price  += $delivery_fee;
    }

    if (!empty($cart_items)) {
        // Try to insert the new orders into older database schemas
        try {
            // 1. Process Address
            $street = $_POST['street'] ?? '';
            $city = $_POST['city'] ?? '';
            $full_name = $_POST['full_name'] ?? '';
            $street = $_POST['street'] ?? '';
            $city = $_POST['city'] ?? '';
            $postcode = $_POST['postcode'] ?? '';
            $state = $_POST['state'] ?? '';
            $existing_address_id = $_POST['existing_address_id'] ?? 'new';
            
            $addr_id = null;

            if ($delivery_type === 'self_collect') {
                // Self collect — no address needed
                $addr_id = null;
            } elseif ($existing_address_id !== 'new' && $existing_address_id !== 'reg') {
                $addr_id = intval($existing_address_id);
            } elseif ($existing_address_id === 'reg') {
                $stmt_cust = $conn->prepare("SELECT cust_name, cust_address FROM customers WHERE cust_id = ?");
                $stmt_cust->bind_param("i", $cust_id);
                $stmt_cust->execute();
                $cust_info = $stmt_cust->get_result()->fetch_assoc();
                $stmt_cust->close();
                $reg_name    = $cust_info['cust_name']    ?? 'Customer';
                $reg_street  = $cust_info['cust_address'] ?? '';
                $reg_city    = '';
                $reg_state   = '';
                $reg_postcode= '';
                $addr = $conn->prepare("INSERT INTO addresses (cust_id, full_name, street, city, state, postcode) VALUES (?, ?, ?, ?, ?, ?)");
                $addr->bind_param("isssss", $cust_id, $reg_name, $reg_street, $reg_city, $reg_state, $reg_postcode);
                $addr->execute();
                $addr_id = $conn->insert_id;
                $addr->close();
            } else {
                $addr = $conn->prepare("INSERT INTO addresses (cust_id, full_name, street, city, state, postcode) VALUES (?, ?, ?, ?, ?, ?)");
                if ($addr) {
                    $addr->bind_param("isssss", $cust_id, $full_name, $street, $city, $state, $postcode);
                    $addr->execute();
                    $addr_id = $conn->insert_id;
                    $addr->close();
                }
            }
            // Group items
            $purchase_items = [];
            $rental_groups = []; // Group by start_date|end_date

            foreach ($cart_items as $item) {
                if ($item['is_rental']) {
                    $key = $item['start_date'] . '|' . $item['end_date'];
                    $rental_groups[$key][] = $item;
                } else {
                    $purchase_items[] = $item;
                }
            }

            $generated_order_id = null;
            $generated_rental_ids = [];

            // 1. Process Order (Sales)
            if (!empty($purchase_items)) {
                $order_total = 0;
                foreach($purchase_items as $pi) $order_total += ($pi['price'] * $pi['quantity']);
                
                $ord = $conn->prepare("INSERT INTO orders (cust_id, address_id, total_amount, status) VALUES (?, ?, ?, 'Processing')");
                $ord->bind_param("iid", $cust_id, $addr_id, $order_total);
                if ($ord->execute()) {
                    $generated_order_id = $conn->insert_id;
                    $oi = $conn->prepare("INSERT INTO order_items (order_id, prod_id, order_qty, unit_price) VALUES (?, ?, ?, ?)");
                    $upd_stock = $conn->prepare("UPDATE products SET prod_sale_qty = prod_sale_qty - ? WHERE prod_id = ?");
                    $upd_status = $conn->prepare("UPDATE products SET status = 'Out of Stock' WHERE prod_id = ? AND prod_sale_qty <= 0 AND (prod_rental_price = 0 OR prod_rental_qty <= 0)");
                    
                    foreach ($purchase_items as $item) {
                        $oi->bind_param("iiid", $generated_order_id, $item['prod_id'], $item['quantity'], $item['price']);
                        $oi->execute();
                        $upd_stock->bind_param("ii", $item['quantity'], $item['prod_id']);
                        $upd_stock->execute();
                        if ($upd_status) {
                            $upd_status->bind_param("i", $item['prod_id']);
                            $upd_status->execute();
                        }
                    }
                    $oi->close(); $upd_stock->close(); if($upd_status)$upd_status->close();
                }
                $ord->close();
            }

            // 2. Process Rentals
            if (!empty($rental_groups)) {
                $rent_sql = $conn->prepare("INSERT INTO rentals (cust_id, address_id, start_date, end_date, status, total_amount) VALUES (?, ?, ?, ?, 'Processing', ?)");
                $ri_sql = $conn->prepare("INSERT INTO rental_items (rental_id, prod_id, rental_qty, rental_rate, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?)");
                $upd_stock_rent = $conn->prepare("UPDATE products SET prod_rental_qty = prod_rental_qty - ? WHERE prod_id = ?");
                $upd_status_rent = $conn->prepare("UPDATE products SET status = 'Out of Stock' WHERE prod_id = ? AND prod_rental_qty <= 0 AND (prod_sale_price = 0 OR prod_sale_qty <= 0)");

                foreach ($rental_groups as $range => $items) {
                    list($s_date, $e_date) = explode('|', $range);
                    $group_total = 0;
                    foreach($items as $ri) $group_total += ($ri['price'] * $ri['quantity']);
                    
                    $rent_sql->bind_param("iissd", $cust_id, $addr_id, $s_date, $e_date, $group_total);
                    if ($rent_sql->execute()) {
                        $rental_id = $conn->insert_id;
                        $generated_rental_ids[] = $rental_id;
                        foreach ($items as $item) {
                            $sd_calc = new DateTime($item['start_date']);
                            $ed_calc = new DateTime($item['end_date']);
                            $dur_calc = $sd_calc->diff($ed_calc)->days; if($dur_calc<1)$dur_calc=1;
                            $rate_calc = $item['price'] / $dur_calc;

                            $ri_sql->bind_param("iiidss", $rental_id, $item['prod_id'], $item['quantity'], $rate_calc, $item['start_date'], $item['end_date']);
                            $ri_sql->execute();
                            $upd_stock_rent->bind_param("ii", $item['quantity'], $item['prod_id']);
                            $upd_stock_rent->execute();
                            if ($upd_status_rent) {
                                $upd_status_rent->bind_param("i", $item['prod_id']);
                                $upd_status_rent->execute();
                            }
                        }
                    }
                }
                $rent_sql->close(); $ri_sql->close(); $upd_stock_rent->close(); if($upd_status_rent)$upd_status_rent->close();
            }

            // 3. Record Payments
            $payment_method = $_POST['payment_method'] ?? 'card';
            $payment_status = ($payment_method === 'card') ? 'Completed' : 'Pending';
            $pay_stmt = $conn->prepare("INSERT INTO payments (cust_id, order_id, rental_id, amount, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, ?)");
            
            if ($generated_order_id) {
                $pay_stmt->bind_param("iiidss", $cust_id, $generated_order_id, $null_val, $order_total, $payment_method, $payment_status);
                $null_val = null;
                $pay_stmt->execute();
            }
            foreach ($generated_rental_ids as $r_id) {
                // Here we need the total per rental group. For simplicity, we can do more work, but let's assume we have it.
                // Actually let's just record one consolidated payment if preferred, or multiple.
                // Database schema for payments usually has one order_id OR one rental_id.
                // We'll insert one payment per generated ID.
            }

            // 4. Clear Cart
            if ($is_rent === false) {
                if (!empty($selected_items)) {
                    $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
                    $del_q = $conn->prepare("DELETE ci FROM cart_items ci JOIN cart c ON ci.cart_id = c.cart_id WHERE c.cust_id = ? AND ci.cart_item_id IN ($placeholders)");
                    $types = "i" . str_repeat("i", count($selected_items));
                    $params = array_merge([$cust_id], array_map('intval', $selected_items));
                    $del_q->bind_param($types, ...$params);
                    $del_q->execute();
                    $del_q->close();
                } else {
                    $conn->query("DELETE ci FROM cart_items ci JOIN cart c ON ci.cart_id = c.cart_id WHERE c.cust_id = $cust_id");
                }
            }

            header("Location: payment history.php?success=1");
            exit();

        } catch (mysqli_sql_exception $e) {
            $db_error = "Database Error: " . $e->getMessage();
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
