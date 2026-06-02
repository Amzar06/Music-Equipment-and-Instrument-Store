<?php
session_start();

$_SESSION = array();


if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logout</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card" style="text-align: center;">
      <h2>You Have Logged Out</h2>
      <p>Thank you for using the system!</p>

      <a href="/Music-Equipment-and-Instrument-Store/product/cust login.php">
        <button type="button">Login Again</button>
      </a>
    </div>
  </div>

</body>
</html>
