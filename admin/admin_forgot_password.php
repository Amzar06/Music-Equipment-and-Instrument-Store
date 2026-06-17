<?php
session_start();
require_once('../database.php'); // Adjust path if needed

$step = 1;
$message = "";
$email = "";
$question = "";

// STEP 1: Verify Staff Email
if (isset($_POST['submit_email'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['staff_email']));
    $query = mysqli_query($conn, "SELECT security_question, status FROM staff WHERE staff_email = '$email'");
    
    if (mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        
        // Prevent suspended admins from resetting passwords
        if ($row['status'] !== 'Active') {
            $message = "This account is inactive. Please contact the Superadmin.";
        } elseif (!empty($row['security_question'])) {
            $question = $row['security_question'];
            $step = 2; 
        } else {
            $message = "Security question not configured. Contact the Superadmin.";
        }
    } else {
        $message = "No staff account found with that email address.";
    }
}

// STEP 2: Verify Security Answer
if (isset($_POST['submit_answer'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $answer = strtolower(trim($_POST['security_answer'])); 
    
    $query = mysqli_query($conn, "SELECT security_answer FROM staff WHERE staff_email = '$email'");
    $row = mysqli_fetch_assoc($query);
    
    if (strtolower($row['security_answer']) === $answer) {
        $step = 3; 
    } else {
        $step = 2; 
        $question = $_POST['question'];
        $message = "Incorrect security answer. Access denied.";
    }
}

// STEP 3: Save New Hashed Password
if (isset($_POST['submit_new_password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];
    
    // Hash it before saving!
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    mysqli_query($conn, "UPDATE staff SET staff_password = '$hashed_password' WHERE staff_email = '$email'");
    
    $step = 4; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Recovery | System Portal</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f3f4f6; display: flex; justify-content: center; padding-top: 100px; margin: 0; }
        .recovery-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        .recovery-card h2 { margin-top: 0; color: #111827; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; }
        .form-group input { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
        .btn:hover { background: #4338ca; }
        .error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; font-size: 0.85rem; margin-bottom: 20px; }
        .success { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>

    <div class="recovery-card">
        <h2>Admin Recovery</h2>
        
        <?php if (!empty($message)): ?>
            <div class="error"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <p style="color: #6b7280; font-size: 0.9rem;">Enter your staff email to verify identity.</p>
            <form method="POST">
                <div class="form-group">
                    <label>Staff Email</label>
                    <input type="email" name="staff_email" required placeholder="admin@musicstore.com">
                </div>
                <button type="submit" name="submit_email" class="btn">Verify</button>
            </form>
        <?php endif; ?>

        <?php if ($step == 2): ?>
            <p style="color: #6b7280; font-size: 0.9rem;">Answer your security question.</p>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="question" value="<?php echo htmlspecialchars($question); ?>">
                
                <div class="form-group">
                    <label>Security Question:</label>
                    <div style="padding: 10px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 10px;">
                        <?php echo htmlspecialchars($question); ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Your Answer</label>
                    <input type="text" name="security_answer" required autocomplete="off">
                </div>
                <button type="submit" name="submit_answer" class="btn">Submit Answer</button>
            </form>
        <?php endif; ?>

        <?php if ($step == 3): ?>
            <p style="color: #6b7280; font-size: 0.9rem;">Create a new secure password.</p>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                
                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required placeholder="••••••••">
                </div>
                <button type="submit" name="submit_new_password" class="btn" style="background: #10b981;">Update Password</button>
            </form>
        <?php endif; ?>

        <?php if ($step == 4): ?>
            <div class="success">
                <h3>Clearance Restored</h3>
                <p>Your admin password has been updated securely.</p>
                <a href="admin_login.php" style="display: block; margin-top: 15px; color: #065f46; font-weight: bold; text-decoration: none;">Return to Login</a>
            </div>
        <?php endif; ?>

    </div>

</body>
</html>