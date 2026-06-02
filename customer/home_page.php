<?php include "includes/header.php"; ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>

    <link rel="stylesheet" href="customer.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<!-- HERO SECTION -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">

    <div class="carousel-inner">

        <div class="carousel-item active">
            <img src="images/guitar.jpg" class="d-block w-100" height="500">
            <div class="carousel-caption">
                <h1>Musical Instruments For Everyone</h1>
                <p>Buy or rent your favorite instruments today.</p>
                <a href="product.php" class="btn btn-primary">Shop Now</a>
            </div>
        </div>

        <div class="carousel-item">
            <img src="images/piano.jpg" class="d-block w-100" height="500">
            <div class="carousel-caption">
                <h1>Premium Pianos</h1>
                <p>Perfect for beginners and professionals.</p>
                <a href="product.php" class="btn btn-primary">Explore</a>
            </div>
        </div>

        <div class="carousel-item">
            <img src="images/drum.jpg" class="d-block w-100" height="500">
            <div class="carousel-caption">
                <h1>Rent Before You Buy</h1>
                <p>Affordable rental plans available.</p>
                <a href="product.php" class="btn btn-primary">View Rentals</a>
            </div>
        </div>

    </div>

</div>

<!-- FEATURED PRODUCTS -->
<section class="container py-5">

    <h2 class="mb-4">Featured Instruments</h2>

    <div class="row">

        <div class="col-md-4">
            <img src="images/guitar.jpg" class="img-fluid rounded">
            <h5 class="mt-3">Acoustic Guitar</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <img src="images/piano.jpg" class="img-fluid rounded">
            <h5 class="mt-3">Digital Piano</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <img src="images/drum.jpg" class="img-fluid rounded">
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

<?php include "includes/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>