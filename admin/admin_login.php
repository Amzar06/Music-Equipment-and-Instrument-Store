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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f3f4f6; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
            color: #111827;
        }
        
        .login-box { 
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); 
            width: 100%; 
            max-width: 350px; 
            border: 1px solid #d1d5db; 
        }
        
        .error { 
            color: #b91c1c; 
            background: #fef2f2; 
            border: 1px solid #fca5a5; 
            padding: 10px; 
            border-radius: 8px; 
            margin-bottom: 15px; 
            text-align: center; 
            font-size: 0.9rem; 
            font-weight: 500;
        }
        
        /* Wrapper needed for the eye icon */
        .input-group { 
            position: relative; 
            width: 100%; 
            margin: 10px 0; 
        }
        
        input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #d1d5db; 
            border-radius: 8px; 
            box-sizing: border-box; 
            outline: none; 
            transition: border-color 0.2s; 
            font-family: inherit;
        }
        
        /* Adds padding so typed dots don't hide behind the eye */
        input[type="password"], input[id="password"] { 
            padding-right: 40px; 
        } 
        
        input:focus { 
            border-color: #1a1c23; 
        }
        
        button { 
            width: 100%; 
            padding: 12px; 
            background: #1a1c23; /* Dark Slate to match Sidebar */
            color: white; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: background 0.2s; 
            margin-top: 10px; 
            font-family: inherit;
        }
        
        button:hover { 
            background: #000000; 
        }
        
        /* Eye Icon Positioning */
        .toggle-password { 
            position: absolute; 
            right: 12px; 
            top: 12px; 
            cursor: pointer; 
            color: #6b7280; 
            width: 20px; 
            height: 20px; 
        }
        
        .toggle-password:hover { 
            color: #111827; 
        }

        label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #6b7280;
        }

        /* Hide Microsoft Edge's default password reveal icon */
        input::-ms-reveal,
        input::-ms-clear {
        display: none;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2 style="text-align:center; margin-top: 0; margin-bottom: 25px;">Staff Login</h2>
    
    <?php if ($error != ""): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="" autocomplete="off">
        
        <input style="display:none" type="email" name="fakeusernameremembered">
        <input style="display:none" type="password" name="fakepasswordremembered">

        <label>Email Address</label>
        <input type="email" name="email" required autocomplete="new-password">
        
        <label>Password</label>
        <div class="input-group">
            <input type="password" name="password" id="password" required autocomplete="new-password">
            <span class="toggle-password" onclick="togglePassword()" title="Toggle Password Visibility">
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            </span>
        </div>
        
        <button type="submit">Login</button>
    </form>
</div>

<script>
    function togglePassword() {
        const pwd = document.getElementById('password');
        const icon = document.getElementById('eyeIcon');
        
        if (pwd.type === 'password') {
            pwd.type = 'text';
            // Switch to Eye-Slashed SVG
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
        } else {
            pwd.type = 'password';
            // Switch back to Eye-Open SVG
            icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
        }
    }
</script>

</body>
</html>