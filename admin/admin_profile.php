<?php
session_start();
if (!isset($_SESSION['staff_id'])) { 
    header("Location: admin_login.php"); 
    exit(); 
}
require_once('../database.php');

$page_title = "Profile";
$active = "profile"; 
$hide_search = true; 

$current_user_id = intval($_SESSION['staff_id']);
$message = ""; 
$message_type = "";

$staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staff WHERE staff_id = $current_user_id"));

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $staff_name = mysqli_real_escape_string($conn, trim($_POST['staff_name']));
    
    // Fixed: Removed staff_phone_number and staff_address to prevent undefined array key warnings
    $update_query = "UPDATE staff SET staff_name = '$staff_name'";

    // Update Security if provided
    if (!empty($_POST['security_question']) && !empty($_POST['security_answer'])) {
        $sq = mysqli_real_escape_string($conn, $_POST['security_question']);
        $sa = password_hash(strtolower(trim($_POST['security_answer'])), PASSWORD_DEFAULT);
        $update_query .= ", security_question = '$sq', security_answer = '$sa'";
    }

    if (!empty($_POST['new_password'])) {
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $update_query .= ", staff_password = '$new_pass'";
    }

    $update_query .= " WHERE staff_id = $current_user_id";

    if (mysqli_query($conn, $update_query)) {
        $_SESSION['staff_name'] = $staff_name;
        $message = "Profile successfully updated.";
        $message_type = "success";
        $staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staff WHERE staff_id = $current_user_id"));
    } else {
        $message = "Error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

require_once('admin_header.php');
?>

<div style="display: flex; justify-content: flex-end; padding: 10px 20px;">
    <div class="admin-profile" style="display: flex; align-items: center; gap: 15px;">
        <span class="status-pill completed" style="text-transform: uppercase; letter-spacing: 1px; padding: 6px 12px; font-size: 0.75rem;">
            <?php echo (isset($_SESSION['staff_role']) && $_SESSION['staff_role'] === 'Administrator') ? 'SUPERADMIN' : 'ADMIN'; ?>
        </span>
        
        <a href="admin_profile.php" 
           style="font-weight: 600; color: #111827; text-decoration: none; padding: 6px 14px; background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 20px; transition: all 0.2s;"
           onmouseover="this.style.backgroundColor='#e5e7eb'; this.style.borderColor='#d1d5db'; this.style.textDecoration='underline';" 
           onmouseout="this.style.backgroundColor='#f3f4f6'; this.style.borderColor='#e5e7eb'; this.style.textDecoration='none';">
           <?php echo htmlspecialchars($_SESSION['staff_name'] ?? 'Staff'); ?>
        </a>
    </div>
</div>

<div style="max-width: 700px; margin: 0 auto; margin-top: 20px;">
    
    <?php if (!empty($message)): ?>
        <div style="background: <?php echo ($message_type == 'success') ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo ($message_type == 'success') ? '#065f46' : '#991b1b'; ?>; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <strong><?php echo $message; ?></strong>
        </div>
    <?php endif; ?>

    <?php if (isset($staff['staff_password']) && password_verify("Meais@67", $staff['staff_password'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
            <strong>⚠️ Alert:</strong> You are using the default temporary password. <strong>Update it below now.</strong>
        </div>
    <?php endif; ?>
        
    <form action="admin_profile.php" method="POST">
        <div style="margin-bottom: 16px;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Full Name</label>
            <input type="text" name="staff_name" required value="<?php echo htmlspecialchars($staff['staff_name'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
        </div>

        <h4 style="margin: 24px 0 16px 0;">Security</h4>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Security Question:</label>
                <select name="security_question" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
                    <option value="">-- Select Question --</option>
                    <option value="What was the name of your first pet?" <?php echo (isset($staff['security_question']) && $staff['security_question'] == 'What was the name of your first pet?') ? 'selected' : ''; ?>>What was the name of your first pet?</option>
                    <option value="What city were you born in?" <?php echo (isset($staff['security_question']) && $staff['security_question'] == 'What city were you born in?') ? 'selected' : ''; ?>>What city were you born in?</option>
                </select>
            </div>
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Answer:</label>
                <input type="text" name="security_answer" placeholder="Enter new answer..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
            </div>
        </div>

        <div style="margin-bottom: 24px;">
            <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px;">Change Password:</label>
            <input type="password" name="new_password" placeholder="Leave blank to keep current..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px;">
        </div>

        <button type="submit" name="update_profile" style="width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Save Changes</button>
    </form>
</div>

<?php require_once('admin_footer.php'); ?>