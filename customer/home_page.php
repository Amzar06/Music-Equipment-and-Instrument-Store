<!DOCTYPE html>
<html>
<head>
  <title>Home Page</title>
  <link rel="stylesheet" href="customer.css">
  <style>
    /* Add this styling to make all the blue blocks wider and uniform */
    .button-group {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 15px; /* Creates clean spacing without needing <br> tags */
      margin-top: 20px;
    }

    .btn-link {
      display: inline-block;
      width: 450px;             /* Forces all blocks to be the exact same wide width */
      padding: 12px 0;          /* Adds vertical padding for a better clickable area */
      background-color: #1d61f2;/* The dark blue color from your image */
      color: white;
      text-align: center;
      text-decoration: none;    /* Removes the default underline from links */
      border-radius: 8px;       /* Smooth rounded corners */
      font-weight: bold;
      font-size: 14px;
      transition: background-color 0.2s ease;
    }

    /* Subtle hover effect */
    .btn-link:hover {
      background-color: #08193d;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="card" style="text-center"> <h2>Welcome User</h2>
      <p>Select an option below:</p>

      <div class="button-group">
        <a href="edit_profile_page.php" class="btn-link">Edit Profile</a>
        <a href="change_password.php" class="btn-link">Change Password</a>
        <a href="product_details.php" class="btn-link">View Products</a>
        <a href="logout.php" class="btn-link">Log Out</a> 
      </div>

    </div> </div> </body>
</html>