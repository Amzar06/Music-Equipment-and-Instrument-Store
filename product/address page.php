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
    
    <div id="rentSummary" style="margin-bottom: 24px; padding: 16px; background: #f8fafc; border: 1px solid var(--card-border); border-radius: 8px; display: none; text-align: center;"></div>
    
    <form action="qr payment.php" method="GET" id="addressForm">
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

<script>
    const urlParams = new URLSearchParams(window.location.search);
    const type = urlParams.get('type');
    if (type === 'rent') {
        const days = parseInt(urlParams.get('days')) || 1;
        const price = parseFloat(urlParams.get('price')) || 0;
        const total = days * price;
        const summary = document.getElementById('rentSummary');
        summary.style.display = 'block';
        summary.innerHTML = `<h3 style="margin-bottom: 8px;">Rental Summary</h3>
                             <p style="margin: 0; color: var(--text-secondary);"><strong>Duration:</strong> ${days} Day(s)</p>
                             <p style="margin: 0; margin-top: 4px; font-size: 1.1rem; color: var(--success);"><strong>Total Price:</strong> RM ${total.toFixed(2)}</p>
                             <input type="hidden" name="amount" value="${total.toFixed(2)}" form="addressForm">`;
    }
</script>

</body>
</html>
