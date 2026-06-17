<?php
session_start();
require_once('../database.php');

$step = 1;
$error = "";
$success = "";
$user_email = "";
$security_question = "";

// STEP 1: Verify Email
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_email'])) {
    $user_email = mysqli_real_escape_string($conn, trim($_POST['email']));
    
    $query = "SELECT * FROM staff WHERE staff_email = '$user_email' AND status = 'Active'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $staff = mysqli_fetch_assoc($result);
        if (!empty($staff['security_question']) && !empty($staff['security_answer'])) {
            $step = 2; // Move to question phase
            $security_question = $staff['security_question'];
            $_SESSION['recovery_email'] = $user_email; // Save email temporarily
        } else {
            $error = "No security question configured for this account. Contact your Superadmin to reset your password manually.";
        }
    } else {
        $error = "Email not found or account is inactive.";
    }
}

// STEP 2: Verify Answer & Reset Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $user_email = $_SESSION['recovery_email'];
    $answer_attempt = strtolower(trim($_POST['security_answer']));
    $new_password = $_POST['new_password'];
    
    $query = "SELECT * FROM staff WHERE staff_email = '$user_email'";
    $result = mysqli_query($conn, $query);
    $staff = mysqli_fetch_assoc($result);
    
    // Check if their typed answer matches the hashed answer in the database
    if (password_verify($answer_attempt, $staff['security_answer'])) {
        $hashed_new_pass = password_hash($new_password, PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE staff SET staff_password = '$hashed_new_pass' WHERE staff_email = '$user_email'");
        
        $success = "Password successfully reset! You may now log in.";
        $step = 3;
        unset($_SESSION['recovery_email']);
    } else {
        $error = "Incorrect answer to the security question.";
        $step = 2;
        $security_question = $staff['security_question'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); width: 100%; max-width: 400px; }
        input { width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; margin-top: 8px; margin-bottom: 20px; outline: none; }
        button { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

<div class="card">
    <h2 style="margin-top: 0; color: #111827;">Forgot Password</h2>
    
    <?php if (!empty($error)): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-size: 0.85rem;"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($step == 1): ?>
        <p style="color: #4b5563; font-size: 0.9rem;">Enter your staff email to verify identity.</p>
        <form method="POST">
            <label style="font-size: 0.85rem; font-weight: 600;">Staff Email</label>
            <input type="email" name="email" required placeholder="admin@musicstore.com">
            <button type="submit" name="verify_email">Verify Email</button>
            <div style="text-align: center; margin-top: 16px;"><a href="admin_login.php" style="color: #4f46e5; font-size: 0.85rem; text-decoration: none;">Back to Login</a></div>
        </form>

    <?php elseif ($step == 2): ?>
        <p style="color: #4b5563; font-size: 0.9rem;">Answer your security question to reset your password.</p>
        <form method="POST">
            <label style="font-size: 0.85rem; font-weight: 600;">Security Question:</label>
            <div style="padding: 12px; background: #f3f4f6; border-radius: 8px; margin-top: 8px; margin-bottom: 20px; color: #111827; font-weight: 500;">
                <?php echo htmlspecialchars($security_question); ?>
            </div>
            
            <label style="font-size: 0.85rem; font-weight: 600;">Your Secret Answer</label>
            <input type="text" name="security_answer" required placeholder="Enter answer...">

            <label style="font-size: 0.85rem; font-weight: 600;">New Password</label>
            <input type="password" name="new_password" required placeholder="Enter new password...">

            <button type="submit" name="reset_password">Reset Password</button>
            <div style="text-align: center; margin-top: 16px;">
                <a href="admin_login.php" style="color: #4f46e5; font-size: 0.85rem; text-decoration: none;">Back to Login</a>
            </div>
        </form>

    <?php elseif ($step == 3): ?>
        <div style="background: #d1fae5; color: #065f46; padding: 16px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; text-align: center;">
            <?php echo $success; ?>
        </div>
        <a href="admin_login.php" style="display: block; width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; text-align: center; text-decoration: none; box-sizing: border-box;">Return to Login</a>
    <?php endif; ?>
</div>

</body>
</html>