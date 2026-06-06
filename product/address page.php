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
$days = 1; 

if (!empty($start_date) && !empty($end_date)) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    $days = $interval->days;
    if ($days < 1) $days = 1; // Minimum 1 day
}

$total = 0;
$rent_summary = "";
$existing_addresses = [];

if (isset($conn)) {
    // 1. Fetch registration address from customers table
    $stmt_cust = $conn->prepare("SELECT cust_name, cust_street, cust_city, cust_state, cust_postcode FROM customers WHERE cust_id = ?");
    $stmt_cust->bind_param("i", $cust_id);
    $stmt_cust->execute();
    $res_cust = $stmt_cust->get_result();
    if ($cust = $res_cust->fetch_assoc()) {
        if (!empty($cust['cust_street']) || !empty($cust['cust_city'])) {
            $existing_addresses[] = [
                'address_id' => 'reg',
                'full_name' => $cust['cust_name'],
                'street' => $cust['cust_street'],
                'city' => $cust['cust_city'],
                'state' => $cust['cust_state'],
                'postcode' => $cust['cust_postcode'],
                'is_reg' => true
            ];
        }
    }
    $stmt_cust->close();

    // 2. Fetch previously used addresses
    $stmt_addr = $conn->prepare("SELECT * FROM addresses WHERE cust_id = ? ORDER BY created_at DESC");
    $stmt_addr->bind_param("i", $cust_id);
    $stmt_addr->execute();
    $res_addr = $stmt_addr->get_result();
    while($row = $res_addr->fetch_assoc()) {
        $row['is_reg'] = false;
        $existing_addresses[] = $row;
    }
    $stmt_addr->close();
}

if ($type === 'rent') {
    if (isset($conn)) {
        $stmt = $conn->prepare("SELECT prod_rental_price FROM products WHERE prod_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $price = $row['prod_rental_price'];
                $total = $days * $price;
                $rent_summary = "<h3 style='margin-bottom: 8px;'>Rental Summary</h3>";
                $rent_summary .= "<p style='margin: 0; color: var(--text-secondary);'><strong>Dates:</strong> $start_date to $end_date</p>";
                $rent_summary .= "<p style='margin: 0; margin-top: 4px; color: var(--text-secondary);'><strong>Duration:</strong> $days Day(s)</p>";
                $rent_summary .= "<p style='margin: 0; margin-top: 8px; font-size: 1.1rem; color: var(--success);'><strong>Total Price:</strong> RM " . number_format($total, 2) . "</p>";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Delivery Address</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2>Delivery Address</h2>
    <p class="text-center mb-4">Please provide your details below</p>
    
    <?php if ($type === 'rent' && $rent_summary !== ""): ?>
    <div id="rentSummary" style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border: 1px solid var(--card-border); border-radius: 8px; text-align: center;">
        <?php echo $rent_summary; ?>
    </div>
    <?php endif; ?>
    
    <form action="qr payment.php" method="GET" id="addressForm">
        <?php if ($type === 'rent'): ?>
            <input type="hidden" name="type" value="rent">
            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>">
            <input type="hidden" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
            <input type="hidden" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
            <input type="hidden" name="days" value="<?php echo htmlspecialchars($days); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($total); ?>">
        <?php endif; ?>

        <?php if (!empty($existing_addresses)): ?>
            <div style="margin-bottom: 24px; padding: 16px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px;">
                <label style="display: block; font-weight: 700; margin-bottom: 12px; color: #0369a1;">Use Existing Address</label>
                <select name="existing_address_id" id="existingAddr" style="width: 100%; padding: 12px; border-radius: 8px; border: 1px solid #7dd3fc; background: white;" onchange="toggleAddressType()">
                    <option value="new">-- Create New Address --</option>
                    <?php foreach ($existing_addresses as $addr): ?>
                        <option value="<?php echo $addr['address_id']; ?>" 
                                data-street="<?php echo htmlspecialchars($addr['street'] ?? ''); ?>"
                                data-city="<?php echo htmlspecialchars($addr['city'] ?? ''); ?>"
                                data-state="<?php echo htmlspecialchars($addr['state'] ?? ''); ?>"
                                data-postcode="<?php echo htmlspecialchars($addr['postcode'] ?? ''); ?>"
                                data-name="<?php echo htmlspecialchars($addr['full_name'] ?? ''); ?>">
                            <?php echo htmlspecialchars($addr['full_name'] . " - " . ($addr['city'] ?: $addr['street'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div id="newAddressFields">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">Reception Name *</label>
                <input type="text" name="full_name" placeholder="Name of Person Receiving" id="fullName" required>
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">Street Address *</label>
                <input type="text" name="street" placeholder="No, Building, Street" id="street" required>
            </div>
            <div style="display: flex; gap: 16px; margin-bottom: 16px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">City *</label>
                    <input type="text" name="city" placeholder="City" id="city" required>
                </div>
                <div style="flex: 1;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">Postcode *</label>
                    <input type="text" name="postcode" placeholder="Postcode" id="postcode" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                </div>
            </div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 4px; color: var(--text-secondary);">State *</label>
                <input type="text" name="state" placeholder="State" id="state" required>
            </div>
        </div>
        
        <div style="display: flex; gap: 16px; margin-top: 16px;">
            <a href="product page.php" style="flex: 1; text-align: center; padding: 12px; border-radius: 8px; margin: 0; background: #f1f5f9; color: #475569;">Back</a>
            <button type="submit" style="flex: 1; margin-top: 0;">Continue to Payment</button>
        </div>
    </form>
</div>

<script>
function toggleAddressType() {
    const select = document.getElementById('existingAddr');
    const fields = document.getElementById('newAddressFields');
    
    // Inputs
    const fullNameInp = document.getElementById('fullName');
    const streetInp = document.getElementById('street');
    const cityInp = document.getElementById('city');
    const postcodeInp = document.getElementById('postcode');
    const stateInp = document.getElementById('state');
    
    if (select && select.value !== 'new') {
        const opt = select.options[select.selectedIndex];
        
        // Fill and Lock
        fullNameInp.value = opt.getAttribute('data-name') || '';
        streetInp.value = opt.getAttribute('data-street') || '';
        cityInp.value = opt.getAttribute('data-city') || '';
        postcodeInp.value = opt.getAttribute('data-postcode') || '';
        stateInp.value = opt.getAttribute('data-state') || '';
        
        // Set to ReadOnly
        fullNameInp.readOnly = true;
        streetInp.readOnly = true;
        cityInp.readOnly = true;
        postcodeInp.readOnly = true;
        stateInp.readOnly = true;
        
        fields.style.opacity = '0.7'; 
    } else {
        // Clear and Unlock
        fullNameInp.value = '';
        streetInp.value = '';
        cityInp.value = '';
        postcodeInp.value = '';
        stateInp.value = '';
        
        fullNameInp.readOnly = false;
        streetInp.readOnly = false;
        cityInp.readOnly = false;
        postcodeInp.readOnly = false;
        stateInp.readOnly = false;
        
        fields.style.opacity = '1';
    }
}
// Initialize
document.addEventListener('DOMContentLoaded', toggleAddressType);
</script>

</body>
</html>
