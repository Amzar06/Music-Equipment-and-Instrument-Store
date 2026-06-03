<?php
session_start();
include '../database.php';

// Force authentication check: Redirect to login page if customer session isn't active
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../product/cust login.php");
    exit();
}

$cust_id = $_SESSION['cust_id'];
$cust_name = "User";

// Fetch the authenticated user's name dynamically from the database
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT cust_name FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $cust_name = $row['cust_name'];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Musical Instrument Store</title>
    <link rel="stylesheet" href="customer.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #ffffff;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px; 
            border-bottom: 1px solid #eaeaea;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1000;
        }

        .logo h1 {
            font-size: 22px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #1d61f2; 
            white-space: nowrap;
        }

        .search-container {
            flex-grow: 1;
            max-width: 400px;
            margin: 0 30px;
        }

        .search-container input {
            width: 100%;
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
        }

        .search-container input:focus {
            border-color: #1d61f2;
        }

        nav {
            margin-right: 30px; 
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        nav ul li a {
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            color: #666; 
            transition: color 0.2s;
        }

        nav ul li a:hover {
            color: #1d61f2;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .welcome-text {
            font-size: 14px;
            font-weight: 500;
            color: #333;
        }

        .welcome-text strong {
            color: #1d61f2;
        }

        .btn-signin {
            background: #1d61f2; 
            color: #fff !important;
            border: 1px solid #1d61f2;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            white-space: nowrap;
        }

        .btn-join {
            background: #111;
            color: #fff; 
            border: none;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            text-decoration: none;
        }

        .logout-link {
            background: #e11d48; 
            color: #fff;
            border: 1px solid #e11d48;
            padding: 8px 18px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            white-space: nowrap;
            transition: background 0.2s;
        }

        .logout-link:hover {
            background: #be123c;
            border-color: #be123c;
        }

        /* --- HERO BANNER --- */
        .hero-banner {
            position: relative;
            width: 100%;
            height: 60vh;
            background: linear-gradient(rgba(0,0,0,0.15), rgba(0,0,0,0.35)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?q=80&w=1600') no-repeat center center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .hero-content h2 {
            font-size: 48px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            color: #fff; 
        }

        .hero-content p {
            font-size: 16px;
            margin-bottom: 24px;
            color: #fff;
        }

        .btn-shop-hero {
            background: #fff;
            color: #111;
            border: none;
            padding: 12px 30px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 25px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        /* --- MAIN CONTENT & GRID --- */
        .main-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .product-box {
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        .product-image {
            width: 100%;
            height: 300px;
            background: #f6f6f6;
            border-radius: 6px;
            margin-bottom: 15px;
            background-size: cover;
            background-position: center;
            transition: transform 0.2s ease;
        }

        .product-box:hover .product-image {
            transform: scale(1.02);
        }

        .product-box h4 {
            margin-bottom: 5px;
            font-size: 16px;
        }

        .product-box button {
            align-self: flex-start;
            background: #111;
            color: #fff;
            border: none;
            padding: 8px 20px;
            font-size: 12px;
            border-radius: 20px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
        }

        .product-box button:hover {
            background: #333;
        }
    </style>
</head>
<body>

    <header>
        <div class="logo">
            <h1>Instrument Store</h1>
        </div>

        <div class="search-container">
            <input type="text" placeholder="Search instruments, brands, gear...">
        </div>
        
        <nav>
            <ul>
                <li><a href="/Music-Equipment-and-Instrument-Store/product/product page.php">All</a></li>
                <li><a href="#">Guitars</a></li>
                <li><a href="#">Pianos</a></li>
                <li><a href="#">Drums</a></li>
            </ul>
        </nav>

        <div class="auth-buttons">
            <?php if (isset($_SESSION['cust_id'])): ?>
                <span class="welcome-text">Hello, <strong><?php echo htmlspecialchars($cust_name); ?></strong></span>
                <a href="logout_page.php" class="logout-link">Log Out</a>
            <?php else: ?>
                <a href="../product/cust login.php" class="btn-signin">Sign In</a>
                <a href="register_page.php" class="btn-join">Join</a>
            <?php endif; ?>
        </div>
    </header>

    <section class="hero-banner">
        <div class="hero-content">
            <h2>Step Into Rhythm</h2>
            <p>Minimal design. Maximum impact.</p>
            <a href="#featured" class="btn-shop-hero">View Collection</a>
        </div>
    </section>

    <main class="main-container" id="featured">
        <h3 class="section-title">Featured Instruments</h3>

        <div class="product-grid">
            <div class="product-box">
                <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1510915361894-db8b60106cb1?q=80&w=500');"></div>
                <h4>Acoustic Guitar</h4>
                <a href="product_details_page.php?id=1"><button>View Details</button></a>
            </div>

            <div class="product-box">
                <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=500');"></div>
                <h4>Digital Piano</h4>
                <a href="product_details_page.php?id=2"><button>View Details</button></a>
            </div>

            <div class="product-box">
                <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1543443374-b6fe10a6ab7b?q=80&w=500');"></div>
                <h4>Drum Set</h4>
                <a href="product_details_page.php?id=3"><button>View Details</button></a>
            </div>

            <div class="product-box">
                <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1564186763535-ebb21ef5277f?q=80&w=500');"></div>
                <h4>Electric Guitar</h4>
                <a href="product_details_page.php?id=4"><button>View Details</button></a>
            </div>

            <div class="product-box">
                <div class="product-image" style="background-image: url('https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcSSinNKHS_yQVS5TOue4wX8OHQ7gZzp6yN1rAVwW3wu4195BsczxYcv_r2A_ma3');"></div>
                <h4>Ukulele</h4>
                <a href="product_details_page.php?id=5"><button>View Details</button></a>
            </div>

            <div class="product-box">
                <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1612225330812-01a9c6b355ec?q=80&w=500');"></div>
                <h4>Violin</h4>
                <a href="product_details_page.php?id=6"><button>View Details</button></a>
            </div>
        </div>
    </main>

</body>
</html>