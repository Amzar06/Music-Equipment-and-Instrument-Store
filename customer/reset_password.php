<?php
session_start();
include '../database.php';

$message = "";
$message_type = ""; // 'success' or 'danger'
$show_form = false;
$token = "";

// 1. PROCESS PASSWORD SUBMISSION (When form is explicitly posted)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_password_action'])) {
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $cust_id = $_SESSION['reset_cust_id'] ?? null;

    if (!$cust_id) {
        $message = "Session expired or invalid. Please restart the process.";
        $message_type = "danger";
    } elseif (strlen($new_password) < 4) { // Modified minimum length to match short passwords like abc123
        $message = "Password must be at least 4 characters long.";
        $message_type = "danger";
        $show_form = true; 
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match. Please retype carefully.";
        $message_type = "danger";
        $show_form = true; 
    } else {
        if (isset($conn)) {
            // MATCHING YOUR DATABASE: Storing password as plain text as seen in your table ('abc123')
            $plain_password = $new_password;
            
            $conn->begin_transaction();
            try {
                // Update customer password 
                $update_stmt = $conn->prepare("UPDATE customers SET cust_password = ? WHERE cust_id = ?");
                $update_stmt->bind_param("si", $plain_password, $cust_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Delete used token
                $delete_stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $delete_stmt->bind_param("s", $token);
                $delete_stmt->execute();
                $delete_stmt->close();
                
                $conn->commit();
                $message = "Your password has been successfully updated! You can now log in.";
                $message_type = "success";
                unset($_SESSION['reset_cust_id']); 
                
            } catch (Exception $e) {
                $conn->rollback();
                $message = "An unexpected error occurred. Please try again.";
                $message_type = "danger";
                $show_form = true;
            }
        }
    }
} 
// 2. VERIFY THE TOKEN FIRST (When arriving via the simulation button GET request)
elseif (isset($_GET['token'])) {
    $token = trim($_GET['token']);
    
    if (isset($conn)) {
        // Query checking if token exists and hasn't expired yet
        $stmt = $conn->prepare("SELECT cust_id FROM password_resets WHERE token = ? AND expiry > NOW() LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("s", $token);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user_reset = $result->fetch_assoc();
                $_SESSION['reset_cust_id'] = $user_reset['cust_id'];
                $show_form = true; // Safely unlocks and shows the password fields
            } else {
                $message = "This password reset link is invalid or has expired. Please request a new one.";
                $message_type = "danger";
            }
            $stmt->close();
        }
    }
} else {
    // This is what triggered your error screen!
    $message = "Access denied. No password reset token was provided.";
    $message_type = "danger";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Musical Store</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; font-family: system-ui, -apple-system, sans-serif; }
        .header-banner { background-color: #0d3b8e; color: white; padding: 15px 0; text-align: center; font-weight: 600; letter-spacing: 1px; font-size: 1.1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .reset-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .reset-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: none; padding: 40px; width: 100%; max-width: 450px; }
        .icon-header { width: 60px; height: 60px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 20px auto; }
        .form-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; margin-bottom: 8px; }
        .form-control { border-radius: 10px; padding: 12px 16px; border: 1.5px solid #e2e8f0; background-color: #f8fafc; font-weight: 500; }
        .form-control:focus { border-color: #2563eb; box-shadow: none; background-color: #fff; }
        .btn-submit { background: #0d3b8e; color: white; border-radius: 10px; padding: 12px; font-weight: 700; border: none; transition: all 0.2s; }
        .btn-submit:hover { background: #082c6c; transform: translateY(-1px); }
        .action-link { color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .action-link:hover { color: #0d3b8e; }
    </style>
</head>
<body>

    <div class="header-banner">
        <i class="fa-solid fa-music me-2"></i> MUSICAL INSTRUMENT STORE
    </div>

    <div class="reset-container">
        <div class="reset-card">
            <div class="icon-header">
                <i class="fa-solid fa-lock-open"></i>
            </div>
            
            <h2 class="text-center mb-2" style="font-weight: 800; font-size: 1.75rem;">Create New Password</h2>
            <p class="text-center text-muted small mb-4">Please type your secure new account credentials access layout below.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> d-flex align-items-center mb-4" role="alert" style="border-radius: 10px; font-size: 0.88rem; font-weight: 500;">
                    <div class="me-2"><?php echo $message_type === 'success' ? '✅' : '⚠️'; ?></div>
                    <div><?php echo $message; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <form action="reset_password.php" method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="update_password_action" value="1">

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter new password" required>
                    </div>

                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Repeat new password" required>
                    </div>

                    <button type="submit" class="btn btn-submit w-100">
                        Update Password <i class="fa-solid fa-check ms-1"></i>
                    </button>
                </form>
            <?php else: ?>
                <div class="text-center mt-2">
                    <?php if ($message_type === 'success'): ?>
                        <a href="cust login.php" class="btn btn-submit w-100 py-2 text-decoration-none">Go to Login Page</a>
                    <?php else: ?>
                        <a href="forgot_password.php" class="btn btn-secondary w-100 py-2 text-decoration-none">Request New Link</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="text-center mt-4">
                <a href="cust login.php" class="action-link">
                    <i class="fa-solid fa-arrow-left me-1" style="font-size: 0.8rem;"></i> Return to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>