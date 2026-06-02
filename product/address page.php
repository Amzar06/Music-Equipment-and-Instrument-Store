<?php
session_start();
include '../database.php';
if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}

$type = $_GET['type'] ?? '';
$product_id = $_GET['product_id'] ?? 0;
$days = $_GET['days'] ?? 1;
$total = 0;
$rent_summary = "";

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
                $rent_summary .= "<p style='margin: 0; color: var(--text-secondary);'><strong>Duration:</strong> $days Day(s)</p>";
                $rent_summary .= "<p style='margin: 0; margin-top: 4px; font-size: 1.1rem; color: var(--success);'><strong>Total Price:</strong> RM " . number_format($total, 2) . "</p>";
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
            <input type="hidden" name="days" value="<?php echo htmlspecialchars($days); ?>">
            <input type="hidden" name="amount" value="<?php echo htmlspecialchars($total); ?>">
        <?php endif; ?>
        <div>
            <input type="text" name="street" placeholder="Street Address" required>
        </div>
        <div style="display: flex; gap: 16px;">
            <div style="flex: 1;">
                <input type="text" name="city" placeholder="City" required>
            </div>
            <div style="flex: 1;">
                <input type="text" name="postcode" placeholder="Postcode" inputmode="numeric" pattern="[0-9]*" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
            </div>
        </div>
        <div>
            <input type="text" name="state" placeholder="State" required>
        </div>
        
        <div style="display: flex; gap: 16px; margin-top: 16px;">
            <a href="product page.php" style="flex: 1; text-align: center; padding: 12px; background: rgba(255,255,255,0.1); border-radius: 8px; margin: 0;">Back to Product</a>
            <button type="submit" style="flex: 1; margin-top: 0;">Continue to Payment</button>
        </div>
    </form>
</div>

</body>
</html>
