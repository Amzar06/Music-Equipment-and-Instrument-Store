<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musical Instrument Store</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .hero {
            background: url('images/banner.jpg') center/cover no-repeat;
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

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Musical Instrument Store</a>

<<<<<<< HEAD
        <div class="ms-auto">
            <a href="cust_login.php" class="btn btn-outline-light me-2">Login</a>
            <a href="customer_register.php" class="btn btn-light">Register</a>
        </div>
=======
    <div class="carousel-inner">

        <!-- Slide 1 -->
        <div class="carousel-item active">
            <div class="d-flex align-items-center justify-content-center bg-dark text-white"
                 style="height:500px;">
                <div class="text-center">
                    <h1>Musical Instruments For Everyone</h1>
                    <p>Buy or rent your favorite instruments today.</p>
                    <a href="../product/product page.php" class="btn btn-primary">Shop Now</a>
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
                    <a href="../product/product page.php" class="btn btn-primary">Explore</a>
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
                    <a href="../product/product page.php" class="btn btn-primary">View Rentals</a>
                </div>
            </div>
        </div>

>>>>>>> b759cffec65d15947fa942bbce6ba1957157e339
    </div>
</nav>

<!-- Hero Section -->
<div class="hero">
    <div class="hero-content">
        <h1>Musical Instrument Rental & Sales</h1>
        <p>Browse instruments without logging in</p>

        <a href="product.php" class="btn btn-primary">
            Shop Now
        </a>
    </div>
</div>

<!-- Featured Instruments -->
<section class="container py-5">

    <h2 class="mb-4">Featured Instruments</h2>

    <div class="row">

        <div class="col-md-4">
            <img src="images/guitar.jpg" class="product-img">
            <h5 class="mt-3">Acoustic Guitar</h5>
            <a href="../product/product page.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <img src="images/piano.jpg" class="product-img">
            <h5 class="mt-3">Digital Piano</h5>
            <a href="../product/product page.php" class="btn btn-dark">View</a>
        </div>

        <div class="col-md-4">
            <img src="images/drum.jpg" class="product-img">
            <h5 class="mt-3">Drum Set</h5>
            <a href="../product/product page.php" class="btn btn-dark">View</a>
        </div>

    </div>

</section>

<!-- Categories -->
<section class="container py-5">

    <h2 class="mb-4">Shop By Category</h2>

    <div class="row">

        <div class="col-md-3">
<<<<<<< HEAD
            <a href="product.php?category=guitar"
               class="btn btn-outline-dark category-btn">
               Guitars
=======
            <a href="../product/product page.php?category=guitar" class="btn btn-outline-dark w-100">
                Guitars
>>>>>>> b759cffec65d15947fa942bbce6ba1957157e339
            </a>
        </div>

        <div class="col-md-3">
<<<<<<< HEAD
            <a href="product.php?category=piano"
               class="btn btn-outline-dark category-btn">
               Pianos
=======
            <a href="../product/product page.php?category=piano" class="btn btn-outline-dark w-100">
                Pianos
>>>>>>> b759cffec65d15947fa942bbce6ba1957157e339
            </a>
        </div>

        <div class="col-md-3">
<<<<<<< HEAD
            <a href="product.php?category=drum"
               class="btn btn-outline-dark category-btn">
               Drums
=======
            <a href="../product/product page.php?category=drum" class="btn btn-outline-dark w-100">
                Drums
>>>>>>> b759cffec65d15947fa942bbce6ba1957157e339
            </a>
        </div>

        <div class="col-md-3">
<<<<<<< HEAD
            <a href="product.php?category=violin"
               class="btn btn-outline-dark category-btn">
               Violins
=======
            <a href="../product/product page.php?category=violin" class="btn btn-outline-dark w-100">
                Violins
>>>>>>> b759cffec65d15947fa942bbce6ba1957157e339
            </a>
        </div>

    </div>

</section>

<!-- Footer -->
<footer class="bg-dark text-white text-center p-3">
    © 2026 Musical Instrument Store
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>