<?php
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Profile</title>
    <link rel="stylesheet" href="customer.css">
</head>

<body>

<div class="container">

    <div class="card">

        <h2>User Profile</h2>
        <p>View your account information</p>

        <label>Full Name</label>
        <input type="text" value="John Doe" readonly>

        <label>Email Address</label>
        <input type="email" value="john@example.com" readonly>

        <label>Phone Number</label>
        <input type="text" value="0123456789" readonly>

        <br>

        <a href="edit_profile.php">
            <button type="button">Edit Profile</button>
        </a>

        <br><br>

        <a href="home.php">
            <button type="button">Back to Home</button>
        </a>

    </div>

</div>

</body>
</html>