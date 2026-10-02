<?php
/**
 * CivilTrack - Payout Management Actions
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$today = date('Y-m-d');

// Mark single payout as Paid
if ($action === 'mark_paid') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    $pay_date = trim($_POST['payment_date'] ?? $today);

    if ($id > 0) {
        $safe_date = mysqli_real_escape_string($conn, $pay_date);
        mysqli_query($conn, "UPDATE payouts SET payment_status = 'Paid', payment_date = '$safe_date' WHERE id = $id");
        set_flash('success', 'Wage payout marked as Paid successfully.');
    }

    $redirect = $_SERVER['HTTP_REFERER'] ?? '../payouts.php';
    header("Location: " . $redirect);
    exit;
}

// Revert payout back to Pending
if ($action === 'revert_pending') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($id > 0) {
        mysqli_query($conn, "UPDATE payouts SET payment_status = 'Pending', payment_date = NULL WHERE id = $id");
        set_flash('info', 'Payout status reverted to Pending.');
    }

    $redirect = $_SERVER['HTTP_REFERER'] ?? '../payouts.php';
    header("Location: " . $redirect);
    exit;
}

// Mark multiple payouts as paid
if ($action === 'batch_pay') {
    $payout_ids = $_POST['payout_ids'] ?? [];
    if (!empty($payout_ids) && is_array($payout_ids)) {
        $ids = array_map('intval', $payout_ids);
        $ids = array_filter($ids, function($v) { return $v > 0; });
        if (!empty($ids)) {
            $idList = implode(',', $ids);
            mysqli_query($conn, "UPDATE payouts SET payment_status = 'Paid', payment_date = '$today' WHERE id IN ($idList)");
            set_flash('success', count($ids) . ' payouts marked as Paid.');
        }
    }
    header("Location: ../payouts.php");
    exit;
}

header("Location: ../payouts.php");
exit;
