<?php
// 1. Move session and database logic to the absolute top of the file before any HTML renders
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: ../product/cust login.php");
    exit();
}

$cust_id = $_SESSION['cust_id'];
$cust_name = "User";

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT cust_name FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $cust_name = $row['cust_name'];
        }
        $stmt->close();
    }
}

// 2. Include the shared universal header
include "includes/header.php"; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Customer Dashboard - Online Shoes Store</title>
  
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">

  <style>
    body {
      margin: 0;
      background-color: #f8f9fa; /* Light background typical for clean e-commerce profiles */
    }

    /* Custom styling for dashboard buttons using Bootstrap patterns */
    .dashboard-card {
      border: none;
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .btn-dashboard {
      width: 100%;
      border-radius: 50px; /* Matching the .btn-shop / pill style of the storefront */
      padding: 12px 30px;
      font-weight: 600;
      transition: all 0.2s ease-in-out;
    }
  </style>
</head>
<body>

<section class="container py-5 d-flex justify-content-center">
  <div class="card dashboard-card w-100 p-4 p-md-5" style="max-width: 500px;">
    
    <h2 class="mb-2 fw-semibold text-center">
      Welcome, <?php echo htmlspecialchars(explode(' ', trim($cust_name))[0] ?? 'User'); ?>!
    </h2>
    <p class="text-muted text-center mb-4">Select an option below to manage your account.</p>

    <div class="d-grid gap-3">
      <a href="user_profile_page.php" class="btn btn-outline-dark btn-dashboard">
        View Profile
      </a>
      
      <a href="edit_profile_page.php" class="btn btn-outline-dark btn-dashboard">
        Edit Profile
      </a>
      
      <a href="change_password_page.php" class="btn btn-outline-dark btn-dashboard">
        Change Password
      </a>
      
      <a href="../product/product page.php" class="btn btn-dark btn-dashboard">
        Shop Instruments
      </a>
      
      <div class="pt-4 mt-2 border-top">
        <a href="logout_page.php" class="btn btn-outline-danger btn-dashboard">
          Log Out
        </a>
      </div>
    </div>

  </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php 
// 3. Include the shared universal footer
include "includes/footer.php"; 
?>
</body>
</html>