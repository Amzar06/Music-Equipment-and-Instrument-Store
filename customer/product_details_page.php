<?php

session_start();
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Simulating a database query result based on the ID
if ($product_id === 1) {
    $product_name        = "Fender Stratocaster Electric Guitar";
    $product_price       = "RM 3,250.00";
    $product_description = "Classic sunburst electric guitar with vintage-style pickups and a smooth maple neck.";
} else {
    // Fallback/Default mock data if another ID is accessed
    $product_name        = "Yamaha P-45 Digital Piano";
    $product_price       = "RM 2,400.00";
    $product_description = "88-key weighted action digital piano, perfect for beginners and intermediate players.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Details - <?php echo htmlspecialchars($product_name); ?></title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>

  <div class="container">
    <div class="card">
      <h2>Product Details</h2>
      <p>View product information</p>

      <label for="product_name">Product Name</label>
      <input type="text" id="product_name" value="<?php echo htmlspecialchars($product_name); ?>" readonly>

      <label for="price">Price</label>
      <input type="text" id="price" value="<?php echo htmlspecialchars($product_price); ?>" readonly>

      <label for="description">Description</label>
      <textarea id="description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; background-color: #f9f9f9; resize: none;" readonly><?php echo htmlspecialchars($product_description); ?></textarea>
      
      <div style="margin-top: 20px; text-align: center;">
          <a href="home_page.php" style="color: #1d61f2; text-decoration: none; font-weight: bold; font-size: 14px;">← Back to Dashboard</a>
      </div>
      
    </div>
  </div>
</body>
</html>