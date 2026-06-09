<?php
session_start();
require_once('../database.php');

$status = "";
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];

    // Check if the email exists in the system
    $check_query = "SELECT * FROM staff WHERE staff_email = '$email'";
    $check_result = mysqli_query($conn, $check_query);

    if (mysqli_num_rows($check_result) == 1) {
        $staff = mysqli_fetch_assoc($check_result);
        
        // --- STRONG PASSWORD VALIDATION ---
        if (strlen($new_password) < 8 || !preg_match('/[0-9]/', $new_password) || !preg_match('/[^a-zA-Z0-9]/', $new_password)) {
            $status = "error";
            $message = "Password must be at least 8 characters, contain a number, and a symbol.";
        } 
        elseif ($new_password === $staff['staff_password']) {
            $status = "error";
            $message = "Your new password cannot be the same as your current password.";
        } else {
            // Update the password in the database
            $update_query = "UPDATE staff SET staff_password = '$new_password' WHERE staff_email = '$email'";
            if (mysqli_query($conn, $update_query)) {
                
                // --- SECURITY FIX: FORCE LOGOUT ---
                // Destroy any active sessions so the user is forced to log in manually
                session_unset();
                session_destroy();
                
                $status = "success";
                $message = "A verification link has been sent to your email.";
            } else {
                $status = "error";
                $message = "Database error: " . mysqli_error($conn);
            }
        }
    } else {
        $status = "error";
        $message = "Email not found in our system.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Music Store</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            width: 100%;
            max-width: 400px;
            box-sizing: border-box;
        }
        .login-card h2 {
            margin-top: 0;
            color: #111827;
            font-weight: 700;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 20px;
            display: block;
            width: 100%;
        }
        .form-group label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 8px;
            text-align: left;
        }
        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s;
            display: block;
        }
        .form-control:focus {
            border-color: #111827;
        }
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none;
        }
        .password-container {
            position: relative;
            display: block;
            width: 100%;
        }
        .toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #6b7280;
            padding: 0;
            display: flex;
            align-items: center;
            height: auto;
        }
        .toggle-btn:hover {
            color: #111827;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: #111827;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            display: block;
            margin-top: 10px;
        }
        .login-btn:hover {
            background: #000000;
        }
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #fca5a5;
        }
        .password-hint {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 6px;
            display: block;
        }

        /* Black and White Modal Notification Styles */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-box {
            background: white;
            padding: 32px;
            border-radius: 12px;
            text-align: center;
            max-width: 320px;
            width: 90%;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            border: 2px solid #111827;
        }
        .modal-icon {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #f3f4f6;
            margin: 0 auto 16px auto;
        }
        .modal-box h3 {
            margin: 0 0 12px 0;
            color: #111827;
            font-size: 1.25rem;
        }
        .modal-box p {
            margin: 0 0 24px 0;
            color: #4b5563;
            font-size: 0.9rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <?php if ($status == 'success'): ?>
    <div class="modal-overlay">
        <div class="modal-box">
            <div class="modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#111827" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3>Email Sent</h3>
            <p><?php echo $message; ?></p>
            <button onclick="window.location.href='admin_login.php'" class="login-btn">OK</button>
        </div>
    </div>
    <script>
        setTimeout(function() {
            window.location.href = 'admin_login.php';
        }, 3000); 
    </script>
    <?php endif; ?>
    <div class="login-card">
        <h2>Reset Password</h2>
        <p style="text-align: center; font-size: 0.85rem; color: #6b7280; margin-top: -15px; margin-bottom: 20px;">
            Enter your email and new password to receive a verification link.
        </p>
        
        <?php if ($status == 'error'): ?>
            <div class="error-message">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <form action="admin_forgot_password.php" method="POST">
            <div class="form-group">
                <label>Registered Email Address</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label>New Password</label>
                <div class="password-container">
                    <input type="password" name="new_password" id="new_password" class="form-control" required 
                           pattern="(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}" 
                           title="Must contain at least 8 characters, including one number and one symbol.">
                    <button type="button" class="toggle-btn" onclick="togglePassword()">
                        <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
                <span class="password-hint">Minimum 8 characters, 1 number, and 1 symbol.</span>
            </div>

            <button type="submit" name="reset_password" class="login-btn">Update Password</button>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin_login.php" style="color: #4b5563; font-size: 0.85rem; text-decoration: none; font-weight: 600;">&larr; Back to Login</a>
            </div>
        </form>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('new_password');
            const eyeIcon = document.getElementById('eye-icon');
            if(passwordInput) {
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
                }
            }
        }
    </script>
</body>
</html>