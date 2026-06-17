<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

// ==========================================
// STRICT SUPERADMIN SECURITY LOCK
// ==========================================
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Administrator') {
    $_SESSION['flash_message'] = "Access Denied: Only Administrators can edit staff accounts.";
    $_SESSION['flash_type'] = "error";
    header("Location: admin_dashboard.php");
    exit();
}

require_once('../database.php');

$page_title = "Edit Staff Profile";
$active = "staff";
$hide_search = true; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "No staff member selected.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_admin.php");
    exit();
}

$edit_id = intval($_GET['id']);
$staff_query = "SELECT * FROM staff WHERE staff_id = $edit_id";
$staff_result = mysqli_query($conn, $staff_query);

if (mysqli_num_rows($staff_result) == 0) {
    $_SESSION['flash_message'] = "Staff account not found.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_admin.php");
    exit();
}

$staff = mysqli_fetch_assoc($staff_result);
$message = ""; $message_type = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_staff'])) {
    $staff_name    = mysqli_real_escape_string($conn, trim($_POST['staff_name']));
    $staff_email   = mysqli_real_escape_string($conn, trim($_POST['staff_email']));
    $staff_phone   = mysqli_real_escape_string($conn, trim($_POST['staff_phone_number']));
    $staff_address = mysqli_real_escape_string($conn, trim($_POST['staff_address']));
    $staff_role    = mysqli_real_escape_string($conn, $_POST['staff_role']);
    $status        = mysqli_real_escape_string($conn, $_POST['status']);

    if (!empty($staff_name) && !empty($staff_email)) {
        
        // Excludes password, security_question, and security_answer.
        // Those belong to the user's private admin_profile.php now.
        $update_query = "UPDATE staff SET 
                         staff_name = '$staff_name', 
                         staff_email = '$staff_email', 
                         staff_phone_number = '$staff_phone', 
                         staff_address = '$staff_address', 
                         staff_role = '$staff_role', 
                         status = '$status'
                         WHERE staff_id = $edit_id";
        
        $email_check = mysqli_query($conn, "SELECT * FROM staff WHERE staff_email = '$staff_email' AND staff_id != $edit_id");
        
        if (mysqli_num_rows($email_check) > 0) {
            $message = "That email address is already in use by another staff member.";
            $message_type = "error";
        } else {
            if (mysqli_query($conn, $update_query)) {
                $_SESSION['flash_message'] = "Staff account updated successfully.";
                $_SESSION['flash_type'] = "success";
                header("Location: manage_admin.php");
                exit();
            } else {
                $message = "Database error: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    } else {
        $message = "Name and Email are required fields.";
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
        <h3 style="margin-top: 0; margin-bottom: 24px; font-weight: 700; color: #111827; font-size: 1.5rem;">Edit Staff: <?php echo htmlspecialchars($staff['staff_name']); ?></h3>
        
        <form action="admin_edit_staff.php?id=<?php echo $edit_id; ?>" method="POST">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Full Name *</label>
                    <input type="text" name="staff_name" required value="<?php echo htmlspecialchars($staff['staff_name']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Email Address *</label>
                    <input type="email" name="staff_email" required value="<?php echo htmlspecialchars($staff['staff_email']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Phone Number</label>
                <input type="text" name="staff_phone_number" value="<?php echo htmlspecialchars($staff['staff_phone_number']); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Home Address</label>
                <textarea name="staff_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-family: inherit; outline: none; box-sizing: border-box; resize: vertical;"><?php echo htmlspecialchars($staff['staff_address']); ?></textarea>
            </div>

            <div style="padding: 12px 16px; background: #f3f4f6; border-left: 4px solid #6b7280; border-radius: 4px; margin-bottom: 24px;">
                <p style="margin: 0; font-size: 0.85rem; color: #4b5563; font-weight: 500;">
                    🔒 <strong>Security Notice:</strong> To maintain strict data privacy, Superadmins cannot view or change staff passwords or security questions. Staff must utilize the Account Recovery portal if they lose access.
                </p>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">System Role</label>
                    <select name="staff_role" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white; outline: none; box-sizing: border-box;">
                        <option value="Staff" <?php echo ($staff['staff_role'] == 'Staff') ? 'selected' : ''; ?>>Staff</option>
                        <option value="Administrator" <?php echo ($staff['staff_role'] == 'Administrator') ? 'selected' : ''; ?>>Administrator (Superadmin)</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Account Status</label>
                    <select name="status" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white; outline: none; box-sizing: border-box;">
                        <option value="Active" <?php echo ($staff['status'] == 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Suspended" <?php echo ($staff['status'] == 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                        <option value="Inactive" <?php echo ($staff['status'] == 'Inactive') ? 'selected' : ''; ?>>Inactive (Revoked)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="manage_admin.php" style="padding: 12px 24px; background: white; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 600; text-decoration: none; transition: 0.2s;" onmouseover="this.style.backgroundColor='#f3f4f6'" onmouseout="this.style.backgroundColor='white'">
                    Cancel
                </a>
                <button type="submit" name="update_staff" style="padding: 12px 24px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>