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
    'cust_street' => 'N/A',
    'cust_city' => 'N/A',
    'cust_state' => 'N/A',
    'cust_postcode' => 'N/A'
];

$orders = []; // Array to store order history

if (isset($conn)) {
    // 1. Fetch Customer Information
    $stmt = $conn->prepare("SELECT cust_name, cust_email, cust_phone_number, cust_street, cust_city, cust_state, cust_postcode FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_data['cust_name'] = $row['cust_name'] ?? 'N/A';
            $user_data['cust_email'] = $row['cust_email'] ?? 'N/A';
            $user_data['cust_phone_number'] = $row['cust_phone_number'] ?? 'N/A';
            $user_data['cust_street'] = $row['cust_street'] ?? '';
            $user_data['cust_city'] = $row['cust_city'] ?? '';
            $user_data['cust_state'] = $row['cust_state'] ?? '';
            $user_data['cust_postcode'] = $row['cust_postcode'] ?? '';
        }
        $stmt->close();
    }

    // 2. Fetch Customer Order History (Adjust column names to match your database)
    $order_stmt = $conn->prepare("SELECT order_id, order_date, total_amount, status FROM orders WHERE cust_id = ? ORDER BY order_date DESC");
    if ($order_stmt) {
        $order_stmt->bind_param("i", $cust_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        while ($order_row = $order_result->fetch_assoc()) {
            $orders[] = $order_row;
        }
        $order_stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>User Profile & Orders</title>
  <link rel="stylesheet" href="customer.css">
  <style>
    /* Quick local styles to support the new layout layout */
    .profile-layout {
      display: flex;
      gap: 24px;
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
      flex-wrap: wrap;
    }
    .order-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 16px;
    }
    .order-table th, .order-table td {
      border: 1px solid rgba(255,255,255,0.1); /* Matches dark themes well */
      padding: 10px;
      text-align: left;
    }
    .order-table th {
      background-color: rgba(255,255,255,0.05);
    }
    .no-orders {
      padding: 16px;
      text-align: center;
      opacity: 0.6;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="profile-layout">
      
      <!-- Left Side: Profile Info Card -->
      <div class="card" style="flex: 1; min-width: 300px;">
        <h2>User Profile</h2>
        <p style="margin-bottom: 24px;">Your account information</p>

        <label>Full Name</label>
        <input type="text" value="<?php echo htmlspecialchars($user_data['cust_name']); ?>" readonly>

        <label>Email Address</label>
        <input type="email" value="<?php echo htmlspecialchars($user_data['cust_email']); ?>" readonly>
        
        <label>Phone Number</label>
        <input type="text" value="<?php echo htmlspecialchars($user_data['cust_phone_number']); ?>" readonly>
        
        <label>Address</label>
        <textarea style="width: 100%; background: rgba(0,0,0,0.1); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; padding: 10px; font-size: 0.9rem;" readonly><?php 
          if($user_data['cust_street']) {
            echo htmlspecialchars($user_data['cust_street']) . "\n" . 
                 htmlspecialchars($user_data['cust_postcode']) . " " . 
                 htmlspecialchars($user_data['cust_city']) . "\n" . 
                 htmlspecialchars($user_data['cust_state']);
          } else {
            echo "N/A";
          }
        ?></textarea>

        <div style="display: flex; gap: 16px; margin-top: 24px;">
            <a href="home_page.php" style="flex:1; text-decoration:none;"><button style="width:100%; background: rgba(255,255,255,0.1);">Back</button></a>
            <a href="edit_profile_page.php" style="flex:1; text-decoration:none;"><button style="width:100%;">Edit Profile</button></a>
        </div>
      </div>

      <!-- Right Side: Order History Card -->
      <div class="card" style="flex: 1.5; min-width: 400px;">
        <h2>Order History</h2>
        <p style="margin-bottom: 24px;">Track your past and current orders</p>

        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <p>You haven't placed any orders yet.</p>
            </div>
        <?php else: ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                            <td><?php echo htmlspecialchars(date('d M Y', strtotime($order['order_date']))); ?></td>
                            <td>RM <?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                            <td>
                                <span class="status-badge <?php echo htmlspecialchars(strtolower($order['status'])); ?>">
                                    <?php echo htmlspecialchars($order['status']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
      </div>

    </div>
  </div>
</body>
</html>