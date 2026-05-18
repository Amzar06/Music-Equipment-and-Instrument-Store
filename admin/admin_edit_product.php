<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$success = "";
$error = "";

if (!isset($_GET['id']) || $_GET['id'] == "") {
    header("Location: admin_products.php");
    exit();
}

$prod_id = $_GET['id'];

$sql = "SELECT * FROM products WHERE prod_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $prod_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: admin_products.php");
    exit();
}

$product = mysqli_fetch_assoc($result);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_product'])) {
    $prod_name         = $_POST['prod_name'];
    $category_id       = $_POST['category_id'];
    $prod_description  = $_POST['prod_description'];
    $prod_sale_price   = $_POST['prod_sale_price'];
    $prod_rental_price = $_POST['prod_rental_price'];
    $prod_qty          = $_POST['prod_qty'];
    $status            = $_POST['status'];
    $prod_image        = $product['prod_image'];

    if ($prod_name == "" || $category_id == "" || $prod_qty == "") {
        $error = "Product name, category and quantity are required.";
    } else {
        if (isset($_FILES['prod_image']) && $_FILES['prod_image']['error'] == 0) {
            $allowed   = array("jpg", "jpeg", "png");
            $file_name = $_FILES['prod_image']['name'];
            $ext       = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_file_name = time() . "_" . $file_name;
                $upload_path   = "../images/" . $new_file_name;
                move_uploaded_file($_FILES['prod_image']['tmp_name'], $upload_path);
                $prod_image = $new_file_name;
            } else {
                $error = "Only JPG, JPEG and PNG files are allowed.";
            }
        }

        if ($error == "") {
            $sql  = "UPDATE products SET category_id=?, prod_name=?, prod_description=?, prod_sale_price=?, prod_rental_price=?, prod_qty=?, prod_image=?, status=? WHERE prod_id=?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "issddissi", $category_id, $prod_name, $prod_description, $prod_sale_price, $prod_rental_price, $prod_qty, $prod_image, $status, $prod_id);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Product updated successfully.";

                $sql2   = "SELECT * FROM products WHERE prod_id = ?";
                $stmt2  = mysqli_prepare($conn, $sql2);
                mysqli_stmt_bind_param($stmt2, "i", $prod_id);
                mysqli_stmt_execute($stmt2);
                $result2 = mysqli_stmt_get_result($stmt2);
                $product = mysqli_fetch_assoc($result2);
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

$page_title = "Edit Product";
$active     = "products";
require_once('admin_header.php');
?>

<div class="main-header">
    <div>
        <h1>Edit Product</h1>
        <div class="meta">Update product details</div>
    </div>
    <a href="admin_products.php" class="btn btn-outline">Back to Products</a>
</div>

<?php if ($success != ""): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-head">
        <span>Edit Product — <?php echo $product['prod_name']; ?></span>
    </div>
    <div style="padding: 20px;">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label for="prod_name">Product Name</label>
                    <input type="text" id="prod_name" name="prod_name" value="<?php echo htmlspecialchars($product['prod_name']); ?>">
                </div>
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- Select Category --</option>
                        <?php while ($category = mysqli_fetch_assoc($categories)): ?>
                        <option value="<?php echo $category['category_id']; ?>"
                            <?php if ($category['category_id'] == $product['category_id']) echo "selected"; ?>>
                            <?php echo $category['category_name']; ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="prod_description">Description</label>
                <textarea id="prod_description" name="prod_description"><?php echo htmlspecialchars($product['prod_description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="prod_sale_price">Sale Price (RM)</label>
                    <input type="number" id="prod_sale_price" name="prod_sale_price" step="0.01" value="<?php echo $product['prod_sale_price']; ?>">
                </div>
                <div class="form-group">
                    <label for="prod_rental_price">Rental Price Per Day (RM)</label>
                    <input type="number" id="prod_rental_price" name="prod_rental_price" step="0.01" value="<?php echo $product['prod_rental_price']; ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="prod_qty">Stock Quantity</label>
                    <input type="number" id="prod_qty" name="prod_qty" value="<?php echo $product['prod_qty']; ?>">
                </div>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="available" <?php if ($product['status'] == 'available') echo "selected"; ?>>Available</option>
                        <option value="out_of_stock" <?php if ($product['status'] == 'out_of_stock') echo "selected"; ?>>Out of Stock</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="prod_image">Product Image</label>
                <?php if ($product['prod_image'] != ""): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="../images/<?php echo $product['prod_image']; ?>" alt="current image" style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:1px solid #e0e0e0;">
                        <div style="font-size:12px; color:#888; margin-top:4px;">Current image — upload a new one to replace it</div>
                    </div>
                <?php endif; ?>
                <input type="file" id="prod_image" name="prod_image" accept="image/*">
            </div>

            <div style="display:flex; gap:12px;">
                <button type="submit" name="update_product" class="btn btn-green">Save Changes</button>
                <a href="admin_products.php" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>