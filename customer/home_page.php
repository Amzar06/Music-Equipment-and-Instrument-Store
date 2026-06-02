<?php

session_start();


if (!isset($_SESSION['user_id'])) {
    header("Location: /Music-Equipment-and-Instrument-Store/product/cust login.php");
    exit(); 
}


$user_name = $_SESSION['user_name'] ?? "User";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Home Page</title>
  <link rel="stylesheet" href="customer.css">
  <style>
    .button-group {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px; 
      margin-top: 20px;
    }

    .btn-link {
      display: inline-block;
      width: 450px;             
      padding: 12px 0;          
      background-color: #1d61f2;
      color: white;
      text-align: center;
      text-decoration: none;    
      border-radius: 8px;       
      font-weight: bold;
      font-size: 14px;
      transition: background-color 0.2s ease;
    }

    .btn-link:hover {
      background-color: #124ec4; 
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="card" style="text-align: center;"> 
      
      <h2>Welcome, <?php echo htmlspecialchars($user_name); ?>!</h2>
      <p>Select an option below:</p>

      <div class="button-group">
        <a href="edit_profile_page.php" class="btn-link">Edit Profile</a>
        <a href="change_password_page.php" class="btn-link">Change Password</a>
        <a href="product_details_page.php" class="btn-link">View Products</a>
        <a href="logout_page.php" class="btn-link" style="background-color: #d9534f;">Log Out</a> 
      </div>

    </div> 
  </div> 

</body>
</html>