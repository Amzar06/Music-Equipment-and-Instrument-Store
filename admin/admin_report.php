<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }

if (!isset($_SESSION['staff_role']) || $_SESSION['staff_role'] !== 'Administrator') {
    $_SESSION['flash_message'] = "Access Denied: Only Administrators can view reports.";
    $_SESSION['flash_type'] = "error";
    header("Location: admin_dashboard.php");
    exit();
}

require_once('../database.php');

$page_title = "Revenue Reports";
$active = "reports"; 
$hide_search = true;

// Get the current filter from URL (default to 'all')
$filter = isset($_GET['type']) ? $_GET['type'] : 'all';

$transactions = [];
$total_revenue = 0;
$total_count = 0;

// 1. Fetch Orders (Sales) if applicable
if ($filter == 'all' || $filter == 'sales') {
    $order_query = "SELECT o.order_id AS id, o.order_date AS date, o.total_amount, o.status, c.cust_name 
                    FROM orders o 
                    JOIN customers c ON o.cust_id = c.cust_id 
                    WHERE o.status != 'Cancelled'";
    $order_result = mysqli_query($conn, $order_query);
    
    while ($row = mysqli_fetch_assoc($order_result)) {
        $row['trans_type'] = 'Sale';
        $transactions[] = $row;
        $total_revenue += $row['total_amount'];
        $total_count++;
    }
}

// 2. Fetch Rentals if applicable
if ($filter == 'all' || $filter == 'rentals') {
    $rental_query = "SELECT r.rental_id AS id, r.created_at AS date, r.total_amount, r.status, c.cust_name 
                     FROM rentals r 
                     JOIN customers c ON r.cust_id = c.cust_id 
                     WHERE r.status != 'Cancelled'";
    $rental_result = mysqli_query($conn, $rental_query);
    
    while ($row = mysqli_fetch_assoc($rental_result)) {
        $row['trans_type'] = 'Rental';
        $transactions[] = $row;
        $total_revenue += $row['total_amount'];
        $total_count++;
    }
}

// 3. Sort the combined array by Date (Newest First)
usort($transactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

require_once('admin_header.php');
?>

<div style="max-width: 1200px; margin: 0 auto; margin-top: 20px;">
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h4 style="margin: 0; color: #6b7280; font-size: 0.9rem; text-transform: uppercase;">Filtered Revenue</h4>
            <div style="font-size: 2.5rem; font-weight: 700; color: #10b981; margin-top: 8px;">
                RM <?php echo number_format($total_revenue, 2); ?>
            </div>
        </div>
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
            <h4 style="margin: 0; color: #6b7280; font-size: 0.9rem; text-transform: uppercase;">Valid Transactions</h4>
            <div style="font-size: 2.5rem; font-weight: 700; color: #4f46e5; margin-top: 8px;">
                <?php echo $total_count; ?>
            </div>
        </div>
    </div>

    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h3 style="margin: 0; font-weight: 700; color: #111827; font-size: 1.25rem;">Transaction History</h3>
            
            <form action="admin_report.php" method="GET" style="display: flex; align-items: center; gap: 12px;">
                <label style="font-size: 0.9rem; font-weight: 600; color: #4b5563;">View:</label>
                <select name="type" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; font-size: 0.9rem;" onchange="this.form.submit()">
                    <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All Transactions</option>
                    <option value="sales" <?php echo ($filter == 'sales') ? 'selected' : ''; ?>>Sales Only</option>
                    <option value="rentals" <?php echo ($filter == 'rentals') ? 'selected' : ''; ?>>Rentals Only</option>
                </select>
            </form>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left;">
                <thead style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                    <tr>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Date</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Type</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">ID</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Customer</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Amount (RM)</th>
                        <th style="padding: 12px 16px; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transactions) > 0): ?>
                        <?php foreach ($transactions as $t): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 16px; color: #6b7280; font-size: 0.9rem;"><?php echo date('d M Y, h:i A', strtotime($t['date'])); ?></td>
                            
                            <td style="padding: 16px;">
                                <span style="font-weight: 700; font-size: 0.8rem; padding: 4px 8px; border-radius: 4px; <?php echo ($t['trans_type'] == 'Sale') ? 'background: #e0e7ff; color: #3730a3;' : 'background: #fef3c7; color: #92400e;'; ?>">
                                    <?php echo strtoupper($t['trans_type']); ?>
                                </span>
                            </td>
                            
                            <td style="padding: 16px; font-weight: 600; color: #111827;">#<?php echo $t['id']; ?></td>
                            <td style="padding: 16px; color: #4b5563;"><?php echo htmlspecialchars($t['cust_name']); ?></td>
                            <td style="padding: 16px; font-weight: 700; color: #111827;"><?php echo number_format($t['total_amount'], 2); ?></td>
                            <td style="padding: 16px;">
                                <span style="background: #f3f4f6; color: #4b5563; padding: 4px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">
                                    <?php echo htmlspecialchars($t['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align: center; padding: 40px; color: #9ca3af;">No transactions found for this filter.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once('admin_footer.php'); ?>