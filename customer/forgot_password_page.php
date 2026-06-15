<?php
session_start();
include '../database.php';

$message = "";
$message_type = ""; // 'success' or 'danger'
$reset_url = "";    // To hold the link for testing/demonstration purposes

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } else {
        if (isset($conn)) {
            // Check if the email exists in the customers table
            $stmt = $conn->prepare("SELECT cust_id, cust_name FROM customers WHERE cust_email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $cust_id = $user['cust_id'];
                    
                    // 1. GENERATE A SECURE CRYPTOGRAPHIC TOKEN
                    $token = bin2hex(random_bytes(32)); 
                    
                    // 2. SET EXPIRATION TIME (e.g., 30 minutes from now)
                    $expiry = date("Y-m-d H:i:s", strtotime("+30 minutes"));

                    // First, clean up any older pending reset requests for this customer
                    $clean_stmt = $conn->prepare("DELETE FROM password_resets WHERE cust_id = ?");
                    $clean_stmt->bind_param("i", $cust_id);
                    $clean_stmt->execute();
                    $clean_stmt->close();

                    // 3. STORE THE TOKENS IN YOUR DB TABLE
                    $insert_stmt = $conn->prepare("INSERT INTO password_resets (email, token, cust_id, expiry) VALUES (?, ?, ?, ?)");
                    if ($insert_stmt) {
                        $insert_stmt->bind_param("ssis", $email, $token, $cust_id, $expiry);
                        $insert_stmt->execute();
                        $insert_stmt->close();

                        // 4. CREATE THE TARGET LINK TO reset_password.php
                        $reset_url = "reset_password.php?token=" . $token;

                        $message = "A password reset token has been successfully generated for your account!";
                        $message_type = "success";
                    } else {
                        $message = "Failed to initiate token system. Please check your database structure.";
                        $message_type = "danger";
                    }
                } else {
                    $message = "This email address is not registered in our system.";
                    $message_type = "danger";
                }
                $stmt->close();
            } else {
                $message = "Database error. Please try again later.";
                $message_type = "danger";
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
    <link rel="stylesheet" href="customer.css?v=4.0">
    <style>
        body { 
            background-color: #f8fafc; 
            color: #1e293b;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .header-banner {
            background-color: #0d3b8e;
            color: white;
            padding: 15px 0;
            text-align: center;
            font-weight: 600;
            letter-spacing: 1px;
            font-size: 1.1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .reset-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            border: none;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            transition: transform 0.2s;
        }
        .icon-header {
            width: 60px;
            height: 60px;
            background: #eff6ff;
            color: #2563eb;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 20px auto;
        }
        .form-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            font-weight: 500;
        }
        .form-control:focus {
            border-color: #2563eb;
            box-shadow: none;
            background-color: #fff;
        }
        .btn-submit {
            background: #0d3b8e;
            color: white;
            border-radius: 10px;
            padding: 12px;
            font-weight: 700;
            border: none;
            transition: all 0.2s;
        }
        .btn-submit:hover {
            background: #082c6c;
            color: white;
            transform: translateY(-1px);
        }
        .back-to-login {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: color 0.2s;
        }
        .back-to-login:hover {
            color: #0d3b8e;
        }
    </style>
</head>
<body>

    <div class="header-banner">
        <i class="fa-solid fa-music me-2"></i> MUSICAL INSTRUMENT STORE
    </div>

    <div class="reset-container">
        <div class="reset-card">
            <div class="icon-header">
                <i class="fa-solid fa-key"></i>
            </div>
            
            <h2 class="text-center fw-800 mb-2" style="font-weight: 800; font-size: 1.75rem;">Forgot Password?</h2>
            <p class="text-center text-muted small mb-4">Enter your registered email address below and we'll help you get back on track.</p>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> d-flex flex-column role='alert' style='border-radius: 10px; font-size: 0.88rem; font-weight: 500;'>
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-2">
                            <?php echo $message_type === 'success' ? '🛡️' : '⚠️'; ?>
                        </div>
                        <div>
                            <?php echo $message; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($reset_url)): ?>
                        <div class="mt-2 w-100">
                            <a href="<?php echo $reset_url; ?>" class="btn btn-success btn-sm w-100 fw-bold" style="border-radius: 8px;">
                                <i class="fa-solid fa-envelope-open me-1"></i> [Simulate Email] Click to Reset Password
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form action="reset_password.php" method="POST" autocomplete="off">
                <div class="mb-4">
                    <label for="emailInput" class="form-label">Email Address</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="emailInput" 
                        class="form-control" 
                        placeholder="e.g., alex@example.com" 
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        required
                    >
                </div>

                <button type="submit" class="btn btn-submit w-100 mb-3">
                    Send Reset Link <i class="fa-solid fa-arrow-right ms-2" style="font-size: 0.85rem;"></i>
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="cust login.php" class="back-to-login">
                    <i class="fa-solid fa-arrow-left me-2" style="font-size: 0.8rem;"></i> Back to Login
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>