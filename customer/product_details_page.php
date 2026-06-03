<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Get the product ID from the URL (defaulting to 1 if not set)
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;

// Detailed data mapping featuring multiple instrument models per case
switch ($product_id) {
    case 1:
        $product_name        = "Acoustic Guitars Collection";
        $product_price       = "From RM 499.00 - RM 2,499.00";
        $product_description = "• Beginner: Yamaha F310 (RM 499.00) - Great tone, sweet resonance, perfect for student starters.\n\n"
                             . "• Intermediate: Fender CD-60S (RM 850.00) - Solid spruce top with mahogany back and sides for a richer expression.\n\n"
                             . "• Premium: Taylor 114e (RM 2,499.00) - Professional grand auditorium style featuring custom built-in ES2 electronics.";
        $product_image       = "https://images.unsplash.com/photo-1510915361894-db8b60106cb1?q=80&w=500";
        break;

    case 2:
        $product_name        = "Digital Pianos & Keyboards";
        $product_price       = "From RM 750.00 - RM 3,500.00";
        $product_description = "• Portable: Roland GO:KEYS (RM 750.00) - Lightweight 61-note build with onboard Bluetooth connectivity.\n\n"
                             . "• Mid-Range: Casio Privia PX-S1100 (RM 1,299.00) - Ultra-slim profile featuring an smart scaled hammer action keyboard.\n\n"
                             . "• Professional: Yamaha Arius YDP-145 (RM 3,500.00) - Full console home cabinet with authentic grand piano voice projection.";
        $product_image       = "https://images.unsplash.com/photo-1552422535-c45813c61732?q=80&w=500";
        break;

    case 3:
        $product_name        = "Acoustic & Electronic Drums";
        $product_price       = "From RM 899.00 - RM 4,200.00";
        $product_description = "• Standard Kit: Pearl Export Series (RM 899.00) - 5-piece configuration setup engineered with blended poplar/mahogany shells.\n\n"
                             . "• Compact Option: Tama Club-JAM (RM 1,150.00) - Small footprint setup optimized for tight stage performances and busking.\n\n"
                             . "• Silent Studio: Roland TD-07KV V-Drums (RM 4,200.00) - All-mesh electronic setup designed for noise-free headphone practice.";
        $product_image       = "https://images.unsplash.com/photo-1543443374-b6fe10a6ab7b?q=80&w=500";
        break;

    case 4:
        $product_name        = "Electric Guitars & Amps";
        $product_price       = "From RM 680.00 - RM 2,150.00";
        $product_description = "• Vintage Style: Squier Affiliate Stratocaster (RM 680.00) - Classic body lines with lightweight handling, great for learning classic rock.\n\n"
                             . "• Modern Metal: Ibanez RG Standard (RM 1,150.00) - Flat, fast Wizard III maple neck optimized for precision shredding.\n\n"
                             . "• Deluxe Package: Epiphone Les Paul Custom (RM 2,150.00) - Gold hardware accents paired with dual ProBucker humbuckers.";
        $product_image       = "https://images.unsplash.com/photo-1564186763535-ebb21ef5277f?q=80&w=500";
        break;

    case 5:
        $product_name        = "Ukuleles & Folk Strings";
        $product_price       = "From RM 180.00 - RM 550.00";
        $product_description = "• Soprano: Kala Mahogany Soprano (RM 180.00) - Warm, full-bodied standard traditional island pitch resonance.\n\n"
                             . "• Concert Size: Cordoba 15CM (RM 320.00) - Slightly larger wood chamber offering enhanced projection and finger room.\n\n"
                             . "• Tenor Electric: Lanikai QM-BLCEC (RM 550.00) - Quilted maple cutaway framework ready to plug straight into an amplifier.";
        $product_image       = "https://encrypted-tbn3.gstatic.com/images?q=tbn:ANd9GcSSinNKHS_yQVS5TOue4wX8OHQ7gZzp6yN1rAVwW3wu4195BsczxYcv_r2A_ma3";
        break;

    case 6:
        $product_name        = "Orchestral Violins & Bows";
        $product_price       = "From RM 450.00 - RM 1,890.00";
        $product_description = "• Entry Starter: Prima 200 Outfit (RM 450.00) - Sturdy choice complete with case and standard wood horsehair bow.\n\n"
                             . "• Student Level: Stentor Student II (RM 650.00) - Solid carved tone woods matched with ebony fingerboards and premium tailpiece pegs.\n\n"
                             . "• Advanced Artist: Hofner AS-160-V (RM 1,890.00) - Selected European spruce top treated with custom spirit varnish coating.";
        $product_image       = "https://images.unsplash.com/photo-1612225330812-01a9c6b355ec?q=80&w=500";
        break;

    default:
        $product_name        = "Custom Musical Instruments";
        $product_price       = "Price Upon Request";
        $product_description = "We carry a wide range of custom boutique gear and specialty variants. Please contact our support desk for personalized orders and inventory status updates.";
        $product_image       = "https://images.unsplash.com/photo-1511192336575-5a79af67a629?q=80&w=500";
        break;
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
        max-width: 650px;
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
        height: 280px;
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
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-sizing: border-box;
        background-color: #f9f9f9;
        resize: vertical;
        font-size: 14px;
        color: #444;
        line-height: 1.6;
        margin-bottom: 25px;
        font-family: inherit;
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
      <h2>Product Catalog</h2>
      <p class="subtitle">Available inventory variants</p>

      <img src="<?php echo htmlspecialchars($product_image); ?>" alt="<?php echo htmlspecialchars($product_name); ?>" class="product-img-preview">

      <label for="product_name">Category Class</label>
      <input type="text" id="product_name" value="<?php echo htmlspecialchars($product_name); ?>" readonly>

      <label for="price">Price Tier Range</label>
      <input type="text" id="price" value="<?php echo htmlspecialchars($product_price); ?>" readonly>

      <label for="description">Available Models & Specifications</label>
      <textarea id="description" rows="10" readonly><?php echo htmlspecialchars($product_description); ?></textarea>
      
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