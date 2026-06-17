<?php
session_start();
include '../database.php';

$message = "";
$message_type = ""; 

// =========================================================================
// SINGLE STEP PROCESS: VALIDATE EVERYTHING AND REDIRECT TO LOGIN ON SUCCESS
// =========================================================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_email'])) {
    $email = trim($_POST['email']);
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = trim($_POST['security_answer'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "danger";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "danger";
    } elseif (empty($security_question) || empty($security_answer)) {
        $message = "Please complete the security verification questions.";
        $message_type = "danger";
    } elseif (strlen($new_password) < 8 || !preg_match('/[0-9]/', $new_password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) { 
        $message = "Password must be a minimum of 8 characters and contain at least 1 number and 1 symbol.";
        $message_type = "danger";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match. Please try again.";
        $message_type = "danger";
    } else {
        if (isset($conn)) {
            // Fetch customer details along with their registration security questions from database
            $stmt = $conn->prepare("SELECT cust_id, cust_security_question, cust_security_answer FROM customers WHERE cust_email = ?");
            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    
                    // Standardize the answer to lowercase to match the formatting used during registration
                    $processed_answer = strtolower($security_answer);
                    
                    // HASH MATCHING: Check the question explicitly, and verify the hashed answer using password_verify
                    if ($user['cust_security_question'] !== $security_question || !password_verify($processed_answer, $user['cust_security_answer'])) {
                        $message = "Security verification failed. Incorrect question or answer matching.";
                        $message_type = "danger";
                    } else {
                        // SECURE UPDATE: Converting plain text into a strong cryptographic hash
                        $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                        
                        $update_stmt = $conn->prepare("UPDATE customers SET cust_password = ? WHERE cust_id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param("si", $hashed_password, $user['cust_id']);
                            
                            if ($update_stmt->execute()) {
                                // Redirect straight to the customer login page upon successful update
                                header("Location: /Music-Equipment-and-Instrument-Store/product/cust login.php");
                                exit();
                            } else {
                                $message = "Database error. Failed to save password mapping.";
                                $message_type = "danger";
                            }
                            $update_stmt->close();
                        }
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
    <style>
        body { background-color: #f8fafc; color: #1e293b; min-height: 100vh; display: flex; flex-direction: column; font-family: system-ui, sans-serif; }
        .header-banner { background-color: #0d3b8e; color: white; padding: 15px 0; text-align: center; font-weight: 600; letter-spacing: 1px; font-size: 1.1rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .reset-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 40px 20px; }
        .reset-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); border: none; padding: 40px; width: 100%; max-width: 450px; }
        .icon-header { width: 60px; height: 60px; background: #eff6ff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 20px auto; }
        .form-label { font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #94a3b8; margin-bottom: 8px; }
        .form-control, .form-select { border-radius: 10px; padding: 12px 16px; border: 1.5px solid #e2e8f0; background-color: #f8fafc; font-weight: 500; color: #1e293b; }
        .form-control:focus, .form-select:focus { border-color: #2563eb; box-shadow: none; background-color: #fff; }
        
        .password-group { position: relative; }
        .password-group .form-control { padding-right: 45px; }
        .toggle-icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #94a3b8; z-index: 10; transition: color 0.2s; }
        .toggle-icon:hover { color: #1e293b; }

        .btn-submit { background: #0d3b8e; color: white; border-radius: 10px; padding: 12px; font-weight: 700; border: none; transition: all 0.2s; }
        .btn-submit:hover { background: #082c6c; transform: translateY(-1px); }
        .back-to-login { color: #64748b; text-decoration: none; font-size: 0.9rem; font-weight: 600; }
        .back-to-login:hover { color: #0d3b8e; }
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

            <form action="" method="POST" autocomplete="off">
                <input type="hidden" name="submit_email" value="1">
                
                <div class="mb-3">
                    <label for="emailInput" class="form-label">Registered Email Address</label>
                    <input type="email" name="email" id="emailInput" class="form-control" placeholder="e.g. abc@gmail.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="security_question" class="form-label">Security Verification</label>
                    <select name="security_question" id="security_question" class="form-select" required>
                        <option value="" disabled <?php echo !isset($_POST['security_question']) ? 'selected' : ''; ?>>-- Choose a Security Verification Question --</option>
                        <?php
                        $questions = [
                            "What was the name of your first pet?",
                            "What is your mother's maiden name?",
                            "What elementary school did you attend?",
                            "In what city were you born?",
                            "What was your favorite food as a child?"
                        ];
                        foreach ($questions as $q) {
                            $selected = (isset($_POST['security_question']) && $_POST['security_question'] === $q) ? 'selected' : '';
                            echo "<option value=\"$q\" $selected>$q</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="security_answer" class="form-label">Security Answer</label>
                    <input type="text" name="security_answer" id="security_answer" class="form-control" placeholder="Case-insensitive answer protection" value="<?php echo isset($_POST['security_answer']) ? htmlspecialchars($_POST['security_answer']) : ''; ?>" required>
                </div>

                <div class="mb-3">
                    <label for="new_password" class="form-label">New Password</label>
                    <div class="password-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" placeholder="Enter your password" required>
                        <i class="fa-solid fa-eye toggle-icon" onclick="toggleVisibility('new_password', this)"></i>
                    </div>
                    <div style="font-size: 0.72em; color: #64748b; margin-top: 6px;">
                        Minimum 8 characters, 1 number, and 1 symbol.
                    </div>
                </div>

                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-group">
                        <input type="password" name="confirm_password" id="confirm_password" class="form-control" placeholder="Confirm your password" required>
                        <i class="fa-solid fa-eye toggle-icon" onclick="toggleVisibility('confirm_password', this)"></i>
                    </div>
                    <div style="font-size: 0.72em; color: #64748b; margin-top: 6px;">
                        Minimum 8 characters, 1 number, and 1 symbol.
                    </div>
                </div>

                <button type="submit" class="btn btn-submit w-100 mb-3">
                    Change Password <i class="fa-solid fa-arrow-right ms-1"></i>
                </button>
            </form>

            <div class="text-center mt-4">
                <a href="/Music-Equipment-and-Instrument-Store/product/cust login.php" class="back-to-login">
                    <i class="fa-solid fa-arrow-left me-2" style="font-size: 0.8rem;"></i> Return to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        function toggleVisibility(inputId, icon) {
            const inputField = document.getElementById(inputId);
            if (inputField.type === "password") {
                inputField.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                inputField.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>