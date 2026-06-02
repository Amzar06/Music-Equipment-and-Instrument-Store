<?php
// Start the session if needed
session_start();

// Initialize message variables
$error_message = "";
$success_message = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize email input
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

    // 1. Validation: Check if empty
    if (empty($email)) {
        $error_message = "Please enter your email address.";
    } 
    // 2. Validation: Check if email format is valid
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } 
    else {
        /* 3. DATABASE & EMAIL LOGIC (Placeholder)
           This is where you would typically:
           - Check if the email exists in your admin/users database table.
           - If it exists, generate a unique, time-sensitive token.
           - Store that token in a `password_resets` table in your database.
           - Send an email to the user with a link like: 
             https://yourwebsite.com/reset_password.php?token=XYZ...
        */

        // Simulating a successful submission (Security tip: even if the email doesn't 
        // exist, it's often best practice to show a generic success message to prevent 
        // malicious users from guessing valid admin emails).
        $success_message = "If that email is registered, a password reset link has been sent.";
        
        // Optional: Redirect back to login page after 3 seconds
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