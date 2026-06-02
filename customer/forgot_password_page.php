<?php
session_start();


$error_message = "";
$success_message = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize email input
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } 
    else {
        $success_message = "If that email is registered, a password reset link has been sent.";
        
       
        header("Refresh: 3; URL=login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>


  <div class="container">
    <div class="card">
      <h2>Forgot Password</h2>
      <p>Enter your email to reset password</p>

      <?php if (!empty($error_message)): ?>
          <div class="alert error" style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      
      <?php if (!empty($success_message)): ?>
          <div class="alert success" style="color: green; margin-bottom: 15px;"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required>
        
        <button type="submit">Send Reset Link</button>
      </form>
      
      <div style="margin-top: 15px; text-align: center;">
          <a href="login.php" style="color: #666; text-decoration: none; font-size: 14px;">Back to Login</a>
      </div>

    </div>
  </div>
</body>
</html>