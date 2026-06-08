<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Edit Instrument";
$active = "products";

// 1. FETCH THE PRODUCT DATA
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "No product selected for editing.";
    $_SESSION['flash_type'] = "error";
    header("Location: admin_products.php");
    exit();
}

$edit_id = intval($_GET['id']);
$product_query = "SELECT * FROM products WHERE prod_id = $edit_id";
$product_result = mysqli_query($conn, $product_query);

if (mysqli_num_rows($product_result) == 0) {
    $_SESSION['flash_message'] = "Product not found.";
    $_SESSION['flash_type'] = "error";
    header("Location: admin_products.php");
    exit();
}

$product = mysqli_fetch_assoc($product_result);

// 2. HANDLE THE UPDATE FORM SUBMISSION
$message = "";
$message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $prod_name        = mysqli_real_escape_string($conn, $_POST['prod_name']);
    $category_id      = intval($_POST['category_id']);
    $prod_description = mysqli_real_escape_string($conn, $_POST['prod_description']);
    $staff_id         = intval($_SESSION['staff_id']); 
    
    // Strict Backend Data Sanitization
    $prod_sale_qty     = isset($_POST['for_sale']) && isset($_POST['prod_sale_qty']) ? intval(preg_replace('/[^0-9]/', '', $_POST['prod_sale_qty'])) : 0;
    $prod_sale_price   = isset($_POST['for_sale']) && isset($_POST['prod_sale_price']) ? floatval($_POST['prod_sale_price']) : 0;
    
    $prod_rental_qty   = isset($_POST['for_rent']) && isset($_POST['prod_rental_qty']) ? intval(preg_replace('/[^0-9]/', '', $_POST['prod_rental_qty'])) : 0;
    $prod_rental_price = isset($_POST['for_rent']) && isset($_POST['prod_rental_price']) ? floatval($_POST['prod_rental_price']) : 0;
    
    // Image Upload Handling (Keep old image if no new one is selected)
    $image_name = $_POST['current_image']; 
    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $image_name = time() . "_" . basename($_FILES["prod_image"]["name"]);
        $target_file = $target_dir . $image_name;
        move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_file);
    }

    if (!empty($prod_name) && $category_id > 0) {
        if (!isset($_POST['for_sale']) && !isset($_POST['for_rent'])) {
            $message = "You must assign the item for Sale or Rent.";
            $message_type = "error";
        } else {
            // Validate numbers
            $is_valid = true;
            $validation_error = "";

            if (isset($_POST['for_sale']) && ($prod_sale_price < 0.01 || $prod_sale_qty < 1)) {
                $is_valid = false;
                $validation_error = "Sale price and quantity must be valid numbers greater than 0.";
            }
            if (isset($_POST['for_rent']) && ($prod_rental_price < 0.01 || $prod_rental_qty < 1)) {
                $is_valid = false;
                $validation_error = "Rental price and quantity must be valid numbers greater than 0.";
            }

            if (!$is_valid) {
                $message = $validation_error;
                $message_type = "error";
            } else {
                // THE UPDATE QUERY
                $update_query = "UPDATE products SET 
                                 prod_name = '$prod_name', 
                                 category_id = $category_id, 
                                 staff_id = $staff_id, 
                                 prod_description = '$prod_description', 
                                 prod_sale_price = $prod_sale_price, 
                                 prod_rental_price = $prod_rental_price, 
                                 prod_sale_qty = $prod_sale_qty, 
                                 prod_rental_qty = $prod_rental_qty, 
                                 prod_image = '$image_name' 
                                 WHERE prod_id = $edit_id";
                
                if (mysqli_query($conn, $update_query)) {
                    $_SESSION['flash_message'] = "Instrument updated successfully!";
                    $_SESSION['flash_type'] = "success";
                    header("Location: admin_products.php");
                    exit();
                } else {
                    $message = "Database error: " . mysqli_error($conn);
                    $message_type = "error";
                }
            }
        }
    } else {
        $message = "Please provide all required fields.";
        $message_type = "error";
    }
}

// Fetch categories for the dropdown
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

// Determine if the checkboxes should be checked initially based on DB data
$has_sale = ($product['prod_sale_price'] > 0 || $product['prod_sale_qty'] > 0);
$has_rent = ($product['prod_rental_price'] > 0 || $product['prod_rental_qty'] > 0);

require_once('admin_header.php');
?>

