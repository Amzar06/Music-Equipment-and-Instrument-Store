<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $prod_name         = $_POST['prod_name'];
    $category_id       = $_POST['category_id'];
    $prod_description  = $_POST['prod_description'];
    $prod_sale_price   = $_POST['prod_sale_price'];
    $prod_rental_price = $_POST['prod_rental_price'];
    $prod_qty          = $_POST['prod_qty'];
    $status            = $_POST['status'];
    $prod_image        = "";

    if ($prod_name == "" || $category_id == "" || $prod_description == "" || $prod_sale_price == "" || $prod_rental_price == "" || $prod_qty == "" || $status == "") {
        $error = "Product name, category, and quantity fields are required.";
    } else {
        if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
            $allowed   = array('jpg', 'jpeg', 'png');
            $file_name = $_FILES['prod_image']['name'];
            $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if(in_array($ext, $allowed)) {
                $new_file_name = time() . '_' . $file_name;
                $upload_path   = '../uploads/' . $new_file_name;
                move_uploaded_file($_FILES['prod_image']['tmp_name'], $upload_path);
                $prod_image = $new_file_name;
            } else {
                $error = "Only JPG, JPEG and PNG allowed.";
            }
        }
    }

    if ($error == "") {
        $sql = "INSERT INTO products (category_id, prod_name, prod_description, prod_sale_price, prod_rental_price, prod_qty, prod_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issddiss", $category_id, $prod_name, $prod_description, $prod_sale_price, $prod_rental_price, $prod_qty, $prod_image, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Product added successfully.";
        } else {
            $error = "Please try adding the product again.";
        }
    }
}

$sql_categories = "SELECT * FROM categories ORDER BY category_name ASC";
$categories = mysqli_query($conn, $sql_categories);

$sql_products = "SELECT p.*, c.category_name FROM products p LEFT JOIN categories c ON p.category_id = c.category_id ORDER BY p.prod_id DESC";
$products = mysqli_query($conn, $sql_products);

$page_title = "Products";
$active     = "products";    
require_once('admin_header.php');
?>

<div class="main-header">
    <div>
        <h1>Products</h1>
        <div class="meta">Manage products</div>
    </div>
</div>

<?php if ($error != ""): ?>
    <div class="alert alert-red"><?php echo $error; ?></div>
<?php endif; ?>
<?php if ($success != ""): ?>
    <div class="alert alert-green"><?php echo $success; ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-head">
        <span>Add New Product</span>
    </div>
    <div style="padding: 20px;">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="prod_name">Product Name</label>
                    <input type="text" id="prod_name" name="prod_name" placeholder="Yamaha Acoustic Guitar">
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        <?php 
                        $categories_copy = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");
                        while($cat = mysqli_fetch_assoc($categories_copy)): 
                        ?>
                            <option value="<?php echo $cat['category_id']; ?>"><?php echo $cat['category_name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>  
            </div>

            <div class="form-group">
                <label for="prod_description">Description</label>
                <textarea id="prod_description" name="prod_description"></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="prod_sale_price">Sale Price RM</label>
                    <input type="number" id="prod_sale_price" name="prod_sale_price" step="0.01" placeholder="0.00">
                </div>

                <div class="form-group">
                    <label for="prod_rental_price">Rental Price RM (per day)</label>
                    <input type="number" id="prod_rental_price" name="prod_rental_price" step="0.01" placeholder="0.00">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="prod_qty">Product Quantity</label>
                    <input type="number" id="prod_qty" name="prod_qty" placeholder="0">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="available">Available</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="prod_image">Product Image</label>
                <input type="file" id="prod_image" name="prod_image" accept="image/*">
            </div>

            <button type="submit" name="add_product" class="btn btn-green">Add Product</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <span>All Products</span>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Image</th> 
                <th>Product Name</th>
                <th>Category</th>
                <th>Sale Price (RM)</th>
                <th>Rental Price (RM/day)</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($products) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><?php echo $row['prod_id']; ?></td>
                        <td>
                            <?php if ($row['prod_image'] != ""): ?>
                                <img src="../uploads/<?php echo $row['prod_image']; ?>" alt="product" style="width:50px; height:50px; object-fit:cover; border-radius:6px;">
                            <?php else: ?>
                                <div style="width:50px; height:50px; background:#f0f0f0; border-radius:6px; display:flex; align-items:center; justify-content:center; color:#888; font-size:11px;">No Image</div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $row['prod_name']; ?></td>
                        <td><?php echo $row['category_name']; ?></td>
                        <td><?php echo number_format($row['prod_sale_price'], 2); ?></td>
                        <td><?php echo number_format($row['prod_rental_price'], 2); ?></td>
                        <td><?php echo $row['prod_qty']; ?></td>
                        <td>
                            <span class="status status-<?php echo $row['status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                            </span>
                        </td>
                        <td style="display:flex; gap:8px;">
                            <a href="admin_edit_product.php?id=<?php echo $row['prod_id']; ?>" class="btn btn-outline btn-s">Edit</a>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="prod_id" value="<?php echo $row['prod_id']; ?>">
                                <button type="submit" name="delete_product" class="btn btn-danger btn-s" onclick="return confirm('Delete this product?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="9" style="text-align:center; padding:24px; color:#888;">No products found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>