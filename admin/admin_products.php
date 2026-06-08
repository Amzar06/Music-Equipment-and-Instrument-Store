<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Inventory Management";
$active = "products";

// ==========================================
// FLASH MESSAGE LOGIC
// ==========================================
$message = "";
$message_type = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

// ==========================================
// 1. HANDLE SOFT DELETE ACTION (Archiving)
// ==========================================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Soft delete to preserve rental/sales history
    $delete_query = "UPDATE products SET status = 'Discontinued' WHERE prod_id = $delete_id";
    
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['flash_message'] = "Product archived successfully. (History preserved)";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error archiving product: " . mysqli_error($conn);
        $_SESSION['flash_type'] = "error";
    }
    
    header("Location: admin_products.php");
    exit();
}

// ==========================================
// 2. HANDLE ADD PRODUCT FORM SUBMISSION
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $prod_name        = mysqli_real_escape_string($conn, $_POST['prod_name']);
    $category_id      = intval($_POST['category_id']);
    $prod_description = mysqli_real_escape_string($conn, $_POST['prod_description']);
    $staff_id         = intval($_SESSION['staff_id']); 
    
    $prod_sale_qty     = isset($_POST['for_sale']) && isset($_POST['prod_sale_qty']) ? intval(preg_replace('/[^0-9]/', '', $_POST['prod_sale_qty'])) : 0;
    $prod_sale_price   = isset($_POST['for_sale']) && isset($_POST['prod_sale_price']) ? floatval($_POST['prod_sale_price']) : 0;
    
    $prod_rental_qty   = isset($_POST['for_rent']) && isset($_POST['prod_rental_qty']) ? intval(preg_replace('/[^0-9]/', '', $_POST['prod_rental_qty'])) : 0;
    $prod_rental_price = isset($_POST['for_rent']) && isset($_POST['prod_rental_price']) ? floatval($_POST['prod_rental_price']) : 0;
    
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
        if (!isset($_POST['for_sale']) && !isset($_POST['for_rent'])) {
            $_SESSION['flash_message'] = "You must assign the item for Sale or Rent.";
            $_SESSION['flash_type'] = "error";
            header("Location: admin_products.php");
            exit();
        } else {
            $is_valid = true;
            $validation_error = "";

            if (isset($_POST['for_sale'])) {
                if ($prod_sale_price < 0.01 || $prod_sale_qty < 1) {
                    $is_valid = false;
                    $validation_error = "Sale price and quantity must be valid numbers greater than 0.";
                }
            }
            if (isset($_POST['for_rent'])) {
                if ($prod_rental_price < 0.01 || $prod_rental_qty < 1) {
                    $is_valid = false;
                    $validation_error = "Rental price and quantity must be valid numbers greater than 0.";
                }
            }

            if (!$is_valid) {
                $_SESSION['flash_message'] = $validation_error;
                $_SESSION['flash_type'] = "error";
                header("Location: admin_products.php");
                exit();
            }

            $insert_query = "INSERT INTO products (prod_name, category_id, staff_id, prod_description, prod_sale_price, prod_rental_price, prod_sale_qty, prod_rental_qty, prod_image, status) 
                             VALUES ('$prod_name', $category_id, $staff_id, '$prod_description', $prod_sale_price, $prod_rental_price, $prod_sale_qty, $prod_rental_qty, '$image_name', 'Available')";
            
            if (mysqli_query($conn, $insert_query)) {
                $_SESSION['flash_message'] = "New instrument added to inventory successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Database error: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "error";
            }
            header("Location: admin_products.php");
            exit();
        }
    } else {
        $_SESSION['flash_message'] = "Please provide all required fields.";
        $_SESSION['flash_type'] = "error";
        header("Location: admin_products.php");
        exit();
    }
}

// ==========================================
// 3. FETCH DATA WITH SEARCH & SORT LOGIC
// ==========================================
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

// Catch Search & Sort Inputs
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$where_clause = "WHERE p.status != 'Discontinued'";

if (!empty($search)) {
    $where_clause .= " AND (p.prod_name LIKE '%$search%' OR c.category_name LIKE '%$search%')";
}

// Apply Local Sort
if ($sort == 'name_asc') {
    $order_clause = "ORDER BY p.prod_name ASC";
} elseif ($sort == 'price_high') {
    $order_clause = "ORDER BY p.prod_sale_price DESC";
} elseif ($sort == 'price_low') {
    $order_clause = "ORDER BY p.prod_sale_price ASC";
} else {
    $order_clause = "ORDER BY p.prod_id DESC"; // Default: Newest first
}

$products_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   $where_clause 
                   $order_clause";

