<!DOCTYPE html>
<html>
<head>
  <title>Customer Register</title>
  <link rel="stylesheet" href="customer.css">
</head>

<body>

  <div class="header">ADMIN PORTAL</div>

  <div class="container">

    <div class="card">

      <h2>Create Account</h2>
      <p>Register a new customer account</p>

      <form action="cust login.php" method="POST">

        <label>Full Name</label>
        <input type="text" 
               name="fullname"
               placeholder="Enter your full name" 
               required>

        <label>Email Address</label>
        <input type="email" 
               name="email"
               placeholder="Enter your email" 
               required>

        <label>Password</label>
        <input type="password" 
               name="password"
               placeholder="Enter your password" 
               required>

        <label>Confirm Password</label>
        <input type="password" 
               name="confirm_password"
               placeholder="Confirm your password" 
               required>

        <button type="submit">Register</button>

      </form>

      <br>

      <p>
        Already have an account?
        <a href="cust login.php">Login</a>
      </p>

    </div>

  </div>

</body>
</html>