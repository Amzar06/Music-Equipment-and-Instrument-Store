<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }
require_once('../database.php');

$page_title = "Edit Catalog Item";
$active = "products";
$hide_search = true;

if (!isset($_GET['id'])) { header("Location: admin_products.php"); exit(); }
$prod_id = intval($_GET['id']);

// ==========================================
// HANDLE PRODUCT DELETION (Safe Delete)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $delete_query = "DELETE FROM products WHERE prod_id = $prod_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['flash_message'] = "Item successfully deleted from catalog.";
        $_SESSION['flash_type'] = "success";
        header("Location: admin_products.php"); 
        exit();
    } else {
        // Error 1451 means MySQL blocked it due to past sales/rentals
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

// ==========================================
// HANDLE MASTER CATALOG UPDATES
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $p_name = mysqli_real_escape_string($conn, trim($_POST['prod_name']));
    $p_sale_price = floatval($_POST['prod_sale_price']);
    $p_rental_price = floatval($_POST['prod_rental_price']);
    $p_sale_qty = intval($_POST['prod_sale_qty']);
    $p_rental_qty = intval($_POST['prod_rental_qty']);
    $p_desc = mysqli_real_escape_string($conn, trim($_POST['prod_description']));

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
                <span style="background: #e0e7ff; color: #4f46e5; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">Active in Catalog</span>
            </div>
        </div>

        <form action="admin_edit_product.php?id=<?php echo $prod_id; ?>" method="POST">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Product Name</label>
                <input type="text" name="prod_name" value="<?php echo htmlspecialchars($product['prod_name']); ?>" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Listing Mode</label>
                <select id="listing_mode" onchange="toggleFields()" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 1rem; background: #f8fafc; cursor: pointer;">
                    <option value="hybrid">Hybrid (Available for Sale & Rent)</option>
                    <option value="sale_only">Retail Only (Sale)</option>
                    <option value="rent_only">Fleet Only (Rent)</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <h4 style="margin-top: 0; color: #0f172a; margin-bottom: 12px;">Retail Sales Data</h4>
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #64748b; font-weight: bold;">Sale Price (RM)</label>
                    <input type="number" step="0.01" name="prod_sale_price" value="<?php echo $product['prod_sale_price']; ?>" required style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #64748b; font-weight: bold;">Available Stock (Sales)</label>
                    <input type="number" name="prod_sale_qty" value="<?php echo $product['prod_sale_qty']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                </div>

                <div style="background: #fffbeb; padding: 16px; border-radius: 8px; border: 1px solid #fef3c7;">
                    <h4 style="margin-top: 0; color: #92400e; margin-bottom: 12px;">Rental Fleet Data</h4>
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #b45309; font-weight: bold;">Rental Rate (RM/Day)</label>
                    <input type="number" step="0.01" name="prod_rental_price" value="<?php echo $product['prod_rental_price']; ?>" required style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #fde68a; border-radius: 4px;">
                    
                    <label style="display: block; margin-bottom: 4px; font-size: 0.85rem; color: #b45309; font-weight: bold;">Available Stock (Rentals)</label>
                    <input type="number" name="prod_rental_qty" value="<?php echo $product['prod_rental_qty']; ?>" required style="width: 100%; padding: 8px; border: 1px solid #fde68a; border-radius: 4px;">
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; margin-bottom: 6px; font-weight: 600; color: #374151;">Description</label>
                <textarea name="prod_description" rows="4" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; font-family: inherit;"><?php echo htmlspecialchars($product['prod_description']); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; margin-top: 10px;">
                <button type="submit" name="update_product" style="flex-grow: 1; padding: 14px; background: #2563eb; color: white; border: none; border-radius: 8px; font-size: 1.05rem; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#1d4ed8'" onmouseout="this.style.backgroundColor='#2563eb'">
                    Save Catalog Changes
                </button>
                
                <button type="submit" name="delete_product" onclick="return confirm('Are you sure you want to permanently delete this item?');" style="padding: 14px 24px; background: #ef4444; color: white; border: none; border-radius: 8px; font-size: 1.05rem; font-weight: bold; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#dc2626'" onmouseout="this.style.backgroundColor='#ef4444'">
                    Delete Item
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleFields() {
    const mode = document.getElementById('listing_mode').value;
    
    const salePrice = document.querySelector('input[name="prod_sale_price"]');
    const saleQty = document.querySelector('input[name="prod_sale_qty"]');
    const rentPrice = document.querySelector('input[name="prod_rental_price"]');
    const rentQty = document.querySelector('input[name="prod_rental_qty"]');

    salePrice.readOnly = false; salePrice.style.background = '#fff'; salePrice.style.opacity = '1';
    saleQty.readOnly = false; saleQty.style.background = '#fff'; saleQty.style.opacity = '1';
    rentPrice.readOnly = false; rentPrice.style.background = '#fff'; rentPrice.style.opacity = '1';
    rentQty.readOnly = false; rentQty.style.background = '#fff'; rentQty.style.opacity = '1';

    if (mode === 'sale_only') {
        rentPrice.readOnly = true; rentPrice.value = 0; rentPrice.style.background = '#e5e7eb'; rentPrice.style.opacity = '0.6';
        rentQty.readOnly = true; rentQty.value = 0; rentQty.style.background = '#e5e7eb'; rentQty.style.opacity = '0.6';
    } else if (mode === 'rent_only') {
        salePrice.readOnly = true; salePrice.value = 0; salePrice.style.background = '#e5e7eb'; salePrice.style.opacity = '0.6';
        saleQty.readOnly = true; saleQty.value = 0; saleQty.style.background = '#e5e7eb'; saleQty.style.opacity = '0.6';
    }
}

window.onload = function() {
    const sPrice = parseFloat(document.querySelector('input[name="prod_sale_price"]').value) || 0;
    const rPrice = parseFloat(document.querySelector('input[name="prod_rental_price"]').value) || 0;
    const modeSelect = document.getElementById('listing_mode');
    
    if (sPrice > 0 && rPrice == 0) {
        modeSelect.value = 'sale_only';
    } else if (rPrice > 0 && sPrice == 0) {
        modeSelect.value = 'rent_only';
    } else {
        modeSelect.value = 'hybrid';
    }
    
    toggleFields();
};
</script>

<?php require_once('admin_footer.php'); ?>