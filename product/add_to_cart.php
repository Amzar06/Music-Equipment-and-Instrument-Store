<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    $_SESSION['cust_id'] = 1; 
}
$cust_id = $_SESSION['cust_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prod_id'])) {
    $prod_id = intval($_POST['prod_id']);
    
    // Check if connection exists
    if (!isset($conn) || $conn->connect_error) {
        header("Location: product page.php?error=db");
        exit;
    }
    
    // Check if cart exists for user
    $cart_query = $conn->prepare("SELECT id FROM cart WHERE user_id = ?");
    if (!$cart_query) {
        header("Location: product page.php?error=query");
        exit;
    }
    
    $cart_query->bind_param("i", $cust_id);
    $cart_query->execute();
    $result = $cart_query->get_result();
    
    if ($result->num_rows > 0) {
        $cart = $result->fetch_assoc();
        $cart_id = $cart['id'];
    } else {
        // Create new cart
        $create_cart = $conn->prepare("INSERT INTO cart (user_id) VALUES (?)");
        if (!$create_cart) {
            header("Location: product page.php?error=create");
            exit;
        }
        $create_cart->bind_param("i", $cust_id);
        $create_cart->execute();
        $cart_id = $create_cart->insert_id;
        $create_cart->close();
    }
    $cart_query->close();
    
    // Insert item into cart_items
    $insert_item = $conn->prepare("INSERT INTO cart_items (cart_id, instrument_id) VALUES (?, ?)");
    if (!$insert_item) {
        header("Location: product page.php?error=insert");
        exit;
    }
    $insert_item->bind_param("ii", $cart_id, $prod_id);
    $insert_item->execute();
    $insert_item->close();
    
    header("Location: product page.php?added=" . urlencode($prod_id));
    exit;
} else {
    header("Location: product page.php");
    exit;
}
?>
