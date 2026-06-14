<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "Manage Categories";
$active = "categories";

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
// 1. ADD NEW CATEGORY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $category_name = mysqli_real_escape_string($conn, trim($_POST['category_name']));
    
    if (!empty($category_name)) {
        // Check if category already exists
        $check = mysqli_query($conn, "SELECT * FROM categories WHERE category_name = '$category_name'");
        if (mysqli_num_rows($check) > 0) {
            $_SESSION['flash_message'] = "Category '$category_name' already exists.";
            $_SESSION['flash_type'] = "error";
        } else {
            if (mysqli_query($conn, "INSERT INTO categories (category_name) VALUES ('$category_name')")) {
                $_SESSION['flash_message'] = "New category added successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Database error: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "error";
            }
        }
    }
    header("Location: admin_categories.php");
    exit();
}

// ==========================================
// 2. DELETE CATEGORY
// ==========================================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Check if products are still using this category before deleting
    $check_products = mysqli_query($conn, "SELECT * FROM products WHERE category_id = $delete_id");
    
    if (mysqli_num_rows($check_products) > 0) {
        $_SESSION['flash_message'] = "Cannot delete this category because there are products assigned to it.";
        $_SESSION['flash_type'] = "error";
    } else {
        if (mysqli_query($conn, "DELETE FROM categories WHERE category_id = $delete_id")) {
            $_SESSION['flash_message'] = "Category deleted successfully.";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Database error: " . mysqli_error($conn);
            $_SESSION['flash_type'] = "error";
        }
    }
    header("Location: admin_categories.php");
    exit();
}

// ==========================================
// 3. FETCH CATEGORIES
// ==========================================
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$where_clause = "";

if (!empty($search)) {
    $where_clause = "WHERE category_name LIKE '%$search%'";
}

$categories_query = "SELECT * FROM categories $where_clause ORDER BY category_name ASC";
$categories_result = mysqli_query($conn, $categories_query);

require_once('admin_header.php');
?>

<div style="max-width: 1200px; margin: 0 auto; margin-top: 20px;">
    
    <?php if (!empty($message)): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 350px 1fr; gap: 32px; align-items: start;">
        
        <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">Add New Category</h3>
            
            <form action="admin_categories.php" method="POST">
                <div style="margin-bottom: 24px;">
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Category Name *</label>
                    <input type="text" name="category_name" required placeholder="e.g. Acoustic Guitars" 
                           style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                </div>

                <button type="submit" name="add_category" 
                        style="width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" 
                        onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                    Create Category
                </button>
            </form>
        </div>

        <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">Active Categories</h3>
            
            <?php if(!empty($search)): ?>
                <div style="margin-bottom: 16px; font-size: 0.85rem; color: #4b5563;">
                    Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong>
                    <a href="admin_categories.php" style="color: #ef4444; text-decoration: none; font-weight: 600; margin-left: 10px;">Clear</a>
                </div>
            <?php endif; ?>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid #e5e7eb;">
                            <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">ID</th>
                            <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Category Name</th>
                            <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($categories_result) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($categories_result)): ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td style="padding: 16px 8px; color: #6b7280; font-size: 0.9rem;">#<?php echo $row['category_id']; ?></td>
                                <td style="padding: 16px 8px; font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td style="padding: 16px 8px; text-align: right;">
                                    <a href="admin_categories.php?delete_id=<?php echo $row['category_id']; ?>" 
                                       style="color: #ef4444; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #fee2e2; transition: 0.2s; display: inline-block;"
                                       onclick="return confirm('Are you sure you want to delete <?php echo addslashes($row['category_name']); ?>? You cannot delete categories that contain products.')">
                                       Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 40px; color: #9ca3af; font-size: 0.95rem;">
                                    No categories found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php require_once('admin_footer.php'); ?>