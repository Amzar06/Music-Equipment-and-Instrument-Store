<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Inventory Management";
$active = "products";

$message = "";
$message_type = "";

// ==========================================
// 1. HANDLE DELETE ACTION
// ==========================================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    $delete_query = "DELETE FROM products WHERE prod_id = $delete_id";
    if (mysqli_query($conn, $delete_query)) {
        $message = "Product deleted successfully.";
        $message_type = "success";
    } else {
        $message = "Error deleting product: " . mysqli_error($conn);
        $message_type = "error";
    }
}

// ==========================================
// 2. HANDLE ADD PRODUCT FORM SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $prod_name        = mysqli_real_escape_string($conn, $_POST['prod_name']);
    $category_id      = intval($_POST['category_id']);
    $prod_sale_price  = floatval($_POST['prod_sale_price']);
    $prod_rental_price = floatval($_POST['prod_rental_price']);
    $prod_qty         = intval($_POST['prod_qty']);
    $prod_description = mysqli_real_escape_string($conn, $_POST['prod_description']);
    $staff_id         = intval($_SESSION['staff_id']); 
    
    // Image Upload Handling
    $image_name = "default.jpg"; 
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
        $insert_query = "INSERT INTO products (prod_name, category_id, staff_id, prod_description, prod_sale_price, prod_rental_price, prod_qty, prod_image, status) 
                         VALUES ('$prod_name', $category_id, $staff_id, '$prod_description', $prod_sale_price, $prod_rental_price, $prod_qty, '$image_name', 'available')";
        
        if (mysqli_query($conn, $insert_query)) {
            $message = "New instrument added to inventory successfully!";
            $message_type = "success";
        } else {
            $message = "Database error: " . mysqli_error($conn);
            $message_type = "error";
        }
    } else {
        $message = "Please provide all required fields.";
        $message_type = "error";
    }
}

// ==========================================
// 3. FETCH DATA FOR LAYOUT VIEWING
// ==========================================
// Pull categories for dropdown filter
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

// Pull current instruments
$products_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   ORDER BY p.prod_id DESC";
$products_result = mysqli_query($conn, $products_query);

require_once('admin_header.php');
?>

<?php if (!empty($message)): ?>
    <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 32px; align-items: start;">
    
    <div class="table-container" style="margin-top: 0;">
        <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-main);">Add New Instrument</h3>
        
        <form action="admin_products.php" method="POST" enctype="multipart/form-data">
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Product Name *</label>
                <input type="text" name="prod_name" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none;">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Category *</label>
                <select name="category_id" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; background: white; outline: none;">
                    <option value="">-- Select Category --</option>
                    <?php while($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Sale Price (RM) *</label>
                    <input type="number" step="0.01" name="prod_sale_price" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none;">
                </div>
                <div class="form-group">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Rental/Day (RM) *</label>
                    <input type="number" step="0.01" name="prod_rental_price" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Stock Level (Qty) *</label>
                <input type="number" name="prod_qty" required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none;">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Instrument Description</label>
                <textarea name="prod_description" rows="3" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; outline: none; resize: vertical;"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Product Image</label>
                <input type="file" name="prod_image" accept="image/*" style="font-size: 0.9rem;">
            </div>

            <button type="submit" name="add_product" style="width: 100%; padding: 12px; background: var(--accent-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Save to Inventory
            </button>
        </form>
    </div>

    <div class="table-container" style="margin-top: 0;">
        <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-main);">Current Live Stock</h3>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Product Details</th>
                        <th>Prices</th>
                        <th>Stock</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($products_result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($products_result)): ?>
                        <tr>
                            <td style="width: 60px;">
                                <img src="../uploads/<?php echo (!empty($row['prod_image'])) ? $row['prod_image'] : 'default.jpg'; ?>" 
                                     alt="Item" 
                                     style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color);">
                            </td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-main);"><?php echo $row['prod_name']; ?></div>
                                <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-top: 2px;"><?php echo $row['category_name']; ?></div>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem; color: #111827;">Sale: <strong>RM <?php echo number_format($row['prod_sale_price'], 2); ?></strong></div>
                                <div style="font-size: 0.85rem; color: #4b5563; margin-top: 2px;">Rent: <strong>RM <?php echo number_format($row['prod_rental_price'], 2); ?>/day</strong></div>
                            </td>
                            <td>
                                <span class="status-pill <?php echo ($row['prod_qty'] > 5) ? 'completed' : 'pending'; ?>">
                                    <?php echo $row['prod_qty']; ?> units
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <a href="admin_products.php?delete_id=<?php echo $row['prod_id']; ?>" 
                                   style="color: #ef4444; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #fee2e2; transition: 0.2s;"
                                   onclick="return confirm('Are you sure you want to permanently delete this item from inventory?')">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #9ca3af;">No products found in database.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once('admin_footer.php'); ?>