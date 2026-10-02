<?php
/**
 * CivilTrack - Worker Details & History
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();
$worker_id = (int)($_GET['id'] ?? 0);

if ($worker_id <= 0) {
    set_flash('error', 'Invalid worker specified.');
    header("Location: workers.php");
    exit;
}

// Fetch worker profile
$res = mysqli_query($conn, "SELECT * FROM workers WHERE id = $worker_id");
$worker = mysqli_fetch_assoc($res);

if (!$worker) {
    set_flash('error', 'Worker not found.');
    header("Location: workers.php");
    exit;
}

// Fetch Work & Attendance History
$res = mysqli_query($conn, "
    SELECT a.*, s.site_name, p.payment_status, p.payment_date, p.id as payout_id
    FROM attendance a
    JOIN sites s ON a.site_id = s.id
    LEFT JOIN payouts p ON p.attendance_id = a.id
    WHERE a.worker_id = $worker_id
    ORDER BY a.work_date DESC, a.id DESC
");
$attendanceHistory = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Financial Summary for this worker
$res = mysqli_query($conn, "
    SELECT 
        COUNT(CASE WHEN status IN ('Present', 'Half Day') THEN 1 END) as days_worked,
        COALESCE(SUM(calculated_wage), 0) as total_earned,
        (SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE worker_id = $worker_id AND payment_status = 'Paid') as total_paid,
        (SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE worker_id = $worker_id AND payment_status = 'Pending') as total_pending
    FROM attendance
    WHERE worker_id = $worker_id
");
$metrics = mysqli_fetch_assoc($res);

$daysWorked = $metrics['days_worked'] ?? 0;
$totalEarned = (float)($metrics['total_earned'] ?? 0);
$totalPaid = (float)($metrics['total_paid'] ?? 0);
$totalPending = (float)($metrics['total_pending'] ?? 0);

$page_title = $worker['name'] . ' - Profile';
include __DIR__ . '/includes/header.php';
?>

<!-- Header & Quick Navigation -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="workers.php" style="color: var(--text-muted); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 6px;">
            &larr; Back to All Workers
        </a>
        <h2 style="font-size: 24px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px;">
            <?php echo e($worker['name']); ?>
            <span class="badge badge-info"><?php echo e($worker['worker_type']); ?></span>
            <?php if ($worker['status'] === 'Active'): ?>
                <span class="badge badge-success">Active</span>
            <?php else: ?>
                <span class="badge badge-danger">Inactive</span>
            <?php endif; ?>
        </h2>
        <div style="font-size: 13.5px; color: var(--text-secondary); margin-top: 4px;">
            Worker ID: <strong>#WRK-<?php echo str_pad($worker['id'], 3, '0', STR_PAD_LEFT); ?></strong> &bull;
            Phone: <strong><?php echo e($worker['phone']); ?></strong> &bull;
            Address: <strong><?php echo e($worker['address'] ?: 'Not specified'); ?></strong>
        </div>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="attendance.php?worker_id=<?php echo $worker['id']; ?>" class="btn btn-primary">
            <?php echo render_icon('attendance'); ?>
            Log Attendance
        </a>
    </div>
</div>

<!-- Worker Metric Cards -->
<div class="stats-grid">
    <div class="stat-card card-blue">
        <div class="stat-icon icon-blue">
            <?php echo render_icon('calendar'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Days Worked</div>
            <div class="stat-value"><?php echo $daysWorked; ?></div>
            <div class="stat-sub">Present or Half-day shifts</div>
        </div>
    </div>

    <div class="stat-card card-purple">
        <div class="stat-icon icon-purple">
            <?php echo render_icon('payouts'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Daily Wage Rate</div>
            <div class="stat-value"><?php echo format_currency($worker['daily_wage']); ?></div>
            <div class="stat-sub">Standard 100% full day rate</div>
        </div>
    </div>

    <div class="stat-card card-green">
        <div class="stat-icon icon-green">
            <?php echo render_icon('check'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Total Amount Paid</div>
            <div class="stat-value"><?php echo format_currency($totalPaid); ?></div>
            <div class="stat-sub">Disbursed cash / payouts</div>
        </div>
    </div>

    <div class="stat-card card-orange">
        <div class="stat-icon icon-orange">
            <?php echo render_icon('dollar'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Pending Wage Due</div>
            <div class="stat-value"><?php echo format_currency($totalPending); ?></div>
            <div class="stat-sub">Awaiting payout settlement</div>
        </div>
    </div>
</div>

<!-- Work History & Payouts Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php echo render_icon('attendance'); ?>
            Work & Attendance History (<?php echo count($attendanceHistory); ?> Sessions)
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Work Date</th>
                        <th>Construction Site</th>
                        <th>Attendance</th>
                        <th>Calculated Wage</th>
                        <th>Payout Status</th>
                        <th>Payment Date</th>
                        <th>Work Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceHistory)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                No work sessions recorded yet for this worker.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendanceHistory as $rec): ?>
                            <tr>
                                <td><strong><?php echo format_date($rec['work_date']); ?></strong></td>
                                <td>
                                    <a href="site_details.php?id=<?php echo $rec['site_id']; ?>" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                                        <?php echo e($rec['site_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($rec['status'] === 'Present'): ?>
                                        <span class="badge badge-success">Present (100%)</span>
                                    <?php elseif ($rec['status'] === 'Half Day'): ?>
                                        <span class="badge badge-warning">Half Day (50%)</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Absent (0%)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight: 700; color: var(--primary);">
                                    <?php echo format_currency($rec['calculated_wage']); ?>
                                </td>
                                <td>
                                    <?php if ($rec['payment_status'] === 'Paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $rec['payment_date'] ? format_date($rec['payment_date']) : '<span style="color:var(--text-muted);">-</span>'; ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;">
                                    <?php echo e($rec['notes'] ?: '-'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