$products_query = "SELECT p.*, c.category_name 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.category_id 
                   $where_clause 
                   $order_clause";
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

            <div class="form-group" style="margin-bottom: 16px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 14px; color: #111827;">Availability, Price & Stock *</label>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            <input type="checkbox" id="for_sale" name="for_sale" value="1" onchange="toggleStock('sale')" style="width: 16px; height: 16px;"> 
                            Available for Sale
                        </label>
                        <div id="stock_sale_container" style="display: none; padding-left: 24px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 4px;">Sale Price (RM)</label>
                                    <input type="number" step="0.01" name="prod_sale_price" id="prod_sale_price" class="form-control" placeholder="0.00" min="0.01" disabled style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 4px;">Sale Quantity</label>
                                    <input type="number" name="prod_sale_qty" id="prod_sale_qty" class="form-control" placeholder="1" min="1" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" disabled style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="height: 1px; background: #e5e7eb; width: 100%;"></div>

                    <div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            <input type="checkbox" id="for_rent" name="for_rent" value="1" onchange="toggleStock('rent')" style="width: 16px; height: 16px;"> 
                            Available for Rent
                        </label>
                        <div id="stock_rent_container" style="display: none; padding-left: 24px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 4px;">Rental Price/Day (RM)</label>
                                    <input type="number" step="0.01" name="prod_rental_price" id="prod_rental_price" class="form-control" placeholder="0.00" min="0.01" disabled style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 4px;">Rental Quantity</label>
                                    <input type="number" name="prod_rental_qty" id="prod_rental_qty" class="form-control" placeholder="1" min="1" step="1" oninput="this.value = this.value.replace(/[^0-9]/g, '')" disabled style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Instrument Description</label>
                <textarea name="prod_description" rows="3" style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; font-family: inherit; outline: none; resize: vertical;"></textarea>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Product Image</label>
                <input type="file" name="prod_image" accept="image/*" style="font-size: 0.9rem;">
            </div>

            <button type="submit" name="add_product" style="width: 100%; padding: 12px; background: var(--accent-color, #111827); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                Save to Inventory
            </button>
        </form>
    </div>

    <div class="table-container" style="margin-top: 0;">
        <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-main);">Current Stock</h3>
        
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: flex-end;">
            <form action="admin_products.php" method="GET" style="display: flex; gap: 12px; align-items: center;">
                <?php if(!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <span style="font-size: 0.85rem; color: #4b5563;">Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong></span>
                <?php endif; ?>
                
                <select name="sort" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; outline: none; font-size: 0.9rem;" onchange="this.form.submit()">
    <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Sort: Newest First</option>
    <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Sort: Name (A-Z)</option>
    <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Sort: Highest Sale Price</option>
    <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Sort: Lowest Sale Price</option>
</select>
                
                <?php if(!empty($search) || $sort != 'name_asc'): ?>
                    <a href="admin_products.php" style="color: #ef4444; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb; text-align: left;">
                        <th style="padding: 12px 8px;">Image</th>
                        <th style="padding: 12px 8px;">Product Details</th>
                        <th style="padding: 12px 8px;">Prices</th>
                        <th style="padding: 12px 8px;">Stock Levels</th>
                        <th style="padding: 12px 8px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($products_result, 0);
                    if (mysqli_num_rows($products_result) > 0): 
                    ?>
                        <?php while($row = mysqli_fetch_assoc($products_result)): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 12px 8px; width: 60px;">
                                <img src="../uploads/<?php echo (!empty($row['prod_image'])) ? $row['prod_image'] : 'default.jpg'; ?>" 
                                     alt="Item" 
                                     style="width: 45px; height: 45px; object-fit: cover; border-radius: 6px; border: 1px solid var(--border-color, #e5e7eb);">
                            </td>
                            <td style="padding: 12px 8px;">
                                <div style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                                <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-top: 2px;"><?php echo htmlspecialchars($row['category_name']); ?></div>
                            </td>
                            <td style="padding: 12px 8px;">
                                <?php if($row['prod_sale_qty'] > 0 || $row['prod_sale_price'] > 0): ?>
                                    <div style="font-size: 0.85rem; color: #111827;">Sale: <strong>RM <?php echo number_format($row['prod_sale_price'], 2); ?></strong></div>
                                <?php endif; ?>
                                <?php if($row['prod_rental_qty'] > 0 || $row['prod_rental_price'] > 0): ?>
                                    <div style="font-size: 0.85rem; color: #4b5563; margin-top: 2px;">Rent: <strong>RM <?php echo number_format($row['prod_rental_price'], 2); ?>/day</strong></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 8px;">
                                <div style="font-size: 0.85rem; display: flex; flex-direction: column; gap: 4px;">
                                    <?php if($row['prod_sale_qty'] > 0 || $row['prod_sale_price'] > 0): ?>
                                        <span style="background: <?php echo ($row['prod_sale_qty'] > 0) ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo ($row['prod_sale_qty'] > 0) ? '#065f46' : '#991b1b'; ?>; padding: 2px 8px; border-radius: 12px; display: inline-block; width: fit-content;">
                                            Sale: <?php echo $row['prod_sale_qty']; ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if($row['prod_rental_qty'] > 0 || $row['prod_rental_price'] > 0): ?>
                                        <span style="background: <?php echo ($row['prod_rental_qty'] > 0) ? '#dbeafe' : '#fee2e2'; ?>; color: <?php echo ($row['prod_rental_qty'] > 0) ? '#1e40af' : '#991b1b'; ?>; padding: 2px 8px; border-radius: 12px; display: inline-block; width: fit-content;">
                                            Rent: <?php echo $row['prod_rental_qty']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 12px 8px; text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="admin_edit_product.php?id=<?php echo $row['prod_id']; ?>" 
                                       style="color: #4f46e5; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #e0e7ff; transition: 0.2s;">
                                       Edit
                                    </a>
                                    <a href="admin_products.php?delete_id=<?php echo $row['prod_id']; ?>" 
                                       style="color: #ef4444; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #fee2e2; transition: 0.2s;"
                                       onclick="return confirm('Are you sure you want to archive this item? (Old receipts will be preserved)')">
                                       Delete
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #9ca3af;">
                                No products found. <?php echo !empty($search) ? 'Try adjusting your search filters.' : ''; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script>
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
            qtyInput.value = '';
            priceInput.value = '';
        }
    }
</script>

<?php require_once('admin_footer.php'); ?>