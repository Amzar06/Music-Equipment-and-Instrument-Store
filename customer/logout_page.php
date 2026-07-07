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
      /* Center the container vertically and horizontally on the screen */
      body {
          margin: 0;
          padding: 0;
          display: flex;
          justify-content: center;
          align-items: center;
          min-height: 100vh;
          background-color: #f8fafc; /* Optional: adds a light background like your image */
      }

      .container {
          width: 100%;
          display: flex;
          justify-content: center;
          align-items: center;
      }

      /* Styled the card wrapper to look clean and elevated */
      .card {
          text-align: center; 
          max-width: 420px; 
          width: 100%;
          padding: 30px;
          background: #ffffff;
          border-radius: 12px;
          box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1); /* Optional: matches the card shadow in your screenshot */
      }

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
    <div class="card">
      <h2 style="color: #6366f1; margin-top: 0;">You Have Logged Out</h2>
      <p style="color: #555; margin-bottom: 12px;">Thank you for using the system!</p>

      <div class="button-group">
          <a href="../product/cust login.php">
              <button type="button" style="background-color: #2563eb; color: white; border: none;">Login Again</button>
          </a>

          <a href="home_page.php">
              <button type="button" class="btn-secondary">Go to Home Page</button>
          </a>
      </div>
    </div>
  </div>

</body>
</html>