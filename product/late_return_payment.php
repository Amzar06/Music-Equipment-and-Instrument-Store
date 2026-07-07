<?php
session_start();
include '../database.php';

if (!isset($_SESSION['cust_id'])) {
    header("Location: cust login.php");
    exit();
}

$cust_id   = $_SESSION['cust_id'];
$rental_id = isset($_GET['rental_id']) ? intval($_GET['rental_id']) : 0;

// Validate session data set by return_rental.php
if (
    $rental_id <= 0 ||
    !isset($_SESSION['late_return_rental_id']) ||
    $_SESSION['late_return_rental_id'] !== $rental_id ||
    !isset($_SESSION['late_return_amount'])
) {
    header("Location: payment history.php");
    exit();
}

$amount    = (float) $_SESSION['late_return_amount'];
$late_days = (int)   ($_SESSION['late_return_days'] ?? 0);

// ── POST: process payment ─────────────────────────────────────────────────────
$payment_error   = '';
$payment_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_now'])) {
    $cardholder = trim($_POST['cardholder'] ?? '');
    $card_no    = preg_replace('/\D/', '', $_POST['card_no'] ?? '');
    $expiry     = trim($_POST['expiry']  ?? '');
    $cvv        = trim($_POST['cvv']     ?? '');

    if (!$cardholder || strlen($card_no) < 13 || !$expiry || strlen($cvv) < 3) {
        $payment_error = "Please fill in all card details correctly.";
    } else {
        // Mark rental as Processing + items returned
        $upd = $conn->prepare("UPDATE rentals SET status = 'Processing' WHERE rental_id = ?");
        $upd->bind_param("i", $rental_id);
        $upd->execute();
        $upd->close();

        $upd2 = $conn->prepare("UPDATE rental_items SET return_status = 'Returned' WHERE rental_id = ?");
        $upd2->bind_param("i", $rental_id);
        $upd2->execute();
        $upd2->close();

        // Record extra payment in payments table
        $conn->prepare("
            INSERT INTO payments (cust_id, rental_id, amount, payment_method, payment_status)
            VALUES (?, ?, ?, 'Online Banking', 'Completed')
        ")->execute() ?: null;
        $pay_ins = $conn->prepare("
            INSERT INTO payments (cust_id, rental_id, amount, payment_method, payment_status)
            VALUES (?, ?, ?, 'Card', 'Completed')
        ");
        if ($pay_ins) {
            $pay_ins->bind_param("iid", $cust_id, $rental_id, $amount);
            $pay_ins->execute();
            $pay_ins->close();
        }

        // Clear session keys
        unset($_SESSION['late_return_rental_id'], $_SESSION['late_return_amount'], $_SESSION['late_return_days']);

        header("Location: payment history.php?return_success=rental");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pay Late Return Charges</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="style.css?v=5.0">
    <style>
        body { background: #f8fafc; }
        .pay-card {
            background: #fff;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            max-width: 560px;
            margin: 60px auto;
            border: 1px solid #f1f5f9;
        }
        .charge-banner {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 14px;
            padding: 22px;
            color: white;
            text-align: center;
            margin-bottom: 28px;
        }
        .charge-banner .amount { font-size: 2.4rem; font-weight: 900; }
        .charge-banner .label  { font-size: 0.9rem; opacity: .85; }

        .form-label { font-weight: 600; font-size: 0.9rem; color: #374151; margin-bottom: 4px; }
        .form-control {
            border-radius: 10px; border: 1.5px solid #e2e8f0;
            padding: 12px 14px; font-size: 0.95rem;
            transition: border-color .2s;
        }
        .form-control:focus { border-color: #7c3aed; box-shadow: none; outline: none; }

        .pay-btn {
            width: 100%; padding: 15px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white; font-size: 1rem; font-weight: 800; cursor: pointer;
            transition: opacity .2s;
        }
        .pay-btn:hover { opacity: .92; }
    </style>
</head>
<body>
<div class="container">
    <div class="pay-card">
        <h2 style="font-weight:800;color:#1e293b;margin-bottom:6px;">
            <i class="fa-solid fa-credit-card me-2" style="color:#ef4444;"></i>Late Return Payment
        </h2>
        <p style="color:#64748b;margin-bottom:24px;">
            Rental #<?php echo $rental_id; ?> &nbsp;·&nbsp;
            <?php echo $late_days; ?> overdue day(s)
        </p>

        <!-- Amount banner -->
        <div class="charge-banner">
            <div class="label">Total Late Return Charges Due</div>
            <div class="amount">RM <?php echo number_format($amount, 2); ?></div>
            <div class="label" style="margin-top:4px;">Includes 25% late penalty per overdue day</div>
        </div>

        <?php if ($payment_error): ?>
            <div class="alert alert-danger" style="border-radius:10px;font-size:.9rem;">
                <i class="fa-solid fa-circle-xmark me-1"></i><?php echo htmlspecialchars($payment_error); ?>
            </div>
        <?php endif; ?>

        <!-- Card form -->
        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Cardholder Name</label>
                <input type="text" name="cardholder" class="form-control" placeholder="e.g. Ahmad Razif" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Card Number</label>
                <input type="text" name="card_no" class="form-control" placeholder="XXXX XXXX XXXX XXXX" maxlength="19"
                       oninput="this.value=this.value.replace(/\D/g,'').replace(/(.{4})/g,'$1 ').trim()" required>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-6">
                    <label class="form-label">Expiry Date</label>
                    <input type="text" name="expiry" class="form-control" placeholder="MM/YY" maxlength="5"
                           oninput="if(this.value.length===2&&!this.value.includes('/'))this.value+='/'" required>
                </div>
                <div class="col-6">
                    <label class="form-label">CVV</label>
                    <input type="password" name="cvv" class="form-control" placeholder="•••" maxlength="4" required>
                </div>
            </div>
            <button type="submit" name="pay_now" class="pay-btn">
                <i class="fa-solid fa-lock me-2"></i>Pay RM <?php echo number_format($amount, 2); ?> Now
            </button>
        </form>

        <div style="text-align:center;margin-top:18px;">
            <a href="return_rental.php?rental_id=<?php echo $rental_id; ?>"
               style="color:#94a3b8;font-size:.85rem;text-decoration:none;">
                ← Go back
            </a>
        </div>
    </div>
</div>
</body>
</html>
