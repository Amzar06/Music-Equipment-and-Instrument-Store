<?php
// Start the session to check if the user is logged in
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Simulated session data for demonstration purposes. 
// Remove or comment out these 2 lines once your actual login system is connected!
$_SESSION['user_id'] = 1; 
$_SESSION['username'] = "Alex"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>
    <link rel="stylesheet" href="customer.css">

    <style>
        /* Modern Reset & Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #111;
        }

        /* --- NAVIGATION HEADER --- */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            border-bottom: 1px solid #eaeaea;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1000;
        }

        .logo h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 25px;
        }

        nav ul li a {
            text-decoration: none;
            color: #111;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        nav ul li a:hover {
            color: #666;
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Sign In / Join Buttons */
        .btn-signin {
            background: none;
            border: 1px solid #ccc;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-signin:hover {
            border-color: #111;
        }

        .btn-join {
            background: #111;
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            transition: background 0.2s;
        }

        .btn-join:hover {
            background: #333;
        }

        /* User Profile Dropdown Styles */
        .user-profile-menu {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 500;
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #555;
            border: 1px solid #ddd;
        }

        .logout-link {
            font-size: 12px;
            color: #ff4d4d;
            text-decoration: none;
            margin-left: 5px;
        }

        /* --- HERO BANNER --- */
        .hero-banner {
            position: relative;
            width: 100%;
            height: 75vh;
            background: linear-gradient(rgba(0,0,0,0.1), rgba(0,0,0,0.3)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?q=80&w=1600') no-repeat center center/cover;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
        }

        .hero-content h2 {
            font-size: 48px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 8px;
            text-shadow: 0px 2px 10px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 16px;
            margin-bottom: 24px;
            font-weight: 300;
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
            transition: transform 0.2s, background 0.2s;
            display: inline-block;
        }

        .btn-shop-hero:hover {
            background: #f0f0f0;
            transform: scale(1.03);
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
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-bottom: 60px;
        }

        .product-card {
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        .product-image-placeholder {
            width: 100%;
            height: 280px;
            background: #f6f6f6;
            border-radius: 6px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 14px;
            background-size: cover;
            background-position: center;
        }

        .product-category {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .product-name {
            font-size: 15px;
            font-weight: 600;
            color: #111;
            margin-bottom: 4px;
        }

        .product-price {
            font-size: 14px;
            color: #111;
            margin-bottom: 12px;
            font-weight: 500;
        }

        .btn-shop-card {
            align-self: flex-start;
            background: #111;
            color: #fff;
            border: none;
            padding: 6px 18px;
            font-size: 12px;
            border-radius: 20px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s;
        }

        .btn-shop-card:hover {
            background: #333;
        }
    </style>
</head>
<body>

    <!-- DYNAMIC NAVIGATION HEADER -->
    <header>
        <div class="logo">
            <h1>Musical Instrument Store</h1>
        </div>
        
        <nav>
            <ul>
                <li><a href="#">All</a></li>
                <li><a href="#">Guitars</a></li>
                <li><a href="#">Pianos</a></li>
                <li><a href="#">Drums</a></li>
            </ul>
        </nav>

        <div class="auth-buttons">
            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- SHOW THIS WHEN LOGGED IN -->
                <div class="user-profile-menu">
                    <div class="avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span>Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <a href="profile.php" class="btn-signin" style="margin-left: 10px;">Profile</a>
                    <a href="logout.php" class="logout-link">Log Out</a>
                </div>
            <?php else: ?>
                <!-- SHOW THIS BY DEFAULT (NOT LOGGED IN) -->
                <button class="btn-signin" onclick="location.href='login.php'">Sign In</button>
                <button class="btn-join" onclick="location.href='register.php'">Join</button>
            <?php endif; ?>
        </div>
    </header>

    <!-- HERO IMAGE BANNER SECTION -->
    <section class="hero-banner">
        <div class="hero-content">
            <h2>Step Into Rhythm</h2>
            <p>Premium build. Unmatched sound output.</p>
            <a href="#featured" class="btn-shop-hero">View Collection</a>
        </div>
    </section>

    <!-- MAIN PRODUCT CORNER -->
    <main class="main-container" id="featured">
        
        <h3 class="section-title">Featured Instruments</h3>

        <div class="product-grid">

            <!-- Card 1 -->
            <div class="product-card">
                <div class="product-image-placeholder" style="background-image: url('https://images.unsplash.com/photo-1510915361894-db8b60106cb1?q=80&w=500');"></div>
                <div class="product-category">Strings</div>
                <h4 class="product-name">Acoustic Guitar</h4>
                <p class="product-price">RM 499.00</p>
                <a href="product_details.php" class="btn-shop-card">Shop</a>
            </div>

            <!-- Card 2 -->
            <div class="product-card">
                <div class="product-image-placeholder" style="background-image: url('https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=500');"></div>
                <div class="product-category">Keys</div>
                <h4 class="product-name">Digital Piano</h4>
                <p class="product-price">RM 1,299.00</p>
                <a href="product_details.php" class="btn-shop-card">Shop</a>
            </div>

            <!-- Card 3 -->
            <div class="product-card">
                <div class="product-image-placeholder" style="background-image: url('https://images.unsplash.com/photo-1543443374-b6fe10a6ab7b?q=80&w=500');"></div>
                <div class="product-category">Percussion</div>
                <h4 class="product-name">Drum Set</h4>
                <p class="product-price">RM 899.00</p>
                <a href="product_details.php" class="btn-shop-card">Shop</a>
            </div>

        </div>
    </main>

</body>
</html>