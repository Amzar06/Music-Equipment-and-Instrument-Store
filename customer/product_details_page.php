<?php
session_start();
include '../database.php';
try {
    $stmt = $pdo->prepare("SELECT name, price, description FROM products WHERE id = :id");
    $stmt->execute(['id' => 1]);
    $product = $stmt->fetch();

    // Fallback if the product wasn't found
    if (!$product) {
        $product = [
            'name'        => 'Not Found',
            'price'       => 'N/A',
            'description' => 'No product data available.'
        ];
    }
} catch (Exception $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Product Details</title>
  <link rel="stylesheet" href="customer.css">
</head>
<body>
  <div class="header">ADMIN PORTAL</div>

  <div class="container">
    <div class="card">
      <h2>Product Details</h2>
      <p>View product information</p>

      <label>Product Name</label>
      <input type="text" value="<?php echo htmlspecialchars($product['name']); ?>" readonly>

      <label>Price</label>
      <input type="text" value="<?php echo htmlspecialchars($product['price']); ?>" readonly>

      <label>Description</label>
      <input type="text" value="<?php echo htmlspecialchars($product['description']); ?>" readonly>
    </div>
  </div>
</body>
</html>