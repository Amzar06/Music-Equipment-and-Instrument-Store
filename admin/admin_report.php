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
    $_SESSION['flash_message'] = "Access Denied: Only Administrators can view financial reports.";
    $_SESSION['flash_type'] = "error";
    header("Location: admin_dashboard.php");
    exit();
}

require_once('../database.php');

$page_title = "Business Reports";
$active = "reports";
$hide_search = true; 

// ==========================================
// TIMEFRAME FILTER LOGIC
// ==========================================
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'all';
$order_date_filter = "";
$rental_date_filter = "";

if ($timeframe == 'daily') {
    $order_date_filter = "AND DATE(order_date) = CURDATE()";
    $rental_date_filter = "AND DATE(start_date) = CURDATE()";
} elseif ($timeframe == 'weekly') {
    $order_date_filter = "AND YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)";
    $rental_date_filter = "AND YEARWEEK(start_date, 1) = YEARWEEK(CURDATE(), 1)";
} elseif ($timeframe == 'monthly') {
    $order_date_filter = "AND MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())";
    $rental_date_filter = "AND MONTH(start_date) = MONTH(CURDATE()) AND YEAR(start_date) = YEAR(CURDATE())";
} elseif ($timeframe == 'yearly') {
    $order_date_filter = "AND YEAR(order_date) = YEAR(CURDATE())";
    $rental_date_filter = "AND YEAR(start_date) = YEAR(CURDATE())";
}

// ==========================================
// FETCH FILTERED DATA
// ==========================================
// Sales Revenue 
$sales_query = mysqli_query($conn, "SELECT SUM(total_amount) as revenue FROM orders WHERE status='Delivered' $order_date_filter");
$sales_total = ($sales_query && mysqli_num_rows($sales_query) > 0) ? (float)mysqli_fetch_assoc($sales_query)['revenue'] : 0.00;

// Rental Revenue
$rental_query = mysqli_query($conn, "SELECT SUM(total_amount) as revenue FROM rentals WHERE status='Returned' $rental_date_filter");
$rental_total = ($rental_query && mysqli_num_rows($rental_query) > 0) ? (float)mysqli_fetch_assoc($rental_query)['revenue'] : 0.00;

// Top Performing Products (Filtered by timeframe)
$top_products = [];
$top_query = "SELECT p.prod_name, c.category_name, SUM(oi.order_qty) as total_sold
              FROM order_items oi
              JOIN products p ON oi.prod_id = p.prod_id
              JOIN categories c ON p.category_id = c.category_id
              JOIN orders o ON oi.order_id = o.order_id
              WHERE o.status = 'Delivered' $order_date_filter
              GROUP BY p.prod_id
              ORDER BY total_sold DESC
              LIMIT 5";
$top_res = mysqli_query($conn, $top_query);
if ($top_res) {
    while($row = mysqli_fetch_assoc($top_res)) {
        $top_products[] = $row;
    }
}

require_once('admin_header.php');
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; max-width: 1200px; margin-left: auto; margin-right: auto;">
    
    <form action="admin_report.php" method="GET" style="display: flex; align-items: center; gap: 10px;">
        <label style="font-weight: 600; color: #4b5563; font-size: 0.9rem;">Filter By:</label>
        <select name="timeframe" onchange="this.form.submit()" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; font-weight: 600; color: #111827; background: white;">
            <option value="all" <?php echo ($timeframe == 'all') ? 'selected' : ''; ?>>All Time (Lifetime)</option>
            <option value="daily" <?php echo ($timeframe == 'daily') ? 'selected' : ''; ?>>Today</option>
            <option value="weekly" <?php echo ($timeframe == 'weekly') ? 'selected' : ''; ?>>This Week</option>
            <option value="monthly" <?php echo ($timeframe == 'monthly') ? 'selected' : ''; ?>>This Month</option>
            <option value="yearly" <?php echo ($timeframe == 'yearly') ? 'selected' : ''; ?>>This Year</option>
        </select>
    </form>

    <button onclick="downloadPDF()" style="background: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s;" onmouseover="this.style.backgroundColor='#4338ca'" onmouseout="this.style.backgroundColor='#4f46e5'">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
        </svg>
        Download PDF
    </button>
</div>

<div id="printable-report" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); max-width: 1200px; margin: 0 auto;">
    
    <div style="border-bottom: 2px solid #e5e7eb; padding-bottom: 15px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: flex-end;">
        <div>
            <h2 style="margin: 0; color: #111827; font-size: 1.8rem;">Financial Performance Report</h2>
            <p style="margin: 4px 0 0 0; color: #4f46e5; font-weight: 600; text-transform: uppercase; font-size: 0.85rem;">
                Timeframe: <?php echo htmlspecialchars($timeframe == 'all' ? 'Lifetime' : $timeframe); ?>
            </p>
        </div>
        <div style="text-align: right; color: #9ca3af; font-size: 0.85rem;">
            Generated on: <strong><?php echo date('d M Y, h:i A'); ?></strong>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; text-align: center;">
            <span style="color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 0.85rem;">Sales Revenue</span>
            <h3 style="color: #4f46e5; margin: 10px 0 0 0; font-size: 2rem;">RM <?php echo number_format($sales_total, 2); ?></h3>
        </div>
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; border: 1px solid #e5e7eb; text-align: center;">
            <span style="color: #6b7280; font-weight: 600; text-transform: uppercase; font-size: 0.85rem;">Rental Revenue</span>
            <h3 style="color: #10b981; margin: 10px 0 0 0; font-size: 2rem;">RM <?php echo number_format($rental_total, 2); ?></h3>
        </div>
    </div>

    <div class="table-container" style="border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
        <h3 style="margin-top: 0; margin-bottom: 5px; font-weight: 700; color: #111827;">Top Performing Products</h3>
        <p style="color: #6b7280; margin-bottom: 20px; font-size: 0.85rem;">Based on units successfully delivered during the selected timeframe.</p>
        
        <?php if (!empty($top_products)): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 2px solid #e5e7eb; background: #f3f4f6;">
                            <th style="padding: 12px 16px; color: #374151;">Product Name</th>
                            <th style="padding: 12px 16px; color: #374151;">Category</th>
                            <th style="padding: 12px 16px; text-align: right; color: #374151;">Total Sold</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $prod): ?>
                        <tr style="border-bottom: 1px solid #f3f4f6;">
                            <td style="padding: 14px 16px; font-weight: 600; color: #111827;"><?php echo htmlspecialchars($prod['prod_name']); ?></td>
                            <td style="padding: 14px 16px; color: #4b5563;"><?php echo htmlspecialchars($prod['category_name']); ?></td>
                            <td style="padding: 14px 16px; text-align: right; font-weight: 700; color: #4f46e5;"><?php echo $prod['total_sold']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #9ca3af; background: #f9fafb; border-radius: 8px; border: 1px dashed #d1d5db;">
                No sales data available for the selected timeframe.
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
function downloadPDF() {
    var element = document.getElementById('printable-report');
    var opt = {
        margin:       0.5,
        filename:     'Business_Report_<?php echo htmlspecialchars($timeframe); ?>_<?php echo date("Y_m_d"); ?>.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true },
        jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
}
</script>

<?php require_once('admin_footer.php'); ?>