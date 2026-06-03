<?php
// Move session and database logic to the absolute top
session_start();
include '../database.php';

// Check if user is logged in to customize the nav bar
$is_logged_in = isset($_SESSION['cust_id']);
$cust_name = "User";

if ($is_logged_in && isset($conn)) {
    $cust_id = $_SESSION['cust_id'];
    $stmt = $conn->prepare("SELECT cust_name FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $cust_name = explode(' ', trim($row['cust_name']))[0]; // First name only
        }
        $stmt->close();
    }
}

include "includes/header.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Online Shoes Store</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">

  <style>
    body {
      margin: 0;
    }

    /* FIXED NAV STYLING */
    .navbar {
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .navbar-brand {
      font-weight: 700;
      letter-spacing: 1px;
    }
    .nav-link {
      font-weight: 500;
      text-transform: uppercase;
      font-size: 0.9rem;
      letter-spacing: 0.5px;
    }

    /* HERO CAROUSEL STYLING */
    .hero-slide {
      height: 70vh;
      background-size: cover;
      background-position: center;
      position: relative;
    }
    .hero-overlay {
      position: absolute;
      top: 0; left: 0; right: 0; bottom: 0;
      background: rgba(0, 0, 0, 0.35);
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
      text-align: center;
      padding: 20px;
    }
    .hero-overlay h1 {
      font-weight: 800;
      letter-spacing: 2px;
      margin-bottom: 15px;
    }

    /* FEATURED IMAGES */
    .featured-img {
      width: 100%;
      height: 350px;
      object-fit: cover;
      border-radius: 8px;
    }

    /* SPORT CARDS SCROLL */
    .sport-scroll {
      display: flex;
      gap: 20px;
      overflow-x: auto;
      padding-bottom: 15px;
    }
    .sport-card {
      position: relative;
      min-width: 250px;
      flex: 1;
    }
    .sport-img {
      width: 100%;
      height: 300px;
      object-fit: cover;
      border-radius: 8px;
    }
    .sport-btn {
      position: absolute;
      bottom: 15px;
      left: 50%;
      transform: translateX(-50%);
      white-space: nowrap;
    }

    .btn-shop {
      border-radius: 50px;
      padding: 12px 30px;
      font-weight: 600;
    }
  </style>
</head>
<body>

<!-- FIXED TOP BAR (NAVBAR) -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">SHOE STORE</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="mainNavbar">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link active" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="product.php?gender=all">All Shoes</a></li>
        <li class="nav-item"><a class="nav-link" href="product.php?category=25">New Releases</a></li>
        <li class="nav-item"><a class="nav-link" href="product.php?category=26">Lifestyle</a></li>
      </ul>
      
      <div class="d-flex align-items-center gap-3">
        <?php if ($is_logged_in): ?>
          <span class="text-light me-2">Hello, <strong><?php echo htmlspecialchars($cust_name); ?></strong></span>
          <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Dashboard</a>
          <a href="logout_page.php" class="btn btn-danger btn-sm rounded-pill px-3">Log Out</a>
        <?php else: ?>
          <a href="../product/cust login.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Login</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- HERO CAROUSEL (EXPANDED TO 4 SLIDES WITH MORE PICS) -->
<div id="heroCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="4000">
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
    <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="3"></button>
  </div>

  <div class="carousel-inner">
    <!-- Slide 1 -->
    <div class="carousel-item active">
      <div class="hero-slide" style="background-image:url('assets/images/Rosie.jpg');">
        <div class="hero-overlay">
          <h1>LICENSED FOR SKILL</h1>
          <p>Designed for athletes who demand performance.</p>
          <a href="product.php?category=25" class="btn btn-light btn-lg rounded-pill px-4">Shop Now</a>
        </div>
      </div>
    </div>

    <!-- Slide 2 -->
    <div class="carousel-item">
      <div class="hero-slide" style="background-image:url('assets/images/Rosie5.jpg');">
        <div class="hero-overlay">
          <h1>EVERYDAY MOTION</h1>
          <p>Comfort and control for your day-to-day routine.</p>
          <a href="product.php?gender=all" class="btn btn-light btn-lg rounded-pill px-4">Explore</a>
        </div>
      </div>
    </div>

    <!-- Slide 3 -->
    <div class="carousel-item">
      <div class="hero-slide" style="background-image:url('assets/images/Rosie4.jpg');">
        <div class="hero-overlay">
          <h1>STEP INTO STYLE</h1>
          <p>Minimal design. Maximum impact.</p>
          <a href="product.php?category=25" class="btn btn-light btn-lg rounded-pill px-4">View Collection</a>
        </div>
      </div>
    </div>

    <!-- Slide 4 (NEW ADDED PICTURE SLIDE) -->
    <div class="carousel-item">
      <div class="hero-slide" style="background-image:url('assets/images/Rosie2.jpg');">
        <div class="hero-overlay">
          <h1>LIMITLESS HORIZONS</h1>
          <p>Push past boundaries with engineered comfort tech.</p>
          <a href="product.php?category=27" class="btn btn-light btn-lg rounded-pill px-4">See Specials</a>
        </div>
      </div>
    </div>
  </div>

  <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div>

<!-- FEATURED SECTION (EXPANDED TO 4 COLUMNS TO DISPLAY MORE IMAGES) -->
<section class="container py-5">
  <h2 class="mb-4 fw-semibold">Featured Collections</h2>

  <div class="row g-4">
    <!-- Item 1 -->
    <div class="col-sm-6 col-md-3">
      <img src="assets/images/features1.jpg" class="featured-img mb-3" alt="New Arrival">
      <h6>New Arrival</h6>
      <p class="fw-semibold text-truncate">Rosé X Puma</p>
      <a href="product.php?category=25" class="btn btn-dark btn-sm rounded-pill px-3">Shop</a>
    </div>

    <!-- Item 2 -->
    <div class="col-sm-6 col-md-3">
      <img src="assets/images/features2.jpg" class="featured-img mb-3" alt="Lifestyle">
      <h6>Lifestyle</h6>
      <p class="fw-semibold text-truncate">Built for the everyday</p>
      <a href="product.php?category=26" class="btn btn-dark btn-sm rounded-pill px-3">Shop</a>
    </div>

    <!-- Item 3 -->
    <div class="col-sm-6 col-md-3">
      <img src="assets/images/features3.jpg" class="featured-img mb-3" alt="Motor Sport">
      <h6>Motor Sport</h6>
      <p class="fw-semibold text-truncate">Attack Pack Series</p>
      <a href="product.php?category=27" class="btn btn-dark btn-sm rounded-pill px-3">Shop</a>
    </div>

    <!-- Item 4 (NEW ADDED COLS / PICS) -->
    <div class="col-sm-6 col-md-3">
      <img src="assets/images/features4.jpg" class="featured-img mb-3" alt="Limited Edition">
      <h6>Limited Edition</h6>
      <p class="fw-semibold text-truncate">Retro Classic Pro</p>
      <a href="product.php?category=25" class="btn btn-dark btn-sm rounded-pill px-3">Shop</a>
    </div>
  </div>
</section>

<!-- SHOP BY SPORT SECTION -->
<section class="container py-5 shop-by-sport">
  <h2 class="mb-4 fw-semibold">Shop by Sport</h2>
  <div class="sport-scroll">
    <!-- Basketball -->
    <div class="sport-card">
      <img src="assets/images/basketball.jpg" class="sport-img">
      <a href="product.php?category=28" class="btn btn-light rounded-pill sport-btn fw-semibold">Basketball</a>
    </div>
    <!-- Running -->
    <div class="sport-card">
      <img src="assets/images/running.jpg" class="sport-img">
      <a href="product.php?category=29" class="btn btn-light rounded-pill sport-btn fw-semibold">Running</a>
    </div>
    <!-- Football -->
    <div class="sport-card">
      <img src="assets/images/football.jpg" class="sport-img">
      <a href="product.php?category=30" class="btn btn-light rounded-pill sport-btn fw-semibold">Football</a>
    </div>
    <!-- Training -->
    <div class="sport-card">
      <img src="assets/images/training.jpg" class="sport-img">
      <a href="product.php?category=31" class="btn btn-light rounded-pill sport-btn fw-semibold">Training</a>
    </div>
  </div>
</section>

<!-- Bootstrap 5 JavaScript Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php include "includes/footer.php"; ?>
</body>
</html>