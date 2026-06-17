<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit;
}
$cust_id = $_SESSION['cust_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prod_id'])) {
    $prod_id = intval($_POST['prod_id']);
    $type = $_POST['type'] ?? 'buy';
    $start_date = $_POST['start_date'] ?? null;
    $end_date = $_POST['end_date'] ?? null;
    
    if (!isset($conn) || $conn->connect_error) {
        $redirect = ($type === 'rent') ? "rent page.php" : "product page.php";
        header("Location: $redirect?error=db");
        exit;
    }
    
    // Check if cart exists for user
    $cart_query = $conn->prepare("SELECT cart_id FROM cart WHERE cust_id = ?");
    $cart_query->bind_param("i", $cust_id);
    $cart_query->execute();
    $result = $cart_query->get_result();
    
    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['cart_id'];
    } else {
        $create_cart = $conn->prepare("INSERT INTO cart (cust_id) VALUES (?)");
        $create_cart->bind_param("i", $cust_id);
        $create_cart->execute();
        $cart_id = $create_cart->insert_id;
        $create_cart->close();
    }
    $cart_query->close();
    
    // For Buy items, check stock
    if ($type === 'buy') {
        $stock_query = $conn->prepare("SELECT prod_sale_qty, prod_name FROM products WHERE prod_id = ?");
        $stock_query->bind_param("i", $prod_id);
        $stock_query->execute();
        $stock_res = $stock_query->get_result()->fetch_assoc();
        $available_stock = $stock_res['prod_sale_qty'] ?? 0;
        $prod_name = $stock_res['prod_name'] ?? 'Product';
        $stock_query->close();

        $check_item = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND prod_id = ? AND start_date IS NULL");
        $check_item->bind_param("ii", $cart_id, $prod_id);
        $check_item->execute();
        $item_result = $check_item->get_result();

        if ($item_result->num_rows > 0) {
            $item = $item_result->fetch_assoc();
            $new_qty = $item['quantity'] + 1;
            if ($new_qty > $available_stock) {
                header("Location: product page.php?error=out_of_stock&name=" . urlencode($prod_name));
                exit;
            }
            $update_item = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
            $update_item->bind_param("ii", $new_qty, $item['cart_item_id']);
            $update_item->execute();
            $update_item->close();
        } else {
            if ($available_stock < 1) {
                header("Location: product page.php?error=out_of_stock&name=" . urlencode($prod_name));
                exit;
            }
            $insert_item = $conn->prepare("INSERT INTO cart_items (cart_id, prod_id, quantity) VALUES (?, ?, 1)");
            $insert_item->bind_param("ii", $cart_id, $prod_id);
            $insert_item->execute();
            $insert_item->close();
        }
        $check_item->close();
        header("Location: product page.php?added=" . urlencode($prod_id));
        exit;
    } else {
        // For Rent items, ALWAYS add as separate item because dates might differ
        // Check availability (optional but good)
        $insert_item = $conn->prepare("INSERT INTO cart_items (cart_id, prod_id, quantity, start_date, end_date) VALUES (?, ?, 1, ?, ?)");
        $insert_item->bind_param("iiss", $cart_id, $prod_id, $start_date, $end_date);
        $insert_item->execute();
        $insert_item->close();
        
        header("Location: rent page.php?added=" . urlencode($prod_id));
        exit;
    }
} else {
    header("Location: product page.php");
    exit;
}
?>