<div style="max-width: 800px; margin: 0 auto;">
    
    <a href="admin_products.php" style="display: inline-flex; align-items: center; gap: 6px; color: #4b5563; text-decoration: none; font-size: 0.9rem; font-weight: 600; margin-bottom: 20px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
        </svg>
        Back to Inventory
    </a>

    <?php if (!empty($message)): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div class="table-container" style="margin-top: 0; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; margin-bottom: 24px; font-weight: 700; color: #111827; font-size: 1.5rem;">Edit Instrument: <?php echo htmlspecialchars($product['prod_name']); ?></h3>
        
        <form action="admin_edit_product.php?id=<?php echo $edit_id; ?>" method="POST" enctype="multipart/form-data">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Product Name *</label>
                    <input type="text" name="prod_name" required value="<?php echo htmlspecialchars($product['prod_name']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                </div>

                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Category *</label>
                    <select name="category_id" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white; outline: none; box-sizing: border-box;">
                        <option value="">-- Select Category --</option>
                        <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                            <option value="<?php echo $cat['category_id']; ?>" <?php echo ($cat['category_id'] == $product['category_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px; padding: 20px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
                <label style="display: block; font-size: 0.95rem; font-weight: 700; margin-bottom: 16px; color: #111827;">Availability, Price & Stock *</label>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 10px;">
                            <input type="checkbox" id="for_sale" name="for_sale" value="1" onchange="toggleStock('sale')" <?php echo $has_sale ? 'checked' : ''; ?> style="width: 18px; height: 18px;"> 
                            Available for Sale
                        </label>
                        <div id="stock_sale_container" style="display: <?php echo $has_sale ? 'block' : 'none'; ?>; padding-left: 28px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label style="font-size: 0.8rem; color: #6b7280; display: block; margin-bottom: 4px; font-weight: 500;">Sale Price (RM)</label>
                                    <input type="number" step="0.01" name="prod_sale_price" id="prod_sale_price" class="form-control" value="<?php echo $product['prod_sale_price']; ?>" <?php echo $has_sale ? 'required' : 'disabled'; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="font-size: 0.8rem; color: #6b7280; display: block; margin-bottom: 4px; font-weight: 500;">Sale Quantity</label>
                                    <input type="number" name="prod_sale_qty" id="prod_sale_qty" class="form-control" value="<?php echo $product['prod_sale_qty']; ?>" min="1" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" <?php echo $has_sale ? 'required' : 'disabled'; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height: 1px; background: #e5e7eb; width: 100%;"></div>

                    <div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 10px;">
                            <input type="checkbox" id="for_rent" name="for_rent" value="1" onchange="toggleStock('rent')" <?php echo $has_rent ? 'checked' : ''; ?> style="width: 18px; height: 18px;"> 
                            Available for Rent
                        </label>
                        <div id="stock_rent_container" style="display: <?php echo $has_rent ? 'block' : 'none'; ?>; padding-left: 28px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                <div>
                                    <label style="font-size: 0.8rem; color: #6b7280; display: block; margin-bottom: 4px; font-weight: 500;">Rental Price/Day (RM)</label>
                                    <input type="number" step="0.01" name="prod_rental_price" id="prod_rental_price" class="form-control" value="<?php echo $product['prod_rental_price']; ?>" <?php echo $has_rent ? 'required' : 'disabled'; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; box-sizing: border-box;">
                                </div>
                                <div>
                                    <label style="font-size: 0.8rem; color: #6b7280; display: block; margin-bottom: 4px; font-weight: 500;">Rental Quantity</label>
                                    <input type="number" name="prod_rental_qty" id="prod_rental_qty" class="form-control" value="<?php echo $product['prod_rental_qty']; ?>" min="1" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" <?php echo $has_rent ? 'required' : 'disabled'; ?> style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; box-sizing: border-box;">
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Instrument Description</label>
                <textarea name="prod_description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-family: inherit; outline: none; resize: vertical; box-sizing: border-box;"><?php echo htmlspecialchars($product['prod_description']); ?></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 30px; display: flex; align-items: center; gap: 20px; background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <div>
                    <p style="font-size: 0.75rem; color: #6b7280; margin-top: 0; margin-bottom: 4px; font-weight: 600; text-transform: uppercase;">Current Image</p>
                    <img src="../uploads/<?php echo (!empty($product['prod_image'])) ? htmlspecialchars($product['prod_image']) : 'default.jpg'; ?>" alt="Current Product" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; border: 1px solid #d1d5db;">
                </div>
                <div style="flex-grow: 1;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Upload New Image (Optional)</label>
                    <input type="hidden" name="current_image" value="<?php echo htmlspecialchars($product['prod_image']); ?>">
                    <input type="file" name="prod_image" accept="image/*" style="font-size: 0.9rem; width: 100%;">
                    <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 6px; margin-bottom: 0;">Leave blank to keep the current image.</p>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="admin_products.php" style="padding: 12px 24px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.2s;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='white'">
                    Cancel
                </a>
                <button type="submit" name="update_product" style="padding: 12px 24px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Exact same UI logic from the Add form to ensure disabled inputs aren't submitted
    function toggleStock(type) {
        const checkbox = document.getElementById('for_' + type);
        const container = document.getElementById('stock_' + type + '_container');
        const idPrefix = (type === 'rent') ? 'prod_rental' : 'prod_sale';
        const qtyInput = document.getElementById(idPrefix + '_qty');
        const priceInput = document.getElementById(idPrefix + '_price');
        
        if (checkbox.checked) {
            container.style.display = 'block';
            qtyInput.disabled = false;
            priceInput.disabled = false;
            qtyInput.setAttribute('required', 'true');
            priceInput.setAttribute('required', 'true');
        } else {
            container.style.display = 'none';
            qtyInput.disabled = true;
            priceInput.disabled = true;
            qtyInput.removeAttribute('required');
            priceInput.removeAttribute('required');
        }
    }
</script>

<?php require_once('admin_footer.php'); ?>