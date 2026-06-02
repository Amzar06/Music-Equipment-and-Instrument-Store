<?php
session_start();


$error_message = "";
$success_message = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize user inputs
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = "All fields are required.";
    } 
    elseif ($new_password !== $confirm_password) {
        $error_message = "New passwords do not match.";
    } 
    elseif (strlen($new_password) < 8) {
        $error_message = "New password must be at least 8 characters long.";
    } 
    else {



        $success_message = "Password updated successfully!";
        

        header("Refresh: 2; URL=home_page.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Change Password</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>Change Password</h2>
      <p>Update your account password</p>

      <?php if (!empty($error_message)): ?>
          <div class="alert error" style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      
      <?php if (!empty($success_message)): ?>
          <div class="alert success" style="color: green; margin-bottom: 15px;"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        
        <label for="current_password">Current Password</label>
        <input type="password" id="current_password" name="current_password" placeholder="Enter current password" required>

        <label for="new_password">New Password</label>
        <input type="password" id="new_password" name="new_password" placeholder="Enter new password" required>

        <label for="confirm_password">Confirm New Password</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>

        <button type="submit">Update Password</button>
      </form>
      
    </div>
  </div>
</body>
</html>