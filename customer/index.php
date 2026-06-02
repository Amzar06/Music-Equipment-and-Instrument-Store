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
<html>
<head>
    <title>Musical Instrument Store</title>
    <link rel="stylesheet" href="customer.css">

    <style>
        /* Modern Layout & Positioning (Preserving original font colors) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: #ffffff;
        }

        /* --- NAVIGATION HEADER --- */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 40px;
            border-bottom: 1px solid #ddd;
            position: sticky;
            top: 0;
            background: #fff;
            z-index: 1000;
        }

        .logo h1 {
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        }

        .auth-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Clean Sign In / Join Buttons from Screenshot */
        .btn-signin {
            background: none;
            border: 1px solid #ccc;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        .btn-join {
            background: #111;
            color: #fff; /* Button background contrast color */
            border: none;
            padding: 7px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        /* Dynamic User Profile Layout */
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
            color: #666;
            border: 1px solid #ddd;
        }

        .logout-link {
            font-size: 12px;
            text-decoration: none;
            margin-left: 5px;
        }

        /* --- HERO BANNER --- */
        .hero-banner {
            position: relative;
            width: 100%;
            height: 70vh;
            background: linear-gradient(rgba(0,0,0,0.05), rgba(0,0,0,0.15)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?q=80&w=1600') no-repeat center center/cover;
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
            color: #fff; /* Keeps text legible over background image */
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
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 30px;
        }

        .product-box {
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        /* Box styling changed to modern image display card */
        .product-image {
            width: 100%;
            height: 300px;
            background: #f6f6f6;
            border-radius: 6px;
            margin-bottom: 15px;
            background-size: cover;
            background-position: center;
        }

        .product-box h4 {
            margin-bottom: 5px;
            font-size: 16px;
        }

        .product-box p {
            color: #666; /* Kept your original hex color style */
            font-size: 14px;
            margin-bottom: 12px;
        }

        .product-box button {
            align-self: flex-start;
            background: #111;
            color: #fff;
            border: none;
            padding: 6px 18px;
            font-size: 12px;
            border-radius: 20px;
            cursor: pointer;
        }
    </style>
</head>
<body>

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
                <div class="user-profile-menu">
                    <div class="avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <span>Hi, <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                    <a href="profile.php" class="btn-signin" style="margin-left: 10px;">Profile</a>
                    <a href="logout.php" class="logout-link">Log Out</a>
                </div>
            <?php else: ?>
                <button class="btn-signin" onclick="location.href='login.php'">Sign In</button>
                <button class="btn-join" onclick="location.href='register.php'">Join</button>
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
                <p>RM 499.00</p>
                <a href="product_details.php">
                    <button>View Details</button>
                </a>
            </div>

            <div class="product-box">
                <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=500');"></div>
                <h4>Digital Piano</h4>
                <p>RM 1,299.00</p>
                <a href="product_details.php">
                    <button>View Details</button>
                </a>
            </div>

            <div class="product-box">
                <div class="product-image" style="background-image: url('https://images.unsplash.com/photo-1543443374-b6fe10a6ab7b?q=80&w=500');"></div>
                <h4>Drum Set</h4>
                <p>RM 899.00</p>
                <a href="product_details.php">
                    <button>View Details</button>
                </a>
            </div>

        </div>
    </main>

</body>
</html>