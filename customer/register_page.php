<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Customer Register</title>

    <link rel="stylesheet" href="customer.css">

    <style>
        body{
            display:flex;
            justify-content:center;
            align-items:center;
            height:100vh;
            background:#f3f3f3;
        }
    </style>
</head>

<body>

<div class="container login-container">

    <h2>Create Account</h2>
    <p class="text-center mb-4">Register a new customer account</p>

    <form action="cust login.php" method="POST">

        <div>
            <input type="text" 
                   name="fullname" 
                   placeholder="Enter Full Name" 
                   required>
        </div>

        <div>
            <input type="email" 
                   name="email" 
                   placeholder="Enter Email" 
                   required>
        </div>

        <div>
            <input type="password" 
                   name="password" 
                   placeholder="Enter Password" 
                   required>
        </div>

        <div>
            <input type="password" 
                   name="confirm_password" 
                   placeholder="Confirm Password" 
                   required>
        </div>

        <button type="submit">Register</button>

    </form>

    <div class="text-center mt-4" style="font-size:14px; margin-top:20px;">

        Already have an account?
        <a href="cust login.php">Login</a>

    </div>

</div>

</body>
</html>
