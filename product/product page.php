<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Check if the user is logged in (for navbar dynamic profile display context)
$is_logged_in = isset($_SESSION['cust_id']);

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

// Fetch products with their category names — only products available for sale
$products = [];
if (isset($conn)) {
    $prod_query = $conn->query("
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.prod_sale_price > 0
          AND p.status != 'Discontinued'
        ORDER BY p.prod_id DESC
    ");
    if ($prod_query) {
        while($row = $prod_query->fetch_assoc()) {
            $products[] = $row;
        }
    }
}

// Fetch cart count
$cart_count = 0;
if (isset($conn) && $is_logged_in) {
    $count_query = $conn->prepare("SELECT SUM(ci.quantity) as total FROM cart_items ci JOIN cart c ON ci.cart_id = c.cart_id WHERE c.cust_id = ?");
    $count_query->bind_param("i", $cust_id);
    $count_query->execute();
    $count_res = $count_query->get_result()->fetch_assoc();
    $cart_count = $count_res['total'] ?? 0;
    $count_query->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Buy Instruments</title>
    <link rel="stylesheet" href="style.css?v=5.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
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
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        /* Integrated Custom Profile Icon Dropdown Styles from Homepage */
        .user-dropdown-toggle {
            color: rgba(255, 255, 255, 0.85) !important;
            font-size: 1.35rem;
            cursor: pointer;
            transition: color 0.2s;
        }
        .user-dropdown-toggle:hover {
            color: #20c997 !important;
        }
        .dropdown-menu-end {
            right: 0;
            left: auto;
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
            <ul class="navbar-nav ms-auto align-items-center" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link" href="../customer/home_page.php">Home</a></li>
                <li class="nav-item"><a class="nav-link active" href="product page.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="payment history.php">My Orders</a></li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle user-dropdown-toggle" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-circle-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2" aria-labelledby="userMenu">
                        <?php if ($is_logged_in): ?>
                            <li class="px-3 py-1 text-muted small fw-bold text-uppercase">
                                Hi, <?php echo htmlspecialchars($_SESSION['cust_name'] ?? 'Customer'); ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../customer/user_profile_page.php"><i class="fa-regular fa-id-card me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="payment history.php"><i class="fa-solid fa-clock-history me-2"></i> Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../customer/logout_page.php" onclick="return confirmLogout(event);"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="cust login.php"><i class="fa-solid fa-right-to-bracket me-2"></i> Customer Login</a></li>
                            <li><a class="dropdown-item" href="../customer/register_page.php"><i class="fa-solid fa-user-plus me-2"></i> Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div id="lightbox" onclick="this.style.display='none'">
    <img id="lightboxImg" src="">
</div>

<div style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80') center/cover; padding: 40px 0; margin-bottom: 0; border-bottom: 4px solid #10b981;">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h2 style="margin: 0; font-size: 2.2rem; font-weight: 800; color: white;">Musical Instruments</h2>
            <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0 0;">Premium selection for every musician</p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="cart page.php" style="position: relative; padding: 10px 20px; background: #2563eb; color: white; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 0.9rem; box-shadow: 0 4px 12px rgba(37,99,235,0.3);">
                🛒 View Cart
                <?php if ($cart_count > 0): ?>
                    <span style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <?php echo $cart_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </div>
    </div>
</div>

<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; margin-top: 25px;">

    <div class="nav-tabs">
        <a href="product page.php" class="nav-link buy active">Buy Instruments</a>
        <a href="rent page.php" class="nav-link rent">Rent Instruments</a>
    </div>

    <div style="margin-bottom: 32px; display: flex; align-items: center; gap: 12px;">
        <label for="categoryFilter" style="font-weight: 600; color: var(--text-primary);">Sort by Category:</label>
        <select id="categoryFilter" style="padding: 10px 16px; font-size: 1rem; border-radius: 10px; border: 1.5px solid var(--card-border); background: #f8fafc; color: var(--text-primary); cursor: pointer;" onchange="filterCategory()">
            <option value="all">All Instruments</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars(strtolower($cat['category_name'])); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'out_of_stock'): ?>
            <div style="margin-left: auto; background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; animation: shake 0.5s;">
                ⚠️ <?php echo htmlspecialchars($_GET['name'] ?? 'Item'); ?> is out of stock!
            </div>
        <?php endif; ?>
    </div>

    <div class="product-grid" id="productGrid">
    <?php if (empty($products)): ?>
        <div style="text-align: center; color: var(--text-secondary); grid-column: 1 / -1; padding: 48px;">
            <h3>No products found.</h3>
        </div>
    <?php else: ?>
        <?php foreach($products as $product): ?>
            <div class="card" data-category="<?php echo htmlspecialchars(strtolower($product['category_name'])); ?>" onclick="toggleExpand(this)" style="cursor: pointer;">
                <?php 
                    $is_out_of_stock = ($product['prod_sale_qty'] <= 0);
                ?>
                <?php if (!empty($product['prod_image'])): ?>
                    <div style="position: relative;" onclick="openLightbox(event, '../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>')">
                        <div style="width: 100%; height: 200px; background-size: cover; background-position: center; background-image: url('../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>'); border-radius: 10px; margin-bottom: 16px; transition: transform 0.3s; <?php echo $is_out_of_stock ? 'filter: grayscale(60%) brightness(0.8);' : ''; ?>"
                             onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        </div>
                        <?php if ($is_out_of_stock): ?>
                            <div style="position: absolute; top: 10px; left: 10px; background: #ef4444; color: white; font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(239,68,68,0.4);">
                                Out of Stock
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="position: relative;">
                        <div style="width: 100%; height: 200px; background-color: #f1f5f9; border-radius: 10px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; color: #94a3b8; border: 2px dashed #e2e8f0;">[ No Image ]</div>
                        <?php if ($is_out_of_stock): ?>
                            <div style="position: absolute; top: 10px; left: 10px; background: #ef4444; color: white; font-size: 0.7rem; font-weight: 800; padding: 4px 10px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; box-shadow: 0 2px 6px rgba(239,68,68,0.4);">
                                Out of Stock
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <h3 style="margin-bottom: 4px;"><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                <p style="font-weight: 700; color: #2563eb; font-size: 1.1rem; margin-bottom: 12px;">RM <?php echo number_format($product['prod_sale_price'] ?? 0, 2); ?></p>

                <p class="short-desc" style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 12px;">
                    <?php echo htmlspecialchars($product['prod_description']); ?>
                </p>

                <div class="view-more-hint" style="text-align: right; margin-top: -5px; margin-bottom: 12px; font-size: 0.75rem; color: #2563eb; font-weight: 700;">
                    View Detail <i class="fa-solid fa-chevron-down"></i>
                </div>

                <div class="expanded-details" style="max-height: 0; overflow: hidden; transition: all 0.4s ease; border-top: 1px solid #f1f5f9; padding-top: 12px; display: none;">
                    <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6; margin-bottom: 16px; margin-top: 8px;">
                        <?php echo htmlspecialchars($product['prod_description']); ?>
                    </p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.8rem; background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Category</span> <?php echo htmlspecialchars($product['category_name']); ?></div>
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Stock</span> <?php echo htmlspecialchars($product['prod_sale_qty']); ?> units</div>
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Status</span> 
                            <span style="color: <?php echo $is_out_of_stock ? '#ef4444' : '#10b981'; ?>; font-weight: 700;">
                                <?php echo $is_out_of_stock ? 'Out of Stock' : 'Available'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="product-actions mt-2" onclick="event.stopPropagation()">
                    <?php if ($is_out_of_stock): ?>
                        <button type="button" disabled style="width: 100%; padding: 12px; border-radius: 10px; font-weight: 700; background: #e5e7eb; color: #9ca3af; border: none; cursor: not-allowed; margin-bottom: 8px;">Out of Stock</button>
                    <?php else: ?>
                        <form action="add_to_cart.php" method="POST" style="margin-bottom: 8px;">
                            <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                            <button type="submit" style="width: 100%; padding: 12px; border-radius: 10px; font-weight: 700;">🛒 Add to Cart</button>
                        </form>
                        <a href="address page.php?type=buy&product_id=<?php echo htmlspecialchars($product['prod_id']); ?>" 
                           style="display: block; width: 100%; padding: 12px; border-radius: 10px; font-weight: 700; background: #10b981; color: white; text-align: center; text-decoration: none; font-size: 0.95rem; box-sizing: border-box;">
                            ⚡ Buy Now
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<script>
// Logout Confirmation Box Logic
function confirmLogout(event) {
    const baseConfirm = confirm("Are you sure you want to log out of your account?");
    if (!baseConfirm) {
        event.preventDefault();
        return false;
    }
    return true;
}

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
    
    const hint = card.querySelector('.view-more-hint');
    
    if (details.style.display === 'none' || details.style.display === '') {
        details.style.display = 'block';
        details.style.maxHeight = '800px'; 
        shortDesc.style.display = 'none';
        hint.style.display = 'none';
        card.style.transform = 'translateY(-4px)';
        card.style.boxShadow = '0 12px 20px -5px rgba(0, 0, 0, 0.1)';
    } else {
        details.style.display = 'none';
        details.style.maxHeight = '0px';
        shortDesc.style.display = '-webkit-box';
        hint.style.display = 'block';
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>