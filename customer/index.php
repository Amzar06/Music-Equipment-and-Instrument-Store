<?php
// Define common styles or variables here for better maintainability
$primary_color = "#1e62ec";
$button_style  = "width: 100%; padding: 12px 0; background-color: $primary_color; color: white; border: none; border-radius: 6px; font-size: 16px; font-weight: bold; cursor: pointer;";
$link_style    = "display: inline-block; width: 80%; text-decoration: none;";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal</title>
    <link rel="stylesheet" href="customer.css">
</head>
<body>

<div class="container" style="display: flex; justify-content: center; align-items: center; height: 100vh;">
    <div class="card" style="text-align: center; width: 100%; max-width: 450px;">

        <h2>Customer Portal</h2>
        <p>Welcome to the system</p>

        <a href="/Music-Equipment-and-Instrument-Store/product/cust login.php" style="<?php echo $link_style; ?>">
            <button type="button" style="<?php echo $button_style; ?>">Login</button>
        </a>

        <br><br>

        <a href="register_page.php" style="<?php echo $link_style; ?>">
            <button type="button" style="<?php echo $button_style; ?>">Register</button>
        </a>

    </div>
</div>

</body>
</html>