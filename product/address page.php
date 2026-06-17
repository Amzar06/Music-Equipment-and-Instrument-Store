<?php
session_start();
include '../database.php';
if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

$type = $_GET['type'] ?? '';
$product_id = $_GET['product_id'] ?? 0;
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$selected_items = $_GET['selected_items'] ?? [];
$days = 1;

if (!empty($start_date) && !empty($end_date)) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $days = $interval->days;
    if ($days < 1) $days = 1;
}

$subtotal = 0;
$rent_price_per_day = 0;
$prod_name_display = '';
$existing_addresses = [];

if (isset($conn)) {
    // 1. Fetch registration address
    $stmt_cust = $conn->prepare("SELECT cust_name, cust_street AS street, cust_city AS city, cust_state AS state, cust_postcode AS postcode FROM customers WHERE cust_id = ?");
    $stmt_cust->bind_param("i", $cust_id);
    $stmt_cust->execute();
    $res_cust = $stmt_cust->get_result();
    if ($cust = $res_cust->fetch_assoc()) {
        if (!empty($cust['street'])) {
            $existing_addresses[] = [
                'address_id' => 'reg',
                'full_name'  => $cust['cust_name'],
                'street'     => $cust['street'],
                'city'       => $cust['city'] ?? '',
                'state'      => $cust['state'] ?? '',
                'postcode'   => $cust['postcode'] ?? '',
                'label'      => 'Primary Address (From Profile)',
                'is_reg'     => true
            ];
        }
    }
    $stmt_cust->close();

    // 2. Fetch recent addresses
    $stmt_addr = $conn->prepare("SELECT * FROM addresses WHERE cust_id = ? ORDER BY created_at DESC");
    $stmt_addr->bind_param("i", $cust_id);
    $stmt_addr->execute();
    $res_addr = $stmt_addr->get_result();
    $primary = !empty($existing_addresses) ? $existing_addresses[0] : null;
    while ($row = $res_addr->fetch_assoc()) {
        if (count($existing_addresses) >= 4) break;
        if ($primary) {
            $is_same = (
                trim($row['full_name']) == trim($primary['full_name']) &&
                trim($row['street'])    == trim($primary['street'])    &&
                trim($row['city'])      == trim($primary['city'])      &&
                trim($row['state'])     == trim($primary['state'])     &&
                trim($row['postcode'])  == trim($primary['postcode'])
            );
            if ($is_same) continue;
        }
        $row['is_reg'] = false;
        $row['label']  = "Created Address: " . $row['street'] . " (" . $row['full_name'] . ")";
        $existing_addresses[] = $row;
    }
    $stmt_addr->close();
}

