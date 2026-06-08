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
    <title>Buy Instruments</title>
    <link rel="stylesheet" href="style.css?v=2.0">
    <style>
        /* Smooth transition for filtering */
        .card { transition: all 0.3s ease; }
        
        /* Lightbox Styles */
        #lightbox {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.9);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }
        #lightbox img {
            max-width: 90%;
            max-height: 90%;
            border-radius: 12px;
            box-shadow: 0 0 40px rgba(0,0,0,0.5);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body>

<!-- Lightbox Modal -->
<div id="lightbox" onclick="this.style.display='none'">
    <img id="lightboxImg" src="">
</div>

<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, #1e293b, #334155); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Buy Instruments</h2>
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="../customer/home_page.php" style="padding: 10px 18px; background: rgba(37, 99, 235, 0.08); color: #2563eb; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Homepage</a>
            <a href="payment history.php" style="padding: 10px 18px; background: rgba(16, 185, 129, 0.08); color: #10b981; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Order History</a>
            <a href="cart page.php" style="padding: 10px 18px; background: rgba(100, 116, 139, 0.08); color: #475569; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">View Cart</a>
            <a href="../customer/logout_page.php" style="padding: 10px 18px; background: rgba(239, 68, 68, 0.08); color: #ef4444; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 0.9rem; border: 1px solid rgba(239, 68, 68, 0.2);">Logout</a>
        </div>
    </div>

    <div class="nav-tabs">
        <a href="product page.php" class="nav-link buy active">Buy Instruments</a>
        <a href="rent page.php" class="nav-link rent">Rent Instruments</a>
    </div>

    <!-- Category Sort Section -->
    <div style="margin-bottom: 32px; display: flex; align-items: center; gap: 12px;">
        <label for="categoryFilter" style="font-weight: 600; color: var(--text-primary);">Sort by Category:</label>
        <select id="categoryFilter" style="padding: 10px 16px; font-size: 1rem; border-radius: 10px; border: 1.5px solid var(--card-border); background: #f8fafc; color: var(--text-primary); cursor: pointer;" onchange="filterCategory()">
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
        </div>
    <?php else: ?>
        <?php foreach($products as $product): ?>
            <div class="card" data-category="<?php echo htmlspecialchars(strtolower($product['category_name'])); ?>" onclick="toggleExpand(this)" style="cursor: pointer;">
                <?php if (!empty($product['prod_image'])): ?>
                    <div onclick="openLightbox(event, '../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>')" 
                         style="width: 100%; height: 200px; background-size: cover; background-position: center; background-image: url('../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>'); border-radius: 10px; margin-bottom: 16px; transition: transform 0.3s;"
                         onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                <?php else: ?>
                    <div style="width: 100%; height: 200px; background-color: #f1f5f9; border-radius: 10px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; color: #94a3b8; border: 2px dashed #e2e8f0;">[ No Image ]</div>
                <?php endif; ?>
                
                <h3 style="margin-bottom: 4px;"><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                <p style="font-weight: 700; color: #2563eb; font-size: 1.1rem; margin-bottom: 12px;">RM <?php echo number_format($product['prod_sale_price'] ?? 0, 2); ?></p>

                <p class="short-desc" style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 12px;">
                    <?php echo htmlspecialchars($product['prod_description']); ?>
                </p>

                <!-- Expanded Details (Hidden by default) -->
                <div class="expanded-details" style="max-height: 0; overflow: hidden; transition: all 0.4s ease; border-top: 1px solid #f1f5f9; padding-top: 12px; display: none;">
                    <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 16px; margin-top: 8px;">
                        <?php echo htmlspecialchars($product['prod_description']); ?>
                    </p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.8rem; background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Category</span> <?php echo htmlspecialchars($product['category_name']); ?></div>
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Stock</span> <?php echo htmlspecialchars($product['prod_qty']); ?> units</div>
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Status</span> <span style="color: <?php echo strtolower($product['status']) === 'available' ? '#10b981' : '#ef4444'; ?>; font-weight: 700;"><?php echo htmlspecialchars($product['status']); ?></span></div>
                    </div>
                </div>

                <div class="product-actions mt-2" onclick="event.stopPropagation()">
                    <form action="add_to_cart.php" method="POST">
                        <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                        <button type="submit" style="width: 100%; padding: 12px; border-radius: 10px; font-weight: 700;">Add to Cart</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<script>
function openLightbox(event, imgSrc) {
    event.stopPropagation();
    const lightbox = document.getElementById('lightbox');
    const lightboxImg = document.getElementById('lightboxImg');
    lightboxImg.src = imgSrc;
    lightbox.style.display = 'flex';
}

function toggleExpand(card) {
    const details = card.querySelector('.expanded-details');
    const shortDesc = card.querySelector('.short-desc');
    
    if (details.style.display === 'none' || details.style.display === '') {
        details.style.display = 'block';
        details.style.maxHeight = '800px'; 
        shortDesc.style.display = 'none';
        card.style.transform = 'translateY(-4px)';
        card.style.boxShadow = '0 12px 20px -5px rgba(0, 0, 0, 0.1)';
    } else {
        details.style.display = 'none';
        details.style.maxHeight = '0px';
        shortDesc.style.display = '-webkit-box';
        card.style.transform = 'none';
        card.style.boxShadow = '';
    }
}

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
