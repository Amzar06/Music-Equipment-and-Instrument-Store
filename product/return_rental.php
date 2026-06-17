<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}

$cust_id = $_SESSION['cust_id'];
$rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;

if ($rental_id <= 0) {
    header("Location: payment history.php");
    exit();
}

// Fetch rental and items
$query = $conn->prepare("
    SELECT r.*, p.prod_name, p.prod_rental_price
    FROM rentals r
    JOIN rental_items ri ON r.rental_id = ri.rental_id
    JOIN products p ON ri.prod_id = p.prod_id
    WHERE r.rental_id = ? AND r.cust_id = ?
    LIMIT 1
");
$query->bind_param("ii", $rental_id, $cust_id);
$query->execute();
$rental = $query->get_result()->fetch_assoc();
$query->close();

if (!$rental) {
    die("Rental not found.");
}

$today = date('Y-m-d');
$end_date = $rental['end_date'];
$start_date = $rental['start_date'];

// Calculate original days
$start_ts = strtotime($start_date);
$end_ts = strtotime($end_date);
$today_ts = strtotime($today);
$original_days = ($end_ts - $start_ts) / 86400;
if ($original_days <= 0) $original_days = 1;

$daily_rate = $rental['total_amount'] / $original_days;

$adjustment_type = 'none';
$adjustment_amount = 0;
$days_diff = ($today_ts - $end_ts) / 86400;

if ($days_diff < 0) {
    // Early return
    $adjustment_type = 'refund';
    $early_days = abs($days_diff);
    $adjustment_amount = $early_days * $daily_rate;
} elseif ($days_diff > 0) {
    // Late return
    $adjustment_type = 'extra_charge';
    $late_days = $days_diff;
    $adjustment_amount = $late_days * $daily_rate * 1.25; // 25% penalty
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_return'])) {
    // Update status to Processing (Awaiting Admin Approval)
    $upd = $conn->prepare("UPDATE rentals SET status = 'Processing' WHERE rental_id = ?");
    $upd->bind_param("i", $rental_id);
    if ($upd->execute()) {
        header("Location: payment history.php?return_success=rental");
        exit();
    }
    $upd->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Return Rental</title>
    <link rel="stylesheet" href="style.css?v=5.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .return-card { 
            background: white; 
            border-radius: 20px; 
            padding: 40px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 650px;
            margin: 60px auto;
            border: 1px solid #f1f5f9;
        }
        .summary-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #f1f5f9; }
        .summary-label { color: #64748b; font-weight: 500; }
        .summary-value { font-weight: 700; color: #1e293b; }
        .warning-box {
            background: #fff7ed; border: 1px solid #ffedd5;
            padding: 16px; border-radius: 12px; margin-top: 24px;
            color: #9a3412; font-size: 0.9rem;
            display: flex; gap: 12px; align-items: flex-start;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="return-card">
        <h2 style="font-weight: 800; color: #1e293b; margin-bottom: 8px;">Return Instrument</h2>
        <p style="color: #64748b; margin-bottom: 30px;">Summary of your rental return for <strong><?php echo htmlspecialchars($rental['prod_name']); ?></strong></p>

        <div class="summary-row">
            <span class="summary-label">Rental Period</span>
            <span class="summary-value"><?php echo date('d M', $start_ts); ?> — <?php echo date('d M', $end_ts); ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Return Date (Today)</span>
            <span class="summary-value"><?php echo date('d M Y'); ?></span>
        </div>
        
        <?php if ($adjustment_type === 'refund'): ?>
            <div style="background: #f0fdf4; padding: 20px; border-radius: 12px; margin-top: 20px; text-align: center;">
                <div style="color: #166534; font-size: 0.9rem; font-weight: 600;">Early Return Benefit</div>
                <div style="font-size: 2rem; font-weight: 800; color: #10b981;">+ RM <?php echo number_format($adjustment_amount, 2); ?></div>
                <p style="font-size: 0.8rem; color: #16a34a; margin: 5px 0 0 0;">Estimated refund based on <?php echo $early_days; ?> unused day(s).</p>
            </div>
        <?php elseif ($adjustment_type === 'extra_charge'): ?>
            <div style="background: #fff1f2; padding: 20px; border-radius: 12px; margin-top: 20px; text-align: center;">
                <div style="color: #991b1b; font-size: 0.9rem; font-weight: 600;">Late Return Extra Charges</div>
                <div style="font-size: 2rem; font-weight: 800; color: #ef4444;">- RM <?php echo number_format($adjustment_amount, 2); ?></div>
                <p style="font-size: 0.8rem; color: #b91c1c; margin: 5px 0 0 0;">Penalty based on <?php echo $late_days; ?> overdue day(s).</p>
            </div>
            
            <div class="warning-box">
                <span style="font-size: 1.5rem;">⚠️</span>
                <div>
                    <strong>IMPORTANT REMINDER:</strong><br>
                    Your account can be suspended or blacklisted if the return date is overdue. Please ensure all items are returned on time in the future to avoid penalties.
                </div>
            </div>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: #64748b;">
                Instrument returned exactly on time.
            </div>
        <?php endif; ?>

        <form action="" method="POST" style="margin-top: 30px;">
            <div style="display: flex; gap: 16px;">
                <a href="payment history.php" style="flex: 1; text-align: center; padding: 14px; background: #f1f5f9; color: #475569; border-radius: 12px; text-decoration: none; font-weight: 600;">Back</a>
                <button type="submit" name="confirm_return" style="flex: 2; padding: 14px; background: #7c3aed; color: white; border: none; border-radius: 12px; font-weight: 700;">Confirm Return</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
