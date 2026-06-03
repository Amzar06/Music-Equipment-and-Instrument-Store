<?php
session_start();
include '../database.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (isset($conn)) {
        // Using plain text to match cust login.php
        $stmt = $conn->prepare("SELECT cust_id FROM customers WHERE cust_email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already registered.";
            } else {
                $insert = $conn->prepare("INSERT INTO customers (cust_name, cust_email, cust_password) VALUES (?, ?, ?)");
                if ($insert) {
                    $insert->bind_param("sss", $name, $email, $password);
                    if ($insert->execute()) {
                        $success = "Registration successful! You can now login.";
                    } else {
                        $error = "Registration failed.";
                    }
                    $insert->close();
                }
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>Create Account</h2>
      <p>Register a new account</p>

      <?php if ($error): ?>
          <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; text-align: center;">
              <?php echo htmlspecialchars($error); ?>
          </div>
      <?php endif; ?>
      <?php if ($success): ?>
          <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; text-align: center;">
              <?php echo htmlspecialchars($success); ?>
          </div>
      <?php endif; ?>

      <form action="register_page.php" method="POST">
          <label>Full Name</label>
          <input type="text" name="name" placeholder="Enter your full name" required>

          <label>Email Address</label>
          <input type="email" name="email" placeholder="Enter your email" required>

          <label>Password</label>
          <input type="password" name="password" placeholder="Enter your password" required>

          <label>Confirm Password</label>
          <input type="password" name="confirm_password" placeholder="Confirm your password" required>

          <button type="submit" style="width: 100%; margin-top: 12px;">Register</button>
      </form>
      
      <div style="margin-top: 20px; text-align: center; font-size: 14px;">
          Already have an account? <a href="../product/cust login.php" style="color: var(--accent); text-decoration: none;">Login here</a>
      </div>
    </div>
  </div>

</body>
</html>