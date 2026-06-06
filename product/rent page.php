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
            <a href="../customer/logout_page.php" style="margin: 0 12px 0 0; padding: 10px 20px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 8px; text-decoration: none;">Logout</a>
            <a href="cart page.php" style="margin: 0; padding: 10px 20px; background: rgba(255,255,255,0.1); border-radius: 8px; text-decoration: none;">View Cart</a>
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
    <div style="background: #fffbeb; border: 1px solid #fef3c7; color: #92400e; padding: 16px; border-radius: 12px; margin-bottom: 32px; display: flex; align-items: center; gap: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <span style="font-size: 1.5rem;">⚠️</span>
        <p style="margin: 0; font-weight: 600; font-size: 0.95rem;">Important: Customers are permitted to rent only one instrument once per week. Please plan your schedule accordingly.</p>
    </div>

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
                    <form action="address page.php" method="GET" class="rent-form">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                        <input type="hidden" name="type" value="rent">
                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($product['prod_rental_price']); ?>">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 12px;">
                            <div>
                                <label style="display:block; font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">Start Date</label>
                                <input type="date" name="start_date" required min="<?php echo date('Y-m-d'); ?>" style="padding: 8px; font-size: 0.85rem;">
                            </div>
                            <div>
                                <label style="display:block; font-size: 0.8rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">End Date</label>
                                <input type="date" name="end_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" style="padding: 8px; font-size: 0.85rem;">
                            </div>
                        </div>
                        <button type="submit" class="rent-btn" style="width: 100%;">Rent Now</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<script>
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
