<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>

    <link rel="stylesheet" href="customer.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
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
    </style>
</head>

<body>

<!-- HERO SECTION -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">

    <div class="carousel-inner">

        <!-- Slide 1 -->
        <div class="carousel-item active">
            <div class="d-flex align-items-center justify-content-center bg-dark text-white"
                 style="height:500px;">
                <div class="text-center">
                    <h1>Musical Instruments For Everyone</h1>
                    <p>Buy or rent your favorite instruments today.</p>
                    <a href="product.php" class="btn btn-primary">Shop Now</a>
                </div>
            </div>
        </div>

        <!-- Slide 2 -->
        <div class="carousel-item">
            <div class="d-flex align-items-center justify-content-center bg-secondary text-white"
                 style="height:500px;">
                <div class="text-center">
                    <h1>Premium Pianos</h1>
                    <p>Perfect for beginners and professionals.</p>
                    <a href="product.php" class="btn btn-primary">Explore</a>
                </div>
            </div>
        </div>

        <!-- Slide 3 -->
        <div class="carousel-item">
            <div class="d-flex align-items-center justify-content-center bg-primary text-white"
                 style="height:500px;">
                <div class="text-center">
                    <h1>Rent Before You Buy</h1>
                    <p>Affordable rental plans available.</p>
                    <a href="product.php" class="btn btn-primary">View Rentals</a>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- FEATURED PRODUCTS -->
<section class="container py-5">

    <h2 class="mb-4">Featured Instruments</h2>

    <div class="row">

        <div class="col-md-4">
            <div class="border p-5 text-center bg-light">
                Acoustic Guitar
            </div>
            <h5 class="mt-3">Acoustic Guitar</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <div class="border p-5 text-center bg-light">
                Digital Piano
            </div>
            <h5 class="mt-3">Digital Piano</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <div class="border p-5 text-center bg-light">
                Drum Set
            </div>
            <h5 class="mt-3">Drum Set</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

    </div>

</section>

<!-- SHOP BY CATEGORY -->
<section class="container py-5">

    <h2 class="mb-4">Shop By Category</h2>

    <div class="row">

        <div class="col-md-3">
            <a href="product.php?category=guitar" class="btn btn-outline-dark w-100">
                Guitars
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=piano" class="btn btn-outline-dark w-100">
                Pianos
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=drum" class="btn btn-outline-dark w-100">
                Drums
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=violin" class="btn btn-outline-dark w-100">
                Violins
            </a>
        </div>

    </div>

</section>

<footer class="bg-dark text-white text-center p-3">
    © 2026 Musical Instrument Rental & Sales System
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>