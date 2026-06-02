<?php
session_start();


$user_name  = $_SESSION['user_name']  ?? "";
$user_email = $_SESSION['user_email'] ?? "";
$user_phone = $_SESSION['user_phone'] ?? "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Profile</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>


  <div class="container">
    <div class="card">
      <h2>User Profile</h2>
      <p>Your account information</p>

      <label for="fullname">Full Name</label>
      <input type="text" id="fullname" value="<?php echo htmlspecialchars($user_name); ?>" readonly>

      <label for="email">Email Address</label>
      <input type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
      
      <label for="phone">Phone Number</label>
      <input type="text" id="phone" value="<?php echo htmlspecialchars($user_phone); ?>" readonly>
      
    </div>
  </div>
</body>
</html>