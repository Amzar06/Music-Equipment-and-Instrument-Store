<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header("Location: admin_login.php");
    exit();
}

require_once('../database.php');

$page_title = "Process Returns";
$active = "returns"; 

$message = ""; $message_type = "";

// ==========================================
// THE AUTO-TRACKING RETURN LOGIC
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_return'])) {
    $rental_item_id = intval($_POST['rental_item_id']);
    $return_condition = mysqli_real_escape_string($conn, trim($_POST['return_condition']));

    if (!empty($return_condition)) {
        // 1. Find out which product this is and how many were rented
        $check_query = "SELECT prod_id, rental_qty FROM rental_items WHERE rental_item_id = $rental_item_id";
        $check_result = mysqli_query($conn, $check_query);
        
        if ($row = mysqli_fetch_assoc($check_result)) {
            $p_id = $row['prod_id'];
            $qty_to_return = $row['rental_qty'];

            // 2. Mark the specific rental transaction as 'Returned' with the condition notes
            $update_item = "UPDATE rental_items 
                            SET return_status = 'Returned', return_condition = '$return_condition' 
                            WHERE rental_item_id = $rental_item_id";
            
            // 3. Auto-Restock: Add the quantity back into the RENTAL catalog specifically
            $restock_product = "UPDATE products 
                                SET prod_rental_qty = prod_rental_qty + $qty_to_return 
                                WHERE prod_id = $p_id";

            if (mysqli_query($conn, $update_item) && mysqli_query($conn, $restock_product)) {
                $message = "Item successfully returned and rental inventory auto-restocked (+{$qty_to_return}).";
                $message_type = "success";
            } else {
                $message = "Database Error: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    } else {
        $message = "You must enter the physical condition of the item to process the return.";
        $message_type = "error";
    }
}

// Fetch all items currently rented 'Out' by joining tables
$pending_query = "SELECT ri.*, p.prod_name, r.created_at as rent_date 
                  FROM rental_items ri 
                  JOIN products p ON ri.prod_id = p.prod_id 
                  JOIN rentals r ON ri.rental_id = r.rental_id 
                  WHERE ri.return_status = 'Out' 
                  ORDER BY r.created_at ASC";
$pending_result = mysqli_query($conn, $pending_query);

require_once('admin_header.php');
?>

<div style="max-width: 1000px; margin: 0 auto; margin-top: 20px;">
    
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0; color: #111827;">Process Pending Returns</h2>
        <p style="color: #6b7280; font-size: 0.95rem; margin-top: 6px;">Log wear-and-tear conditions to automatically restore rental catalog availability.</p>
    </div>

    <?php if (!empty($message)): ?>
        <div style="padding: 14px; border-radius: 8px; margin-bottom: 24px; font-weight: 500; <?php echo ($message_type == 'success') ? 'background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0;' : 'background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5;'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <div style="background: white; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; overflow: hidden;">
        <table style="width: 100%; border-collapse: collapse; text-align: left;">
            <thead style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                <tr>
                    <th style="padding: 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Trans. ID</th>
                    <th style="padding: 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Product Detail</th>
                    <th style="padding: 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Qty Out</th>
                    <th style="padding: 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase; width: 40%;">Inspection & Return</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($pending_result) > 0): ?>
                    <?php while($item = mysqli_fetch_assoc($pending_result)): ?>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 16px; color: #6b7280; font-weight: 600;">
                            #<?php echo $item['rental_id']; ?><br>
                            <span style="font-size: 0.75rem; font-weight: normal;">Item #<?php echo $item['rental_item_id']; ?></span>
                        </td>
                        <td style="padding: 16px;">
                            <strong style="color: #111827;"><?php echo htmlspecialchars($item['prod_name']); ?></strong><br>
                            <span style="font-size: 0.85rem; color: #6b7280;">Rented on: <?php echo date('M d, Y', strtotime($item['rent_date'])); ?></span>
                        </td>
                        <td style="padding: 16px; text-align: center;">
                            <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 12px; font-weight: bold; font-size: 0.85rem;">
                                <?php echo $item['rental_qty']; ?>
                            </span>
                        </td>
                        <td style="padding: 16px; background: #fafaf9;">
                            <form action="admin_returns.php" method="POST" style="display: flex; gap: 8px;">
                                <input type="hidden" name="rental_item_id" value="<?php echo $item['rental_item_id']; ?>">
                                
                                <input type="text" name="return_condition" placeholder="e.g., Good, Minor Scratch..." required 
                                       style="flex-grow: 1; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; font-size: 0.9rem;">
                                
                                <button type="submit" name="confirm_return" 
                                        style="padding: 10px 16px; background: #10b981; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s;" 
                                        onmouseover="this.style.backgroundColor='#059669'" onmouseout="this.style.backgroundColor='#10b981'">
                                    Restock
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 60px 20px; color: #9ca3af;">
                            <div style="font-size: 2rem; margin-bottom: 10px;">🎸</div>
                            <div style="font-weight: 600; color: #4b5563; font-size: 1.1rem;">All Caught Up!</div>
                            <div style="font-size: 0.9rem;">There are currently no outstanding rentals to process.</div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>