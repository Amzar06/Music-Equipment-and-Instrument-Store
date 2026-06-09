<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

// ==========================================
// SUPERADMIN SECURITY LOCK
// ==========================================
if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Administrator') {
    $_SESSION['flash_message'] = "Access Denied: Only Administrators can manage staff accounts.";
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

// 1. HANDLE SOFT DELETE
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    if ($delete_id === intval($_SESSION['staff_id'])) {
        $_SESSION['flash_message'] = "You cannot deactivate your own account while logged in.";
        $_SESSION['flash_type'] = "error";
    } else {
        $delete_query = "UPDATE staff SET status = 'Inactive' WHERE staff_id = $delete_id";
        if (mysqli_query($conn, $delete_query)) {
            $_SESSION['flash_message'] = "Staff member deactivated successfully. (History preserved)";
            $_SESSION['flash_type'] = "success";
        } else {
            $_SESSION['flash_message'] = "Error deactivating staff: " . mysqli_error($conn);
            $_SESSION['flash_type'] = "error";
        }
    }
    header("Location: manage_admin.php");
    exit();
}

// 2. HANDLE ADD STAFF
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $staff_name    = mysqli_real_escape_string($conn, trim($_POST['staff_name']));
    $staff_email   = mysqli_real_escape_string($conn, trim($_POST['staff_email']));
    $staff_phone   = mysqli_real_escape_string($conn, trim($_POST['staff_phone_number']));
    $staff_address = mysqli_real_escape_string($conn, trim($_POST['staff_address']));
    $staff_role    = mysqli_real_escape_string($conn, $_POST['staff_role']);
    
    $staff_pass  = password_hash($_POST['staff_password'], PASSWORD_DEFAULT);

    if (!empty($staff_name) && !empty($staff_email) && !empty($_POST['staff_password'])) {
        $check_query = "SELECT * FROM staff WHERE staff_email = '$staff_email'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $_SESSION['flash_message'] = "A staff member with this email already exists.";
            $_SESSION['flash_type'] = "error";
        } else {
            $insert_query = "INSERT INTO staff (staff_name, staff_email, staff_phone_number, staff_address, staff_password, staff_role, status) 
                             VALUES ('$staff_name', '$staff_email', '$staff_phone', '$staff_address', '$staff_pass', '$staff_role', 'Active')";
            
            if (mysqli_query($conn, $insert_query)) {
                $_SESSION['flash_message'] = "New staff member added successfully!";
                $_SESSION['flash_type'] = "success";
            } else {
                $_SESSION['flash_message'] = "Database error: " . mysqli_error($conn);
                $_SESSION['flash_type'] = "error";
            }
        }
    } else {
        $_SESSION['flash_message'] = "Please fill in all required fields.";
        $_SESSION['flash_type'] = "error";
    }
    header("Location: manage_admin.php");
    exit();
}

// 3. FETCH DATA WITH SEARCH & SORT
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

$where_clause = "WHERE status != 'Inactive'";

if (!empty($search)) {
    $where_clause .= " AND (staff_name LIKE '%$search%' OR staff_email LIKE '%$search%' OR staff_phone_number LIKE '%$search%')";
}

if ($sort == 'name_desc') {
    $order_clause = "ORDER BY staff_name DESC";
} elseif ($sort == 'role_admin') {
    $order_clause = "ORDER BY FIELD(staff_role, 'Administrator', 'Staff'), staff_name ASC";
} else {
    $order_clause = "ORDER BY staff_name ASC"; 
}

