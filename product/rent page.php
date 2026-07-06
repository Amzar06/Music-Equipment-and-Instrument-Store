<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Get all existing bookings so we can block those dates in the UI
$bookedPeriods = [];
if (isset($conn)) {
    $bookingCheck = $conn->query("
        SELECT ri.prod_id, r.start_date, r.end_date, ri.rental_qty 
        FROM rental_items ri 
        JOIN rentals r ON ri.rental_id = r.rental_id 
        WHERE r.status NOT IN ('Cancelled', 'Returned')
    ");
    while($booking = $bookingCheck->fetch_assoc()) {
        $p_id = $booking['prod_id'];
        if (!isset($bookedPeriods[$p_id])) $bookedPeriods[$p_id] = [];
        $bookedPeriods[$p_id][] = [
            'from' => $booking['start_date'],
            'to' => $booking['end_date'],
            'qty' => (int)$booking['rental_qty']
        ];
    }
}

// Fetch types/categories
$typeList = [];
if (isset($conn)) {
    $catRes = $conn->query("SELECT * FROM categories");
    if ($catRes) {
        while($c = $catRes->fetch_assoc()) {
            $typeList[] = $c;
        }
    }
}

// Get gear available for rent
$rentalGear = [];
if (isset($conn)) {
    $rentalSql = $conn->query("
        SELECT p.*, c.category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE p.prod_rental_price > 0
          AND p.status != 'Discontinued'
        ORDER BY p.prod_id DESC
    ");
    if ($rentalSql) {
        while($item = $rentalSql->fetch_assoc()) {
            $rentalGear[] = $item;
        }
    }
}

// Current cart status for the badge
$userLoggedIn = isset($_SESSION['cust_id']);
$itemsInCart = 0;
if (isset($conn) && $userLoggedIn) {
    $cartCountStmt = $conn->prepare("SELECT SUM(ci.quantity) as total FROM cart_items ci JOIN cart c ON ci.cart_id = c.cart_id WHERE c.cust_id = ?");
    $cartCountStmt->bind_param("i", $cust_id);
    $cartCountStmt->execute();
    $countData = $cartCountStmt->get_result()->fetch_assoc();
    $itemsInCart = $countData['total'] ?? 0;
    $cartCountStmt->close();
}

// Rental limit (3 per week) removed per user request.
// Any user can now rent any number of instruments.
$can_rent = true;
$rentals_this_week = 0;
$limit = 999;
$next_rentable_date = null;
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

        /* Profile Icon Dropdown */
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
<!-- Bootstrap & FontAwesome -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                    <a class="nav-link dropdown-toggle user-dropdown-toggle" id="userMenuRent" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-circle-user"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm mt-2" aria-labelledby="userMenuRent">
                        <?php if ($userLoggedIn): ?>
                            <li class="px-3 py-1 text-muted small fw-bold text-uppercase">
                                Hi, <?php echo htmlspecialchars($_SESSION['cust_name'] ?? 'Customer'); ?>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="../customer/user_profile_page.php"><i class="fa-regular fa-id-card me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="payment history.php"><i class="fa-solid fa-clock-history me-2"></i> Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../customer/logout_page.php" onclick="return confirmLogout(event);"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="cust login.php"><i class="fa-solid fa-right-to-bracket me-2"></i> Login</a></li>
                            <li><a class="dropdown-item" href="../customer/register_page.php"><i class="fa-solid fa-user-plus me-2"></i> Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
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
                <?php if ($itemsInCart > 0): ?>
                    <span style="position: absolute; top: -8px; right: -8px; background: #ef4444; color: white; font-size: 0.65rem; padding: 2px 6px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                        <?php echo $itemsInCart; ?>
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
        <label for="categoryFilter" style="font-weight: 600; color: var(--text-primary);">Filter Type:</label>
        <select id="categoryFilter" style="padding: 10px 16px; font-size: 1rem; border-radius: 10px; border: 1.5px solid var(--card-border); background: #f8fafc; color: var(--text-primary); cursor: pointer;" onchange="filterCategory()">
            <option value="all">All Gear</option>
            <?php foreach ($typeList as $cat): ?>
                <option value="<?php echo htmlspecialchars(strtolower($cat['category_name'])); ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div style="background: #f0fdf4; border: 1.5px solid #10b981; color: #166534; padding: 18px; border-radius: 14px; margin-bottom: 32px; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <span style="font-size: 1.5rem;">📅</span>
        <div>
            <p style="margin: 0; font-weight: 600; font-size: 0.95rem;">Availability Check Active</p>
            <p style="margin: 0; font-size: 0.85rem; opacity: 0.8;">Instruments are available based on their individual booking schedule. Select a date range to see availability.</p>
        </div>
    </div>

    <div style="margin-bottom: 20px;">
        <?php if (isset($_GET['error']) && $_GET['error'] === 'already_booked'): ?>
            <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 10px 20px; border-radius: 10px; font-weight: 600; font-size: 0.9rem; animation: shake 0.5s;">
                ⚠️ <?php echo htmlspecialchars($_GET['name'] ?? 'Item'); ?> is already booked for these dates (including maintenance buffer).
            </div>
        <?php endif; ?>
    </div>

    <div class="product-grid" id="productGrid">
    <?php if (empty($rentalGear)): ?>
        <div style="text-align: center; color: var(--text-secondary); grid-column: 1 / -1; padding: 48px;">
            <h3>Nothing available for rent right now.</h3>
        </div>
    <?php else: ?>
        <?php foreach($rentalGear as $product): ?>
            <div class="card" data-category="<?php echo htmlspecialchars(strtolower($product['category_name'])); ?>" onclick="toggleExpand(this)" style="cursor: pointer;">
                <?php if (!empty($product['prod_image'])): ?>
                    <div style="position: relative;" onclick="openLightbox(event, '../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>')">
                        <div style="width: 100%; height: 180px; background-size: cover; background-position: center; background-image: url('../uploads/<?php echo htmlspecialchars($product['prod_image']); ?>'); border-radius: 10px; margin-bottom: 16px; transition: transform 0.3s;"
                             onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                        </div>
                        <div style="position: absolute; top: 10px; right: 10px; background: rgba(0, 0, 0, 0.5); color: white; width: 32px; height: 32px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.9rem; transition: background 0.2s;" title="Maximize image"
                             onmouseover="this.style.background='rgba(0,0,0,0.8)'" onmouseout="this.style.background='rgba(0,0,0,0.5)'">
                            <i class="fa-solid fa-expand"></i>
                        </div>
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
                    
                    <div style="font-size: 0.8rem; background: #f8fafc; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                        <span style="color: #64748b; display: block; font-size: 0.7rem; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">Instrument Category</span> 
                        <span style="font-weight: 600; color: #1e293b;"><?php echo htmlspecialchars($product['category_name']); ?></span>
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
                        <form action="add_to_cart.php" method="POST" class="rent-form" onsubmit="return validateRental(this)" style="display: flex; flex-direction: column; gap: 8px;">
                            <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                            <input type="hidden" name="type" value="rent">
                            
                            <!-- Hidden fields to store split dates for backend -->
                            <input type="hidden" name="start_date" id="start_date_<?php echo $product['prod_id']; ?>">
                            <input type="hidden" name="end_date" id="end_date_<?php echo $product['prod_id']; ?>">
    
                            <div style="margin-bottom: 8px;">
                                <label style="display:block; font-size: 0.85rem; font-weight: 700; color: #64748b; margin-bottom: 6px;">Select Rental Period <span style="color: #ef4444;">*</span></label>
                                <input type="text" class="range-picker" placeholder="<?php echo !$rent_btn_disabled ? 'Choose dates..' : $btn_text; ?>" readonly 
                                       data-prod-id="<?php echo $product['prod_id']; ?>"
                                       data-stock="<?php echo htmlspecialchars($product['prod_rental_qty']); ?>"
                                       <?php echo $rent_btn_disabled ? 'disabled' : ''; ?>
                                       style="padding: 12px; font-size: 0.95rem; background: <?php echo !$rent_btn_disabled ? 'white' : '#f1f5f9'; ?>; cursor: <?php echo !$rent_btn_disabled ? 'pointer' : 'not-allowed'; ?>; border: 1px solid #e2e8f0; border-radius: 8px; width: 100%;">
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <button type="submit" name="add_to_cart" class="btn btn-primary" <?php echo $rent_btn_disabled ? 'disabled style="background:#94a3b8; border-color:#94a3b8; cursor:not-allowed;"' : ''; ?> style="padding: 12px; border-radius: 10px; font-weight: 700;">🛒 Add to Cart</button>
                                <button type="button" class="btn btn-success" <?php echo $rent_btn_disabled ? 'disabled style="background:#059669; border-color:#059669; cursor:not-allowed;"' : ''; ?> 
                                        onclick="if(validateRental(this.form)) { this.form.action='address page.php'; this.form.method='GET'; this.form.submit(); }"
                                        style="padding: 12px; border-radius: 10px; font-weight: 700; background: #10b981; border-color: #10b981; color: white;">⚡ Rent Now</button>
                            </div>
                        </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    </div>
</div>

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmLogout(event) {
    const confirmed = confirm("Are you sure you want to log out of your account?");
    if (!confirmed) {
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

function toggleExpand(el) {
    const details = el.querySelector('.expanded-details');
    const intro = el.querySelector('.short-desc');
    const moreBtn = el.querySelector('.view-more-hint');
    
    if (details.style.display === 'none' || details.style.display === '') {
        details.style.display = 'block';
        details.style.maxHeight = '800px'; 
        intro.style.display = 'none';
        moreBtn.style.display = 'none';
        el.style.transform = 'translateY(-4px)';
        el.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1)';
    } else {
        details.style.display = 'none';
        details.style.maxHeight = '0px';
        intro.style.display = '-webkit-box';
        moreBtn.style.display = 'block';
        el.style.transform = 'none';
        el.style.boxShadow = '';
    }
}

let fpInstances = {};

function validateRental(currentForm) {
    const pId = currentForm.querySelector('input[name="prod_id"]').value;
    const startVal = document.getElementById('start_date_' + pId).value;
    const endVal = document.getElementById('end_date_' + pId).value;
    
    if (!startVal || !endVal) {
        // Instead of just an alert, let's make it look like we are "asking"
        alert("Wait! You haven't picked your rental dates yet. Please choose when you'd like the instrument.");
        
        if (fpInstances[pId]) {
            fpInstances[pId].open();
            fpInstances[pId].element.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return false;
    }
    return true;
}

const bookedPeriodsJson = <?php echo json_encode($bookedPeriods); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const datePickers = document.querySelectorAll(".range-picker");
    datePickers.forEach(picker => {
        const pId = picker.getAttribute('data-prod-id');
        const stockLimit = parseInt(picker.getAttribute('data-stock') || '1');
        const bookedForThisItem = bookedPeriodsJson[pId] || [];
        
        fpInstances[pId] = flatpickr(picker, {
            mode: "range",
            minDate: "today",
            dateFormat: "Y-m-d",
            disable: [
                function(date) {
                    const checkDate = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 0, 0, 0, 0);
                    let activeBookings = 0;
                    for (const range of bookedForThisItem) {
                        const [fY, fM, fD] = range.from.split('-');
                        const start = new Date(fY, fM - 1, fD, 0, 0, 0, 0);
                        
                        const [tY, tM, tD] = range.to.split('-');
                        const end = new Date(tY, tM - 1, tD, 23, 59, 59, 999);
                        end.setDate(end.getDate() + 2); // 2-day buffer
                        
                        if (checkDate >= start && checkDate <= end) {
                            activeBookings += range.qty;
                        }
                    }
                    return activeBookings >= stockLimit;
                }
            ],
            onClose: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const s = instance.formatDate(selectedDates[0], "Y-m-d");
                    const e = instance.formatDate(selectedDates[1], "Y-m-d");
                    
                    document.getElementById('start_date_' + pId).value = s;
                    document.getElementById('end_date_' + pId).value = e;
                }
            }
        });
    });
});

function filterCategory() {
    const chosen = document.getElementById('categoryFilter').value;
    const allItems = document.querySelectorAll('.card');
    allItems.forEach(item => {
        if (chosen === 'all' || item.getAttribute('data-category') === chosen) {
            item.style.display = ''; 
        } else {
            item.style.display = 'none';
        }
    });
}
</script>

</body>
</html>
