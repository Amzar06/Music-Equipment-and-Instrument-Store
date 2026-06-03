<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the product ID from the URL (defaulting to 1 if not set)
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Simulating a database query result based on the ID passed from the home page
if ($product_id === 1) {
    $product_name        = "Yamaha F310 Acoustic Guitar";
    $product_price       = "RM 499.00";
    $product_description = "The Yamaha F310 offers outstanding quality at a truly affordable price. Its lively sound from a spruce/meranti body has a bright, sweet resonance. Perfect for beginners and seasoned players alike.";
    $product_image       = "https://images.unsplash.com/photo-1510915361894-db8b60106cb1?q=80&w=500";
} elseif ($product_id === 2) {
    $product_name        = "Casio Privia Digital Piano";
    $product_price       = "RM 1,299.00";
    $product_description = "Features a multi-dimensional Morphing AiR sound source with scaled hammer action keys. Provides the rich, expressive tone and feel of a concert grand piano in a compact, modern chassis.";
    $product_image       = "https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=500";
} elseif ($product_id === 3) {
    $product_name        = "Pearl Export Drum Set";
    $product_price       = "RM 899.00";
    $product_description = "The legends of tomorrow play Export today. This 5-piece drum kit features reference-inspired shell composition, durable hardware, and a striking finish designed for raw high-impact performance.";
    $product_image       = "https://images.unsplash.com/photo-1543443374-b6fe10a6ab7b?q=80&w=500";
} else {
    // Fallback if an unrecognized ID is given
    $product_name        = "Premium Musical Gear";
    $product_price       = "Contact for Price";
    $product_description = "High-quality selected instrument optimized for professional musicians and student performers.";
    $product_image       = "https://images.unsplash.com/photo-1511192336575-5a79af67a629?q=80&w=500";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Details - <?php echo htmlspecialchars($product_name); ?></title>
  <link rel="stylesheet" href="../customer.css">
  
  <style>
    body {
        background-color: #f4f6f9;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
    }
    .container {
        max-width: 600px;
        margin: 50px auto;
        padding: 0 20px;
    }
    .card {
        background: #fff;
        padding: 30px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    h2 {
        margin-bottom: 5px;
        color: #111;
    }
    .subtitle {
        color: #666;
        margin-bottom: 25px;
        font-size: 14px;
    }
    .product-img-preview {
        width: 100%;
        height: 250px;
        object-fit: cover;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
        color: #333;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    input[type="text"] {
        width: 100%;
        padding: 10px 12px;
        margin-bottom: 20px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background-color: #f9f9f9;
        font-size: 15px;
        color: #444;
        box-sizing: border-box;
    }
    textarea {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
        background-color: #f9f9f9;
        resize: none;
        font-size: 14px;
        color: #555;
        line-height: 1.5;
        margin-bottom: 25px;
    }
    .btn-action {
        display: block;
        background-color: #1d61f2;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 4px;
        font-weight: bold;
        text-decoration: none;
        font-size: 14px;
        max-width: 260px;
        margin: 0 auto 20px auto;
        text-align: center;
        transition: background 0.2s;
    }
    .btn-action:hover {
        background-color: #154ec4;
    }
    .back-link {
        display: block;
        text-align: center;
        color: #1d61f2;
        text-decoration: none;
        font-weight: bold;
        font-size: 14px;
    }
    .back-link:hover {
        text-decoration: underline;
    }
  </style>
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>Product Details</h2>
      <p class="subtitle">View product information</p>

      <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($product_name); ?>" class="product-img-preview">

      <label for="product_name">Product Name</label>
      <input type="text" id="product_name" value="<?php echo htmlspecialchars($product_name); ?>" readonly>

      <label for="price">Price</label>
      <input type="text" id="price" value="<?php echo htmlspecialchars($product_price); ?>" readonly>

      <label for="description">Description</label>
      <textarea id="description" rows="5" readonly><?php echo htmlspecialchars($product_description); ?></textarea>
      
      <div>
          <a href="index.php" class="btn-action">Back to Storefront</a>
      </div>

      <div>
          <a href="index.php" class="back-link">← Back to Dashboard</a>
      </div>
      
    </div>
  </div>
</body>
</html>