<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }

if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Administrator') {
    $_SESSION['flash_message'] = "Access Denied.";
    $_SESSION['flash_type'] = "error";
    header("Location: admin_dashboard.php");
    exit();
}

require_once('../database.php');

$page_title = "Manage Staff";
$active = "staff"; 

$message = ""; $message_type = "";
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    if ($delete_id === intval($_SESSION['staff_id'])) {
        $_SESSION['flash_message'] = "You cannot deactivate your own account while logged in.";
        $_SESSION['flash_type'] = "error";
    } else {
        mysqli_query($conn, "UPDATE staff SET status = 'Inactive' WHERE staff_id = $delete_id");
        $_SESSION['flash_message'] = "Staff member deactivated.";
        $_SESSION['flash_type'] = "success";
    }
    header("Location: manage_admin.php");
    exit();
}

// Handle adding staff

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $staff_name    = mysqli_real_escape_string($conn, trim($_POST['staff_name']));
    $staff_email   = mysqli_real_escape_string($conn, trim($_POST['staff_email']));
    $staff_phone   = mysqli_real_escape_string($conn, trim($_POST['staff_phone_number']));
    $staff_address = mysqli_real_escape_string($conn, trim($_POST['staff_address']));
    $staff_role    = mysqli_real_escape_string($conn, $_POST['staff_role']);
    
    $staff_pass = password_hash("Meais@67", PASSWORD_DEFAULT);

    if (!empty($staff_name) && !empty($staff_email)) {
        $check_result = mysqli_query($conn, "SELECT * FROM staff WHERE staff_email = '$staff_email'");
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['flash_message'] = "Email already exists.";
            $_SESSION['flash_type'] = "error";
        } else {
            $insert_query = "INSERT INTO staff (staff_name, staff_email, staff_phone_number, staff_address, staff_password, staff_role, status) 
                             VALUES ('$staff_name', '$staff_email', '$staff_phone', '$staff_address', '$staff_pass', '$staff_role', 'Active')";
            if (mysqli_query($conn, $insert_query)) {
                $_SESSION['flash_message'] = "Account created! Default password is 'Meais@67'.";
                $_SESSION['flash_type'] = "success";
            }
        }
    }
    header("Location: manage_admin.php");
    exit();
}

// Fetch data

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

$where_clause = "WHERE status NOT IN('Inactive', 'Unavailable')";
if (!empty($search)) $where_clause .= " AND (staff_name LIKE '%$search%' OR staff_email LIKE '%$search%')";
$order_clause = ($sort == 'name_desc') ? "ORDER BY staff_name DESC" : (($sort == 'role_admin') ? "ORDER BY FIELD(staff_role, 'Administrator', 'Staff'), staff_name ASC" : "ORDER BY staff_name ASC");

$staff_result = mysqli_query($conn, "SELECT * FROM staff $where_clause $order_clause");

require_once('admin_header.php');
?>

<?php if (!empty($message)): ?>
    <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
        <?php echo $message; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 350px 1fr; gap: 32px; align-items: start;">
    <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">Add New Staff</h3>
        <form action="manage_admin.php" method="POST">
            <div style="margin-bottom: 16px;"><label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Full Name:</label><input type="text" name="staff_name" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"></div>
            <div style="margin-bottom: 16px;"><label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Email Address:</label><input type="email" name="staff_email" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"></div>
            <div style="margin-bottom: 16px;"><label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Phone Number:</label><input type="text" name="staff_phone_number" placeholder="+60..." style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"></div>
            <div style="margin-bottom: 16px;"><label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Home Address:</label><textarea name="staff_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box; resize: vertical;"></textarea></div>
            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Role:</label>
                <select name="staff_role" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
                    <option value="Staff">Admin</option>
                    <option value="Administrator">Superadmin</option>
                </select>
            </div>
            <div style="padding: 12px; background: #fff7ed; border-left: 4px solid #f97316; margin-bottom: 20px; font-size: 0.8rem; color: #9a3412;">
                <strong>Note:</strong> Default password is <strong>Meais@67</strong>. Instruct staff to log in and set their own password and security question.
            </div>
            <button type="submit" name="add_staff" style="width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">Create Account</button>
        </form>
    </div>

    <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">Authorized Personnel</h3>
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="border-bottom: 2px solid #e5e7eb;">
                <tr>
                    <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">ID</th>
                    <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Staff Details</th>
                    <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Role</th>
                    <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($staff_result)): ?>
                <tr style="border-bottom: 1px solid #f3f4f6;">
                    <td style="padding: 16px 8px; color: #6b7280; font-size: 0.9rem;">#<?php echo $row['staff_id']; ?></td>
                    <td style="padding: 16px 8px;">
                        <div style="font-weight: 600; color: #111827;"><?php echo htmlspecialchars($row['staff_name']); ?></div>
                        <div style="font-size: 0.85rem; color: #4b5563;"><?php echo htmlspecialchars($row['staff_email']); ?></div>
                    </td>
                    <td style="padding: 16px 8px;">
                        <span style="background: #f3f4f6; padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;"><?php echo htmlspecialchars($row['staff_role']); ?></span>
                    </td>
                    <td style="padding: 16px 8px; text-align: right;">
                        <a href="admin_edit_staff.php?id=<?php echo $row['staff_id']; ?>" style="color: #4f46e5; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #e0e7ff;">Edit</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once('admin_footer.php'); ?>