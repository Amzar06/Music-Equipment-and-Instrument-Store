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
    
    $update_query = "UPDATE staff SET staff_name = '$staff_name'";

    if (!empty($_POST['security_question']) && !empty($_POST['security_answer'])) {
        $sq = mysqli_real_escape_string($conn, $_POST['security_question']);
        $sa = password_hash(strtolower(trim($_POST['security_answer'])), PASSWORD_DEFAULT);
        $update_query .= ", security_question = '$sq', security_answer = '$sa'";
    }

    if (!empty($_POST['new_password'])) {
        $new_password     = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $message      = "Passwords do not match.";
            $message_type = "error";
        } elseif (strlen($new_password) < 8 ||
                  !preg_match('/[A-Z]/', $new_password) ||
                  !preg_match('/[0-9]/', $new_password) ||
                  !preg_match('/[\W_]/', $new_password)) {
            $message      = "Password must be at least 8 characters and include an uppercase letter, a number and a symbol.";
            $message_type = "error";
        } else {
            $new_pass      = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query .= ", staff_password = '$new_pass'";
        }
    }

    if ($message_type !== "error") {
        $update_query .= " WHERE staff_id = $current_user_id";
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['staff_name'] = $staff_name;
            $message      = "Profile successfully updated.";
            $message_type = "success";
            $staff = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM staff WHERE staff_id = $current_user_id"));
        } else {
            $message      = "Error: " . mysqli_error($conn);
            $message_type = "error";
        }
    }
}

require_once('admin_header.php');
?>

<div style="max-width: 700px; margin: 0 auto; margin-top: 20px;">

    <?php if (!empty($message)): ?>
        <div style="background: <?php echo ($message_type == 'success') ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo ($message_type == 'success') ? '#065f46' : '#991b1b'; ?>; padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid <?php echo ($message_type == 'success') ? '#a7f3d0' : '#fca5a5'; ?>;">
            <strong><?php echo $message; ?></strong>
        </div>
    <?php endif; ?>

    <?php if (isset($staff['staff_password']) && password_verify("Meais@67", $staff['staff_password'])): ?>
        <div style="background: #fee2e2; color: #991b1b; padding: 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #fca5a5;">
            <strong>⚠️ Alert:</strong> You are using the default temporary password. <strong>Update it below now.</strong>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin-top: 0; font-size: 1.5rem; color: #111827;">My Profile</h3>
        <p style="color: #6b7280; font-size: 0.9rem; margin-bottom: 24px;">Manage your info and account recovery settings.</p>

        <form action="admin_profile.php" method="POST">

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #111827;">Full Name</label>
                <input type="text" name="staff_name" required value="<?php echo htmlspecialchars($staff['staff_name'] ?? ''); ?>" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 24px 0;">
            <h4 style="margin: 0 0 16px 0; color: #111827;">Security</h4>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #111827;">Security Question:</label>
                    <select name="security_question" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                        <option value="">-- Select Question --</option>
                        <option value="What was the name of your first pet?" <?php echo (isset($staff['security_question']) && $staff['security_question'] == 'What was the name of your first pet?') ? 'selected' : ''; ?>>What was the name of your first pet?</option>
                        <option value="What city were you born in?" <?php echo (isset($staff['security_question']) && $staff['security_question'] == 'What city were you born in?') ? 'selected' : ''; ?>>What city were you born in?</option>
                    </select>
                </div>
                <div>
                    <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #111827;">Answer:</label>
                    <input type="text" name="security_answer" placeholder="Enter new answer..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                </div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #111827;">New Password:</label>
                <input type="password" name="new_password" id="new_password" placeholder="Leave blank to keep current..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                <div style="margin-top: 8px; padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.78rem; color: #64748b; line-height: 1.8;">
                    Password must contain:
                    <ul style="margin: 4px 0 0 0; padding-left: 18px;">
                        <li>At least <strong>8 characters</strong></li>
                        <li>At least one <strong>uppercase letter</strong> (A-Z)</li>
                        <li>At least one <strong>number</strong> (0-9)</li>
                        <li>At least one <strong>symbol</strong> (e.g. @, #, !, $)</li>
                    </ul>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #111827;">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                <div id="password_match_msg" style="font-size: 0.8rem; margin-top: 6px;"></div>
            </div>

            <button type="submit" name="update_profile" style="width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">Save Changes</button>
        </form>
    </div>
</div>

<script>
document.getElementById('confirm_password').addEventListener('input', function() {
    var pass    = document.getElementById('new_password').value;
    var confirm = this.value;
    var msg     = document.getElementById('password_match_msg');

    if (confirm === '') {
        msg.innerHTML = '';
    } else if (pass === confirm) {
        msg.style.color   = '#065f46';
        msg.innerHTML     = '✓ Passwords match';
    } else {
        msg.style.color   = '#991b1b';
        msg.innerHTML     = '✗ Passwords do not match';
    }
});
</script>

<?php require_once('admin_footer.php'); ?>