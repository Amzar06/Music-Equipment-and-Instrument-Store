<!DOCTYPE html>
<html>
<head>
    <title>Customer Portal</title>
    <style>
        /* Centering the card on the screen */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Card container styling */
        .card {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 450px; /* Keeps the card a nice, readable width */
        }

        /* Typography */
        h2 {
            color: #5b7fff; /* Match your original light blue/purple heading */
            margin-bottom: 8px;
            font-size: 28px;
        }

        p {
            color: #5c677d;
            margin-bottom: 24px;
        }

        /* Flexbox container to center and stack the button links */
        .btn-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px; /* Clean spacing between buttons without using <br> */
        }

        /* Forces the link tags to take up 80% of the card width */
        .btn-group a {
            width: 80%; 
            text-decoration: none;
        }

        /* Style for the wide, centered buttons */
        .btn-group button {
            width: 100%; /* Fills the 80% link width completely */
            padding: 14px 0;
            background-color: #1e62ec; /* Your vibrant blue button color */
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

        /* Subtle hover effect */
        .btn-group button:hover {
            background-color: #164ec4;
        }

        /* Subtle click effect */
        .btn-group button:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card">

        <h2>Customer Portal</h2>
        <p>Welcome to the system</p>

        <div class="btn-group">
            <a href="cust_login.php">
                <button type="button">Login</button>
            </a>

            <a href="customer_register_page.php">
                <button type="button">Register</button>
            </a>
        </div>

    </div>
</div>

</body>
</html>