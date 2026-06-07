<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Fetch categories
$categories = [];
if (isset($conn)) {
    $cat_query = $conn->query("SELECT * FROM categories");
    if ($cat_query) {
        while($row = $cat_query->fetch_assoc()) {
            $categories[] = $row;
        }
    }
}

// Fetch products with their category names
$products = [];
if (isset($conn)) {
    $prod_query = $conn->query("
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
    ");
    if ($prod_query) {
        while($row = $prod_query->fetch_assoc()) {
            $products[] = $row;
        }
    }
}

// Check if user is on rental cooldown (1 week limit)
$can_rent = true;
$next_rentable_date = null;
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT created_at FROM rentals WHERE cust_id = ? AND status != 'Cancelled' ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $cust_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($last = $res->fetch_assoc()) {
        $last_date = strtotime($last['created_at']);
        $one_week_ago = strtotime("-1 week");
        
        if ($last_date > $one_week_ago) {
            $can_rent = false;
            $next_rentable_date = date('d M Y', strtotime("+1 week", $last_date));
        }
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rent Instruments</title>
    <link rel="stylesheet" href="style.css?v=2.0">

</head>
<body>

<div style="width: 100%; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="margin: 0;">Rent Instruments</h2>
        <div>
            <a href="../customer/home_page.php" style="margin: 0 12px 0 0; padding: 10px 20px; background: rgba(37, 99, 235, 0.1); color: #2563eb; border-radius: 8px; text-decoration: none;">Homepage</a>
            <a href="payment history.php" style="margin: 0 12px 0 0; padding: 10px 20px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 8px; text-decoration: none;">Order History</a>
            <a href="../customer/logout_page.php" style="margin: 0 12px 0 0; padding: 10px 20px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 8px; text-decoration: none;">Logout</a>
            <a href="cart page.php" style="margin: 0; padding: 10px 20px; background: rgba(0,0,0,0.05); border-radius: 8px; text-decoration: none; color: #475569;">View Cart</a>
        </div>
    </div>

    <div class="nav-tabs">
        <a href="product page.php" class="nav-link buy">Buy Instruments</a>
        <a href="rent page.php" class="nav-link rent active">Rent Instruments</a>
    </div>

    <!-- Category Sort Section -->
    <div style="margin-bottom: 32px; display: flex; align-items: center; gap: 12px;">
        <label for="categoryFilter" style="font-weight: 600; color: var(--text-primary);">Sort by Category:</label>
        <select id="categoryFilter" style="padding: 10px 16px; font-size: 1rem; border-radius: 6px; border: 1px solid var(--card-border); background: #f8fafc; color: var(--text-primary); cursor: pointer;" onchange="filterCategory()">
            <option value="all">All Instruments</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars(strtolower($cat['category_name'])); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Rental Warning Message -->
    <?php if (!$can_rent): ?>
        <div style="background: #fee2e2; border: 1.5px solid #ef4444; color: #991b1b; padding: 20px; border-radius: 14px; margin-bottom: 32px; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <span style="font-size: 1.8rem;">⏳</span>
            <div>
                <p style="margin: 0; font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">Rental Cooldown Active</p>
                <p style="margin: 0; font-size: 0.95rem; opacity: 0.9;">You have recently rented an instrument. To ensure fair access for all customers, you can only rent <b>one instrument per week</b>.</p>
                <p style="margin-top: 10px; font-weight: 700; font-size: 0.9rem; background: rgba(239, 68, 68, 0.1); display: inline-block; padding: 4px 10px; border-radius: 6px;">
                    🔓 You can rent again starting: <?php echo $next_rentable_date; ?>
                </p>
            </div>
        </div>
    <?php else: ?>
        <div style="background: #f0fdf4; border: 1.5px solid #10b981; color: #166534; padding: 18px; border-radius: 14px; margin-bottom: 32px; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <span style="font-size: 1.5rem;">✅</span>
            <p style="margin: 0; font-weight: 600; font-size: 0.95rem;">You are eligible to rent! Note: Customers are permitted to rent only one instrument per week.</p>
        </div>
    <?php endif; ?>

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .flatpickr-calendar { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-radius: 12px; }
        .flatpickr-day.selected { background: var(--accent) !important; border-color: var(--accent) !important; }
    </style>



    <div class="product-grid" id="productGrid">
    <?php if (empty($products)): ?>
        <div style="text-align: center; color: var(--text-secondary); grid-column: 1 / -1; padding: 48px;">
            <h3>No products found.</h3>
        </div>
    <?php else: ?>
        <?php foreach($products as $product): ?>
            <div class="card" data-category="<?php echo htmlspecialchars(strtolower($product['category_name'])); ?>">
                <?php if (!empty($product['prod_image'])): ?>
                    <div style="width: 100%; height: 180px; background-size: cover; background-position: center; background-image: url('../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>'); border-radius: 8px; margin-bottom: 16px;"></div>
                <?php else: ?>
                    <div style="width: 100%; height: 180px; background-color: rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); border: 2px dashed rgba(255,255,255,0.2);">[ No Image ]</div>
                <?php endif; ?>
                
                <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.4;"><?php echo htmlspecialchars($product['prod_description']); ?></p>
                
                <p><strong>Rent:</strong> RM <?php echo number_format($product['prod_rental_price'] ?? 0, 2); ?> / day</p>

                <div class="product-actions mt-4">
                    <!-- RENT -->
                    <form action="address page.php" method="GET" class="rent-form" onsubmit="return validateRental(this)">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                        <input type="hidden" name="type" value="rent">
                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($product['prod_rental_price']); ?>">
                        
                        <!-- Hidden fields to store split dates for backend -->
                        <input type="hidden" name="start_date" id="start_date_<?php echo $product['prod_id']; ?>">
                        <input type="hidden" name="end_date" id="end_date_<?php echo $product['prod_id']; ?>">

                        <div style="margin-bottom: 16px;">
                            <label style="display:block; font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 6px;">Select Rental Period</label>
                            <input type="text" class="range-picker" placeholder="<?php echo $can_rent ? 'Choose dates..' : 'Rental restricted'; ?>" readonly 
                                   data-prod-id="<?php echo $product['prod_id']; ?>"
                                   <?php echo !$can_rent ? 'disabled' : ''; ?>
                                   style="padding: 12px; font-size: 0.95rem; background: <?php echo $can_rent ? 'white' : '#f1f5f9'; ?>; cursor: <?php echo $can_rent ? 'pointer' : 'not-allowed'; ?>; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%;">
                        </div>
                        <button type="submit" class="rent-btn" <?php echo !$can_rent ? 'disabled style="background:#94a3b8; cursor:not-allowed;"' : ''; ?> style="width: 100%;"><?php echo $can_rent ? 'Rent Now' : 'Restriction Active'; ?></button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
function validateRental(form) {
    const start = form.querySelector('input[name="start_date"]').value;
    const end = form.querySelector('input[name="end_date"]').value;
    
    if (!start || !end) {
        alert("Please select a Rental Period (Start and End date) before renting!");
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    flatpickr(".range-picker", {
        mode: "range",
        minDate: "today",
        dateFormat: "Y-m-d",
        onClose: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                const prodId = instance.element.getAttribute('data-prod-id');
                const start = instance.formatDate(selectedDates[0], "Y-m-d");
                const end = instance.formatDate(selectedDates[1], "Y-m-d");
                
                document.getElementById('start_date_' + prodId).value = start;
                document.getElementById('end_date_' + prodId).value = end;
            }
        }
    });
});

function filterCategory() {
    const selected = document.getElementById('categoryFilter').value;
    const cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        if (selected === 'all' || card.getAttribute('data-category') === selected) {
            card.style.display = ''; 
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

</body>
</html>
