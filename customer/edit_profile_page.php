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
    $cust_name = $_POST['cust_name'] ?? '';
    $cust_email = $_POST['cust_email'] ?? '';
    $cust_phone_number = $_POST['cust_phone_number'] ?? '';
    $cust_address = $_POST['cust_address'] ?? '';
    
    if (isset($conn)) {
        // Prevent duplicate emails
        $check = $conn->prepare("SELECT cust_id FROM customers WHERE cust_email = ? AND cust_id != ?");
        if ($check) {
            $check->bind_param("si", $cust_email, $cust_id);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "Email is already taken by another account.";
            } else {
                $update = $conn->prepare("UPDATE customers SET cust_name = ?, cust_email = ?, cust_phone_number = ?, cust_address = ? WHERE cust_id = ?");
                if ($update) {
                    $update->bind_param("ssssi", $cust_name, $cust_email, $cust_phone_number, $cust_address, $cust_id);
                    if ($update->execute()) {
                        $success = "Profile updated successfully!";
                    } else {
                        $error = "Failed to update profile.";
                    }
                    $update->close();
                }
            }
            $check->close();
        }
    }
}

// Fetch current info
$user_data = ['cust_name' => '', 'cust_email' => '', 'cust_phone_number' => '', 'cust_address' => ''];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT cust_name, cust_email, cust_phone_number, cust_address FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_data = [
                'cust_name' => $row['cust_name'] ?? '',
                'cust_email' => $row['cust_email'] ?? '',
                'cust_phone_number' => $row['cust_phone_number'] ?? '',
                'cust_address' => $row['cust_address'] ?? ''
            ];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Profile</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card" style="width: 100%; max-width: 500px;">
      <h2>Edit Profile</h2>
      <p style="margin-bottom: 24px;">Update your profile information</p>

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

      <form action="edit_profile_page.php" method="POST">
          <label>Full Name</label>
          <input type="text" name="cust_name" value="<?php echo htmlspecialchars($user_data['cust_name']); ?>" required>

          <label>Email Address</label>
          <input type="email" name="cust_email" value="<?php echo htmlspecialchars($user_data['cust_email']); ?>" required>
          
          <label>Phone Number</label>
          <input type="text" name="cust_phone_number" value="<?php echo htmlspecialchars($user_data['cust_phone_number']); ?>">
          
          <label>Address</label>
          <input type="text" name="cust_address" value="<?php echo htmlspecialchars($user_data['cust_address']); ?>">

          <div style="display: flex; gap: 16px; margin-top: 24px;">
              <a href="user_profile_page.php" style="flex:1; text-decoration:none;"><button type="button" style="width:100%; background: rgba(255,255,255,0.1);">Back</button></a>
              <button type="submit" style="flex:1; width:100%;">Save Changes</button>
          </div>
      </form>
    </div>
  </div>
</body>
</html>
