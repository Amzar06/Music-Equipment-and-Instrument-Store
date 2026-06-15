<?php
session_start();
include '../database.php';

$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (isset($conn) && !$conn->connect_error) {
        // 1. Only query by email to pull the customer's record
        $stmt = $conn->prepare("SELECT cust_id, cust_password FROM customers WHERE cust_email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                // 2. Safely verify if the typed password matches the secure hashed password
                if (password_verify($password, $row['cust_password'])) {
                    $_SESSION['cust_id'] = $row['cust_id'];
                    header("Location: product page.php");
                    exit();
                } else {
                    // Password hash mismatch
                    $login_error = "Invalid email or password.";
                }
            } else {
                // Email not found in the system
                $login_error = "Invalid email or password.";
            }
            $stmt->close();
        } else {
            $login_error = "Database query failed.";
        }
    } else {
        $login_error = "Database connection failed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { justify-content: center; align-items: center; } /* Overriding generic flex for perfect center */
    </style>
</head>
<body>

<div class="page-container login-container">
    <h2>Welcome Back</h2>
    <p class="text-center mb-4">Log in to your account</p>

    <?php if (isset($_SESSION['register_success'])): ?>
        <div style="background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; text-align: center;">
            <?php 
                echo $_SESSION['register_success']; 
                unset($_SESSION['register_success']); // Clear message after displaying
            ?>
        </div>
    <?php endif; ?>

    <?php if ($login_error): ?>
        <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9em; text-align: center;">
            <?php echo htmlspecialchars($login_error); ?>
        </div>
    <?php endif; ?>

    <form action="cust login.php" method="POST">
        <div>
            <input type="email" name="email" placeholder="Enter Email" required>
        </div>
        <div>
            <input type="password" name="password" placeholder="Enter Password" required>
        </div>

        <div style="text-align: right; font-size: 14px;">
            <a href="../customer/forgot_password.php" style="margin-top: 0;">Forgot Password?</a>
        </div>

        <button type="submit">Login</button>
    </form>

    <div class="text-center mt-4" style="font-size: 14px;">
        Don't have an account? <a href="../customer/register_page.php">Register</a>
    </div>
</div>

</body>
</html>