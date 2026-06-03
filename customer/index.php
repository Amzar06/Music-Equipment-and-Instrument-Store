<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .hero {
            /* Guaranteed live Unsplash image for a music background banner */
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('https://images.unsplash.com/photo-1511192336575-5a79af67a629?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat;
            height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .hero-content {
            background: rgba(0,0,0,0.5);
            padding: 30px;
            border-radius: 10px;
        }

        .product-img {
            width: 100%;
            height: 250px;
            object-fit: cover;
            border-radius: 10px;
        }

        .category-btn {
            width: 100%;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Musical Instrument Store</a>

        <div class="ms-auto">
            <a href="/Music-Equipment-and-Instrument-Store/product/cust login.php" class="btn btn-outline-light me-2">Login</a>
            <a href="register_page.php" class="btn btn-light">Register</a>
        </div>
    </div>
</nav>

<div class="hero">
    <div class="hero-content">
        <h1>Musical Instrument Rental & Sales</h1>
        <p>Browse instruments without logging in</p>

        <a href="product.php" class="btn btn-primary">
            Shop Now
        </a>
    </div>
</div>

<section class="container py-5">

    <h2 class="mb-4">Featured Instruments</h2>

    <div class="row g-4">

        <div class="col-md-4">
            <img src="https://images.unsplash.com/photo-1510915361894-db8b60106cb1?auto=format&fit=crop&w=600&q=80" class="product-img" alt="Acoustic Guitar">
            <h5 class="mt-3">Acoustic Guitar</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <img src="https://images.unsplash.com/photo-1520523839897-bd0b52f945a0?auto=format&fit=crop&w=600&q=80" class="product-img" alt="Digital Piano">
            <h5 class="mt-3">Digital Piano</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQiLvcVnuRu_HM5vcZU2K6q89uJuTWaib2Ykt8xEz_7ySjfc5j6eGBALIQxmGdc" class="product-img" alt="Drum Set">
            <h5 class="mt-3">Drum Set</h5>
            <a href="product.php" class="btn btn-dark">View</a>
        </div>

    </div>

</section>

<section class="container py-5">

    <h2 class="mb-4">Shop By Category</h2>

    <div class="row g-3">

        <div class="col-md-3">
            <a href="product.php?category=guitar"
               class="btn btn-outline-dark category-btn">
               Guitars
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=piano"
               class="btn btn-outline-dark category-btn">
               Pianos
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=drum"
               class="btn btn-outline-dark category-btn">
               Drums
            </a>
        </div>

        <div class="col-md-3">
            <a href="product.php?category=violin"
               class="btn btn-outline-dark category-btn">
               Violins
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