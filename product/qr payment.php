<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

$delivery_type   = $_GET['delivery_type'] ?? 'delivery'; // 'delivery' or 'self_collect'
$is_delivery     = $delivery_type === 'delivery';
$delivery_fee    = $is_delivery ? 5.00 : 0.00;

// ----- Address details from GET -----
$full_name   = $_GET['full_name']   ?? '';
$street      = $_GET['street']      ?? '';
$city        = $_GET['city']        ?? '';
$postcode    = $_GET['postcode']    ?? '';
$state       = $_GET['state']       ?? '';
$existing_addr_id = $_GET['existing_address_id'] ?? 'new';

// If using a saved/reg address, fetch its details for display
$display_name = $full_name; $display_street = $street;
$display_city = $city; $display_state = $state; $display_postcode = $postcode;

if ($is_delivery && $existing_addr_id !== 'new' && isset($conn)) {
    if ($existing_addr_id === 'reg') {
        $s = $conn->prepare("SELECT cust_name, cust_street, cust_city, cust_state, cust_postcode FROM customers WHERE cust_id = ?");
        $s->bind_param("i", $cust_id); $s->execute();
        $r = $s->get_result()->fetch_assoc(); $s->close();
        if ($r) {
            $display_name=$r['cust_name']; $display_street=$r['cust_street'];
            $display_city=$r['cust_city']; $display_state=$r['cust_state']; $display_postcode=$r['cust_postcode'];
        }
    } else {
        $s = $conn->prepare("SELECT full_name, street, city, state, postcode FROM addresses WHERE address_id = ?");
        $addr_int = intval($existing_addr_id);
        $s->bind_param("i", $addr_int); $s->execute();
        $r = $s->get_result()->fetch_assoc(); $s->close();
        if ($r) {
            $display_name=$r['full_name']; $display_street=$r['street'];
            $display_city=$r['city']; $display_state=$r['state']; $display_postcode=$r['postcode'];
        }
    }
}

// ----- Price calculations -----
$subtotal    = floatval($_GET['subtotal'] ?? $_GET['amount'] ?? 0);
$is_rent     = (isset($_GET['type']) && $_GET['type'] === 'rent');
$days        = intval($_GET['days'] ?? 1);
$product_id  = intval($_GET['product_id'] ?? 0);
$start_date  = $_GET['start_date'] ?? '';
$end_date    = $_GET['end_date']   ?? '';

// For buy (cart), recalculate subtotal if not passed
if (!$is_rent && $subtotal == 0 && isset($conn)) {
    $q = $conn->prepare("SELECT SUM(p.prod_sale_price * ci.quantity) as total FROM cart_items ci JOIN cart c ON ci.cart_id=c.cart_id JOIN products p ON ci.prod_id=p.prod_id WHERE c.cust_id=?");
    $q->bind_param("i", $cust_id); $q->execute();
    $r = $q->get_result()->fetch_assoc(); $q->close();
    $subtotal = floatval($r['total'] ?? 0);
}

// Get product name for rent
$prod_display = '';
if ($is_rent && $product_id > 0 && isset($conn)) {
    $s = $conn->prepare("SELECT prod_name, prod_rental_price FROM products WHERE prod_id = ?");
    $s->bind_param("i", $product_id); $s->execute();
    $r = $s->get_result()->fetch_assoc(); $s->close();
    if ($r) { $prod_display = $r['prod_name']; }
}

