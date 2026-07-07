<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Inventory Management";
$active = "products";


// Flash message logic

$message = ""; $message_type = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}


// Handle delete

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete_query = "UPDATE products SET status = 'Discontinued' WHERE prod_id = $delete_id";
    if (mysqli_query($conn, $delete_query)) {
        $_SESSION['flash_message'] = "Product archived successfully. (History preserved)";
        $_SESSION['flash_type'] = "success";
    } else {
        $_SESSION['flash_message'] = "Error archiving product.";
        $_SESSION['flash_type'] = "error";
    }
    header("Location: admin_products.php");
    exit();
}


// handle add product

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $prod_name        = mysqli_real_escape_string($conn, $_POST['prod_name']);
    $category_id      = intval($_POST['category_id']);
    $prod_description = mysqli_real_escape_string($conn, $_POST['prod_description']);
    $staff_id         = intval($_SESSION['staff_id']); 
    
    $product_type = 'none';
    if (isset($_POST['for_sale']) && isset($_POST['for_rent'])) { $product_type = 'both'; }
    elseif (isset($_POST['for_sale'])) { $product_type = 'sale'; }
    elseif (isset($_POST['for_rent'])) { $product_type = 'rent'; }

    $prod_sale_qty = 0;
    $prod_rental_qty = 0;

    if ($product_type === 'sale' || $product_type === 'both') {
        $prod_sale_qty = intval(preg_replace('/[^0-9]/', '', $_POST['prod_sale_qty'] ?? 0));
    }
    if ($product_type === 'rent' || $product_type === 'both') {
        // Enforce rental quantity to exactly 1 on the server side
        $prod_rental_qty = 1;
    }
    
    $prod_sale_price   = isset($_POST['for_sale']) ? floatval($_POST['prod_sale_price']) : 0;
    $prod_rental_price = isset($_POST['for_rent']) ? floatval($_POST['prod_rental_price']) : 0;
    
    $image_name = "default.jpg"; 
    if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $image_name = time() . "_" . basename($_FILES["prod_image"]["name"]);
        move_uploaded_file($_FILES["prod_image"]["tmp_name"], $target_dir . $image_name);
    }

    if (!empty($prod_name) && $category_id > 0) {
        if ($product_type === 'none') {
            $_SESSION['flash_message'] = "You must assign the item for Sale or Rent.";
            $_SESSION['flash_type'] = "error";
        } else {
            // Updated INSERT statement to include prod_deposit
            $insert_query = "INSERT INTO products (prod_name, category_id, staff_id, prod_description, prod_sale_price, prod_rental_price, prod_sale_qty, prod_rental_qty, prod_image, status) "
                          . "VALUES ('$prod_name', $category_id, $staff_id, '$prod_description', $prod_sale_price, $prod_rental_price, $prod_sale_qty, $prod_rental_qty, '$image_name', 'Available')";
            
            if (mysqli_query($conn, $insert_query)) {
                $_SESSION['flash_message'] = "New instrument added to inventory successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Database error: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "error";
            }
        }
    } else {
        $_SESSION['flash_message'] = "Please provide all required fields.";
        $_SESSION['flash_type'] = "error";
    }
    header("Location: admin_products.php");
    exit();
}


// fetch data

// Fetch categories for both the add-product form and the filter dropdown
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");
$all_categories = [];
while ($cat_row = mysqli_fetch_assoc($categories_result)) {
    $all_categories[] = $cat_row;
}

$search      = isset($_GET['search'])      ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$sort        = isset($_GET['sort'])        ? $_GET['sort'] : 'newest';
$filter_cat  = isset($_GET['filter_cat']) ? intval($_GET['filter_cat']) : 0;
$view        = isset($_GET['view'])        ? $_GET['view'] : 'sale';

$where_clause = "WHERE p.status != 'Discontinued'";
if (!empty($search)) {
    $where_clause .= " AND (p.prod_name LIKE '%$search%' OR c.category_name LIKE '%$search%')";
}
if ($filter_cat > 0) {
    $where_clause .= " AND p.category_id = $filter_cat";
}

$sale_where = $where_clause . " AND p.prod_sale_price > 0";
$rent_where = $where_clause . " AND p.prod_rental_price > 0";

