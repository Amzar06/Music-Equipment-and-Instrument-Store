<!DOCTYPE html>
<html>
<head>
    <title>Customer Homepage</title>
    <link rel="stylesheet" href="customer.css">

    <style>
        .homepage-card {
            max-width: 900px;
            margin: auto;
            text-align: center;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }

        .product-box {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 10px;
            background: #fff;
        }

        .product-box h4 {
            margin-bottom: 10px;
        }

        .product-box p {
            color: #666;
        }
    </style>
</head>

<body>

<div class="container">

    <div class="card homepage-card">

        <h2>Welcome to Our Musical Instrument Store</h2>

        <p>
            Browse our products without logging in.
            Login or register when you're ready to purchase or rent.
        </p>

        <a href="/Music-Equipment-and-Instrument-Store/admin/admin_products.php">
            <button>View Products</button>
        </a>

        <br><br>

        <hr>

        <h3>Featured Instruments</h3>

        <div class="product-grid">

            <div class="product-box">
                <h4>Acoustic Guitar</h4>
                <p>RM 499.00</p>
                <a href="product_details.php">
                    <button>View Details</button>
                </a>
            </div>

            <div class="product-box">
                <h4>Digital Piano</h4>
                <p>RM 1,299.00</p>
                <a href="product_details.php">
                    <button>View Details</button>
                </a>
            </div>

            <div class="product-box">
                <h4>Drum Set</h4>
                <p>RM 899.00</p>
                <a href="product_details.php">
                    <button>View Details</button>
                </a>
            </div>

        </div>

    </div>

</div>

</body>
</html>