$staff_query = "SELECT * FROM staff $where_clause $order_clause";
$staff_result = mysqli_query($conn, $staff_query);

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
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Full Name *</label>
                <input type="text" name="staff_name" required placeholder="e.g. Ali bin Abu" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Email Address *</label>
                <input type="email" name="staff_email" required placeholder="staff@musicstore.com" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Phone Number</label>
                <input type="text" name="staff_phone_number" placeholder="e.g. 012-3456789" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Home Address</label>
                <textarea name="staff_address" rows="3" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; font-family: inherit; outline: none; box-sizing: border-box; resize: vertical;"></textarea>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Temporary Password *</label>
                <input type="password" name="staff_password" required placeholder="••••••••" style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 6px; color: #4b5563;">Account Role *</label>
                <select name="staff_role" required style="width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 8px; background: white; outline: none; box-sizing: border-box;">
                    <option value="Staff">Staff</option>
                    <option value="Administrator">Administrator (Superadmin)</option>
                </select>
            </div>

            <button type="submit" name="add_staff" style="width: 100%; padding: 12px; background: #4f46e5; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s;" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
                Create Account
            </button>
        </form>
    </div>

    <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827; font-size: 1.25rem;">Authorized Personnel</h3>
        
        <div style="background: #f9fafb; border: 1px solid #e5e7eb; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: flex-end;">
            <form action="manage_admin.php" method="GET" style="display: flex; gap: 12px; align-items: center;">
                <?php if(!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <span style="font-size: 0.85rem; color: #4b5563;">Showing results for: <strong>"<?php echo htmlspecialchars($search); ?>"</strong></span>
                <?php endif; ?>
                
                <select name="sort" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white; outline: none; font-size: 0.9rem;" onchange="this.form.submit()">
                    <option value="name_asc" <?php echo ($sort == 'name_asc') ? 'selected' : ''; ?>>Sort: Name (A-Z)</option>
                    <option value="name_desc" <?php echo ($sort == 'name_desc') ? 'selected' : ''; ?>>Sort: Name (Z-A)</option>
                    <option value="role_admin" <?php echo ($sort == 'role_admin') ? 'selected' : ''; ?>>Sort: By Role</option>
                </select>
                
                <?php if(!empty($search) || $sort !== 'name_asc'): ?>
                    <a href="manage_admin.php" style="color: #ef4444; text-decoration: none; font-size: 0.85rem; font-weight: 600;">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">ID</th>
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Staff Details</th>
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Role & Status</th>
                        <th style="padding: 12px 8px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($staff_result) > 0): ?>
                        <?php while($row = mysqli_fetch_assoc($staff_result)): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 16px 8px; color: #6b7280; font-size: 0.9rem;">#<?php echo $row['staff_id']; ?></td>
                            <td style="padding: 16px 8px;">
                                <div style="font-weight: 600; color: #111827; font-size: 1rem;"><?php echo htmlspecialchars($row['staff_name']); ?></div>
                                <div style="font-size: 0.85rem; color: #4b5563; margin-top: 4px;">
                                    <strong>Email:</strong> <?php echo htmlspecialchars($row['staff_email']); ?><br>
                                    <strong>Phone:</strong> <?php echo !empty($row['staff_phone_number']) ? htmlspecialchars($row['staff_phone_number']) : '-'; ?><br>
                                    <strong>Address:</strong> <?php echo !empty($row['staff_address']) ? htmlspecialchars($row['staff_address']) : '-'; ?>
                                </div>
                            </td>
                            <td style="padding: 16px 8px;">
                                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-start;">
                                    <span style="background: <?php echo ($row['staff_role'] == 'Administrator') ? '#ede9fe' : '#f3f4f6'; ?>; 
                                                 color: <?php echo ($row['staff_role'] == 'Administrator') ? '#5b21b6' : '#4b5563'; ?>; 
                                                 padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                        <?php echo htmlspecialchars($row['staff_role']); ?>
                                    </span>
                                    
                                    <span style="background: <?php echo ($row['status'] == 'Active') ? '#d1fae5' : (($row['status'] == 'Suspended') ? '#fef3c7' : '#fee2e2'); ?>; 
                                                 color: <?php echo ($row['status'] == 'Active') ? '#065f46' : (($row['status'] == 'Suspended') ? '#92400e' : '#991b1b'); ?>; 
                                                 padding: 4px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                        <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </div>
                            </td>
                            <td style="padding: 16px 8px; text-align: right;">
                                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                    <a href="admin_edit_staff.php?id=<?php echo $row['staff_id']; ?>" style="color: #4f46e5; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #e0e7ff; transition: 0.2s;">Edit</a>
                                    <?php if ($row['staff_id'] != $_SESSION['staff_id']): ?>
                                        <a href="manage_admin.php?delete_id=<?php echo $row['staff_id']; ?>" style="color: #ef4444; text-decoration: none; font-size: 0.85rem; font-weight: 600; padding: 6px 12px; border-radius: 6px; background: #fee2e2; transition: 0.2s; display: inline-block;" onclick="return confirm('Are you sure you want to completely revoke access for <?php echo addslashes($row['staff_name']); ?>?')">Revoke</a>
                                    <?php else: ?>
                                        <span style="font-size: 0.85rem; color: #9ca3af; font-weight: 500; padding: 6px 0;">Current</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align: center; padding: 40px; color: #9ca3af; font-size: 0.95rem;">No staff members found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>