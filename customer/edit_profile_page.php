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
    
    // Sanitize phone number by removing trailing or accidental whitespace
    $cust_phone_number = trim($_POST['cust_phone_number'] ?? '');
    
    $cust_street = $_POST['cust_street'] ?? '';
    $cust_city = $_POST['cust_city'] ?? '';
    $cust_state = $_POST['cust_state'] ?? '';
    $cust_postcode = $_POST['cust_postcode'] ?? '';
    
    // =========================================================================
    // MALAYSIAN PHONE NUMBER VALIDATION CODE
    // =========================================================================
    // Pattern checks: Must start with +60, followed by a valid prefix (e.g., mobile 10-19, landlines 3-9) 
    // and total length between 9 to 11 digits after country code.
    $my_phone_pattern = '/^\+60(1[0-9]|3|4|5|6|7|8|9)[0-9]{7,8}$/';

    if (empty($cust_phone_number)) {
        $error = "Phone number is required.";
    } elseif (!preg_match($my_phone_pattern, $cust_phone_number)) {
        $error = "Invalid phone number format! It must follow Malaysian format starting with +60 (e.g., +60123456789 or +60312345678).";
    } else {
        if (isset($conn)) {
            // Prevent duplicate emails
            $check = $conn->prepare("SELECT cust_id FROM customers WHERE cust_email = ? AND cust_id != ?");
            if ($check) {
                $check->bind_param("si", $cust_email, $cust_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $error = "Email is already taken by another account.";
                } else {
                    $update = $conn->prepare("UPDATE customers SET cust_name = ?, cust_email = ?, cust_phone_number = ?, cust_street = ?, cust_city = ?, cust_state = ?, cust_postcode = ? WHERE cust_id = ?");
                    if ($update) {
                        $update->bind_param("sssssssi", $cust_name, $cust_email, $cust_phone_number, $cust_street, $cust_city, $cust_state, $cust_postcode, $cust_id);
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
}

// Fetch current info
$user_data = ['cust_name' => '', 'cust_email' => '', 'cust_phone_number' => '', 'cust_street' => '', 'cust_city' => '', 'cust_state' => '', 'cust_postcode' => ''];
if (isset($conn)) {
    $stmt = $conn->prepare("SELECT cust_name, cust_email, cust_phone_number, cust_street, cust_city, cust_state, cust_postcode FROM customers WHERE cust_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $cust_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $user_data = [
                'cust_name' => $row['cust_name'] ?? '',
                'cust_email' => $row['cust_email'] ?? '',
                'cust_phone_number' => $row['cust_phone_number'] ?? '',
                'cust_street' => $row['cust_street'] ?? '',
                'cust_city' => $row['cust_city'] ?? '',
                'cust_state' => $row['cust_state'] ?? '',
                'cust_postcode' => $row['cust_postcode'] ?? ''
            ];
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Musical Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="customer.css?v=3.0">
    <style>
        body { background-color: #f8fafc; color: #1e293b; }
        .edit-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            border: none;
            padding: 40px;
            max-width: 700px;
            margin: 40px auto;
        }
        .form-label {
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 8px;
        }
        .form-control {
            padding: 12px 16px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            font-weight: 500;
            margin-bottom: 20px;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }
        .btn-save {
            background: #0d3b8e;
            color: white;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 700;
            border: none;
            width: 100%;
        }
        .btn-save:hover { background: #082c6c; transform: translateY(-2px); }
        .btn-cancel {
            background: #f1f5f9;
            color: #64748b;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 700;
            border: none;
            width: 100%;
            text-align: center;
            text-decoration: none;
            display: inline-block;
        }
        .btn-cancel:hover { background: #e2e8f0; color: #1e293b; }
        .phone-hint { font-size: 0.78rem; color: #94a3b8; font-weight: 500; margin-top: -15px; margin-bottom: 15px; display: block; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #0d3b8e; padding: 12px 0;">
    <div class="container-fluid px-5">
        <a class="navbar-brand" href="home_page.php" style="font-weight: 500;">Musical Instrument Store</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navLogged">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navLogged">
            <ul class="navbar-nav ms-auto" style="gap: 15px;">
                <li class="nav-item"><a class="nav-link" href="home_page.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../product/product page.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="../product/payment history.php">My Orders</a></li>
                <li class="nav-item"><a class="nav-link active" href="user_profile_page.php">Profile</a></li>
                <li class="nav-item"><a class="nav-link" href="logout_page.php">Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="edit-card">
        <div class="mb-4">
            <h2 style="font-weight: 800; text-align: left; background: none; -webkit-text-fill-color: initial; color: #1e293b; margin: 0;">Edit Profile</h2>
            <p style="color: #64748b; font-weight: 500; margin-top: 5px;">Update your personal information and address</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 rounded-4 mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 rounded-4 mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form action="edit_profile_page.php" method="POST">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="cust_name" class="form-control" value="<?php echo htmlspecialchars($user_data['cust_name']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="cust_email" class="form-control" value="<?php echo htmlspecialchars($user_data['cust_email']); ?>" required>
                </div>
            </div>

            <label class="form-label">Phone Number</label>
            <input type="text" name="cust_phone_number" class="form-control" 
                   placeholder="e.g., +60123456789" 
                   pattern="^\+60(1[0-9]|[3-9])[0-9]{7,8}$" 
                   title="Please enter a valid Malaysian phone number starting with +60 followed by 8 to 10 digits." 
                   value="<?php echo htmlspecialchars($user_data['cust_phone_number']); ?>" required>
            <span class="phone-hint"><i class="fa-solid fa-info-circle me-1"></i> Format must be Malaysian standard including country code (e.g., <strong>+60171234567</strong>)</span>

            <label class="form-label">Street Address</label>
            <input type="text" name="cust_street" class="form-control" value="<?php echo htmlspecialchars($user_data['cust_street']); ?>">

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">City</label>
                    <input type="text" name="cust_city" class="form-control" value="<?php echo htmlspecialchars($user_data['cust_city']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Postcode</label>
                    <input type="text" name="cust_postcode" class="form-control" value="<?php echo htmlspecialchars($user_data['cust_postcode']); ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">State</label>
                    <input type="text" name="cust_state" class="form-control" value="<?php echo htmlspecialchars($user_data['cust_state']); ?>">
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <a href="user_profile_page.php" class="btn-cancel">Cancel</a>
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn-save shadow-sm">Save Changes</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>