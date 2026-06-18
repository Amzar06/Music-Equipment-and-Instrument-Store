<?php
session_start();
if (!isset($_SESSION['staff_id'])) { header("Location: admin_login.php"); exit(); }

// Security lock: Only Administrators can view this page
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


// Get active filters

$filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$period = isset($_GET['period']) ? $_GET['period'] : 'all_time';

$transactions = [];
$total_revenue = 0;
$total_count = 0;


// Date filter logic

$order_date_cond = "";
$rental_date_cond = "";

if ($period == 'daily') {
    $order_date_cond = " AND DATE(o.order_date) = CURDATE()";
    $rental_date_cond = " AND DATE(r.created_at) = CURDATE()";
} elseif ($period == 'weekly') {
    $order_date_cond = " AND YEARWEEK(o.order_date, 1) = YEARWEEK(CURDATE(), 1)";
    $rental_date_cond = " AND YEARWEEK(r.created_at, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($period == 'monthly') {
    $order_date_cond = " AND MONTH(o.order_date) = MONTH(CURDATE()) AND YEAR(o.order_date) = YEAR(CURDATE())";
    $rental_date_cond = " AND MONTH(r.created_at) = MONTH(CURDATE()) AND YEAR(r.created_at) = YEAR(CURDATE())";
} elseif ($period == 'yearly') {
    $order_date_cond = " AND YEAR(o.order_date) = YEAR(CURDATE())";
    $rental_date_cond = " AND YEAR(r.created_at) = YEAR(CURDATE())";
}


// Fetch top 5 best selling product

$best_sellers = [];

$seller_query = "SELECT p.prod_name AS product_name, SUM(od.order_qty) as total_sold 
                 FROM order_items od 
                 JOIN products p ON od.prod_id = p.prod_id 
                 JOIN orders o ON od.order_id = o.order_id
                 WHERE o.status != 'Cancelled' $order_date_cond
                 GROUP BY p.prod_id 
                 ORDER BY total_sold DESC 
                 LIMIT 5";

$seller_result = mysqli_query($conn, $seller_query);
if ($seller_result) {
    while($row = mysqli_fetch_assoc($seller_result)) {
        $best_sellers[] = $row;
    }
}


// Fetch data for both sales & rentals

// Fetch orders for sales

if ($filter == 'all' || $filter == 'sales') {
    $order_query = "SELECT o.order_id AS id, o.order_date AS date, o.total_amount, o.status, c.cust_name 
                    FROM orders o 
                    JOIN customers c ON o.cust_id = c.cust_id 
                    WHERE o.status != 'Cancelled' $order_date_cond";
    $order_result = mysqli_query($conn, $order_query);
    
    if ($order_result) {
        while ($row = mysqli_fetch_assoc($order_result)) {
            $row['trans_type'] = 'Sale';
            $transactions[] = $row;
            $total_revenue += $row['total_amount'];
            $total_count++;
        }
    }
}

// Fetch rentals

if ($filter == 'all' || $filter == 'rentals') {
    $rental_query = "SELECT r.rental_id AS id, r.created_at AS date, r.total_amount, r.status, c.cust_name 
                     FROM rentals r 
                     JOIN customers c ON r.cust_id = c.cust_id 
                     WHERE r.status != 'Cancelled' $rental_date_cond";
    $rental_result = mysqli_query($conn, $rental_query);
    
    if ($rental_result) {
        while ($row = mysqli_fetch_assoc($rental_result)) {
            $row['trans_type'] = 'Rental';
            $transactions[] = $row;
            $total_revenue += $row['total_amount'];
            $total_count++;
        }
    }
}

// Sort the combined array by newest date first

usort($transactions, function($a, $b) {
    return strtotime($b['date']) - strtotime($a['date']);
});

