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
    <title>Products</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Smooth transition for filtering */
        .card { transition: opacity 0.3s ease; }
    </style>
</head>
<body>

<div style="width: 100%; max-width: 1200px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="margin: 0;">Our Instruments</h2>
        <a href="cart page.php" style="margin: 0; padding: 10px 20px; background: rgba(255,255,255,0.1); border-radius: 8px;">View Cart</a>
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

    <div class="product-grid" id="productGrid">
    <?php if (empty($products)): ?>
        <div style="text-align: center; color: var(--text-secondary); grid-column: 1 / -1; padding: 48px;">
            <h3>No products found.</h3>
            <p>Your database currently has no products in the 'products' table. Add some via SQL to see them here.</p>
        </div>
    <?php else: ?>
        <?php foreach($products as $product): ?>
            <div class="card" data-category="<?php echo htmlspecialchars(strtolower($product['category_name'])); ?>">
                <!-- Image Placeholder if real one doesn't exist -->
                <?php if (!empty($product['prod_image'])): ?>
                    <div style="width: 100%; height: 180px; background-size: cover; background-position: center; background-image: url('<?php echo htmlspecialchars($product['prod_image']); ?>'); border-radius: 8px; margin-bottom: 16px;"></div>
                <?php else: ?>
                    <div style="width: 100%; height: 180px; background-color: rgba(255,255,255,0.05); border-radius: 8px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.4); border: 2px dashed rgba(255,255,255,0.2);">[ No Image ]</div>
                <?php endif; ?>
                
                <h3><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                <!-- Product Details -->
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.4;"><?php echo htmlspecialchars($product['prod_description']); ?></p>
                
                <p><strong>Buy:</strong> RM <?php echo number_format($product['prod_sale_price'] ?? 0, 2); ?></p>
                <p><strong>Rent:</strong> RM <?php echo number_format($product['prod_rental_price'] ?? 0, 2); ?> / day</p>

                <div class="product-actions mt-4">
                    <!-- BUY -->
                    <form action="add_to_cart.php" method="POST" style="margin-bottom: 24px;">
                        <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                        <button type="submit">Add to Cart</button>
                        <!-- Showing message from GET if added --><?php if (isset($_GET['added']) && $_GET['added'] == $product['prod_id']) echo "<span style='color: var(--success); font-size: 0.8em; display:block; margin-top:4px;'>Added!</span>"; ?>
                    </form>

                    <!-- RENT -->
                    <form action="address page.php" method="GET">
                        <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                        <input type="hidden" name="type" value="rent">
                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($product['prod_rental_price']); ?>">
                        <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
                            <span style="font-size: 0.95rem; color: var(--text-secondary); white-space: nowrap;">Days:</span>
                            <input type="number" name="days" min="1" required style="padding: 8px;">
                        </div>
                        <button type="submit" class="rent-btn">Rent Instrument</button>
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
