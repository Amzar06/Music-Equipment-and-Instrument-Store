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

// Bulletproof Calculations: Prevents fatal PHP crashes if there are 0 completed orders
$sales_query = mysqli_query($conn, "SELECT SUM(total_amount) as revenue FROM orders WHERE status='completed'");
$sales_total = ($sales_query && mysqli_num_rows($sales_query) > 0) ? (float)mysqli_fetch_assoc($sales_query)['revenue'] : 0.00;

$rental_query = mysqli_query($conn, "SELECT SUM(total_price) as revenue FROM rentals WHERE status='returned'");
$rental_total = ($rental_query && mysqli_num_rows($rental_query) > 0) ? (float)mysqli_fetch_assoc($rental_query)['revenue'] : 0.00;
?>

<div class="stats-grid">
    <div class="stat-card">
        <span>Sales Revenue</span>
        <h3 style="color: var(--accent-color);">RM <?php echo number_format($sales_total, 2); ?></h3>
    </div>
    <div class="stat-card">
        <span>Rental Revenue</span>
        <h3 style="color: var(--accent-color);">RM <?php echo number_format($rental_total, 2); ?></h3>
    </div>
</div>

<div class="table-container">
    <h3 style="margin-bottom: 5px; font-weight: 700; color: var(--text-main);">Top Performing Products</h3>
    <p style="color: #6b7280; margin-bottom: 20px; font-size: 0.85rem;">Based on total units sold.</p>
    
    <div style="text-align: center; padding: 40px; color: #9ca3af; background: #f9fafb; border-radius: 8px; border: 1px dashed var(--border-color);">
        Data will appear as sales are processed.
    </div>
</div>

<?php require_once('admin_footer.php'); ?>