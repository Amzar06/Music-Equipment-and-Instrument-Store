<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}

$cust_id  = $_SESSION['cust_id'];
$rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;

if ($rental_id <= 0) {
    header("Location: payment history.php");
    exit();
}

// Fetch rental header
$qr = $conn->prepare("SELECT * FROM rentals WHERE rental_id = ? AND cust_id = ?");
$qr->bind_param("ii", $rental_id, $cust_id);
$qr->execute();
$rental = $qr->get_result()->fetch_assoc();
$qr->close();

if (!$rental) {
    die("Rental not found.");
}

// Fetch all rental items with their actual daily rates
$qi = $conn->prepare("
    SELECT ri.rental_qty, ri.rental_rate, ri.start_date, ri.end_date,
           p.prod_name, p.prod_image
    FROM rental_items ri
    JOIN products p ON ri.prod_id = p.prod_id
    WHERE ri.rental_id = ?
");
$qi->bind_param("i", $rental_id);
$qi->execute();
$rental_items = $qi->get_result()->fetch_all(MYSQLI_ASSOC);
$qi->close();

$today     = date('Y-m-d');
$today_ts  = strtotime($today);

// Compute total daily rate from actual rental_items rates
// daily_rate_total = sum(qty × rate_per_day) across all items
$total_daily_rate = 0;
foreach ($rental_items as $item) {
    $total_daily_rate += $item['rental_qty'] * $item['rental_rate'];
}

// Use the rental-level start/end dates for overall period comparison
$end_date   = $rental['end_date'];
$start_date = $rental['start_date'];
$start_ts   = strtotime($start_date);
$end_ts     = strtotime($end_date);

// Days between scheduled end and today (positive = late, negative = early)
$days_diff = (int)round(($today_ts - $end_ts) / 86400);

$adjustment_type   = 'none';
$adjustment_amount = 0;
$late_days         = 0;
$early_days        = 0;

if ($today_ts > $end_ts) {
    // ── LATE RETURN: returned after the scheduled end date ────────────────────
    $adjustment_type   = 'extra_charge';
    $late_days         = (int) round(($today_ts - $end_ts) / 86400);
    $adjustment_amount = $late_days * $total_daily_rate * 1.25; // 25% penalty

} elseif ($today_ts < $start_ts) {
    // ── PRE-START RETURN: rental period hasn't begun yet — full refund ─────────
    $adjustment_type   = 'refund';
    $original_days     = max(1, (int) round(($end_ts - $start_ts) / 86400));
    $early_days        = $original_days;                   // all booked days unused
    $adjustment_amount = $rental['total_amount'];          // full amount paid back

} elseif ($today_ts < $end_ts) {
    // ── EARLY RETURN: returned during the rental period ───────────────────────
    $adjustment_type   = 'refund';
    $early_days        = (int) round(($end_ts - $today_ts) / 86400); // remaining days only
    $adjustment_amount = $early_days * $total_daily_rate;

} // else: today === end_ts → returned exactly on time, adjustment_type stays 'none'

// ── POST: Confirm Return ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_return'])) {
    if ($adjustment_type === 'extra_charge') {
        // Store the extra amount in session and redirect to a dedicated extra-charge payment page
        $_SESSION['late_return_rental_id']  = $rental_id;
        $_SESSION['late_return_amount']     = $adjustment_amount;
        $_SESSION['late_return_days']       = $late_days;
        header("Location: late_return_payment.php?rental_id=$rental_id");
        exit();
    }

    // On-time or early return — set status to Processing (awaiting admin approval)
    $upd = $conn->prepare("UPDATE rentals SET status = 'Processing' WHERE rental_id = ?");
    $upd->bind_param("i", $rental_id);
    if ($upd->execute()) {
        $upd_items = $conn->prepare("UPDATE rental_items SET return_status = 'Returned' WHERE rental_id = ?");
        $upd_items->bind_param("i", $rental_id);
        $upd_items->execute();
        $upd_items->close();
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { background: #f8fafc; color: #1e293b; }
        .return-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            max-width: 660px;
            margin: 60px auto;
            border: 1px solid #f1f5f9;
        }
        .summary-row {
            display: flex; justify-content: space-between;
            padding: 12px 0; border-bottom: 1px solid #f1f5f9;
        }
        .summary-label { color: #64748b; font-weight: 500; }
        .summary-value  { font-weight: 700; color: #1e293b; }

        /* items table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 0.88rem; }
        .items-table th { background: #f8fafc; color: #64748b; font-weight: 600; padding: 8px 10px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .items-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
        .items-table img { width: 34px; height: 34px; object-fit: cover; border-radius: 6px; }

        /* charge / refund boxes */
        .charge-box {
            padding: 22px; border-radius: 14px; margin-top: 22px; text-align: center;
        }
        .charge-box .amount { font-size: 2.2rem; font-weight: 800; margin: 6px 0; }
        .charge-box .note   { font-size: 0.8rem; margin: 0; }

        .box-late    { background: #fff1f2; }
        .box-early   { background: #f0fdf4; }
        .box-ontime  { background: #f8fafc; }

        /* contact / warning strip */
        .info-strip {
            border-radius: 12px; padding: 16px 20px;
            display: flex; gap: 14px; align-items: flex-start;
            font-size: 0.88rem; margin-top: 18px;
        }
        .info-strip.warning { background: #fff7ed; border: 1px solid #fed7aa; color: #9a3412; }
        .info-strip.info    { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }
    </style>
</head>
<body>
<div class="container">
    <div class="return-card">
        <h2 style="font-weight:800;color:#1e293b;margin-bottom:6px;">Return Instrument</h2>
        <p style="color:#64748b;margin-bottom:28px;">
            Rental #<?php echo $rental_id; ?> &nbsp;·&nbsp;
            <?php echo date('d M Y', $start_ts); ?> — <?php echo date('d M Y', $end_ts); ?>
        </p>

        <!-- Rental Items -->
        <div style="font-weight:700;font-size:0.8rem;text-transform:uppercase;color:#94a3b8;margin-bottom:6px;letter-spacing:.04em;">
            Items in this rental
        </div>
        <table class="items-table">
            <thead>
                <tr>
                    <th colspan="2">Product</th>
                    <th>Qty</th>
                    <th>Rate/day</th>
                    <th>Period</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rental_items as $item): ?>
                <tr>
                    <td><img src="../uploads/<?php echo htmlspecialchars($item['prod_image'] ?: 'default.jpg'); ?>"></td>
                    <td><?php echo htmlspecialchars($item['prod_name']); ?></td>
                    <td><?php echo $item['rental_qty']; ?></td>
                    <td>RM <?php echo number_format($item['rental_rate'], 2); ?></td>
                    <td style="white-space:nowrap;font-size:0.8rem;color:#64748b;">
                        <?php echo date('d M', strtotime($item['start_date'])); ?>
                        —
                        <?php echo date('d M Y', strtotime($item['end_date'])); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Today's return date -->
        <div class="summary-row" style="margin-top:16px;">
            <span class="summary-label">Returning Today</span>
            <span class="summary-value"><?php echo date('d M Y'); ?></span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Daily Rate (total across items)</span>
            <span class="summary-value">RM <?php echo number_format($total_daily_rate, 2); ?> / day</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Amount Paid</span>
            <span class="summary-value">RM <?php echo number_format($rental['total_amount'], 2); ?></span>
        </div>

        <!-- Adjustment panel -->
        <?php if ($adjustment_type === 'extra_charge'): ?>
            <div class="charge-box box-late">
                <div style="color:#991b1b;font-size:0.9rem;font-weight:700;">⚠️ Late Return — Extra Charges Apply</div>
                <div class="amount" style="color:#ef4444;">RM <?php echo number_format($adjustment_amount, 2); ?></div>
                <p class="note" style="color:#b91c1c;">
                    <?php echo $late_days; ?> overdue day(s) × RM <?php echo number_format($total_daily_rate, 2); ?>/day × 1.25 penalty
                </p>
            </div>
            <div class="info-strip warning">
                <span style="font-size:1.4rem;">⚠️</span>
                <div>
                    <strong>Payment Required:</strong><br>
                    Clicking <em>Confirm Return</em> will take you to a payment page to settle the late return charges before your rental is closed.
                    Your account may be suspended if charges are not paid.
                </div>
            </div>

        <?php elseif ($adjustment_type === 'refund'): ?>
            <div class="charge-box box-early">
                <div style="color:#166534;font-size:0.9rem;font-weight:700;">✅ Early Return</div>
                <div class="amount" style="color:#10b981;">RM <?php echo number_format($adjustment_amount, 2); ?></div>
                <p class="note" style="color:#15803d;">
                    <?php echo $early_days; ?> unused day(s) × RM <?php echo number_format($total_daily_rate, 2); ?>/day
                </p>
            </div>
            <div class="info-strip info">
                <i class="fa-solid fa-circle-info" style="margin-top:2px;font-size:1.1rem;"></i>
                <div>
                    <strong>Want a refund for the unused days?</strong><br>
                    Please contact our customer care team and we will process your refund manually.<br>
                    <a href="tel:+60198853782" style="font-weight:700;color:#1d4ed8;text-decoration:none;">
                        <i class="fa-solid fa-phone me-1"></i>+60 19-885 3782
                    </a>
                </div>
            </div>

        <?php else: ?>
            <div class="charge-box box-ontime">
                <div style="color:#475569;font-size:0.9rem;font-weight:700;">✔ Returned exactly on time — no adjustments needed.</div>
            </div>
        <?php endif; ?>

        <!-- Action buttons -->
        <form action="" method="POST" style="margin-top:30px;">
            <div style="display:flex;gap:16px;">
                <a href="payment history.php"
                   style="flex:1;text-align:center;padding:14px;background:#f1f5f9;color:#475569;border-radius:12px;text-decoration:none;font-weight:600;">
                    Back
                </a>
                <button type="submit" name="confirm_return"
                        style="flex:2;padding:14px;background:<?php echo $adjustment_type==='extra_charge' ? '#ef4444' : '#7c3aed'; ?>;
                               color:white;border:none;border-radius:12px;font-weight:700;">
                    <?php echo $adjustment_type==='extra_charge' ? 'Confirm & Pay Late Charges' : 'Confirm Return'; ?>
                </button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