if ($sort == 'name_asc')   $order_clause = "ORDER BY p.prod_name ASC";
elseif ($sort == 'name_desc') $order_clause = "ORDER BY p.prod_name DESC";
elseif ($sort == 'price_high') $order_clause = "ORDER BY p.prod_sale_price DESC";
elseif ($sort == 'price_low')  $order_clause = "ORDER BY p.prod_sale_price ASC";
else $order_clause = "ORDER BY p.prod_id DESC";

$sale_query = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id $sale_where $order_clause";
$rent_query = "SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id $rent_where $order_clause";

$sale_result = mysqli_query($conn, $sale_query);
$rent_result = mysqli_query($conn, $rent_query);

require_once('admin_header.php');
?>

<?php if (!empty($message)): ?>
    <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 32px; align-items: start;">
    
    <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">Add New Product</h3>
        
        <form action="admin_products.php" method="POST" enctype="multipart/form-data">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Product Name:</label>
                <input type="text" name="prod_name" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Category:</label>
                <select name="category_id" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white; outline: none;">
                    <option value="">-- Select Category --</option>
                    <?php foreach($all_categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['category_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="margin-bottom: 16px; padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 14px; color: #111827;">Price & Stock:</label>

                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.95rem; font-weight: 600; color: #374151; margin-bottom: 8px;">
                            <input type="checkbox" id="for_sale" name="for_sale" value="1" onchange="toggleStock('sale')" style="width: 16px; height: 16px;"> 
                            Available for Sale
                        </label>
                        <div id="stock_sale_container" style="display: none; padding-left: 24px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                <div>
                                    <label style="font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 4px;">Sale Price (RM):</label>
                                    <input type="number" min="0" step="0.01" name="prod_sale_price" id="prod_sale_price" placeholder="0.00" readonly style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; background: #f3f4f6;">
                                </div>
                                <div>
                                    <label style="font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 4px;">Sale Quantity:</label>
                                    <input type="number" min="0" name="prod_sale_qty" id="prod_sale_qty" placeholder="1" readonly style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; background: #f3f4f6;">
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
                            <div style="display: grid; grid-template-columns: 1fr; gap: 12px;">
    <div>
        <label style="font-size: 0.75rem; color: #6b7280; display: block; margin-bottom: 4px;">Price/day (RM):</label>
        <input type="number" min="0" step="0.01" name="prod_rental_price" id="prod_rental_price" placeholder="0.00" readonly style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; background: #f3f4f6;">
    </div>
    <input type="hidden" name="prod_rental_qty" id="prod_rental_qty" value="1">
 </div>
                        </div>
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Product Description:</label>
                <textarea name="prod_description" rows="3" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Product Image:</label>
                <input type="file" name="prod_image" accept="image/*" style="font-size: 0.9rem;">
            </div>

            <button type="submit" name="add_product" style="width: 100%; padding: 12px; background: #111827; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Product</button>
        </form>
    </div>

    <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        
        <div style="display: flex; gap: 16px; border-bottom: 2px solid #e5e7eb; margin-bottom: 20px;">
            <a href="admin_products.php?view=sale" style="padding-bottom: 12px; font-weight: 700; font-size: 1.1rem; color: <?php echo ($view == 'sale') ? '#4f46e5' : '#6b7280'; ?>; border-bottom: 3px solid <?php echo ($view == 'sale') ? '#4f46e5' : 'transparent'; ?>; text-decoration: none;">Sale Products</a>
            <a href="admin_products.php?view=rent" style="padding-bottom: 12px; font-weight: 700; font-size: 1.1rem; color: <?php echo ($view == 'rent') ? '#10b981' : '#6b7280'; ?>; border-bottom: 3px solid <?php echo ($view == 'rent') ? '#10b981' : 'transparent'; ?>; text-decoration: none;">Rent Products</a>
        </div>
        
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
            <form action="admin_products.php" method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" style="flex-grow: 1; min-width: 160px; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                <select name="filter_cat" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; min-width: 180px;">
                    <option value="0">All Categories</option>
                    <?php foreach($all_categories as $cat): ?>
                        <option value="<?php echo $cat['category_id']; ?>" <?php echo ($filter_cat == $cat['category_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['category_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="sort" style="padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none;">
                    <option value="newest" <?php echo ($sort == 'newest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                    <option value="price_high" <?php echo ($sort == 'price_high') ? 'selected' : ''; ?>>Highest Price</option>
                    <option value="price_low" <?php echo ($sort == 'price_low') ? 'selected' : ''; ?>>Lowest Price</option>
                </select>
                <button type="submit" style="padding: 10px 20px; background: #374151; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Apply</button>
                <?php if(!empty($search) || $sort != 'newest' || $filter_cat > 0): ?>
                    <a href="admin_products.php?view=<?php echo $view; ?>" style="color: #ef4444; font-weight: 600; text-decoration: none;">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px 8px; font-size: 0.85rem; color: #4b5563;">Item</th>
                        <th style="padding: 12px 8px; font-size: 0.85rem; color: #4b5563;"><?php echo ($view == 'sale') ? 'Sale Price' : 'Rental Rate'; ?></th>
                        <th style="padding: 12px 8px; font-size: 0.85rem; color: #4b5563;">Available Stock</th>
                        <th style="padding: 12px 8px; text-align: right; font-size: 0.85rem; color: #4b5563;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $active_result = ($view == 'sale') ? $sale_result : $rent_result;
                    if (mysqli_num_rows($active_result) > 0): 
                        while($row = mysqli_fetch_assoc($active_result)): 
                    ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 16px 8px;">
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <img src="../uploads/<?php echo (!empty($row['prod_image'])) ? $row['prod_image'] : 'default.jpg'; ?>" style="width: 45px; height: 45px; border-radius: 6px; object-fit: cover;">
                                    <div>
                                        <div style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['prod_name']); ?></div>
                                        <div style="font-size: 0.75rem; color: #6b7280; text-transform: uppercase; margin-top: 2px;"><?php echo htmlspecialchars($row['category_name']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 16px 8px;">
                                <?php if($view == 'sale'): ?>
                                    <div style="font-size: 0.9rem; font-weight: 600; color: #111827;">RM <?php echo number_format($row['prod_sale_price'], 2); ?></div>
                                <?php else: ?>
                                    <div style="font-size: 0.9rem; font-weight: 600; color: #111827;">RM <?php echo number_format($row['prod_rental_price'], 2); ?> <span style="font-size: 0.75rem; font-weight: normal; color: #6b7280;">/ day</span></div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 16px 8px;">
                                <?php if($view == 'sale'): ?>
                                    <span style="background: <?php echo ($row['prod_sale_qty'] > 0) ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo ($row['prod_sale_qty'] > 0) ? '#065f46' : '#991b1b'; ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                        Qty: <?php echo $row['prod_sale_qty']; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="background: <?php echo ($row['prod_rental_qty'] > 0) ? '#dbeafe' : '#f3f4f6'; ?>; color: <?php echo ($row['prod_rental_qty'] > 0) ? '#1e40af' : '#4b5563'; ?>; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                        Qty: <?php echo $row['prod_rental_qty']; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <a href="admin_edit_product.php?id=<?php echo $row['prod_id']; ?>" style="color: #4f46e5; background: #e0e7ff; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 0.85rem; text-decoration: none;">Manage</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af;">No products found in this category.</td></tr>
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
        
        const inputSuffix = (type === 'rent') ? 'rental' : 'sale';
        
        const priceInput = document.getElementById('prod_' + inputSuffix + '_price');
        const qtyInput = document.getElementById('prod_' + inputSuffix + '_qty');
        
        if (checkbox.checked) {
            container.style.display = 'block';
            priceInput.readOnly = false;
            priceInput.style.background = '#ffffff';
            priceInput.setAttribute('required', 'true');
            
            // If type is rent, we keep quantity locked to 1 and read-only
            if (type === 'rent') {
                qtyInput.readOnly = true;
                qtyInput.style.background = '#f3f4f6';
                qtyInput.value = '1';
                
            } else {
                qtyInput.readOnly = false;
                qtyInput.style.background = '#ffffff';
                qtyInput.setAttribute('required', 'true');
            }
        } else {
            container.style.display = 'none';
            priceInput.readOnly = true;
            priceInput.style.background = '#f3f4f6';
            priceInput.removeAttribute('required');
            priceInput.value = '';
            
            qtyInput.readOnly = true;
            qtyInput.style.background = '#f3f4f6';
            qtyInput.removeAttribute('required');
            qtyInput.value = '';
        }
    }

    window.onload = function() {
        toggleStock('sale');
        toggleStock('rent');
    };
</script>

<?php require_once('admin_footer.php'); ?>