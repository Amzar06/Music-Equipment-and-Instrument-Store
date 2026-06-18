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
  <style>
      .button-group {
          display: flex;
          gap: 12px;
          justify-content: center;
          margin-top: 24px;
      }
      .button-group a {
          flex: 1;
          text-decoration: none;
      }
      .button-group button {
          width: 100%;
          padding: 12px;
          border-radius: 8px;
          font-weight: 600;
          cursor: pointer;
          box-sizing: border-box;
      }
      .btn-secondary {
          background-color: #64748b;
          color: white;
          border: 1px solid #475569;
          transition: background 0.2s;
      }
      .btn-secondary:hover {
          background-color: #475569;
      }
  </style>
</head>
<body>

  <div class="container">
    <div class="card" style="text-align: center; max-width: 420px; margin: 0 auto;">
      <h2>You Have Logged Out</h2>
      <p style="color: #555; margin-bottom: 12px;">Thank you for using the system!</p>

      <div class="button-group">
          <a href="../product/cust login.php">
              <button type="button">Login Again</button>
          </a>

          <a href="home_page.php">
              <button type="button" class="btn-secondary">Go to Home Page</button>
          </a>
      </div>
    </div>
  </div>

</body>
</html>