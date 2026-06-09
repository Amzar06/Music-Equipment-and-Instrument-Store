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
    $phone = $_POST['phone'] ?? '';
    $street = $_POST['street'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $postcode = $_POST['postcode'] ?? '';

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
                $insert = $conn->prepare("INSERT INTO customers (cust_name, cust_email, cust_password, cust_phone_number, cust_street, cust_city, cust_state, cust_postcode) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                if ($insert) {
                    $insert->bind_param("ssssssss", $name, $email, $password, $phone, $street, $city, $state, $postcode);
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

          <label>Phone Number</label>
          <input type="text" name="phone" placeholder="Enter your phone number" required>

          <label>Street Address</label>
          <input type="text" name="street" placeholder="No, Building, Street" required>

          <div style="display: flex; gap: 10px;">
              <div style="flex: 1;">
                  <label>City</label>
                  <input type="text" name="city" placeholder="City" required>
              </div>
              <div style="flex: 1;">
                  <label>Postcode</label>
                  <input type="text" name="postcode" placeholder="Postcode" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
              </div>
          </div>

          <label>State</label>
          <input type="text" name="state" placeholder="State" required>

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