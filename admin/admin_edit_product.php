<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }
require_once('../database.php');

$page_title = "Manage Instrument";
$active = "products";
$hide_search = true;

if (!isset($_GET['id'])) { header("Location: admin_products.php"); exit(); }
$prod_id = intval($_GET['id']);

// 1. Handle adding a specific Physical Asset (Serial Number & Wear Level)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_asset'])) {
    $serial = mysqli_real_escape_string($conn, trim($_POST['serial_number']));
    $condition = mysqli_real_escape_string($conn, $_POST['condition_level']);
    
    if (!empty($serial)) {
        $insert_asset = "INSERT INTO rental_inventory (prod_id, serial_number, condition_level, current_status) 
                         VALUES ($prod_id, '$serial', '$condition', 'Available')";
        if (mysqli_query($conn, $insert_asset)) {
            $_SESSION['flash_message'] = "Physical asset added to rental stock.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error: Serial number might already exist.";
            $_SESSION['flash_type'] = "error";
        }
    }
    header("Location: admin_edit_product.php?id=$prod_id");
    exit();
}

// 2. Handle Asset Removal/Retirement
if (isset($_GET['retire_asset'])) {
    $asset_id = intval($_GET['retire_asset']);
    mysqli_query($conn, "UPDATE rental_inventory SET current_status = 'Retired' WHERE rental_item_id = $asset_id");
    header("Location: admin_edit_product.php?id=$prod_id");
    exit();
}

// Fetch Product Details
$product_query = mysqli_query($conn, "SELECT * FROM products WHERE prod_id = $prod_id");
$product = mysqli_fetch_assoc($product_query);

// Fetch Physical Assets
$assets_query = mysqli_query($conn, "SELECT * FROM rental_inventory WHERE prod_id = $prod_id AND current_status != 'Retired'");

require_once('admin_header.php');
?>

<div style="max-width: 1200px; margin: 0 auto; margin-top: 20px;">
    
    <a href="admin_products.php" style="display: inline-flex; align-items: center; gap: 6px; color: #4b5563; text-decoration: none; font-size: 0.9rem; font-weight: 600; margin-bottom: 20px;">
        ← Back to Inventory
    </a>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($_SESSION['flash_type'] == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; align-items: start;">
        
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #111827;">Catalog Details</h3>
            <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 20px;">Update the general pricing and retail stock here. This affects the storefront display.</p>
            
            <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb; margin-bottom: 20px; display: flex; gap: 16px;">
                <img src="../uploads/<?php echo $product['prod_image'] ?: 'default.jpg'; ?>" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover;">
                <div>
                    <h4 style="margin: 0 0 4px 0; color: #111827;"><?php echo htmlspecialchars($product['prod_name']); ?></h4>
                    <span style="background: #e0e7ff; color: #4f46e5; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">Active in Catalog</span>
                </div>
            </div>

            <button style="width: 100%; padding: 12px; background: #e5e7eb; color: #9ca3af; border: none; border-radius: 8px; font-weight: 600; cursor: not-allowed;">
                [Catalog Edit Form Placeholder]
            </button>
        </div>

        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; margin-bottom: 20px; color: #111827;">Physical Rental Assets</h3>
            <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 20px;">Track specific serial numbers and their damage/wear levels here.</p>
            
            <form action="admin_edit_product.php?id=<?php echo $prod_id; ?>" method="POST" style="background: #f8fafc; padding: 16px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 24px; display: flex; gap: 10px; align-items: flex-end;">
                <div style="flex-grow: 1;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: #4b5563; display: block; margin-bottom: 4px;">Serial / Barcode *</label>
                    <input type="text" name="serial_number" required placeholder="e.g. YAM-10492" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                </div>
                <div style="flex-grow: 1;">
                    <label style="font-size: 0.75rem; font-weight: 600; color: #4b5563; display: block; margin-bottom: 4px;">Current Wear</label>
                    <select name="condition_level" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; background: white;">
                        <option value="Excellent">Excellent</option>
                        <option value="Good">Good</option>
                        <option value="Minor Wear">Minor Wear (Scratches)</option>
                        <option value="Needs Repair">Needs Repair</option>
                    </select>
                </div>
                <button type="submit" name="add_asset" style="background: #10b981; color: white; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 600; cursor: pointer;">+ Add</button>
            </form>

            <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; color: #4b5563; text-align: left;">
                        <th style="padding: 8px;">Serial Number</th>
                        <th style="padding: 8px;">Condition</th>
                        <th style="padding: 8px;">Status</th>
                        <th style="padding: 8px; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($assets_query) > 0): ?>
                        <?php while($asset = mysqli_fetch_assoc($assets_query)): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px 8px; font-weight: 600; color: #111827;"><?php echo htmlspecialchars($asset['serial_number']); ?></td>
                            <td style="padding: 12px 8px;">
                                <?php 
                                    $cond = $asset['condition_level'];
                                    $cond_color = ($cond == 'Excellent' || $cond == 'Good') ? '#059669' : (($cond == 'Minor Wear') ? '#d97706' : '#dc2626');
                                ?>
                                <span style="color: <?php echo $cond_color; ?>; font-weight: 600;"><?php echo htmlspecialchars($cond); ?></span>
                            </td>
                            <td style="padding: 12px 8px;">
                                <span style="background: <?php echo ($asset['current_status'] == 'Available') ? '#d1fae5' : '#fef3c7'; ?>; color: <?php echo ($asset['current_status'] == 'Available') ? '#065f46' : '#92400e'; ?>; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($asset['current_status']); ?>
                                </span>
                            </td>
                            <td style="padding: 12px 8px; text-align: right;">
                                <a href="admin_edit_product.php?id=<?php echo $prod_id; ?>&retire_asset=<?php echo $asset['rental_item_id']; ?>" 
                                   style="color: #ef4444; text-decoration: none; font-weight: 600;"
                                   onclick="return confirm('Retire this serial number from active circulation?')">Retire</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 20px; color: #9ca3af;">No physical assets recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        </div>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>