$grand_total = $subtotal + $delivery_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Summary</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .bill-card { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px; }
        .bill-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; font-size: 0.95rem; }
        .bill-row + .bill-row { border-top: 1px solid #e2e8f0; }
        .bill-row.total { border-top: 2px solid #cbd5e1; margin-top: 4px; padding-top: 14px; font-size: 1.1rem; font-weight: 700; }
        .bill-row .label { color: #64748b; }
        .bill-row .value { font-weight: 600; }
        .addr-card { background: #f0f9ff; border: 1.5px solid #bae6fd; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; }
        .addr-card .addr-title { font-weight: 700; color: #0369a1; margin-bottom: 8px; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.04em; }
        .delivery-badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:0.78rem; font-weight:600; }
    </style>
</head>
<body>

<div class="container text-center" style="max-width: 520px;">
    <h2>Payment Summary</h2>
    <p style="color: var(--text-secondary); margin-bottom: 24px;">Please review your order before completing payment</p>

    <!-- BILL BREAKDOWN -->
    <div class="bill-card" style="text-align: left;">
        <div style="font-weight: 700; font-size: 1rem; margin-bottom: 12px; color: var(--text-primary);">🧾 Order Details</div>

        <?php if ($is_rent && $prod_display): ?>
        <div class="bill-row">
            <span class="label">Item</span>
            <span class="value"><?php echo htmlspecialchars($prod_display); ?> <span style="font-size:0.78rem; background:#7c3aed; color:white; border-radius:4px; padding:1px 6px;">Rental</span></span>
        </div>
        <div class="bill-row">
            <span class="label">Rental Period</span>
            <span class="value"><?php echo htmlspecialchars($start_date); ?> → <?php echo htmlspecialchars($end_date); ?> (<?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?>)</span>
        </div>
        <?php else: ?>
        <div class="bill-row">
            <span class="label">Order Type</span>
            <span class="value"><span style="font-size:0.78rem; background:#2563eb; color:white; border-radius:4px; padding:1px 6px;">Purchase</span></span>
        </div>
        <?php endif; ?>

        <div class="bill-row">
            <span class="label">Subtotal</span>
            <span class="value">RM <?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="bill-row">
            <span class="label">Delivery</span>
            <span class="value" style="color: <?php echo $is_delivery ? '#ef4444' : '#10b981'; ?>">
                <?php if ($is_delivery): ?>
                    +RM <?php echo number_format($delivery_fee, 2); ?>
                    <span class="delivery-badge" style="background:#fef2f2;color:#ef4444;">🚚 Delivery</span>
                <?php else: ?>
                    Free
                    <span class="delivery-badge" style="background:#f0fdf4;color:#16a34a;">🏪 Self Collect</span>
                <?php endif; ?>
            </span>
        </div>
        <div class="bill-row total">
            <span>Total Payable</span>
            <span style="color: var(--success);">RM <?php echo number_format($grand_total, 2); ?></span>
        </div>
    </div>

    <!-- ADDRESS DETAILS (only for delivery) -->
    <?php if ($is_delivery && ($display_city || $display_street)): ?>
    <div class="addr-card" style="text-align: left;">
        <div class="addr-title">📍 Delivery Address</div>
        <div style="font-weight: 600;"><?php echo htmlspecialchars($display_name); ?></div>
        <div style="color: #475569; font-size: 0.92rem; margin-top: 4px;">
            <?php echo htmlspecialchars($display_street); ?><br>
            <?php echo htmlspecialchars($display_city); ?>, <?php echo htmlspecialchars($display_postcode); ?><br>
            <?php echo htmlspecialchars($display_state); ?>
        </div>
    </div>
    <?php elseif (!$is_delivery): ?>
    <div style="background:#f0fdf4; border:1.5px solid #bbf7d0; border-radius:12px; padding:20px; margin-bottom:24px; text-align:left;">
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:12px;">
            <span style="font-size:1.5rem;">🏪</span>
            <div style="font-weight:700; color:#166534; font-size:1rem;">Self Collect Location</div>
        </div>
        <div style="background:white; border-radius:10px; padding:12px 15px; border:1px solid #d1fae5; margin-bottom:12px;">
            <div style="font-weight:700; font-size:0.9rem; color:#1e293b;">📍 MMU Melaka Campus</div>
            <div style="font-size:0.85rem; color:#475569; margin-top:2px; line-height:1.5;">
                Multimedia University, Jalan Ayer Keroh Lama,<br>
                Bukit Beruang, 75450 Melaka
            </div>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="https://www.google.com/maps/search/?api=1&query=Multimedia+University+Melaka" target="_blank" rel="noopener" 
               style="flex:1; text-align:center; padding:10px; border-radius:8px; text-decoration:none; font-weight:700; font-size:0.85rem; background:#4285f4; color:white; display:flex; align-items:center; justify-content:center; gap:5px;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/b/bd/Google_Maps_Logo_2020.svg/32px-Google_Maps_Logo_2020.svg.png" style="width:18px; height:18px;">
                View Location on Google Maps
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- QR Code -->
    <div style="background: white; padding: 24px; border-radius: 12px; display: inline-block; margin-bottom: 24px; border: 1px solid #e2e8f0;">
        <img src="qr_placeholder.png" alt="Scan to Pay" style="width: 180px; height: 180px; object-fit: contain;">
        <p style="color: #333; font-size: 14px; margin-top: 10px; font-weight: 500;">DuitNow / Touch 'n Go</p>
    </div>

    <form action="payment page.php" method="POST" enctype="multipart/form-data">
        <!-- Carry through all order details -->
        <?php if ($is_rent): ?>
            <input type="hidden" name="type"         value="rent">
            <input type="hidden" name="product_id"   value="<?php echo htmlspecialchars($product_id); ?>">
            <input type="hidden" name="start_date"   value="<?php echo htmlspecialchars($start_date); ?>">
            <input type="hidden" name="end_date"     value="<?php echo htmlspecialchars($end_date); ?>">
            <input type="hidden" name="days"         value="<?php echo htmlspecialchars($days); ?>">
        <?php endif; ?>
        <input type="hidden" name="delivery_type"    value="<?php echo htmlspecialchars($delivery_type); ?>">
        <input type="hidden" name="full_name"        value="<?php echo htmlspecialchars($display_name); ?>">
        <input type="hidden" name="street"           value="<?php echo htmlspecialchars($display_street); ?>">
        <input type="hidden" name="city"             value="<?php echo htmlspecialchars($display_city); ?>">
        <input type="hidden" name="postcode"         value="<?php echo htmlspecialchars($display_postcode); ?>">
        <input type="hidden" name="state"            value="<?php echo htmlspecialchars($display_state); ?>">
        <input type="hidden" name="existing_address_id" value="<?php echo htmlspecialchars($existing_addr_id); ?>">

        <!-- Receipt Upload -->
        <div style="text-align: left; margin-bottom: 20px; background:#f8fafc; border:1px solid #e2e8f0; padding:16px; border-radius:10px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-primary);">📎 Upload Payment Receipt *</label>
            <input type="file" name="receipt" accept="image/*,application/pdf" required
                   style="padding: 10px; width: 100%; box-sizing: border-box; background: white; border: 1px solid #e2e8f0; border-radius: 8px; color: #374151;">
        </div>

        <div style="display: flex; gap: 16px; margin-top: 8px;">
            <a href="address page.php" style="flex: 1; text-align: center; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0; display: inline-block;">Back</a>
            <button type="submit" style="flex: 1; margin: 0;">Confirm & Submit Receipt</button>
        </div>
    </form>
</div>

</body>
</html>
