<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }
require_once('../database.php');

$page_title = "Edit Item";
$active = "products";
$hide_search = true;

if (!isset($_GET['id'])) { header("Location: admin_products.php"); exit(); }
$prod_id = intval($_GET['id']);

// Handle deletion of products safely

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $delete_query = "DELETE FROM products WHERE prod_id = $prod_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['flash_message'] = "Item successfully deleted from catalog.";
        $_SESSION['flash_type'] = "success";
        header("Location: admin_products.php"); 
        exit();
    } else {
        if (mysqli_errno($conn) == 1451) {
            $_SESSION['flash_message'] = "Cannot delete: This item is linked to past sales/rentals. Please set stock to 0 to archive it instead.";
        } else {
            $_SESSION['flash_message'] = "Database Error: " . mysqli_error($conn);
        }
        $_SESSION['flash_type'] = "error";
        header("Location: admin_edit_product.php?id=$prod_id");
        exit();
    }
}


// Handle master catalog updates

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $p_name = mysqli_real_escape_string($conn, trim($_POST['prod_name']));
    $p_desc = mysqli_real_escape_string($conn, trim($_POST['prod_description']));
    
    // Fallback to 0 if the field was disabled/locked out in the UI

    $p_sale_price   = isset($_POST['prod_sale_price']) ? floatval($_POST['prod_sale_price']) : 0;
    $p_sale_qty     = isset($_POST['prod_sale_qty']) ? intval($_POST['prod_sale_qty']) : 0;
    $p_rental_price = isset($_POST['prod_rental_price']) ? floatval($_POST['prod_rental_price']) : 0;
    $p_rental_qty   = isset($_POST['prod_rental_qty']) ? intval($_POST['prod_rental_qty']) : 0;

    $update_query = "UPDATE products SET 
                        prod_name = '$p_name',
                        prod_sale_price = $p_sale_price,
                        prod_rental_price = $p_rental_price,
                        prod_sale_qty = $p_sale_qty,
                        prod_rental_qty = $p_rental_qty,
                        prod_description = '$p_desc'
                     WHERE prod_id = $prod_id";

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['flash_message'] = "Catalog details successfully updated!";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Database Error: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
    }
    header("Location: admin_edit_product.php?id=$prod_id");
    exit();
}

$product_query = mysqli_query($conn, "SELECT * FROM products WHERE prod_id = $prod_id");
$product = mysqli_fetch_assoc($product_query);

// Determine structural listing limits on load

$is_sale_allowed = ($product['prod_sale_price'] > 0 || $product['prod_sale_qty'] > 0 || $product['prod_rental_price'] == 0);
$is_rent_allowed = ($product['prod_rental_price'] > 0 || $product['prod_rental_qty'] > 0 || $product['prod_sale_price'] == 0);

require_once('admin_header.php');
?>

<div style="max-width: 800px; margin: 0 auto; margin-top: 20px;">
    
    <a href="admin_products.php" style="display: inline-flex; align-items: center; gap: 6px; color: #4b5563; text-decoration: none; font-size: 0.9rem; font-weight: 600; margin-bottom: 20px;">
        ← Back to Inventory
    </a>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($_SESSION['flash_type'] == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 32px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        
        <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #e5e7eb;">
            <img src="../uploads/<?php echo $product['prod_image'] ?: 'default.jpg'; ?>" style="width: 100px; height: 100px; border-radius: 8px; object-fit: cover; border: 1px solid #d1d5db;">
            <div>
                <h2 style="margin: 0 0 8px 0; color: #111827;">Edit Item: <?php echo htmlspecialchars($product['prod_name']); ?></h2>
                <span style="background: #e0e7ff; color: #4f46e5; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">In Catalog</span>
            </div>
        </div>

        <form action="admin_edit_product.php?id=<?php echo $prod_id; ?>" method="POST">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Product Name</label>
                <input type="text" name="prod_name" value="<?php echo htmlspecialchars($product['prod_name']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                
                <div style="background: <?php echo $is_sale_allowed ? '#f8fafc' : '#f3f4f6'; ?>; padding: 16px; border-radius: 8px; border: 1px solid <?php echo $is_sale_allowed ? '#e2e8f0' : '#e5e7eb'; ?>; opacity: <?php echo $is_sale_allowed ? '1' : '0.6'; ?>;">
                    <h4 style="margin-top: 0; color: #0f172a; margin-bottom: 12px;">Sale Data <?php echo !$is_sale_allowed ? '<span style="font-size:0.75rem; font-weight:normal; color:#9ca3af;">(Rental Only Item)</span>' : ''; ?></h4>
                    
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #64748b; font-weight: bold;">Sale Price (RM)</label>
                    <input type="number" step="0.01" name="prod_sale_price" value="<?php echo $product['prod_sale_price']; ?>" <?php echo $is_sale_allowed ? 'required' : 'disabled'; ?> style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #cbd5e1; border-radius: 4px; background: <?php echo $is_sale_allowed ? '#fff' : '#e5e7eb'; ?>;">
                    
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #64748b; font-weight: bold;">Available Stock (Sales)</label>
                    <input type="number" name="prod_sale_qty" value="<?php echo $product['prod_sale_qty']; ?>" <?php echo $is_sale_allowed ? 'required' : 'disabled'; ?> style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; background: <?php echo $is_sale_allowed ? '#fff' : '#e5e7eb'; ?>;">
                </div>

                <div style="background: <?php echo $is_rent_allowed ? '#fffbeb' : '#f3f4f6'; ?>; padding: 16px; border-radius: 8px; border: 1px solid <?php echo $is_rent_allowed ? '#fef3c7' : '#e5e7eb'; ?>; opacity: <?php echo $is_rent_allowed ? '1' : '0.6'; ?>;">
                    <h4 style="margin-top: 0; color: <?php echo $is_rent_allowed ? '#92400e' : '#0f172a'; ?>; margin-bottom: 12px;">Rental Data <?php echo !$is_rent_allowed ? '<span style="font-size:0.75rem; font-weight:normal; color:#9ca3af;">(Sale Only Item)</span>' : ''; ?></h4>
                    
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #b45309; font-weight: bold;">Rental Rate (RM/Day)</label>
                    <input type="number" step="0.01" name="prod_rental_price" value="<?php echo $product['prod_rental_price']; ?>" <?php echo $is_rent_allowed ? 'required' : 'disabled'; ?> style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #fde68a; border-radius: 4px; background: <?php echo $is_rent_allowed ? '#fff' : '#e5e7eb'; ?>;">
                    
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #b45309; font-weight: bold;">Available Stock (Rentals)</label>
                    <input type="number" name="prod_rental_qty" value="<?php echo $product['prod_rental_qty']; ?>" <?php echo $is_rent_allowed ? 'required' : 'disabled'; ?> style="width: 100%; padding: 8px; border: 1px solid #fde68a; border-radius: 4px; background: <?php echo $is_rent_allowed ? '#fff' : '#e5e7eb'; ?>;">
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Description</label>
                <textarea name="prod_description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit;"><?php echo htmlspecialchars($product['prod_description']); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" name="update_product" style="flex-grow: 1; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 1.05rem; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                    Save Changes
                </button>
                
                <button type="submit" name="delete_product" onclick="return confirm('Are you sure you want to permanently delete this item?');" style="padding: 14px 24px; background: #ef4444; color: white; border: none; border-radius: 8px; font-size: 1.05rem; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#dc2626'" onmouseout="this.style.backgroundColor='#ef4444'">
                    Delete Item
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>