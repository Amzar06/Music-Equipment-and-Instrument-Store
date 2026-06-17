<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$page_title = "Customer Profile";
$active = "customers"; 

// 1. Get the Customer ID from the URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['flash_message'] = "No customer selected.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_customer.php");
    exit();
}

$cust_id = intval($_GET['id']);

// 2. Fetch Customer Details
$customer_query = "SELECT * FROM customers WHERE cust_id = $cust_id";
$customer_result = mysqli_query($conn, $customer_query);

if (mysqli_num_rows($customer_result) == 0) {
    $_SESSION['flash_message'] = "Customer not found.";
    $_SESSION['flash_type'] = "error";
    header("Location: manage_customer.php");
    exit();
}
$customer = mysqli_fetch_assoc($customer_result);

// 3. Handle Admin Actions (Suspend/Blacklist)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = mysqli_real_escape_string($conn, $_POST['new_status']);
    $update_query = "UPDATE customers SET status = '$new_status' WHERE cust_id = $cust_id";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['flash_message'] = "Customer account status updated to $new_status.";
        $_SESSION['flash_type'] = "success";
        // Refresh page to show new status
        header("Location: admin_view_customer.php?id=$cust_id");
        exit();
    }
}

require_once('admin_header.php');
?>

<div style="max-width: 1000px; margin: 0 auto; margin-top: 20px;">
    
    <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($_SESSION['flash_type'] == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $_SESSION['flash_message']; unset($_SESSION['flash_message']); unset($_SESSION['flash_type']); ?>
        </div>
    <?php endif; ?>

    <div style="margin-bottom: 20px;">
        <a href="manage_customer.php" style="color: #4b5563; text-decoration: none; font-weight: 600; font-size: 0.9rem;">
            ← Back to Directory
        </a>
    </div>

    <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px; position: relative;">
        
        <div style="position: absolute; top: 30px; right: 30px; text-align: right;">
            <?php 
                $status = $customer['status'] ?? 'Active';
                $bg = ($status == 'Active') ? '#d1fae5' : '#fee2e2';
                $color = ($status == 'Active') ? '#065f46' : '#991b1b';
            ?>
            <span style="background: <?php echo $bg; ?>; color: <?php echo $color; ?>; padding: 6px 16px; border-radius: 20px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase;">
                <?php echo htmlspecialchars($status); ?>
            </span>
            <div style="font-size: 0.8rem; color: #9ca3af; margin-top: 8px;">
                Joined: <?php echo date('F d, Y', strtotime($customer['created_at'])); ?>
            </div>
        </div>

        <h2 style="margin: 0 0 16px 0; color: #111827; font-size: 1.8rem;"><?php echo htmlspecialchars($customer['cust_name']); ?></h2>
        
        <div style="display: flex; gap: 24px; color: #4b5563; font-size: 0.95rem;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-envelope" style="color: #4f46e5;"></i> 
                <?php echo htmlspecialchars($customer['cust_email']); ?>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-phone" style="color: #e11d48;"></i> 
                <?php echo htmlspecialchars($customer['cust_phone_number'] ?? 'No phone provided'); ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827;">Saved Addresses</h3>
            
            <?php 
                // THE FIX IS HERE: We filter out rows where the street is null or empty!
                $addr_query = "SELECT * FROM addresses WHERE cust_id = $cust_id AND street IS NOT NULL AND TRIM(street) != '' ORDER BY created_at DESC";
                $addr_result = mysqli_query($conn, $addr_query);

                if (mysqli_num_rows($addr_result) > 0): 
                    while($addr = mysqli_fetch_assoc($addr_result)):
            ?>
                <div style="padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px; background: #f9fafb;">
                    <strong style="color: #111827; display: block; margin-bottom: 4px;"><?php echo htmlspecialchars($addr['full_name']); ?></strong>
                    <p style="margin: 0; font-size: 0.85rem; color: #6b7280; line-height: 1.5;">
                        <?php 
                            $parts = array_filter([$addr['street'], $addr['city'], $addr['postcode'], $addr['state']]);
                            echo htmlspecialchars(implode(', ', $parts));
                        ?>
                    </p>
                </div>
            <?php 
                    endwhile; 
                else: 
            ?>
                <div style="padding: 20px; text-align: center; color: #9ca3af; border: 1px dashed #d1d5db; border-radius: 8px;">
                    No valid saved addresses on file.
                </div>
            <?php endif; ?>
        </div>

        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h3 style="margin-top: 0; margin-bottom: 20px; font-weight: 700; color: #111827;">Administrative Actions</h3>
            
            <form action="" method="POST" style="display: flex; flex-direction: column; gap: 12px;">
                
                <?php if ($status == 'Active'): ?>
                    <input type="hidden" name="new_status" value="Suspended">
                    <button type="submit" name="update_status" style="width: 100%; padding: 14px; background: #f59e0b; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#d97706'" onmouseout="this.style.backgroundColor='#f59e0b'">
                         Suspend Account
                    </button>
                    
                <?php else: ?>
                    <input type="hidden" name="new_status" value="Active">
                    <button type="submit" name="update_status" style="width: 100%; padding: 14px; background: #10b981; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10b981'">
                         Reactivate Account
                    </button>
                <?php endif; ?>

            </form>

            <form action="" method="POST" style="margin-top: 12px;" onsubmit="return confirm('Are you sure you want to blacklist this user? They will not be able to log in.');">
                <input type="hidden" name="new_status" value="Blacklisted">
                <button type="submit" name="update_status" style="width: 100%; padding: 14px; background: #111827; color: white; border: none; border-radius: 8px; font-weight: 700; font-size: 0.95rem; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#000000'" onmouseout="this.style.backgroundColor='#111827'">
                     Blacklist Customer
                </button>
            </form>

        </div>

    </div>
</div>

<?php require_once('admin_footer.php'); ?>