<?php
session_start();


$error_message = "";
$success_message = "";


$current_name  = "";
$current_email = "";
$current_phone = "";


// 2. HANDLE FORM SUBMISSION
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize user inputs
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone    = trim($_POST['phone'] ?? '');

    // Basic Validation
    if (empty($fullname) || empty($email) || empty($phone)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        /* 3. DATABASE UPDATE LOGIC (Placeholder)
          This is where you execute an UPDATE SQL query to save the new details.
          
          Example:
          $sql = "UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?";
        */

        // Update our local variables so the form displays the new data right away
        $current_name  = $fullname;
        $current_email = $email;
        $current_phone = $phone;

        $success_message = "Profile updated successfully!";
        
        // Optional: Redirect back to home page after 2 seconds
        header("Refresh: 2; URL=home_page.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>Edit Profile</h2>
      <p>Update your profile information</p>

      <?php if (!empty($error_message)): ?>
          <div class="alert error" style="color: red; margin-bottom: 15px;"><?php echo htmlspecialchars($error_message); ?></div>
      <?php endif; ?>
      
      <?php if (!empty($success_message)): ?>
          <div class="alert success" style="color: green; margin-bottom: 15px;"><?php echo htmlspecialchars($success_message); ?></div>
      <?php endif; ?>

      <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($current_name); ?>" placeholder="Name" required> 

        <label for="email">Email Address</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($current_email); ?>" placeholder="john@gmail.com" required>

        <label for="phone">Phone Number</label>
        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($current_phone); ?>" placeholder="0123456789" required> 

        <button type="submit">Save Changes</button>
      </form>
      
    </div>
  </div>
</body>
</html>
