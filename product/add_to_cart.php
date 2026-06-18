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
    
    // Check if the user already has an active cart record
    $findCart = $conn->prepare("SELECT cart_id FROM cart WHERE cust_id = ?");
    $findCart->bind_param("i", $cust_id);
    $findCart->execute();
    $cartRes = $findCart->get_result();
    
    if ($cartRes->num_rows > 0) {
        $cartData = $cartRes->fetch_assoc();
        $cart_id = $cartData['cart_id'];
    } else {
        // No cart? Let's make one for them real quick
        $newCart = $conn->prepare("INSERT INTO cart (cust_id) VALUES (?)");
        $newCart->bind_param("i", $cust_id);
        $newCart->execute();
        $cart_id = $newCart->insert_id;
        $newCart->close();
    }
    $findCart->close();
    
    // Logic for standard 'Buy' items
    if ($type === 'buy') {
        $checkInventory = $conn->prepare("SELECT prod_sale_qty, prod_name FROM products WHERE prod_id = ?");
        $checkInventory->bind_param("i", $prod_id);
        $checkInventory->execute();
        $invRow = $checkInventory->get_result()->fetch_assoc();
        
        $stockLeft = $invRow ? (int)$invRow['prod_sale_qty'] : 0;
        $itemName = $invRow ? $invRow['prod_name'] : 'Product';
        $checkInventory->close();

        // See if this item is already in their cart (non-rentals only)
        $itemLookup = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND prod_id = ? AND start_date IS NULL");
        $itemLookup->bind_param("ii", $cart_id, $prod_id);
        $itemLookup->execute();
        $lookupRes = $itemLookup->get_result();

        if ($lookupRes->num_rows > 0) {
            $existingItem = $lookupRes->fetch_assoc();
            $updatedQty = (int)$existingItem['quantity'] + 1;
            
            if ($updatedQty > $stockLeft) {
                // Not enough stock for another one
                header("Location: product page.php?error=out_of_stock&name=" . urlencode($itemName));
                exit;
            }
            $updateStmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_item_id = ?");
            $updateStmt->bind_param("ii", $updatedQty, $existingItem['cart_item_id']);
            $updateStmt->execute();
            $updateStmt->close();
        } else {
            if ($stockLeft < 1) {
                header("Location: product page.php?error=out_of_stock&name=" . urlencode($itemName));
                exit;
            }
            // Add a fresh entry
            $addStmt = $conn->prepare("INSERT INTO cart_items (cart_id, prod_id, quantity) VALUES (?, ?, 1)");
            $addStmt->bind_param("ii", $cart_id, $prod_id);
            $addStmt->execute();
            $addStmt->close();
        }
        $itemLookup->close();
        header("Location: product page.php?added=" . urlencode($prod_id));
        exit;
    } else {
        // Rental items — we need to be careful not to over-book even at the cart stage
        $getRentalStock = $conn->prepare("SELECT prod_rental_qty, prod_name FROM products WHERE prod_id = ?");
        $getRentalStock->bind_param("i", $prod_id);
        $getRentalStock->execute();
        $rRow = $getRentalStock->get_result()->fetch_assoc();
        
        $rentalMax = (int)($rRow['prod_rental_qty'] ?? 0);
        $gearName = $rRow['prod_name'] ?? 'Instrument';
        $getRentalStock->close();

        // 1. Check DB for already confirmed bookings
        $checkDB = $conn->prepare("
            SELECT SUM(ri.rental_qty) as used
            FROM rental_items ri 
            JOIN rentals r ON ri.rental_id = r.rental_id 
            WHERE ri.prod_id = ? 
            AND r.status NOT IN ('Cancelled', 'Returned')
            AND (
                (? BETWEEN r.start_date AND DATE_ADD(r.end_date, INTERVAL 2 DAY)) OR
                (? BETWEEN r.start_date AND DATE_ADD(r.end_date, INTERVAL 2 DAY)) OR
                (r.start_date BETWEEN ? AND ?)
            )
        ");
        $checkDB->bind_param("issss", $prod_id, $start_date, $end_date, $start_date, $end_date);
        $checkDB->execute();
        $dbCount = $checkDB->get_result()->fetch_assoc()['used'] ?? 0;
        $checkDB->close();

        // 2. Check current cart items that might overlap
        $checkCartItems = $conn->prepare("
            SELECT SUM(quantity) as in_cart
            FROM cart_items
            WHERE cart_id = ? AND prod_id = ?
            AND (
                (? BETWEEN start_date AND end_date) OR
                (? BETWEEN start_date AND end_date) OR
                (start_date BETWEEN ? AND ?)
            )
        ");
        $checkCartItems->bind_param("iissss", $cart_id, $prod_id, $start_date, $end_date, $start_date, $end_date);
        $checkCartItems->execute();
        $cartOverlapCount = $checkCartItems->get_result()->fetch_assoc()['in_cart'] ?? 0;
        $checkCartItems->close();

        if (($dbCount + $cartOverlapCount + 1) > $rentalMax) {
            // No more room! Either fully booked or already in your cart
            header("Location: rent page.php?error=already_booked&name=" . urlencode($gearName));
            exit;
        }

        // Check if the EXACT same product and date range is already in the cart
        $findExact = $conn->prepare("SELECT cart_item_id, quantity FROM cart_items WHERE cart_id = ? AND prod_id = ? AND start_date = ? AND end_date = ?");
        $findExact->bind_param("iiss", $cart_id, $prod_id, $start_date, $end_date);
        $findExact->execute();
        $exactRes = $findExact->get_result();

        if ($exactRes->num_rows > 0) {
            // Already in cart for these dates - block it as requested
            header("Location: rent page.php?error=already_in_cart&name=" . urlencode($gearName));
            exit;
        } else {
            // Fresh entry for this specific range
            $rentStmt = $conn->prepare("INSERT INTO cart_items (cart_id, prod_id, quantity, start_date, end_date) VALUES (?, ?, 1, ?, ?)");
            $rentStmt->bind_param("iiss", $cart_id, $prod_id, $start_date, $end_date);
            $rentStmt->execute();
            $rentStmt->close();
        }
        $findExact->close();
        
        header("Location: rent page.php?added=" . urlencode($prod_id));
        exit;
    }
} else {
    // If they got here without a POST, just send them back home
    header("Location: product page.php");
    exit;
}
?>
