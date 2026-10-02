<?php
/**
 * CivilTrack - Attendance & Daily Work Actions
 * Automatically calculates wages and creates/updates linked payout records
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Record single attendance entry
if ($action === 'record_single') {
    $worker_id = (int)($_POST['worker_id'] ?? 0);
    $site_id = (int)($_POST['site_id'] ?? 0);
    $work_date = trim($_POST['work_date'] ?? date('Y-m-d'));
    $status = trim($_POST['status'] ?? 'Present');
    $notes = trim($_POST['notes'] ?? '');

    if ($worker_id <= 0 || $site_id <= 0 || empty($work_date)) {
        set_flash('error', 'Please select a worker, construction site, and valid work date.');
        header("Location: ../attendance.php");
        exit;
    }

    // Get worker's daily wage
    $w_res = mysqli_query($conn, "SELECT daily_wage, name FROM workers WHERE id = $worker_id");
    $worker = mysqli_fetch_assoc($w_res);

    if (!$worker) {
        set_flash('error', 'Selected worker was not found.');
        header("Location: ../attendance.php");
        exit;
    }

    $daily_wage = (float)$worker['daily_wage'];
    $calculated_wage = 0.00;

    if ($status === 'Present') {
        $calculated_wage = $daily_wage;
    } elseif ($status === 'Half Day') {
        $calculated_wage = round($daily_wage / 2, 2);
    } elseif ($status === 'Absent') {
        $calculated_wage = 0.00;
    }

    $safe_date = mysqli_real_escape_string($conn, $work_date);
    $safe_status = mysqli_real_escape_string($conn, $status);
    $safe_notes = mysqli_real_escape_string($conn, $notes);

    mysqli_begin_transaction($conn);
    try {
        // Check if attendance record already exists for this worker, site, and date
        $check_res = mysqli_query($conn, "SELECT id FROM attendance WHERE worker_id = $worker_id AND site_id = $site_id AND work_date = '$safe_date'");
        $existing = mysqli_fetch_assoc($check_res);

        if ($existing) {
            $att_id = (int)$existing['id'];
            // Update attendance record
            mysqli_query($conn, "UPDATE attendance SET status = '$safe_status', calculated_wage = $calculated_wage, notes = '$safe_notes' WHERE id = $att_id");

            // Update linked payout amount
            mysqli_query($conn, "UPDATE payouts SET amount = $calculated_wage WHERE attendance_id = $att_id");

            set_flash('info', "Attendance for {$worker['name']} on {$work_date} was updated to {$status} (₹{$calculated_wage}).");
        } else {
            // Insert new attendance record
            $sql = "INSERT INTO attendance (worker_id, site_id, work_date, status, calculated_wage, notes) 
                    VALUES ($worker_id, $site_id, '$safe_date', '$safe_status', $calculated_wage, '$safe_notes')";
            mysqli_query($conn, $sql);
            $att_id = mysqli_insert_id($conn);

            // Auto-create linked payout record
            $payment_status = ($status === 'Absent') ? 'Paid' : 'Pending';
            $payment_date = ($status === 'Absent') ? "'$safe_date'" : "NULL";

            $payout_sql = "INSERT INTO payouts (worker_id, site_id, attendance_id, amount, payment_status, payment_date, notes) 
                           VALUES ($worker_id, $site_id, $att_id, $calculated_wage, '$payment_status', $payment_date, '$safe_notes')";
            mysqli_query($conn, $payout_sql);

            set_flash('success', "Attendance recorded for {$worker['name']}: {$status} - Wage: ₹{$calculated_wage}.");
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_flash('error', 'Error recording attendance: ' . $e->getMessage());
    }

    header("Location: ../attendance.php?date=" . urlencode($work_date) . "&site_id=" . $site_id);
    exit;
}

// Record batch attendance for site crew
if ($action === 'record_batch') {
    $site_id = (int)($_POST['site_id'] ?? 0);
    $work_date = trim($_POST['work_date'] ?? date('Y-m-d'));
    $statuses = $_POST['worker_status'] ?? []; // worker_id => status

    if ($site_id <= 0 || empty($work_date) || empty($statuses)) {
        set_flash('error', 'Please select a site, date, and workers to record batch attendance.');
        header("Location: ../attendance.php");
        exit;
    }

    $safe_date = mysqli_real_escape_string($conn, $work_date);

    mysqli_begin_transaction($conn);
    try {
        $recordedCount = 0;
        foreach ($statuses as $worker_id => $status) {
            $worker_id = (int)$worker_id;
            if ($worker_id <= 0) continue;

            $status = trim($status);
            $safe_status = mysqli_real_escape_string($conn, $status);

            // Fetch wage
            $w_res = mysqli_query($conn, "SELECT daily_wage FROM workers WHERE id = $worker_id");
            $wData = mysqli_fetch_assoc($w_res);
            if (!$wData) continue;

            $daily_wage = (float)$wData['daily_wage'];
            $calculated_wage = 0.00;
            if ($status === 'Present') {
                $calculated_wage = $daily_wage;
            } elseif ($status === 'Half Day') {
                $calculated_wage = round($daily_wage / 2, 2);
            } elseif ($status === 'Absent') {
                $calculated_wage = 0.00;
            }

            // Check existing
            $c_res = mysqli_query($conn, "SELECT id FROM attendance WHERE worker_id = $worker_id AND site_id = $site_id AND work_date = '$safe_date'");
            $exist = mysqli_fetch_assoc($c_res);

            if ($exist) {
                $att_id = (int)$exist['id'];
                mysqli_query($conn, "UPDATE attendance SET status = '$safe_status', calculated_wage = $calculated_wage WHERE id = $att_id");
                mysqli_query($conn, "UPDATE payouts SET amount = $calculated_wage WHERE attendance_id = $att_id");
            } else {
                mysqli_query($conn, "INSERT INTO attendance (worker_id, site_id, work_date, status, calculated_wage) VALUES ($worker_id, $site_id, '$safe_date', '$safe_status', $calculated_wage)");
                $att_id = mysqli_insert_id($conn);

                $payment_status = ($status === 'Absent') ? 'Paid' : 'Pending';
                $payment_date = ($status === 'Absent') ? "'$safe_date'" : "NULL";
                mysqli_query($conn, "INSERT INTO payouts (worker_id, site_id, attendance_id, amount, payment_status, payment_date) VALUES ($worker_id, $site_id, $att_id, $calculated_wage, '$payment_status', $payment_date)");
            }
            $recordedCount++;
        }

        mysqli_commit($conn);
        set_flash('success', "Batch attendance successfully saved for {$recordedCount} workers on {$work_date}.");
    } catch (Exception $e) {
        mysqli_rollback($conn);
        set_flash('error', 'Error in batch attendance: ' . $e->getMessage());
    }

    header("Location: ../attendance.php?date=" . urlencode($work_date) . "&site_id=" . $site_id);
    exit;
}

// Delete attendance record
if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM attendance WHERE id = $id");
        set_flash('success', 'Attendance record removed successfully.');
    }
    header("Location: ../attendance.php");
    exit;
}

header("Location: ../attendance.php");
exit;
