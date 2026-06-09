<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$page_title = "Customer Profile";
$active = "customers";
$hide_search = true; 

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: manage_customer.php");
    exit();
}

$cust_id = intval($_GET['id']);

// ==========================================
// HANDLE ACTIONS (Blacklist, Suspend, Delete)
// ==========================================
if (isset($_GET['action']) && $_SESSION['staff_role'] === 'Administrator') {
    $action = $_GET['action'];
    $allowed_actions = ['Active', 'Suspended', 'Blacklisted', 'Deleted'];
    
    if (in_array($action, $allowed_actions)) {
        mysqli_query($conn, "UPDATE customers SET status = '$action' WHERE cust_id = $cust_id");
        $_SESSION['flash_message'] = "Customer status updated to " . $action . ".";
        $_SESSION['flash_type'] = "success";
        header("Location: admin_view_customer.php?id=$cust_id");
        exit();
    }
}

// Fetch Customer
$customer_query = mysqli_query($conn, "SELECT * FROM customers WHERE cust_id = $cust_id");
if (mysqli_num_rows($customer_query) == 0) {
    header("Location: manage_customer.php");
    exit();
}
$customer = mysqli_fetch_assoc($customer_query);

// Fetch Addresses
$addresses = [];
$addr_req = @mysqli_query($conn, "SELECT * FROM addresses WHERE cust_id = $cust_id");
if($addr_req) { while($a = mysqli_fetch_assoc($addr_req)) { $addresses[] = $a; } }

// Status Colors
$status = $customer['status'] ?? 'Active';
if ($status == 'Active') { $bg = '#d1fae5'; $txt = '#065f46'; }
elseif ($status == 'Suspended') { $bg = '#fef3c7'; $txt = '#92400e'; }
elseif ($status == 'Blacklisted') { $bg = '#111827'; $txt = '#f9fafb'; }
else { $bg = '#fee2e2'; $txt = '#991b1b'; }

require_once('admin_header.php');
?>

<div style="max-width: 1000px; margin: 0 auto; margin-top: 20px;">
    
    <a href="manage_customer.php" style="display: inline-flex; align-items: center; gap: 6px; color: #4b5563; text-decoration: none; font-size: 0.9rem; font-weight: 600; margin-bottom: 20px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
        Back to Directory
    </a>

    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($_SESSION['flash_type'] == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <div style="background: white; padding: 30px; border-radius: 12px; border: 1px solid #e5e7eb; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0 0 8px 0; color: #111827; font-size: 2rem;"><?php echo htmlspecialchars($customer['cust_name']); ?></h2>
            <div style="color: #6b7280; font-size: 0.95rem; display: flex; gap: 16px;">
                <span>📧 <?php echo htmlspecialchars($customer['cust_email']); ?></span>
                <span>📞 <?php echo !empty($customer['cust_phone']) ? htmlspecialchars($customer['cust_phone']) : 'No Phone Provided'; ?></span>
            </div>
        </div>
        <div style="text-align: right;">
            <span style="background: <?php echo $bg; ?>; color: <?php echo $txt; ?>; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase;">
                <?php echo $status; ?>
            </span>
            <p style="margin: 8px 0 0 0; font-size: 0.8rem; color: #9ca3af;">Joined: <?php echo date('F d, Y', strtotime($customer['created_at'])); ?></p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        
        <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; color: #111827; font-size: 1.2rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 10px;">Saved Addresses</h3>
            <?php if (count($addresses) > 0): ?>
                <div style="display: flex; flex-direction: column; gap: 12px; max-height: 300px; overflow-y: auto;">
                    <?php foreach($addresses as $addr): ?>
                        <div style="background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <strong style="display: block; color: #374151; font-size: 0.95rem;">
                                <?php echo htmlspecialchars($addr['full_name']); ?>
                                <?php if(!empty($addr['phone_number'])) echo ' <span style="font-size:0.8rem; color:#6b7280;">(' . htmlspecialchars($addr['phone_number']) . ')</span>'; ?>
                            </strong>
                            <span style="color: #6b7280; font-size: 0.85rem; line-height: 1.5; display: block; margin-top: 4px;">
                                <?php echo htmlspecialchars(implode(', ', array_filter([$addr['street'], $addr['city'], $addr['postcode'], $addr['state'], $addr['country']]))); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="color: #9ca3af; font-size: 0.9rem;">This customer has not saved any addresses yet.</p>
            <?php endif; ?>
        </div>

        <div style="background: white; padding: 24px; border-radius: 12px; border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; color: #111827; font-size: 1.2rem; border-bottom: 1px solid #f3f4f6; padding-bottom: 10px;">Administrator Actions</h3>
            
            <?php if ($_SESSION['staff_role'] === 'Administrator'): ?>
                <p style="font-size: 0.85rem; color: #6b7280; margin-bottom: 20px;">Use these controls to restrict or restore the customer's access to the store.</p>
                
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <?php if ($status !== 'Active'): ?>
                        <a href="admin_view_customer.php?id=<?php echo $cust_id; ?>&action=Active" style="text-align: center; background: #10b981; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600;">✅ Restore to Active</a>
                    <?php endif; ?>
                    
                    <?php if ($status !== 'Suspended'): ?>
                        <a href="admin_view_customer.php?id=<?php echo $cust_id; ?>&action=Suspended" onclick="return confirm('Suspend this account? They will not be able to log in temporarily.')" style="text-align: center; background: #f59e0b; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600;">⏸️ Suspend Account</a>
                    <?php endif; ?>

                    <?php if ($status !== 'Blacklisted'): ?>
                        <a href="admin_view_customer.php?id=<?php echo $cust_id; ?>&action=Blacklisted" onclick="return confirm('BLACKLIST this account? They will be permanently banned from purchasing or renting.')" style="text-align: center; background: #111827; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: 600;">🚫 Blacklist Customer</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 8px; font-size: 0.85rem; font-weight: 600;">
                    Only Administrators can modify customer restrictions.
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<?php require_once('admin_footer.php'); ?>