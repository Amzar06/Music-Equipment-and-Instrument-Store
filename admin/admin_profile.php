<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}
require_once('../database.php');

$page_title = "My Profile";
$active = ""; // Leaves sidebar unhighlighted since this is a personal page
$hide_search = true; 

$current_user_id = intval($_SESSION['staff_id']);
$profile_query = mysqli_query($conn, "SELECT * FROM staff WHERE staff_id = $current_user_id");
$profile = mysqli_fetch_assoc($profile_query);

require_once('admin_header.php');
?>

<div style="max-width: 700px; margin: 0 auto; margin-top: 20px;">
    
    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #f3f4f6; padding-bottom: 20px; margin-bottom: 24px;">
            <div>
                <h2 style="margin: 0 0 8px 0; color: #111827; font-size: 1.8rem;"><?php echo htmlspecialchars($profile['staff_name']); ?></h2>
                <span style="background: #ede9fe; color: #5b21b6; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                    <?php echo htmlspecialchars($profile['staff_role']); ?>
                </span>
            </div>
            <div style="text-align: right;">
                <span style="background: <?php echo ($profile['status'] == 'Active') ? '#d1fae5' : '#fee2e2'; ?>; color: <?php echo ($profile['status'] == 'Active') ? '#065f46' : '#991b1b'; ?>; padding: 4px 12px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;">
                    Status: <?php echo htmlspecialchars($profile['status']); ?>
                </span>
            </div>
        </div>

        <div style="display: grid; gap: 20px;">
            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #6b7280; margin-bottom: 4px;">Email Address</label>
                <div style="font-size: 1rem; color: #111827; font-weight: 500;"><?php echo htmlspecialchars($profile['staff_email']); ?></div>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #6b7280; margin-bottom: 4px;">Phone Number</label>
                <div style="font-size: 1rem; color: #111827; font-weight: 500;"><?php echo !empty($profile['staff_phone_number']) ? htmlspecialchars($profile['staff_phone_number']) : '-'; ?></div>
            </div>

            <div>
                <label style="display: block; font-size: 0.85rem; font-weight: 600; color: #6b7280; margin-bottom: 4px;">Home Address</label>
                <div style="font-size: 1rem; color: #111827; font-weight: 500; line-height: 1.5;"><?php echo !empty($profile['staff_address']) ? nl2br(htmlspecialchars($profile['staff_address'])) : '-'; ?></div>
            </div>
        </div>

        <div style="margin-top: 30px; padding: 16px; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db; display: flex; align-items: center; gap: 12px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p style="margin: 0; font-size: 0.85rem; color: #4b5563;">
                To ensure system security, staff profiles are read-only. If you need to update your phone number, address, or reset your password, please contact an Administrator.
            </p>
        </div>

    </div>
</div>

<?php require_once('admin_footer.php'); ?>