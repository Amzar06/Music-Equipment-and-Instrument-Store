<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

// This feeds into admin_header.php to become the ONLY main title
$page_title = "Category Management";
$active = "categories";

$message = "";
$message_type = "";

// ==========================================
// 1. HANDLE DELETE ACTION
// ==========================================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Safety check: Don't delete if products are using it
    $check_query = mysqli_query($conn, "SELECT * FROM products WHERE category_id = $delete_id");
    if (mysqli_num_rows($check_query) > 0) {
        $message = "Cannot delete: There are products currently assigned to this category.";
        $message_type = "error";
    } else {
        $delete_query = "DELETE FROM categories WHERE category_id = $delete_id";
        if (mysqli_query($conn, $delete_query)) {
            $message = "Category deleted successfully.";
            $message_type = "success";
        } else {
            $message = "Error deleting category: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

// ==========================================
// 2. HANDLE ADD CATEGORY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    
    if (!empty($category_name)) {
        $insert_query = "INSERT INTO categories (category_name) VALUES ('$category_name')";
        if (mysqli_query($conn, $insert_query)) {
            $message = "New category added successfully!";
            $message_type = "success";
        } else {
            $message = "Database error: " . mysqli_error($conn);
            $message_type = "error";
        }
    } else {
        $message = "Category name cannot be empty.";
        $message_type = "error";
    }
}

// ==========================================
// 3. FETCH CATEGORIES
// ==========================================
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY category_name ASC");

require_once('admin_header.php');
?>

<?php if (!empty($message)): ?>
    <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 32px; align-items: start;">
    
    <div class="table-container" style="margin-top: 0;">
        <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-main);">Add New Category</h3>
        
        <form action="admin_categories.php" method="POST">
            <div class="form-group" style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Category Name</label>
                <input type="text" name="category_name" placeholder="e.g., Guitars, Pianos..." required style="width: 100%; padding: 10px; border: 1px solid var(--border-color); border-radius: 8px; outline: none; background: white; color: var(--text-main);">
            </div>

            <button type="submit" name="add_category" style="width: 100%; padding: 12px; background: var(--accent-color); color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;">
                Save Category
            </button>
        </form>
    </div>

    <div class="table-container" style="margin-top: 0;">
        <h3 style="margin-bottom: 20px; font-weight: 700; color: var(--text-main);">Current Categories</h3>
        
        <div style="overflow-x: auto;">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($categories_result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($categories_result)): ?>
                        <tr>
                            <td style="color: #6b7280; font-weight: 500;"><?php echo $row['category_id']; ?></td>
                            <td style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td style="text-align: right;">
                                <a href="admin_categories.php?delete_id=<?php echo $row['category_id']; ?>" 
                                   style="color: #ef4444; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #fee2e2; transition: 0.2s;"
                                   onclick="return confirm('Delete this category? Ensure no products are using it first.')">
                                   Delete
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #9ca3af;">No categories found. Add your first one on the left.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once('admin_footer.php'); ?>