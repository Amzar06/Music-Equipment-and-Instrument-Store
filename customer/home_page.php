<?php
session_start();
include '../database.php';
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../product/cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];
$cust_name = "User";

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT cust_name FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $cust_name = $row['cust_name'];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Customer Dashboard</title>
  <link rel="stylesheet" href="customer.css">
  <style>
      .btn-container { margin-bottom: 16px; text-align: center; }
      .btn-container button { width: 100%; border-radius: 8px; padding: 12px; cursor: pointer; }
  </style>
</head>
<body>

  <div class="container">
    <div class="card" style="width: 100%; max-width: 400px; padding: 32px;">
      <h2>Welcome, <?php echo htmlspecialchars(explode(' ', trim($cust_name))[0] ?? 'User'); ?></h2>
      <p style="margin-bottom: 24px; color: var(--text-secondary);">Select an option below</p>

      <div class="btn-container">
          <a href="user_profile_page.php" style="text-decoration:none;"><button>View Profile</button></a>
      </div>
      <div class="btn-container">
          <a href="edit_profile_page.php" style="text-decoration:none;"><button>Edit Profile</button></a>
      </div>
      <div class="btn-container">
          <a href="change_password_page.php" style="text-decoration:none;"><button>Change Password</button></a>
      </div>
      <div class="btn-container">
          <a href="../product/product page.php" style="text-decoration:none;"><button style="background: var(--accent); color: white;">Shop Instruments</button></a>
      </div>
      <div class="btn-container" style="margin-top: 32px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 24px;">
          <a href="logout_page.php" style="text-decoration:none;"><button style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">Log Out</button></a>
      </div>
    </div>
  </div>
</body>
</html>