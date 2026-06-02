<?php
session_start();


$error_message = "";
$success_message = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
  
    $fullname         = trim($_POST['fullname'] ?? '');
    $email            = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';


    if (empty($fullname) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } 
  
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } 

    elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } 
   
    elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } 
    else {
        

        $success_message = "Account created successfully! Redirecting to login...";
        
        header("Refresh: 2; URL=/Music-Equipment-and-Instrument-Store/product/cust login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>Create Account</h2>
      <p>Register a new account</p>

      <?php if (!empty($error_message)): ?>
          <div class="alert error" style="color: #d9534f; margin-bottom: 15px; font-weight: bold;"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      
      <?php if (!empty($success_message)): ?>
          <div class="alert success" style="color: #5cb85c; margin-bottom: 15px; font-weight: bold;"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" placeholder="Enter your full name" value="<?php echo isset($fullname) ? htmlspecialchars($fullname) : ''; ?>" required>

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required>

        <label for="confirm_password">Confirm Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>

        <button type="submit">Register</button>
      </form>
      
      <div style="margin-top: 15px; text-align: center;">
          <p style="font-size: 14px; color: #666;">Already have an account? <a href="/Music-Equipment-and-Instrument-Store/product/cust login.php" style="color: #007bff; text-decoration: none;">Login here</a></p>
      </div>

    </div>
  </div>

</body>
</html>