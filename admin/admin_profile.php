<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$page_title = "My Profile";
$active = "profile"; 
$hide_search = true; 

$current_user_id = intval($_SESSION['staff_id']);
$message = ""; 
$message_type = "";

// Fetch current user's data
$query = "SELECT * FROM staff WHERE staff_id = $current_user_id";
$result = mysqli_query($conn, $query);
$staff = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $staff_name    = mysqli_real_escape_string($conn, trim($_POST['staff_name']));
    $staff_phone   = mysqli_real_escape_string($conn, trim($_POST['staff_phone_number']));
    $staff_address = mysqli_real_escape_string($conn, trim($_POST['staff_address']));
    
    $security_question = mysqli_real_escape_string($conn, $_POST['security_question']);
    $security_answer   = mysqli_real_escape_string($conn, strtolower(trim($_POST['security_answer'])));

    // Base update query (Notice Email and Role cannot be changed by the user)
    $update_query = "UPDATE staff SET 
                     staff_name = '$staff_name', 
                     staff_phone_number = '$staff_phone', 
                     staff_address = '$staff_address',
                     security_question = '$security_question',
                     security_answer = '$security_answer'";

    // If they typed a new password to replace Meais@67, hash it and add it to the query
    if (!empty($_POST['new_password'])) {
        $new_pass = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $update_query .= ", staff_password = '$new_pass'";
    }

    $update_query .= " WHERE staff_id = $current_user_id";

    if (mysqli_query($conn, $update_query)) {
        // Update session name just in case they changed it
        $_SESSION['staff_name'] = $staff_name;
        $message = "Your profile and security settings have been securely updated.";
        $message_type = "success";
        
        // Refresh data so the form shows the new updates immediately
        $result = mysqli_query($conn, "SELECT * FROM staff WHERE staff_id = $current_user_id");
        $staff = mysqli_fetch_assoc($result);
    } else {
        $message = "Database error: " . mysqli_error($conn);
        $message_type = "error";
    }
}

require_once('admin_header.php');
?>

<div style="max-width: 700px; margin: 0 auto; margin-top: 20px;">
    
    <?php if (!empty($message)): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        <h3 style="margin-top: 0; margin-bottom: 8px; font-weight: 700; color: #111827; font-size: 1.5rem;">Personal Profile</h3>
        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 24px;">Manage your personal information and private security settings.</p>
        
        <form action="admin_profile.php" method="POST">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; padding: 12px; background: #f3f4f6; border-radius: 8px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #6b7280;">Email Address (Locked)</label>
                    <input type="text" disabled value="<?php echo htmlspecialchars($staff['staff_email']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #e5e7eb; color: #6b7280; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #6b7280;">System Role (Locked)</label>
                    <input type="text" disabled value="<?php echo htmlspecialchars($staff['staff_role']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: #e5e7eb; color: #6b7280; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Full Name *</label>
                <input type="text" name="staff_name" required value="<?php echo htmlspecialchars($staff['staff_name']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Phone Number</label>
                <input type="text" name="staff_phone_number" value="<?php echo htmlspecialchars($staff['staff_phone_number']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Home Address</label>
                <textarea name="staff_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-family: inherit; outline: none; box-sizing: border-box; resize: vertical;"><?php echo htmlspecialchars($staff['staff_address']); ?></textarea>
            </div>

            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 24px 0;">
            <h4 style="margin-top: 0; margin-bottom: 16px; font-weight: 600; color: #111827;">Private Security Settings</h4>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; padding: 16px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #92400e;">Your Security Question *</label>
                    <select name="security_question" required style="width: 100%; padding: 10px; border: 1px solid #fcd34d; border-radius: 8px; background: white; outline: none; box-sizing: border-box;">
                        <option value="What was the name of your first pet?" <?php echo ($staff['security_question'] == 'What was the name of your first pet?') ? 'selected' : ''; ?>>What was the name of your first pet?</option>
                        <option value="What is your mother's maiden name?" <?php echo ($staff['security_question'] == "What is your mother's maiden name?") ? 'selected' : ''; ?>>What is your mother's maiden name?</option>
                        <option value="What city were you born in?" <?php echo ($staff['security_question'] == 'What city were you born in?') ? 'selected' : ''; ?>>What city were you born in?</option>
                        <option value="What was the name of your first school?" <?php echo ($staff['security_question'] == 'What was the name of your first school?') ? 'selected' : ''; ?>>What was the name of your first school?</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #92400e;">Your Secret Answer *</label>
                    <input type="text" name="security_answer" required value="<?php echo htmlspecialchars($staff['security_answer']); ?>" style="width: 100%; padding: 10px; border: 1px solid #fcd34d; border-radius: 8px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <div style="padding: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Change Password</label>
                <input type="password" name="new_password" placeholder="Leave blank to keep current password..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                <p style="margin: 6px 0 0 0; font-size: 0.75rem; color: #6b7280;">Superadmins cannot see or reset this password. If lost, you must use the recovery page.</p>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" name="update_profile" style="padding: 12px 24px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                    Save Secure Profile
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>