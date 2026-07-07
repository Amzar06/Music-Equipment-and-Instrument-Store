<?php
session_start();
include '../database.php';

$is_logged_in = isset($_SESSION['cust_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { 
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-content {
            background: rgba(0,0,0,0.3);
            padding: 40px;
            border-radius: 20px;
            max-width: 800px;
        }

        .product-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
        }

        .category-btn {
            width: 100%;
            padding: 15px 0;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #0d3b8e !important;
            border-color: #0d3b8e !important;
            color: white !important;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background-color: #082c6c !important;
            border-color: #082c6c !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        /* User Profile Icon Styling */
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
    <div class="container">
        <a class="navbar-brand" href="home_page.php" style="font-weight: 500;">Musical Instrument Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLogged">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navLogged">
            <ul class="navbar-nav ms-auto align-items-center" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link active" href="home_page.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../product/product page.php">Products</a></li>
                
                <?php if ($is_logged_in): ?>
                    <li class="nav-item"><a class="nav-link" href="../product/payment history.php">My Orders</a></li>
                <?php endif; ?>

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
                            <li><a class="dropdown-item" href="user_profile_page.php"><i class="fa-regular fa-id-card me-2"></i> My Profile</a></li>
                            <li><a class="dropdown-item" href="../product/payment history.php"><i class="fa-solid fa-clock-history me-2"></i> Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout_page.php" onclick="return confirmLogout(event);"><i class="fa-solid fa-right-from-bracket me-2"></i> Logout</a></li>
                        <?php else: ?>
                            <li><a class="dropdown-item" href="../product/cust login.php"><i class="fa-solid fa-right-to-bracket me-2"></i> Customer Login</a></li>
                            <li><a class="dropdown-item" href="register_page.php"><i class="fa-solid fa-user-plus me-2"></i> Create Account</a></li>
                        <?php endif; ?>
                    </ul>
                </ul>
            </ul>
        </div>
    </div>
</nav>

<div class="hero">
    <div class="hero-content text-center">
        <?php if ($is_logged_in): ?>
            <h1>Welcome Back, <?php echo htmlspecialchars($_SESSION['cust_name'] ?? 'Musician'); ?>!</h1>
            <p>Ready to pick up your next performance asset?</p>
        <?php else: ?>
            <h1>Musical Instruments For Everyone</h1>
            <p>Discover premium equipment options and premium rental selections tailored for your style.</p>
        <?php endif; ?>
        <a href="../product/product page.php" class="btn btn-primary px-4 py-2 text-white">Explore Instruments</a>
    </div>
</div>

<section class="container py-5">
    <h2 class="mb-4">Featured Instruments</h2>
    <div class="row g-4">
        <div class="col-md-4 text-center">
            <img src="https://images.unsplash.com/photo-1510915361894-db8b60106cb1?auto=format&fit=crop&w=600&q=80" class="product-img" alt="Acoustic Guitar">
            <h5 class="mt-3">Guitars</h5>
            <a href="../product/product page.php?category=guitar+and+basses" class="btn btn-primary px-4 mt-2 text-white">View</a>
        </div>

        <div class="col-md-4 text-center">
            <img src="https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?auto=format&fit=crop&w=600&q=80" class="product-img" alt="Digital Piano">
            <h5 class="mt-3">Digital Piano</h5>
            <a href="../product/product page.php?category=keyboard+and+pianos" class="btn btn-primary px-4 mt-2 text-white">View</a>
        </div>

        <div class="col-md-4 text-center">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiLvcVnuRu_HM5vcZU2K6q89uJuTWaib2Ykt8xEz_7ySjfc5j6eGBALIQxmGdc" class="product-img" alt="Drum Set">
            <h5 class="mt-3">Drum Set</h5>
            <a href="../product/product page.php?category=drums+%26+percussion" class="btn btn-primary px-4 mt-2 text-white">View</a>
        </div>
    </div>
</section>

<section class="container py-5">
    <h2 class="mb-4">Shop By Category</h2>
    <div class="row g-3">
        <div class="col-md-3">
            <a href="../product/product page.php?category=guitar+and+basses" class="btn btn-outline-dark category-btn">
                🎸 Guitar and Basses
            </a>
        </div>
        <div class="col-md-3">
            <a href="../product/product page.php?category=keyboard+and+pianos" class="btn btn-outline-dark category-btn">
                🎹 Keyboard and Pianos
            </a>
        </div>
        <div class="col-md-3">
            <a href="../product/product page.php?category=drums+%26+percussion" class="btn btn-outline-dark category-btn">
                🥁 Drums &amp; Percussion
            </a>
        </div>
        <div class="col-md-3">
            <a href="../product/product page.php?category=orchestral+strings" class="btn btn-outline-dark category-btn">
                🎻 Orchestral Strings
            </a>
        </div>
        <div class="col-md-3">
            <a href="../product/product page.php?category=brass" class="btn btn-outline-dark category-btn">
                🎺 Brass
            </a>
        </div>
        <div class="col-md-3">
            <a href="../product/product page.php?category=woodwinds" class="btn btn-outline-dark category-btn">
                🎷 Woodwinds
            </a>
        </div>
        <div class="col-md-3">
            <a href="../product/product page.php?category=pro+audio+%26+studio+gear" class="btn btn-outline-dark category-btn">
                🎚️ Pro Audio &amp; Studio Gear
            </a>
        </div>
    </div>
</section>

<footer class="bg-dark text-white text-center p-3">
    © 2026 Musical Instrument Store
</footer>

<script>
function confirmLogout(event) {
    const confirmation = confirm("Are you sure you want to log out of your account?");
    if (!confirmation) {
        event.preventDefault();
        return false;
    }
    return true;
}
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>