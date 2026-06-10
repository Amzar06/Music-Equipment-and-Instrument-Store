<?php
session_start();
if (!isset($_SESSION['staff_id'])) { 
    header("Location: admin_login.php"); 
    exit(); 
}
require_once('../database.php');

$page_title = "Business Reports";
$active = "reports";
require_once('admin_header.php');

// Sales Revenue (Delivered = Completed)
$sales_query = mysqli_query($conn, "SELECT SUM(total_amount) as revenue FROM orders WHERE status='Delivered'");
$sales_total = ($sales_query && mysqli_num_rows($sales_query) > 0) ? (float)mysqli_fetch_assoc($sales_query)['revenue'] : 0.00;

// Rental Revenue (Returned)
$rental_query = mysqli_query($conn, "SELECT SUM(total_amount) as revenue FROM rentals WHERE status='Returned'");
$rental_total = ($rental_query && mysqli_num_rows($rental_query) > 0) ? (float)mysqli_fetch_assoc($rental_query)['revenue'] : 0.00;

// Top Performing Products
$top_products = [];
$top_query = "SELECT p.prod_name, c.category_name, SUM(oi.order_qty) as total_sold
              FROM order_items oi
              JOIN products p ON oi.prod_id = p.prod_id
              JOIN categories c ON p.category_id = c.category_id
              JOIN orders o ON oi.order_id = o.order_id
              WHERE o.status = 'Delivered'
              GROUP BY p.prod_id
              ORDER BY total_sold DESC
              LIMIT 5";
$top_res = mysqli_query($conn, $top_query);
if ($top_res) {
    while($row = mysqli_fetch_assoc($top_res)) {
        $top_products[] = $row;
    }
}
?>

<div class="stats-grid">
    <div class="stat-card">
        <span>Sales Revenue</span>
        <h3 style="color: #4f46e5;">RM <?php echo number_format($sales_total, 2); ?></h3>
    </div>
    <div class="stat-card">
        <span>Rental Revenue</span>
        <h3 style="color: #10b981;">RM <?php echo number_format($rental_total, 2); ?></h3>
    </div>
</div>

<div class="table-container">
    <h3 style="margin-bottom: 5px; font-weight: 700; color: var(--text-main);">Top Performing Products</h3>
    <p style="color: #6b7280; margin-bottom: 20px; font-size: 0.85rem;">Based on total units sold.</p>
    
    <?php if (!empty($top_products)): ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 2px solid #f3f4f6;">
                        <th style="padding: 12px 8px;">Product Name</th>
                        <th style="padding: 12px 8px;">Category</th>
                        <th style="padding: 12px 8px; text-align: right;">Total Sold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $prod): ?>
                    <tr style="border-bottom: 1px solid #f3f4f6;">
                        <td style="padding: 12px 8px; font-weight: 600;"><?php echo htmlspecialchars($prod['prod_name']); ?></td>
                        <td style="padding: 12px 8px;"><?php echo htmlspecialchars($prod['category_name']); ?></td>
                        <td style="padding: 12px 8px; text-align: right; font-weight: 700; color: #4f46e5;"><?php echo $prod['total_sold']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; padding: 40px; color: #9ca3af; background: #f9fafb; border-radius: 8px; border: 1px dashed var(--border-color);">
            No sales data available yet. Mark orders as "Delivered" to see reports.
        </div>
    <?php endif; ?>
</div>

<?php require_once('admin_footer.php'); ?>