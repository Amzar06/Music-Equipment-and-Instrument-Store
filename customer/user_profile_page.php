<?php
session_start();
include '../database.php';
if (!isset($_SESSION['cust_id'])) {
    header("Location: ../product/cust login.php");
    exit();
}
$cust_id = $_SESSION['cust_id'];

// Default values safely assigned
$user_data = [
    'cust_name' => 'N/A',
    'cust_email' => 'N/A',
    'cust_phone_number' => 'N/A',
    'cust_address' => 'N/A'
];

if (isset($conn)) {
    $stmt = $conn->prepare("SELECT cust_name, cust_email, cust_phone_number, cust_address FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_data['cust_name'] = $row['cust_name'] ?? 'N/A';
            $user_data['cust_email'] = $row['cust_email'] ?? 'N/A';
            $user_data['cust_phone_number'] = $row['cust_phone_number'] ?? 'N/A';
            $user_data['cust_address'] = $row['cust_address'] ?? 'N/A';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>User Profile</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>
  <div class="container">
    <div class="card" style="width: 100%; max-width: 500px;">
      <h2>User Profile</h2>
      <p style="margin-bottom: 24px;">Your account information</p>

      <label>Full Name</label>
      <input type="text" value="<?php echo htmlspecialchars($user_data['cust_name']); ?>" readonly>

      <label>Email Address</label>
      <input type="email" value="<?php echo htmlspecialchars($user_data['cust_email']); ?>" readonly>
      
      <label>Phone Number</label>
      <input type="text" value="<?php echo htmlspecialchars($user_data['cust_phone_number']); ?>" readonly>
      
      <label>Address</label>
      <input type="text" value="<?php echo htmlspecialchars($user_data['cust_address']); ?>" readonly>

      <div style="display: flex; gap: 16px; margin-top: 24px;">
          <a href="home_page.php" style="flex:1; text-decoration:none;"><button style="width:100%; background: rgba(255,255,255,0.1);">Back</button></a>
          <a href="edit_profile_page.php" style="flex:1; text-decoration:none;"><button style="width:100%;">Edit Profile</button></a>
      </div>
    </div>
  </div>
</body>
</html>