if ($type === 'rent' && isset($conn)) {
    $stmt = $conn->prepare("SELECT prod_name, prod_rental_price FROM products WHERE prod_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $rent_price_per_day = floatval($row['prod_rental_price']);
            $prod_name_display  = $row['prod_name'];
            $subtotal = $days * $rent_price_per_day;

            // OVERLAP VALIDATION WITH 3-DAY BUFFER
            $stmt_check = $conn->prepare("
                SELECT r.start_date, r.end_date 
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
            $stmt_check->bind_param("isssss", $product_id, $start_date, $end_date, $start_date, $end_date);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                header("Location: rent page.php?error=already_booked&name=" . urlencode($prod_name_display));
                exit();
            }
            $stmt_check->close();
        }
        $stmt->close();
    }
} elseif ($type === 'buy' && $product_id > 0 && isset($conn)) {
    $stmt = $conn->prepare("SELECT prod_name, prod_sale_price FROM products WHERE prod_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $subtotal = floatval($row['prod_sale_price']);
            $prod_name_display = $row['prod_name'];
        }
        $stmt->close();
    }
} elseif (!empty($selected_items) && isset($conn)) {
    $placeholders = implode(',', array_fill(0, count($selected_items), '?'));
    $q = $conn->prepare("
        SELECT p.prod_sale_price, p.prod_rental_price, ci.quantity, ci.start_date, ci.end_date 
        FROM cart_items ci 
        JOIN products p ON ci.prod_id = p.prod_id 
        WHERE ci.cart_item_id IN ($placeholders)
    ");
    $types = str_repeat("i", count($selected_items));
    $params = array_map('intval', $selected_items);
    $q->bind_param($types, ...$params);
    $q->execute();
    $res = $q->get_result();
    while($row = $res->fetch_assoc()) {
        if ($row['start_date'] && $row['end_date']) {
            $s = new DateTime($row['start_date']);
            $e = new DateTime($row['end_date']);
            $d = $s->diff($e)->days; if($d < 1) $d = 1;
            $subtotal += ($row['prod_rental_price'] * $d * $row['quantity']);
        } else {
            $subtotal += ($row['prod_sale_price'] * $row['quantity']);
        }
    }
    $q->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="style.css?v=5.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .delivery-toggle { display: flex; gap: 0; margin-bottom: 24px; border-radius: 10px; overflow: hidden; border: 1.5px solid #e2e8f0; }
        .delivery-toggle label {
            flex: 1; text-align: center; padding: 14px 10px; cursor: pointer;
            font-weight: 600; font-size: 0.95rem; transition: all 0.2s;
            background: #f8fafc; color: #64748b; user-select: none;
        }
        .delivery-toggle input[type="radio"] { display: none; }
        .delivery-toggle input[type="radio"]:checked + label {
            background: var(--accent, #2563eb); color: white;
        }
        .delivery-badge {
            display: inline-block; font-size: 0.75rem; padding: 2px 7px;
            background: #10b981; color: white; border-radius: 20px; margin-left: 6px; vertical-align: middle;
        }
        .self-collect-info {
            background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 10px;
            padding: 16px 20px; margin-bottom: 16px; color: #166534; font-size: 0.93rem;
        }
    </style>
</head>
<body>

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

<div style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80') center/cover; padding: 40px 0; margin-bottom: 0; border-bottom: 4px solid #10b981;">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <h2 style="margin: 0; font-size: 2.2rem; font-weight: 800; color: white;">Checkout</h2>
        <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0 0;">Secure your instrument selection</p>
    </div>
</div>

<div class="container pb-5" style="max-width: 700px; margin: 0 auto;">
    <div style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #f1f5f9;">
        <h3 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">Delivery Details</h3>
        <p style="color: #64748b; margin-bottom: 32px;">Please confirm where you'd like to receive your item.</p>

    <?php if (($type === 'rent' || $type === 'buy') && $subtotal > 0): ?>
    <div style="margin-bottom: 30px; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
        <div style="font-weight: 800; color: #1e293b; margin-bottom: 4px; font-size: 1.1rem;"><?php echo htmlspecialchars($prod_name_display); ?></div>
        <div style="font-size: 0.9rem; color: #64748b; font-weight: 600;">
            <?php if ($type === 'rent'): ?>
                📅 <?php echo htmlspecialchars($start_date); ?> to <?php echo htmlspecialchars($end_date); ?>
                &nbsp;·&nbsp; <?php echo $days; ?> day(s) × RM <?php echo number_format($rent_price_per_day, 2); ?>
            <?php else: ?>
                ⚡ Direct Purchase
            <?php endif; ?>
        </div>
        <div style="margin-top: 10px; font-weight: 800; color: #10b981; font-size: 1.25rem;">Subtotal: RM <?php echo number_format($subtotal, 2); ?></div>
    </div>
    <?php elseif (!empty($selected_items) && $subtotal > 0): ?>
    <div style="margin-bottom: 30px; padding: 20px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px;">
        <div style="font-weight: 800; color: #1e293b; margin-bottom: 4px; font-size: 1.1rem;">Selected Order Summary</div>
        <div style="font-size: 0.9rem; color: #64748b; font-weight: 600;">
            🛒 <?php echo count($selected_items); ?> Item(s) selected from cart
        </div>
        <div style="margin-top: 10px; font-weight: 800; color: #10b981; font-size: 1.25rem;">Subtotal: RM <?php echo number_format($subtotal, 2); ?></div>
    </div>
    <?php endif; ?>

    <form action="qr payment.php" method="GET" id="addressForm" onsubmit="return validateForm()">
        <?php if ($type === 'rent' || $type === 'buy'): ?>
            <input type="hidden" name="type"       value="<?php echo htmlspecialchars($type); ?>">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
            <?php if ($type === 'rent'): ?>
                <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                <input type="hidden" name="end_date"   value="<?php echo htmlspecialchars($end_date); ?>">
                <input type="hidden" name="days"       value="<?php echo htmlspecialchars($days); ?>">
            <?php endif; ?>
            <input type="hidden" name="subtotal"   value="<?php echo htmlspecialchars($subtotal); ?>">
        <?php endif; ?>

        <?php if (!empty($selected_items)): ?>
            <?php foreach ($selected_items as $item_id): ?>
                <input type="hidden" name="selected_items[]" value="<?php echo htmlspecialchars($item_id); ?>">
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Delivery Method Toggle -->
        <div style="margin-bottom: 8px; font-weight: 700; font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em;">Delivery Method</div>
        <div class="delivery-toggle">
            <input type="radio" id="opt_self" name="delivery_type" value="self_collect" onchange="onDeliveryChange()">
            <label for="opt_self">🏪 Self Collect<br><span style="font-size:0.78rem; font-weight:400; opacity:0.8;">Free</span></label>

            <input type="radio" id="opt_delivery" name="delivery_type" value="delivery" onchange="onDeliveryChange()" checked>
            <label for="opt_delivery">🚚 Delivery<span class="delivery-badge">+RM 30.00</span><br><span style="font-size:0.78rem; font-weight:400; opacity:0.8;">To your door</span></label>
        </div>

        <!-- Self Collect Message -->
        <div id="selfCollectInfo" style="display:none; margin-bottom: 20px;">
            <div style="background: #f0fdf4; border: 1.5px solid #bbf7d0; border-radius: 12px; padding: 20px 22px;">
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:14px;">
                    <span style="font-size:1.6rem;">🏪</span>
                    <div>
                        <div style="font-weight:700; font-size:1rem; color:#14532d;">Self Collect — Store Location</div>
                        <div style="font-size:0.82rem; color:#16a34a;">No delivery fee · Pick up at our store</div>
                    </div>
                </div>
                <div style="background:white; border-radius:10px; padding:14px 16px; margin-bottom:14px; border:1px solid #d1fae5;">
                    <div style="font-weight:700; font-size:0.95rem; color:#1e293b; margin-bottom:4px;">📍 Music Equipment &amp; Instrument Store</div>
                    <div style="font-size:0.88rem; color:#475569; line-height:1.7;">
                        Multimedia University (MMU) Melaka<br>
                        Jalan Ayer Keroh Lama, Bukit Beruang,<br>
                        75450 Melaka, Malaysia
                    </div>
                    <div style="font-size:0.82rem; color:#64748b; margin-top:6px;">🕘 Mon – Fri: 9:00 AM – 5:00 PM</div>
                </div>
                <div style="display:flex; gap:10px;">
                    <a href="https://www.google.com/maps/search/?api=1&query=Multimedia+University+Melaka,+Jalan+Ayer+Keroh+Lama,+Bukit+Beruang,+75450+Melaka"
                       target="_blank" rel="noopener"
                       style="flex:1; text-align:center; padding:11px 8px; border-radius:9px; text-decoration:none; font-weight:700; font-size:0.9rem;
                              background:#4285f4; color:white; display:flex; align-items:center; justify-content:center; gap:6px;">
                        Google Maps
                    </a>
                </div>
            </div>
        </div>

        <!-- Address Section (shown for Delivery) -->
        <div id="addressSection">
            <?php if (!empty($existing_addresses)): ?>
                <div style="margin-bottom: 20px; padding: 16px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px;">
                    <label style="display: block; font-weight: 700; margin-bottom: 10px; color: #0369a1;">Use Existing Address</label>
                    <select name="existing_address_id" id="existingAddr" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #7dd3fc; background: white;" onchange="toggleAddressType()">
                        <option value="new">-- Create New Address --</option>
                        <?php $first = true; foreach ($existing_addresses as $addr): ?>
                            <option value="<?php echo $addr['address_id']; ?>"
                                    data-street="<?php echo htmlspecialchars($addr['street'] ?? ''); ?>"
                                    data-city="<?php echo htmlspecialchars($addr['city'] ?? ''); ?>"
                                    data-state="<?php echo htmlspecialchars($addr['state'] ?? ''); ?>"
                                    data-postcode="<?php echo htmlspecialchars($addr['postcode'] ?? ''); ?>"
                                    data-name="<?php echo htmlspecialchars($addr['full_name'] ?? ''); ?>"
                                    <?php if ($first) { echo "selected"; $first = false; } ?>>
                                <?php echo htmlspecialchars($addr['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div id="newAddressFields">
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">Reception Name *</label>
                    <input type="text" name="full_name" placeholder="Name of Person Receiving" id="fullName">
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">Street Address *</label>
                    <input type="text" name="street" placeholder="No, Building, Street" id="street">
                </div>
                <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">City *</label>
                        <input type="text" name="city" placeholder="City" id="city">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">Postcode *</label>
                        <input type="text" name="postcode" placeholder="Postcode" id="postcode" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                    </div>
                </div>
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">State *</label>
                    <select name="state" id="state" style="width: 100%; padding: 12px; border-radius: 8px; border: 1.5px solid #e2e8f0; background: white;">
                        <option value="">Select State</option>
                        <option value="Johor">Johor</option>
                        <option value="Kedah">Kedah</option>
                        <option value="Kelantan">Kelantan</option>
                        <option value="Melaka">Melaka</option>
                        <option value="Negeri Sembilan">Negeri Sembilan</option>
                        <option value="Pahang">Pahang</option>
                        <option value="Perak">Perak</option>
                        <option value="Perlis">Perlis</option>
                        <option value="Pulau Pinang">Pulau Pinang</option>
                        <option value="Selangor">Selangor</option>
                        <option value="Terengganu">Terengganu</option>
                        <option value="Kuala Lumpur">Kuala Lumpur</option>
                        <option value="Putrajaya">Putrajaya</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Payment Method Section -->
        <div style="margin-bottom: 8px; font-weight: 700; font-size: 0.9rem; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; margin-top: 24px;">Payment Method</div>
        <div class="payment-methods" style="display: grid; gap: 12px; margin-bottom: 24px;">
            <label class="pay-option" style="display: flex; align-items: center; padding: 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s; position: relative;">
                <input type="radio" name="payment_method" value="card" checked style="margin-right: 12px; width: 18px; height: 18px;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; font-size: 0.95rem;">💳 Credit / Debit Card</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Visa, Mastercard, AMEX</div>
                </div>
            </label>

            <label class="pay-option" style="display: flex; align-items: center; padding: 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s; position: relative;">
                <input type="radio" name="payment_method" value="tng" style="margin-right: 12px; width: 18px; height: 18px;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; font-size: 0.95rem;">📱 Touch 'n Go eWallet</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Instant payment via TNG App</div>
                </div>
            </label>

            <label class="pay-option" style="display: flex; align-items: center; padding: 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.2s; position: relative;">
                <input type="radio" name="payment_method" value="duitnow" style="margin-right: 12px; width: 18px; height: 18px;">
                <div style="flex: 1;">
                    <div style="font-weight: 700; font-size: 0.95rem;">🏦 DuitNow QR</div>
                    <div style="font-size: 0.8rem; color: #64748b;">Scan and pay from any bank app</div>
                </div>
            </label>
        </div>

        <div style="display: flex; gap: 16px; margin-top: 16px;">
            <a href="<?php echo $type === 'rent' ? 'rent page.php' : 'product page.php'; ?>" style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; margin: 0; background: #f1f5f9; color: #475569;">Back</a>
            <button type="submit" style="flex: 1; margin-top: 0;">Continue to Payment</button>
        </div>
    </form>
</div>

<script>
function onDeliveryChange() {
    const isDelivery = document.getElementById('opt_delivery').checked;
    document.getElementById('addressSection').style.display = isDelivery ? '' : 'none';
    document.getElementById('selfCollectInfo').style.display = isDelivery ? 'none' : '';
}

function toggleAddressType() {
    const select = document.getElementById('existingAddr');
    const fullNameInp  = document.getElementById('fullName');
    const streetInp    = document.getElementById('street');
    const cityInp      = document.getElementById('city');
    const postcodeInp  = document.getElementById('postcode');
    const stateInp     = document.getElementById('state');
    const fields       = document.getElementById('newAddressFields');

    if (select && select.value !== 'new') {
        const opt = select.options[select.selectedIndex];
        fullNameInp.value = opt.getAttribute('data-name')     || '';
        streetInp.value   = opt.getAttribute('data-street')   || '';
        cityInp.value     = opt.getAttribute('data-city')     || '';
        postcodeInp.value = opt.getAttribute('data-postcode') || '';
        stateInp.value    = opt.getAttribute('data-state')    || '';
        fullNameInp.readOnly = streetInp.readOnly = cityInp.readOnly = postcodeInp.readOnly = true;
        stateInp.disabled = true;
        fields.style.opacity = '0.7';
    } else {
        fullNameInp.value = streetInp.value = cityInp.value = postcodeInp.value = '';
        stateInp.value = '';
        fullNameInp.readOnly = streetInp.readOnly = cityInp.readOnly = postcodeInp.readOnly = false;
        stateInp.disabled = false;
        fields.style.opacity = '1';
    }
}

function validateForm() {
    const isDelivery = document.getElementById('opt_delivery').checked;
    if (!isDelivery) return true; // Self collect — no address required

    const existingAddr = document.getElementById('existingAddr');
    if (existingAddr && existingAddr.value !== 'new') return true; // Using existing

    // Validate new address fields
    const fields = ['fullName','street','city','postcode','state'];
    for (const id of fields) {
        const el = document.getElementById(id);
        if (!el || !el.value.trim()) {
            alert('Please fill in all address fields for delivery.');
            el && el.focus();
            return false;
        }
    }
    return true;
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    onDeliveryChange();
    const existingAddr = document.getElementById('existingAddr');
    if (existingAddr) toggleAddressType();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>