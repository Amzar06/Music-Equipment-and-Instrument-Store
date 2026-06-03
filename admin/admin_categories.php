<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_category'])) {
    $category_name = trim($_POST['category_name']);

    if ($category_name == "") {
        $error = "Category name cannot be empty.";
    } else {
        $sql = "INSERT INTO categories (category_name) VALUES (?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $category_name);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Category added successfully.";
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_category'])) {
    $category_id = $_POST['category_id'];

    $sql = "DELETE FROM categories WHERE category_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $category_id);

    if (mysqli_stmt_execute($stmt)) {
        $success = "Category deleted successfully.";
    } else {
        $error = "Cannot delete. This category may have products linked to it.";
    }
}

$sql = "SELECT * FROM categories ORDER BY category_id ASC";
$result = mysqli_query($conn, $sql);

$page_title = "Categories";
$active = "categories";
require_once('admin_header.php');
?>

<div class="main-header">
    <div>
        <h1>Categories</h1>
        <div class="meta">Manage product categories</div>
    </div>
</div>

<?php if ($success != ""): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if ($error != ""): ?>
    <div class="alert alert-error"><?php echo $error; ?></div>
<?php endif; ?>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-head">
        <span>Add New Category</span>
    </div>
    <div style="padding: 20px;">
        <form method="POST" action="">
            <div class="form-group">
                <label for="category_name">Category Name</label>
                <input type="text" id="category_name" name="category_name" placeholder="e.g. Guitars, Pianos">
            </div>
            <button type="submit" name="add_category" class="btn btn-green">Add Category</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <span>All Categories</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Category Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['category_id']; ?></td>
                    <td><?php echo $row['category_name']; ?></td>
                    <td>
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="category_id" value="<?php echo $row['category_id']; ?>">
                            <button type="submit" name="delete_category" class="btn btn-danger btn-sm"
                                onclick="return confirm('Are you sure you want to delete this category?')">
                                Delete
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3" style="text-align:center; padding:24px; color:#888;">No categories found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>