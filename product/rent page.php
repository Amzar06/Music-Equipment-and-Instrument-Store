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

// Fetch cart count
$is_logged_in = isset($_SESSION['cust_id']);
$cart_count = 0;
if (isset($conn) && $is_logged_in) {
    $count_query = $conn->prepare("SELECT SUM(ci.quantity) as total FROM cart_items ci JOIN cart c ON ci.cart_id = c.cart_id WHERE c.cust_id = ?");
    $count_query->bind_param("i", $cust_id);
    $count_query->execute();
    $count_res = $count_query->get_result()->fetch_assoc();
    $cart_count = $count_res['total'] ?? 0;
    $count_query->close();
}

// Check if user is on rental limit (3 instruments per week)
$can_rent = true;
$rentals_this_week = 0;
$limit = 3;
$next_rentable_date = null;

if (isset($conn)) {
    // Count non-cancelled rentals in the last 7 days
    $one_week_ago_sql = date('Y-m-d H:i:s', strtotime("-1 week"));
    $stmt = $conn->prepare("SELECT COUNT(*) as rental_count, MIN(created_at) as oldest_rental FROM rentals WHERE cust_id = ? AND status != 'Cancelled' AND created_at >= ?");
    if ($stmt) {
        $stmt->bind_param("is", $cust_id, $one_week_ago_sql);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $rentals_this_week = $row['rental_count'];
            if ($rentals_this_week >= $limit) {
                $can_rent = false;
                // Next rentable date is 1 week after the OLDEST rental that falls within the current rolling week
                $stmt_oldest = $conn->prepare("SELECT created_at FROM rentals WHERE cust_id = ? AND status != 'Cancelled' AND created_at >= ? ORDER BY created_at ASC LIMIT 1");
                $stmt_oldest->bind_param("is", $cust_id, $one_week_ago_sql);
                $stmt_oldest->execute();
                if ($oldest = $stmt_oldest->get_result()->fetch_assoc()) {
                    $next_rentable_date = date('d M Y', strtotime("+1 week", strtotime($oldest['created_at'])));
                }
                $stmt_oldest->close();
            }
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Rent Instruments</title>
    <link rel="stylesheet" href="style.css?v=4.0">
    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .flatpickr-calendar { box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-radius: 12px; }
        .flatpickr-day.selected { background: #2563eb !important; border-color: #2563eb !important; }
        
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
<!-- Add Bootstrap & FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .navbar-custom { background-color: #0d3b8e; padding: 12px 0; }
    .navbar-brand, .navbar-nav .nav-link { color: white !important; }
    .nav-link:hover { opacity: 0.8; }
    .container-nav { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
</style>

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

<!-- Lightbox Modal -->
<div id="lightbox" onclick="this.style.display='none'">
    <img id="lightboxImg" src="">
</div>

<div style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80') center/cover; padding: 40px 0; margin-bottom: 40px; border-bottom: 4px solid #7c3aed;">
    <div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <h2 style="margin: 0; font-size: 2.2rem; font-weight: 800; color: white;">Rental Catalog</h2>
            <p style="color: rgba(255,255,255,0.8); margin: 5px 0 0 0;">Flexible rental plans for all instruments</p>
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

<div style="width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 20px;">

    <div class="nav-tabs">
        <a href="product page.php" class="nav-link buy">Buy Instruments</a>
        <a href="rent page.php" class="nav-link rent active">Rent Instruments</a>
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

    <!-- Rental Warning Message -->
    <?php if (!$can_rent): ?>
        <div style="background: #fee2e2; border: 1.5px solid #ef4444; color: #991b1b; padding: 20px; border-radius: 14px; margin-bottom: 32px; display: flex; align-items: flex-start; gap: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
            <span style="font-size: 1.8rem;">⏳</span>
            <div>
                <p style="margin: 0; font-weight: 700; font-size: 1.1rem; margin-bottom: 4px;">Rental Limit Reached (3 per week)</p>
                <p style="margin: 0; font-size: 0.95rem; opacity: 0.9;">You have reached the maximum of <b>3 instruments per week</b>. Please return your current rentals or wait for your quota to reset to ensure fair access for all customers.</p>
                <?php if ($next_rentable_date): ?>
                <p style="margin-top: 10px; font-weight: 700; font-size: 0.9rem; background: rgba(239, 68, 68, 0.1); display: inline-block; padding: 4px 10px; border-radius: 6px;">
                    🔓 You can rent again starting: <?php echo $next_rentable_date; ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div style="background: #f0fdf4; border: 1.5px solid #10b981; color: #166534; padding: 18px; border-radius: 14px; margin-bottom: 32px; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
            <span style="font-size: 1.5rem;">✅</span>
            <div>
                <p style="margin: 0; font-weight: 600; font-size: 0.95rem;">You are eligible to rent! Current quota: <b><?php echo $rentals_this_week; ?> / 3</b> instruments used this week.</p>
                <p style="margin: 0; font-size: 0.85rem; opacity: 0.8;">Customers are permitted to rent up to 3 instruments per rolling week.</p>
            </div>
        </div>
    <?php endif; ?>

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
                         style="width: 100%; height: 180px; background-size: cover; background-position: center; background-image: url('../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>'); border-radius: 10px; margin-bottom: 16px; transition: transform 0.3s;"
                         onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                    </div>
                <?php else: ?>
                    <div style="width: 100%; height: 180px; background-color: #f1f5f9; border-radius: 10px; margin-bottom: 16px; display: flex; align-items: center; justify-content: center; color: #94a3b8; border: 2px dashed #e2e8f0;">[ No Image ]</div>
                <?php endif; ?>
                
                <h3 style="margin-bottom: 4px;"><?php echo htmlspecialchars($product['prod_name']); ?></h3>
                <p style="font-weight: 700; color: #7c3aed; margin-bottom: 12px;">RM <?php echo number_format($product['prod_rental_price'] ?? 0, 2); ?> / day</p>

                <p class="short-desc" style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 8px;">
                    <?php echo htmlspecialchars($product['prod_description']); ?>
                </p>

                <div class="view-more-hint" style="text-align: right; margin-top: -5px; margin-bottom: 12px; font-size: 0.75rem; color: #7c3aed; font-weight: 700;">
                    View Detail <i class="fa-solid fa-chevron-down"></i>
                </div>

                <!-- Expanded Details (Hidden by default) -->
                <div class="expanded-details" style="max-height: 0; overflow: hidden; transition: all 0.4s ease; border-top: 1px solid #f1f5f9; padding-top: 10px; display: none;">
                    <p style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; margin-bottom: 16px; margin-top: 8px;">
                        <?php echo htmlspecialchars($product['prod_description']); ?>
                    </p>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.8rem; background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Category</span> <?php echo htmlspecialchars($product['category_name']); ?></div>
                        <div><span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700;">Stock</span> <?php echo htmlspecialchars($product['prod_rental_qty']); ?> units</div>
                    </div>
                </div>

                <div class="product-actions mt-4" onclick="event.stopPropagation()">
                        <!-- RENT -->
                        <?php 
                            $is_out_of_stock = ($product['prod_rental_qty'] <= 0);
                            $rent_btn_disabled = !$can_rent || $is_out_of_stock;
                            $btn_text = 'Rent Now';
                            if ($is_out_of_stock) $btn_text = 'Out of Stock';
                            elseif (!$can_rent) $btn_text = 'Restriction Active';
                        ?>
                        <form action="address page.php" method="GET" class="rent-form" onsubmit="return validateRental(this)">
                            <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                            <input type="hidden" name="type" value="rent">
                            <input type="hidden" name="price" value="<?php echo htmlspecialchars($product['prod_rental_price']); ?>">
                            
                            <!-- Hidden fields to store split dates for backend -->
                            <input type="hidden" name="start_date" id="start_date_<?php echo $product['prod_id']; ?>">
                            <input type="hidden" name="end_date" id="end_date_<?php echo $product['prod_id']; ?>">
    
                            <div style="margin-bottom: 16px;">
                                <label style="display:block; font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 6px;">Select Rental Period <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="range-picker" placeholder="<?php echo !$rent_btn_disabled ? 'Choose dates..' : $btn_text; ?>" readonly 
                                       data-prod-id="<?php echo $product['prod_id']; ?>"
                                       <?php echo $rent_btn_disabled ? 'disabled' : ''; ?>
                                       style="padding: 12px; font-size: 0.95rem; background: <?php echo !$rent_btn_disabled ? 'white' : '#f1f5f9'; ?>; cursor: <?php echo !$rent_btn_disabled ? 'pointer' : 'not-allowed'; ?>; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%;">
                            </div>
                            <button type="submit" class="rent-btn" <?php echo $rent_btn_disabled ? 'disabled style="background:#94a3b8; cursor:not-allowed;"' : ''; ?> style="width: 100%;"><?php echo $btn_text; ?></button>
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
        card.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1)';
    } else {
        details.style.display = 'none';
        details.style.maxHeight = '0px';
        shortDesc.style.display = '-webkit-box';
        hint.style.display = 'block';
        card.style.transform = 'none';
        card.style.boxShadow = '';
    }
}

let fpInstances = {};

function validateRental(form) {
    const prodId = form.querySelector('input[name="product_id"]').value;
    const start = document.getElementById('start_date_' + prodId).value;
    const end = document.getElementById('end_date_' + prodId).value;
    
    if (!start || !end) {
        alert("Please select your rental period first!");
        // Auto-open the picker for the user
        if (fpInstances[prodId]) {
            fpInstances[prodId].open();
            // Scroll to the picker if not in view
            fpInstances[prodId].element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    }
    return true;
}

document.addEventListener('DOMContentLoaded', function() {
    const pickers = document.querySelectorAll(".range-picker");
    pickers.forEach(el => {
        const prodId = el.getAttribute('data-prod-id');
        fpInstances[prodId] = flatpickr(el, {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const start = instance.formatDate(selectedDates[0], "Y-m-d");
                    const end = instance.formatDate(selectedDates[1], "Y-m-d");
                    
                    document.getElementById('start_date_' + prodId).value = start;
                    document.getElementById('end_date_' + prodId).value = end;
                }
            }
        });
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
