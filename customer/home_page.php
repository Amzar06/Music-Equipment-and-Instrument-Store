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

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Musical Instrument Store</a>

        <div class="ms-auto">
            <a href="cust_login.php" class="btn btn-outline-light me-2">Login</a>
            <a href="customer_register.php" class="btn btn-light">Register</a>
        </div>
    </div>
</nav>

<!-- Hero Banner -->
<div class="container-fluid p-0">
    <img src="images/banner.jpg" class="img-fluid w-100" alt="Banner">
</div>

<!-- Featured Products -->
<section class="container py-5">

    <h2 class="text-center mb-4">Featured Instruments</h2>

    <div class="row">

        <div class="col-md-4 text-center">
            <img src="images/guitar.jpg" class="img-fluid rounded mb-3">
            <h5>Acoustic Guitar</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4 text-center">
            <img src="images/piano.jpg" class="img-fluid rounded mb-3">
            <h5>Digital Piano</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4 text-center">
            <img src="images/drum.jpg" class="img-fluid rounded mb-3">
            <h5>Drum Set</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

    </div>

</section>

<!-- Categories -->
<section class="container py-5">

    <h2 class="text-center mb-4">Shop By Category</h2>

    <div class="row g-3">

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