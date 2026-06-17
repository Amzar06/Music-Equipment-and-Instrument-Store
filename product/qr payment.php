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
$delivery_fee    = $is_delivery ? 30.00 : 0.00;

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
        $s = $conn->prepare("SELECT cust_name, cust_address FROM customers WHERE cust_id = ?");
        $s->bind_param("i", $cust_id); $s->execute();
        $r = $s->get_result()->fetch_assoc(); $s->close();
        if ($r) {
            $display_name=$r['cust_name']; $display_street=$r['cust_address'];
            $display_city=''; $display_state=''; $display_postcode='';
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
$selected_items = $_GET['selected_items'] ?? [];

// For buy (cart) or mixed cart, recalculate subtotal if not a single-item rent
if (!$is_rent && $subtotal == 0 && isset($conn)) {
    $where_clause = "c.cust_id=?";
    if (!empty($selected_items)) {
        $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
        $where_clause .= " AND ci.cart_item_id IN ($placeholders)";
    }

    $q = $conn->prepare("
        SELECT p.prod_sale_price, p.prod_rental_price, ci.quantity, ci.start_date, ci.end_date 
        FROM cart_items ci 
        JOIN cart c ON ci.cart_id=c.cart_id 
        JOIN products p ON ci.prod_id=p.prod_id 
        WHERE $where_clause
    ");

    if (!empty($selected_items)) {
        $types = "i" . str_repeat("i", count($selected_items));
        $params = array_merge([$cust_id], $selected_items);
        $q->bind_param($types, ...$params);
    } else {
        $q->bind_param("i", $cust_id);
    }

    $q->execute();
    $res = $q->get_result();
    $total_calc = 0;
    while($row = $res->fetch_assoc()) {
        if ($row['start_date'] && $row['end_date']) {
            $s_calc = new DateTime($row['start_date']);
            $e_calc = new DateTime($row['end_date']);
            $d_calc = $s_calc->diff($e_calc)->days;
            if ($d_calc < 1) $d_calc = 1;
            $total_calc += ($row['prod_rental_price'] * $d_calc * $row['quantity']);
        } else {
            $total_calc += ($row['prod_sale_price'] * $row['quantity']);
        }
    }
    $q->close();
    $subtotal = $total_calc;
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
    <link rel="stylesheet" href="style.css?v=3.0">
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
<!-- Bootstrap for Navbar -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d3b8e; padding: 12px 0;">
    <div class="container-fluid px-5">
        <a class="navbar-brand" href="../customer/home_page.php" style="font-weight: 500;">Musical Instrument Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLogged">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navLogged">
            <ul class="navbar-nav ms-auto" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link" href="../customer/home_page.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="product page.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="payment history.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link" href="../customer/user_profile_page.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="../customer/logout_page.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<body style="padding-top: 20px;">

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
                View Location on Google Maps
            </a>
        </div>
    </div>
    <?php endif; ?>

<?php
$payment_method = $_GET['payment_method'] ?? 'card';
?>

    <!-- Payment View Section -->
    <div style="margin-top: 8px;">
        <?php if ($payment_method === 'card'): ?>
            <!-- Card Form -->
            <div style="text-align: left; background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">💳 Card Information</div>
                    <div id="cardBrandDisplay" style="display: flex; align-items: center; gap: 8px;">
                        <span id="cardTypeLabel" style="font-size: 0.75rem; font-weight: 700; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; display: none;"></span>
                        <div id="brandLogos" style="display: flex; gap: 4px;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/48px-Visa_Inc._logo.svg.png" id="logoVisa" style="height: 15px; opacity: 0.3;">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/48px-Mastercard-logo.svg.png" id="logoMaster" style="height: 15px; opacity: 0.3;">
                        </div>
                    </div>
                </div>
                
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Cardholder Name</label>
                    <input type="text" name="card_name" id="cardName" placeholder="John Doe" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Card Number</label>
                    <div style="position: relative;">
                        <input type="text" name="card_number" id="cardNumber" placeholder="0000 0000 0000 0000" maxlength="19" required style="width: 100%; padding: 12px; padding-right: 45px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                        <span style="position: absolute; right: 12px; top: 12px; color: #94a3b8;" id="cardLock">🔒</span>
                    </div>
                </div>

                <div style="display: flex; gap: 16px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Expiry Date</label>
                        <input type="text" name="card_expiry" id="cardExpiry" placeholder="MM/YY" maxlength="5" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">CVV</label>
                        <input type="text" name="card_cvv" id="cardCvv" placeholder="***" maxlength="3" required style="width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.95rem;">
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- QR Code Section -->
            <div style="background: white; padding: 20px; border-radius: 16px; display: inline-block; margin-bottom: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);">
                <div style="margin-bottom: 12px; font-weight: 700; color: #1e293b; font-size: 1.1rem;">
                    <?php echo $payment_method === 'tng' ? '📱 Touch \'n Go eWallet' : '🏦 DuitNow QR'; ?>
                </div>
                <img src="../uploads/duitnow_qr.jpg" alt="Scan to Pay" style="width: 220px; height: auto; object-fit: contain; border-radius: 12px;">
                <p style="color: #64748b; font-size: 0.85rem; margin-top: 14px; line-height: 1.5; font-weight: 500;">Scan the QR code above using your<br>banking or e-wallet app to pay.</p>
            </div>
        <?php endif; ?>
    </div>

    <form action="payment page.php" method="POST" enctype="multipart/form-data" id="paymentForm">
        <!-- Carry through all order details -->
        <input type="hidden" name="payment_method"  value="<?php echo htmlspecialchars($payment_method); ?>">
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
        <input type="hidden" name="grand_total"      value="<?php echo htmlspecialchars($grand_total); ?>">

        <?php if (!empty($selected_items)): ?>
            <?php foreach ($selected_items as $item_id): ?>
                <input type="hidden" name="selected_items[]" value="<?php echo htmlspecialchars($item_id); ?>">
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Note: Receipt upload removed per user request -->
        
        <?php if ($is_rent): ?>
        <div style="background: #fff7ed; border: 1px solid #ffedd5; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.8rem; color: #9a3412; text-align: left;">
            <strong>⚠️ Reminder:</strong> Your account can be suspended or blacklisted if the rental return date is overdue. Please return the instrument on time.
        </div>
        <?php endif; ?>

        <div style="display: flex; gap: 16px; margin-top: 8px;">
            <a href="address page.php?<?php echo $_SERVER['QUERY_STRING']; ?>" style="flex: 1; text-align: center; padding: 14px; background: #f1f5f9; color: #475569; border-radius: 10px; text-decoration: none; font-weight: 600;">Back</a>
            <button type="submit" style="flex: 1; margin: 0; padding: 14px; background: var(--success); color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; transition: background 0.2s;">
                <?php echo $payment_method === 'card' ? 'Pay Now RM ' : 'I Have Paid RM '; echo number_format($grand_total, 2); ?>
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentForm = document.getElementById('paymentForm');
    const cardNumber = document.getElementById('cardNumber');
    const cardExpiry = document.getElementById('cardExpiry');
    const cardCvv    = document.getElementById('cardCvv');
    const cardTypeLabel = document.getElementById('cardTypeLabel');
    const logoVisa   = document.getElementById('logoVisa');
    const logoMaster = document.getElementById('logoMaster');

    if (!cardNumber) return; // Not on card payment

    // Card Number Logic
    cardNumber.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // 1. Brand Detection & Credit/Debit Identification
        // General Heuristic:
        // Visa starts with 4. 
        // Mastercard starts with 5 or 2.
        // For Debit identification: In many regions, specific BIN ranges are debit.
        // For this task, we will show [Brand] [Credit/Debit]
        
        let brand = '';
        let isDebit = false;
        
        if (value.startsWith('4')) {
            brand = 'Visa';
            logoVisa.style.opacity = '1';
            logoMaster.style.opacity = '0.3';
            // Simple heuristic for demo: even starts might be debit
            isDebit = (parseInt(value.substring(1, 4)) % 2 === 0);
        } else if (value.startsWith('5')) {
            brand = 'Mastercard';
            logoMaster.style.opacity = '1';
            logoVisa.style.opacity = '0.3';
            isDebit = (parseInt(value.substring(1, 4)) % 2 !== 0);
        } else {
            logoVisa.style.opacity = '0.3';
            logoMaster.style.opacity = '0.3';
        }

        if (brand) {
            cardTypeLabel.textContent = brand + ' ' + (isDebit ? 'Debit' : 'Credit');
            cardTypeLabel.style.display = 'block';
            cardTypeLabel.style.background = isDebit ? '#dcfce7' : '#dbeafe';
            cardTypeLabel.style.color = isDebit ? '#166534' : '#1e40af';
        } else {
            cardTypeLabel.style.display = 'none';
        }

        // 2. Formatting
        let formatted = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formatted;
    });

    // Expiry Date Logic (MM/YY)
    cardExpiry.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Month validation (01-12)
        if (value.length >= 1) {
            if (value[0] > '1') {
                value = '0' + value[0]; // Auto-precede with 0 if first digit > 1
            }
        }
        if (value.length >= 2) {
            let month = parseInt(value.substring(0, 2));
            if (month > 12) value = '12' + value.substring(2);
            if (month === 0) value = '01' + value.substring(2);
        }

        if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });

    // CVV Logic (Numeric only)
    cardCvv.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });

    // Form Submission Validation
    paymentForm.addEventListener('submit', function(e) {
        // Expiry Validation
        const expiry = cardExpiry.value;
        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
            alert('Please enter a valid expiry date (MM/YY).');
            e.preventDefault();
            return;
        }

        const [month, year] = expiry.split('/').map(n => parseInt(n));
        const now = new Date();
        const currentYear = parseInt(now.getFullYear().toString().substring(2));
        const currentMonth = now.getMonth() + 1;

        if (month < 1 || month > 12) {
            alert('Invalid month in expiry date. Please use 01-12.');
            e.preventDefault();
            return;
        }

        // Strict future check: Must be greater than current month and year
        if (year < currentYear || (year === currentYear && month <= currentMonth)) {
            alert('The expiry date must be in the future (after ' + (currentMonth < 10 ? '0' + currentMonth : currentMonth) + '/' + currentYear + ').');
            e.preventDefault();
            return;
        }

        // Card Number length
        const rawCard = cardNumber.value.replace(/\s/g, '');
        if (rawCard.length < 16) {
            alert('Please enter a complete card number.');
            e.preventDefault();
            return;
        }
    });
});
</script>

</body>
</html>
