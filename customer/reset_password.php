<?php
session_start();
include '../database.php';

$message = "";
$message_type = ""; 
$step = 1; // Step 1: Enter Email | Step 2: Enter Code & New Password

// STEP 1: CUSTOMER SUBMITS THEIR EMAIL
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_email'])) {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } else {
        if (isset($conn)) {
            $stmt = $conn->prepare("SELECT cust_id FROM customers WHERE cust_email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Generate a random secure 6-digit verification code
                    $verification_code = rand(100000, 999999);
                    
                    // Store details securely in the session state
                    $_SESSION['reset_cust_id'] = $user['cust_id'];
                    $_SESSION['reset_email'] = $email;
                    $_SESSION['otp_code'] = $verification_code;

                    $message = "Verification code generated! Please check the simulator box below.";
                    $message_type = "success";
                    $step = 2; // Advance to verification entry screen
                } else {
                    $message = "This email address is not registered in our system.";
                    $message_type = "danger";
                }
                $stmt->close();
            }
        }
    }
}

// STEP 2: CUSTOMER SUBMITS CODE AND NEW PASSWORD
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_reset'])) {
    $entered_code = trim($_POST['verification_code']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $saved_code = $_SESSION['otp_code'] ?? null;
    $cust_id = $_SESSION['reset_cust_id'] ?? null;

    if (!$cust_id || !$saved_code) {
        $message = "Session expired. Please re-enter your email.";
        $message_type = "danger";
        $step = 1;
    } elseif ($entered_code != $saved_code) {
        $message = "Incorrect verification code. Please check again.";
        $message_type = "danger";
        $step = 2; 
    } elseif (strlen($new_password) < 6) { // Enforcing a healthier 6-character minimum for hashed accounts
        $message = "For better security, your password must be at least 6 characters long.";
        $message_type = "danger";
        $step = 2;
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match. Please try again.";
        $message_type = "danger";
        $step = 2;
    } else {
        if (isset($conn)) {
            // SECURE UPDATE: Converting plain text into a strong cryptographic hash
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            $update_stmt = $conn->prepare("UPDATE customers SET cust_password = ? WHERE cust_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $hashed_password, $cust_id);
                if ($update_stmt->execute()) {
                    $message = "Success! Your password has been hashed and updated safely.";
                    $message_type = "success";
                    
                    // Clear temporary session security data
                    unset($_SESSION['otp_code']);
                    unset($_SESSION['reset_cust_id']);
                    unset($_SESSION['reset_email']);
                    $step = 3; 
                } else {
                    $message = "Database error. Failed to save password mapping.";
                    $message_type = "danger";
                    $step = 2;
                }
                $update_stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Musical Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; font-family: system-ui, sans-serif; }
        .header-banner { background-color: #0d3b8e; color: white; padding: 15px 0; text-align: center; font-weight: 600; letter-spacing: 1px; font-size: 1.1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .reset-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .reset-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: none; padding: 40px; width: 100%; max-width: 450px; }
        .icon-header { width: 60px; height: 60px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 20px auto; }
        .form-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; margin-bottom: 8px; }
        .form-control { border-radius: 10px; padding: 12px 16px; border: 1.5px solid #e2e8f0; background-color: #f8fafc; font-weight: 500; }
        .form-control:focus { border-color: #2563eb; box-shadow: none; background-color: #fff; }
        .btn-submit { background: #0d3b8e; color: white; border-radius: 10px; padding: 12px; font-weight: 700; border: none; transition: all 0.2s; }
        .btn-submit:hover { background: #082c6c; transform: translateY(-1px); }
        .back-to-login { color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .back-to-login:hover { color: #0d3b8e; }
        .simulated-email-box { background: #f0fdf4; border: 1.5px dashed #16a34a; border-radius: 12px; padding: 15px; text-align: center; font-weight: 600; color: #166534; }
    </style>
</head>
<body>

    <div class="header-banner">
        <i class="fa-solid fa-music me-2"></i> MUSICAL INSTRUMENT STORE
    </div>

    <div class="reset-container">
        <div class="reset-card">
            <div class="icon-header">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            
            <h2 class="text-center mb-2" style="font-weight: 800; font-size: 1.75rem;">Secure Password Reset</h2>
            <p class="text-center text-muted small mb-4">Verification codes help keep accounts safe. Enter details below.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> text-center mb-4" role="alert" style="border-radius: 10px; font-size: 0.88rem; font-weight: 500;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 2 && isset($_SESSION['otp_code'])): ?>
                <div class="simulated-email-box mb-4">
                    <div style="font-size: 0.75rem; text-transform: uppercase; color: #15803d; margin-bottom: 2px;">📨 Simulated Email Output</div>
                    Verification Code: <span style="font-size: 1.4rem; font-weight: 800; letter-spacing: 2px; color: #14532d;"><?php echo $_SESSION['otp_code']; ?></span>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <form action="forgot_password.php" method="POST" autocomplete="off">
                    <input type="hidden" name="submit_email" value="1">
                    <div class="mb-4">
                        <label for="emailInput" class="form-label">Registered Email Address</label>
                        <input type="email" name="email" id="emailInput" class="form-control" placeholder="e.g., amzar06@gmail.com" required>
                    </div>
                    <button type="submit" class="btn btn-submit w-100 mb-3">
                        Request Verification Code <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </form>

            <?php elseif ($step == 2): ?>
                <form action="forgot_password.php" method="POST" autocomplete="off">
                    <input type="hidden" name="submit_reset" value="1">
                    
                    <div class="mb-3">
                        <label for="codeInput" class="form-label" style="color: #2563eb;">Enter 6-Digit Code</label>
                        <input type="text" name="verification_code" id="codeInput" class="form-control text-center fw-bold" placeholder="------" maxlength="6" style="font-size: 1.25rem; letter-spacing: 4px;" required>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Minimum 6 characters" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your secure entry" required>
                    </div>

                    <button type="submit" class="btn btn-submit w-100 mb-3" style="background-color: #16a34a;">
                        Verify & Securely Encrypt Password <i class="fa-solid fa-lock ms-1"></i>
                    </button>
                </form>
            
            <?php elseif ($step == 3): ?>
                <div class="text-center mt-2">
                    <a href="cust login.php" class="btn btn-submit w-100 py-2 text-decoration-none">Proceed to Login Page</a>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="cust login.php" class="back-to-login">
                    <i class="fa-solid fa-arrow-left me-2" style="font-size: 0.8rem;"></i> Return to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>