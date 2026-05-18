<?php
session_start();

if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

if ($_SESSION['staff_role'] != 'superadmin') {
    header("Location: admin_dashboard.php");
    exit();
}

require_once '../database.php';
$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_admin'])) {
    $staff_name = $_POST['staff_name'];
    $staff_email = $_POST['staff_email'];
    $staff_password = $_POST['staff_password'];
    $staff_phone_number = $_POST['staff_phone_number'];
    $staff_role = $_POST['staff_role'];

    if ($staff_name == "" || $staff_email == "" || $staff_password == "") {
        $error = "Staff name, email and password are required.";
    } else {
        $check_sql = "SELECT * FROM staff WHERE staff_email=?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "s", $staff_email);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "This email has already been registered.";
        } else {
            $sql = "INSERT INTO staff (staff_name, staff_email, staff_password, staff_phone_number, staff_role) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($insert_stmt, "sssss", $staff_name, $staff_email, $staff_password, $staff_phone_number, $staff_role);
            
            if (mysqli_stmt_execute($insert_stmt)) {
                $success = "Admin added successfully.";
            } else {
                $error = "Please try again.";
            } 
        }
    } 
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_admin'])) {
    $staff_id = $_POST['staff_id'];

    if ($staff_id == $_SESSION['staff_id']) {
        $error = "Own account cannot be deleted.";
    } else {
        $sql = "DELETE FROM staff WHERE staff_id=?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $staff_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success = "Admin deleted successfully.";
        } else {
            $error = "Please try again.";
        }
    }
}

$sql = "SELECT * FROM staff ORDER BY staff_id ASC";
$result = mysqli_query($conn, $sql); // Fixed: Actually execute the query
require_once('admin_header.php');
?>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-head">
        <span>Add new Admin</span>
    </div>

    <div style="padding:20px;">
        <form method="POST" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="staff_name">Full Name</label>
                    <input type="text" id="staff_name" name="staff_name" placeholder="Name...">
                </div>

                <div class="form-group">
                    <label for="staff_email">Email Address</label>
                    <input type="email" id="staff_email" name="staff_email" placeholder="email@gmail.com">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="staff_password">Password</label>
                    <input type="password" id="staff_password" name="staff_password">
                </div>

                <div class="form-group">
                    <label for="staff_phone_number">Phone Number</label>
                    <input type="text" id="staff_phone_number" name="staff_phone_number" placeholder="0123456789">
                </div>
            </div>

            <div class="form-group" style="max-width: 300px;">
                <label for="staff_role">Role</label>
                <select id="staff_role" name="staff_role">
                    <option value="admin">Admin</option>
                    <option value="superadmin">Super Admin</option>
                </select>
            </div>

            <button type="submit" name="add_admin" class="btn btn-green">Add Admin</button>
        </form>
    </div>  
</div>  

<div class="card">
    <div class="card-head">
        <span>Accounts</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Created At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td><?php echo $row['staff_id']; ?></td>
                    <td><?php echo $row['staff_name']; ?></td>
                    <td><?php echo $row['staff_email']; ?></td>
                    <td><?php echo $row['staff_phone_number'] != "" ? $row["staff_phone_number"]: '-'; ?></td>
                    <td><span class="badge"><?php echo ucfirst($row['staff_role']); ?></span></td>
                    <td><?php echo date('d M Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <?php if ($row['staff_id'] != $_SESSION['staff_id']): ?>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="staff_id" value="<?php echo $row['staff_id']; ?>">
                                <button type="submit" name="delete_admin" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this account?')">
                                    Delete
                                </button>
                            </form>
                        <?php else: ?>
                            <span style="font-size:12px; color:#888;">Current user</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>  
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align:center; padding:24px; color:#888;">No accounts found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once('admin_footer.php'); ?>