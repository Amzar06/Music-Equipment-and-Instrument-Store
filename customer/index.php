<!DOCTYPE html>
<html>
<head>
    <title>Customer Portal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

 
        .card {
            background-color: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 100%;
            max-width: 450px; 
        }

        h2 {
            color: #5b7fff; 
            margin-bottom: 8px;
            font-size: 28px;
        }

        p {
            color: #5c677d;
            margin-bottom: 24px;
        }

      
        .btn-group {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
        }

     
        .btn-group a {
            width: 80%; 
            text-decoration: none;
        }

  
        .btn-group button {
            width: 100%;
            padding: 14px 0;
            background-color: #1e62ec; 
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s ease, transform 0.1s ease;
        }

     
        .btn-group button:hover {
            background-color: #164ec4;
        }


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
            <a href="c/Music-Equipment-and-Instrument-Store/product/cust login.php">
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