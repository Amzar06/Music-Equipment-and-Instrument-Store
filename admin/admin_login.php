<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "music_equipment_instrument_store"; 

$conn = mysqli_connect($servername, $username, $password, $dbname);

// If this fails, it will stop here and tell you why
if (!$conn) {
    die("Database Connection failed: " . mysqli_connect_error());
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        
        $sql = "SELECT * FROM staff WHERE staff_email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
           
            if ($password === $row['staff_password']) {
                $_SESSION['staff_id'] = $row['staff_id'];
                $_SESSION['staff_name'] = $row['staff_name'];
                $_SESSION['staff_role'] = $row['staff_role'];

                header("Location: admin_dashboard.php");
                exit();
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No staff account found with that email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Combined Admin Login</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        .error { color: white; background: #d9534f; padding: 10px; border-radius: 5px; margin-bottom: 15px; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>

<div class="login-box">
    <h2 style="text-align:center;">Staff Login</h2>
    
    <?php if ($error != ""): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Email Address</label>
        <input type="email" name="email" required>
        
        <label>Password</label>
        <input type="password" name="password" required>
        
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>