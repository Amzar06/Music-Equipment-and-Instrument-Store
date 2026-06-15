<?php
session_start();
if (isset($_SESSION['cust_id'])) {
    header("Location: home_page.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            /* Small green line at the very top */
            border-top: 5px solid #20c997; 
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }

        /* Synced custom dark-blue button colors */
        .btn-primary,
        .btn-dark,
        .btn-outline-dark {
            background-color: #0d3b8e !important;
            border-color: #0d3b8e !important;
            color: white !important;
        }

        .btn-primary:hover,
        .btn-dark:hover,
        .btn-outline-dark:hover {
            background-color: #082c6c !important;
            border-color: #082c6c !important;
            color: white !important;
        }

        /* Hero Carousel Slider Background Custom Setup */
        .carousel-item-bg {
            height: 500px;
            background-position: center;
            background-size: cover;
            background-repeat: no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Dark tint overlay over banner images to keep text perfectly legible */
        .carousel-overlay {
            background: rgba(0, 0, 0, 0.55);
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Clean Modern Product Card Styling */
        .instrument-card {
            background: #ffffff;
            border: 1px solid #e2e6ef;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .instrument-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        }

        .product-img {
            width: 100%;
            height: 240px;
            object-fit: cover;
        }

        /* Customize Navbar colors to match primary theme */
        .navbar-custom {
            background-color: #0d3b8e !important;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d3b8e; padding: 12px 0;">
    <div class="container">
        <a class="navbar-brand" href="index.php" style="font-weight: 500;">Musical Instrument Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navGuest">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navGuest">
            <ul class="navbar-nav ms-auto" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../product/product page.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="../product/cust login.php">Login</a></li>
                <li class="nav-item"><a class="nav-link" href="register_page.php">Register</a></li>
            </ul>
        </div>
    </div>
</nav>

<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">

        <div class="carousel-item active">
            <div class="carousel-item-bg" style="background-image: url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80');">
                <div class="carousel-overlay text-white text-center">
                    <div>
                        <h1 class="display-4 fw-bold">Musical Instruments For Everyone</h1>
                        <p class="fs-5">Browse instruments without logging in</p>
                        <a href="../product/product page.php" class="btn btn-primary btn-lg mt-2">Shop Now</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-item">
            <div class="carousel-item-bg" style="background-image: url('https://images.unsplash.com/photo-1552422535-c45813c61732?auto=format&fit=crop&w=1600&q=80');">
                <div class="carousel-overlay text-white text-center">
                    <div>
                        <h1 class="display-4 fw-bold">Premium Pianos</h1>
                        <p class="fs-5">Perfect for beginners and professionals.</p>
                        <a href="../product/product page.php" class="btn btn-primary btn-lg mt-2">Explore</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-item">
            <div class="carousel-item-bg" style="background-image: url('https://images.unsplash.com/photo-1445985543470-41fba5c3144a?auto=format&fit=crop&w=1600&q=80');">
                <div class="carousel-overlay text-white text-center">
                    <div>
                        <h1 class="display-4 fw-bold">Rent Before You Buy</h1>
                        <p class="fs-5">Affordable rental plans available.</p>
                        <a href="../product/rent page.php" class="btn btn-primary btn-lg mt-2">View Rentals</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
    
    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</div>

<section class="container py-5">

    <h2 class="mb-4 fw-bold text-secondary">Featured Instruments</h2>

    <div class="row g-4">

        <div class="col-md-4">
            <div class="instrument-card p-3">
                <img src="https://images.unsplash.com/photo-1510915361894-db8b60106cb1?auto=format&fit=crop&w=600&q=80" class="product-img rounded" alt="Acoustic Guitar">
                <h5 class="mt-3 fw-bold mb-1">Acoustic Guitar</h5>
                <p class="text-muted small">Rich tones and clean premium wood craftsmanship.</p>
                <a href="../product/product page.php" class="btn btn-dark w-100">View Product</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="instrument-card p-3">
                <img src="https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?auto=format&fit=crop&w=600&q=80" class="product-img rounded" alt="Digital Piano">
                <h5 class="mt-3 fw-bold mb-1">Digital Piano</h5>
                <p class="text-muted small">Full-weighted natural keys with integrated studio sound engines.</p>
                <a href="../product/product page.php" class="btn btn-dark w-100">View Product</a>
            </div>
        </div>

        <div class="col-md-4">
            <div class="instrument-card p-3">
                <img src="https://images.unsplash.com/photo-1543443374-b6fe10a6ab7b?auto=format&fit=crop&w=600&q=80" class="product-img rounded" alt="Drum Set">
                <h5 class="mt-3 fw-bold mb-1">Drum Set</h5>
                <p class="text-muted small">Full 5-piece professional acoustic set including high-grade brass cymbals.</p>
                <a href="../product/product page.php" class="btn btn-dark w-100">View Product</a>
            </div>
        </div>

    </div>

</section>

<section class="container pb-5">

    <h2 class="mb-4 fw-bold text-secondary">Shop By Category</h2>

    <div class="row g-3">

        <div class="col-md-3">
            <a href="../product/product page.php?category=guitar" class="btn btn-outline-dark w-100 py-3 fw-semibold fs-5">
                🎸 Guitars
            </a>
        </div>

        <div class="col-md-3">
            <a href="../product/product page.php?category=piano" class="btn btn-outline-dark w-100 py-3 fw-semibold fs-5">
                🎹 Pianos
            </a>
        </div>

        <div class="col-md-3">
            <a href="../product/product page.php?category=drum" class="btn btn-outline-dark w-100 py-3 fw-semibold fs-5">
                🥁 Drums
            </a>
        </div>

        <div class="col-md-3">
            <a href="../product/product page.php?category=violin" class="btn btn-outline-dark w-100 py-3 fw-semibold fs-5">
                🎻 Violins
            </a>
        </div>

    </div>

</section>

<footer class="bg-dark text-white text-center p-3">
    © 2026 Musical Instrument Store
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>