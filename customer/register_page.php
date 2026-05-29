<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

<div class="container">
  <div class="card">
    <h2>Create Account</h2>
    <p>Register a new account</p>

    <form action="login.php" method="post">

      <label>Full Name</label>
      <input type="text" name="fullname" placeholder="Enter your full name">

      <label>Email Address</label>
      <input type="email" name="email" placeholder="Enter your email">

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password">

      <label>Confirm Password</label>
      <input type="password" name="confirm_password" placeholder="Confirm your password">

      <button type="submit">Register</button>

    </form>
  </div>
</div>

</body>
</html>