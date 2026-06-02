<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .dark-blue-btn {
            background-color: #0d3b8e;
            border-color: #0d3b8e;
            color: white;
        }

        .dark-blue-btn:hover {
            background-color: #082c6c;
            border-color: #082c6c;
            color: white;
        }

        .hero-section {
            background-color: #f8f9fa;
            padding: 80px 20px;
            text-align: center;
        }

        .category-btn {
            width: 100%;
        }
    </style>
</head>

<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Musical Instrument Store</a>

        <div class="ms-auto">
            <a href="cust_login.php" class="btn dark-blue-btn me-2">Login</a>
            <a href="customer_register.php" class="btn dark-blue-btn">Register</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <h1>Welcome to Musical Instrument Store</h1>
    <p>Browse and rent musical instruments easily.</p>

    <a href="product.php" class="btn dark-blue-btn btn-lg">
        Browse Products
    </a>
</section>

<!-- Featured Instruments -->
<section class="container py-5">

    <h2 class="text-center mb-4">Featured Instruments</h2>

    <div class="row text-center">

        <div class="col-md-4">
            <h5>Acoustic Guitar</h5>
            <p>High quality guitar for beginners and professionals.</p>
            <a href="product.php" class="btn dark-blue-btn">View</a>
        </div>

        <div class="col-md-4">
            <h5>Digital Piano</h5>
            <p>Perfect for learning and performances.</p>
            <a href="product.php" class="btn dark-blue-btn">View</a>
        </div>

        <div class="col-md-4">
            <h5>Drum Set</h5>
            <p>Complete drum set available for rental and purchase.</p>
            <a href="product.php" class="btn dark-blue-btn">View</a>
        </div>

    </div>

</section>

<!-- Categories -->
<section class="container py-5">

    <h2 class="text-center mb-4">Shop By Category</h2>

    <div class="row g-3">

        <div class="col-md-3">
            <a href="product.php?category=guitar"
               class="btn dark-blue-btn category-btn">
                Guitars
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=piano"
               class="btn dark-blue-btn category-btn">
                Pianos
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=drum"
               class="btn dark-blue-btn category-btn">
                Drums
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=violin"
               class="btn dark-blue-btn category-btn">
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