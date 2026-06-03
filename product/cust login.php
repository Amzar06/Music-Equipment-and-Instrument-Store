<?php
session_start();
include '../database.php';

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (isset($conn) && !$conn->connect_error) {
        $stmt = $conn->prepare("SELECT cust_id FROM customers WHERE cust_email = ? AND cust_password = ?");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $password);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $_SESSION['cust_id'] = $row['cust_id'];
                header("Location: product page.php");
                exit();
            } else {
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

<div class="container login-container">
    <h2>Welcome Back</h2>
    <p class="text-center mb-4">Log in to your account</p>

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
            <a href="#" style="margin-top: 0;">Forgot Password?</a>
        </div>

        <button type="submit">Login</button>
    </form>

    <div class="text-center mt-4" style="font-size: 14px;">
        Don't have an account? <a href="../customer/register_page.php">Register</a>
    </div>
</div>

</body>
</html>
