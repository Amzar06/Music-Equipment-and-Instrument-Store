<?php
session_start();
include '../database.php';
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../product/cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (isset($conn)) {
        // verify current password
        $stmt = $conn->prepare("SELECT cust_password FROM customers WHERE cust_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $cust_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if ($row['cust_password'] === $current_password) {
                    $update = $conn->prepare("UPDATE customers SET cust_password = ? WHERE cust_id = ?");
                    if ($update) {
                        $update->bind_param("si", $new_password, $cust_id);
                        if ($update->execute()) {
                            $success = "Password updated successfully!";
                        } else {
                            $error = "Failed to update password.";
                        }
                        $update->close();
                    }
                } else {
                    $error = "Current password is incorrect.";
                }
            } else {
                $error = "User not found.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Change Password</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card" style="width: 100%; max-width: 500px;">
      <h2>Change Password</h2>
      <p style="margin-bottom: 24px;">Update your account password</p>

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

      <form action="change_password_page.php" method="POST">
          <label>Current Password</label>
          <input type="password" name="current_password" placeholder="Enter current password" required>

          <label>New Password</label>
          <input type="password" name="new_password" placeholder="Enter new password" required>

          <label>Confirm New Password</label>
          <input type="password" name="confirm_password" placeholder="Confirm new password" required>

          <div style="display: flex; gap: 16px; margin-top: 24px;">
              <a href="home_page.php" style="flex:1; text-decoration:none;"><button type="button" style="width:100%; background: rgba(255,255,255,0.1);">Dashboard</button></a>
              <button type="submit" style="flex:1; width:100%;">Update Password</button>
          </div>
      </form>
    </div>
  </div>
</body>
</html>