require_once('admin_header.php');
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div style="max-width: 1200px; margin: 0 auto; margin-top: 20px;">
    
    <div style="background: white; padding: 16px 24px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
        <h3 style="margin: 0; font-weight: 700; color: #111827; font-size: 1.25rem;">Report Controls</h3>
        
        <form action="admin_report.php" method="GET" style="display: flex; align-items: center; gap: 12px; margin: 0;">
            <select name="period" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; font-size: 0.9rem; background: #f9fafb;" onchange="this.form.submit()">
                <option value="all_time" <?php echo ($period == 'all_time') ? 'selected' : ''; ?>>All Time</option>
                <option value="daily" <?php echo ($period == 'daily') ? 'selected' : ''; ?>>Today</option>
                <option value="weekly" <?php echo ($period == 'weekly') ? 'selected' : ''; ?>>This Week</option>
                <option value="monthly" <?php echo ($period == 'monthly') ? 'selected' : ''; ?>>This Month</option>
                <option value="yearly" <?php echo ($period == 'yearly') ? 'selected' : ''; ?>>This Year</option>
            </select>

            <select name="type" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; outline: none; font-size: 0.9rem; background: #f9fafb;" onchange="this.form.submit()">
                <option value="all" <?php echo ($filter == 'all') ? 'selected' : ''; ?>>All Types</option>
                <option value="sales" <?php echo ($filter == 'sales') ? 'selected' : ''; ?>>Sales Only</option>
                <option value="rentals" <?php echo ($filter == 'rentals') ? 'selected' : ''; ?>>Rentals Only</option>
            </select>

            <button type="button" onclick="downloadPDF()" style="padding: 8px 16px; background: #ef4444; color: white; border: none; border-radius: 6px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#dc2626'" onmouseout="this.style.backgroundColor='#ef4444'">
                <i class="fa-solid fa-download"></i> Download PDF
            </button>
        </form>
    </div>

    <div id="report-content" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e5e7eb;">
        
        <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 16px; margin-bottom: 24px;">
            <h2 style="color: #111827; margin: 0 0 8px 0; font-size: 1.5rem;">Store Revenue Report</h2>
            <div style="color: #6b7280; font-size: 0.9rem; display: flex; gap: 20px;">
                <span><strong>Generated:</strong> <?php echo date('d M Y, h:i A'); ?></span>
                <span><strong>Filter:</strong> <?php echo ucfirst(str_replace('_', ' ', $period)); ?> / <?php echo ucfirst($filter); ?></span>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px;">
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <h4 style="margin: 0; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Total Revenue</h4>
                <div style="font-size: 2.2rem; font-weight: 700; color: #10b981; margin-top: 8px;">
                    RM <?php echo number_format($total_revenue, 2); ?>
                </div>
            </div>
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb;">
                <h4 style="margin: 0; color: #4b5563; font-size: 0.85rem; text-transform: uppercase;">Total Transactions</h4>
                <div style="font-size: 2.2rem; font-weight: 700; color: #4f46e5; margin-top: 8px;">
                    <?php echo $total_count; ?>
                </div>
            </div>
        </div>

        <h3 style="margin: 0 0 16px 0; font-weight: 700; color: #111827; font-size: 1.1rem;">
            <i class="fa-solid fa-fire" style="color: #f97316; margin-right: 8px;"></i> Top 5 Best-Selling Products
        </h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 30px;">
            <?php if (!empty($best_sellers)): ?>
                <?php 
                $rank = 1;
                foreach ($best_sellers as $item): 
                    // Gold highlight for rank #1
                    $badge_bg = ($rank == 1) ? '#fef08a' : '#f3f4f6';
                    $badge_color = ($rank == 1) ? '#854d0e' : '#4b5563';
                ?>
                    <div style="padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; background: #fafafa; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center;">
                            <span style="font-size: 0.75rem; font-weight: 700; color: <?php echo $badge_color; ?>; background: <?php echo $badge_bg; ?>; padding: 4px 8px; border-radius: 12px; margin-right: 12px;">
                                #<?php echo $rank; ?>
                            </span>
                            <span style="font-weight: 600; color: #111827; font-size: 0.95rem;">
                                <?php echo htmlspecialchars($item['product_name']); ?>
                            </span>
                        </div>
                        <div style="font-size: 1.1rem; font-weight: 700; color: #4f46e5;">
                            <?php echo $item['total_sold']; ?> <span style="font-size: 0.7rem; color: #6b7280; font-weight: 500;">sold</span>
                        </div>
                    </div>
                <?php 
                $rank++;
                endforeach; 
                ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; color: #6b7280; font-size: 0.9rem; font-style: italic;">Not enough sales data for this period to determine best sellers.</div>
            <?php endif; ?>
        </div>

        <h3 style="margin: 0 0 16px 0; font-weight: 700; color: #111827; font-size: 1.1rem;">Transaction History</h3>
        
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead style="background: #f3f4f6; border-bottom: 2px solid #d1d5db;">
                <tr>
                    <th style="padding: 12px; color: #374151; text-transform: uppercase;">Date</th>
                    <th style="padding: 12px; color: #374151; text-transform: uppercase;">Type</th>
                    <th style="padding: 12px; color: #374151; text-transform: uppercase;">ID</th>
                    <th style="padding: 12px; color: #374151; text-transform: uppercase;">Customer</th>
                    <th style="padding: 12px; color: #374151; text-transform: uppercase;">Amount (RM)</th>
                    <th style="padding: 12px; color: #374151; text-transform: uppercase;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0): ?>
                    <?php foreach ($transactions as $t): ?>
                    <tr style="border-bottom: 1px solid #e5e7eb;">
                        <td style="padding: 12px; color: #4b5563;"><?php echo date('d M Y', strtotime($t['date'])); ?></td>
                        <td style="padding: 12px; font-weight: 600; color: <?php echo ($t['trans_type'] == 'Sale') ? '#4f46e5' : '#d97706'; ?>;">
                            <?php echo strtoupper($t['trans_type']); ?>
                        </td>
                        <td style="padding: 12px; font-weight: 600; color: #111827;">#<?php echo $t['id']; ?></td>
                        <td style="padding: 12px; color: #4b5563;"><?php echo htmlspecialchars($t['cust_name']); ?></td>
                        <td style="padding: 12px; font-weight: 700; color: #111827;"><?php echo number_format($t['total_amount'], 2); ?></td>
                        <td style="padding: 12px; color: #4b5563;"><?php echo htmlspecialchars($t['status']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 30px; color: #9ca3af;">No transactions found for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
    </div>
    </div>

<script>
function downloadPDF() {
    const element = document.getElementById('report-content');
    
    //pdf formatting options

    const opt = {
        margin:       10, // margins in mm
        filename:     'Music_Store_Revenue_Report.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2 }, // higher scale = better resolution
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    // Generate and download
    
    html2pdf().set(opt).from(element).save();
}
</script>

<?php require_once('admin_footer.php'); ?>