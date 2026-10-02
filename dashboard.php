<?php
/**
 * CivilTrack - Main Dashboard
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();
$today = date('Y-m-d');

// KPI summary metrics
// Active Sites count
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM sites WHERE status = 'Active'");
$activeSitesCount = mysqli_fetch_assoc($res)['cnt'] ?? 0;

// Workers working today (Present or Half Day)
$safeToday = mysqli_real_escape_string($conn, $today);
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM attendance WHERE work_date = '$safeToday' AND status IN ('Present', 'Half Day')");
$workersTodayCount = mysqli_fetch_assoc($res)['cnt'] ?? 0;

// Pending payouts total amount & count
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt, COALESCE(SUM(amount), 0) as total FROM payouts WHERE payment_status = 'Pending'");
$pendingPayoutsData = mysqli_fetch_assoc($res);
$pendingPayoutsAmount = $pendingPayoutsData['total'] ?? 0;
$pendingPayoutsCount = $pendingPayoutsData['cnt'] ?? 0;

// Total Material Expenses
$res = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM materials");
$materialExpensesTotal = mysqli_fetch_assoc($res)['total'] ?? 0;

// Total Labour Expenses (all payouts amount)
$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payouts");
$labourExpensesTotal = mysqli_fetch_assoc($res)['total'] ?? 0;

// Total Other Expenses
$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
$otherExpensesTotal = mysqli_fetch_assoc($res)['total'] ?? 0;

$grandTotalExpenses = $labourExpensesTotal + $materialExpensesTotal + $otherExpensesTotal;

// Active sites list with today's attendance count
$res = mysqli_query($conn, "
    SELECT s.*, 
        (SELECT COUNT(*) FROM attendance a WHERE a.site_id = s.id AND a.work_date = '$safeToday' AND a.status IN ('Present', 'Half Day')) as workers_today,
        (SELECT COALESCE(SUM(m.total_amount), 0) FROM materials m WHERE m.site_id = s.id) as site_materials,
        (SELECT COALESCE(SUM(p.amount), 0) FROM payouts p WHERE p.site_id = s.id) as site_labour
    FROM sites s 
    WHERE s.status = 'Active' 
    ORDER BY s.id DESC
");
$activeSites = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Recent site activity feed
$recentActivities = [];

// Recent Attendance
$res = mysqli_query($conn, "
    SELECT 'attendance' as act_type, a.created_at, CONCAT('Attendance marked for ', w.name, ' (', a.status, ')') as description, s.site_name, a.calculated_wage as amount
    FROM attendance a
    JOIN workers w ON a.worker_id = w.id
    JOIN sites s ON a.site_id = s.id
    ORDER BY a.created_at DESC LIMIT 4
");
$recentAttendance = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Recent Materials
$res = mysqli_query($conn, "
    SELECT 'material' as act_type, m.created_at, CONCAT('Purchased ', m.material_name, ' (', m.quantity, ' ', m.unit, ')') as description, s.site_name, m.total_amount as amount
    FROM materials m
    JOIN sites s ON m.site_id = s.id
    ORDER BY m.created_at DESC LIMIT 4
");
$recentMaterials = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Recent Workers
$res = mysqli_query($conn, "
    SELECT 'worker' as act_type, created_at, CONCAT('New worker registered: ', name, ' (', worker_type, ')') as description, 'Workforce' as site_name, daily_wage as amount
    FROM workers
    ORDER BY created_at DESC LIMIT 3
");
$recentWorkers = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Recent Payouts marked as Paid
$res = mysqli_query($conn, "
    SELECT 'payout' as act_type, p.created_at, CONCAT('Payout recorded for ', w.name, ' - ', p.payment_status) as description, s.site_name, p.amount
    FROM payouts p
    JOIN workers w ON p.worker_id = w.id
    JOIN sites s ON p.site_id = s.id
    ORDER BY p.created_at DESC LIMIT 3
");
$recentPayouts = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Merge & sort recent activities
$recentActivities = array_merge($recentAttendance, $recentMaterials, $recentWorkers, $recentPayouts);
usort($recentActivities, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentActivities = array_slice($recentActivities, 0, 6);

$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>

<!-- Quick Action Shortcuts -->
<div style="display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap;">
    <a href="attendance.php" class="btn btn-primary">
        <?php echo render_icon('attendance'); ?>
        Record Daily Work
    </a>
    <a href="materials.php" class="btn btn-secondary">
        <?php echo render_icon('plus'); ?>
        Purchase Material
    </a>
    <a href="workers.php" class="btn btn-outline">
        <?php echo render_icon('workers'); ?>
        Register Worker
    </a>
    <a href="sites.php" class="btn btn-outline">
        <?php echo render_icon('sites'); ?>
        Create Site
    </a>
</div>

<!-- KPI Cards -->
<div class="stats-grid">
    <div class="stat-card card-blue">
        <div class="stat-icon icon-blue">
            <?php echo render_icon('sites'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Active Sites</div>
            <div class="stat-value"><?php echo $activeSitesCount; ?></div>
            <div class="stat-sub">Currently undergoing construction</div>
        </div>
    </div>

    <div class="stat-card card-green">
        <div class="stat-icon icon-green">
            <?php echo render_icon('workers'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Workers On Site Today</div>
            <div class="stat-value"><?php echo $workersTodayCount; ?></div>
            <div class="stat-sub"><?php echo date('d M Y'); ?> (Present / Half Day)</div>
        </div>
    </div>

    <div class="stat-card card-orange">
        <div class="stat-icon icon-orange">
            <?php echo render_icon('payouts'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Pending Payouts</div>
            <div class="stat-value"><?php echo format_currency($pendingPayoutsAmount); ?></div>
            <div class="stat-sub"><?php echo $pendingPayoutsCount; ?> wage payouts awaiting payment</div>
        </div>
    </div>

    <div class="stat-card card-purple">
        <div class="stat-icon icon-purple">
            <?php echo render_icon('materials'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Material Expenses</div>
            <div class="stat-value"><?php echo format_currency($materialExpensesTotal); ?></div>
            <div class="stat-sub">Across all site purchases</div>
        </div>
    </div>
</div>

<!-- Main Dashboard Grid -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
    <!-- Left Column: Active Construction Sites -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <?php echo render_icon('sites'); ?>
                    Active Construction Sites
                </div>
                <a href="sites.php" class="btn btn-sm btn-outline">View All Sites</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Site Details</th>
                                <th>Location</th>
                                <th>Workers Today</th>
                                <th>Status</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activeSites)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                        No active construction sites found. <a href="sites.php">Create your first site</a>.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($activeSites as $site): ?>
                                    <tr>
                                        <td>
                                            <div style="font-weight: 600; color: var(--primary);">
                                                <a href="site_details.php?id=<?php echo $site['id']; ?>" style="color: inherit; text-decoration: none;">
                                                    <?php echo e($site['site_name']); ?>
                                                </a>
                                            </div>
                                            <div style="font-size: 12px; color: var(--text-muted);">
                                                Client: <?php echo e($site['client_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div style="font-size: 13px; color: var(--text-secondary);">
                                                <?php echo e($site['location']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-info">
                                                <?php echo $site['workers_today']; ?> workers
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success">
                                                <?php echo e($site['status']); ?>
                                            </span>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="site_details.php?id=<?php echo $site['id']; ?>" class="btn btn-sm btn-outline" title="View Site Details">
                                                <?php echo render_icon('eye'); ?> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Expense Distribution Overview -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <?php echo render_icon('expenses'); ?>
                    Overall Expense Breakdown
                </div>
                <div style="font-size: 14px; font-weight: 700; color: var(--primary);">
                    Total: <?php echo format_currency($grandTotalExpenses); ?>
                </div>
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 150px; background: #f8fafc; padding: 14px; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Labour (Payouts)</div>
                        <div style="font-size: 17px; font-weight: 700; color: var(--primary);"><?php echo format_currency($labourExpensesTotal); ?></div>
                    </div>
                    <div style="flex: 1; min-width: 150px; background: #f8fafc; padding: 14px; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Materials</div>
                        <div style="font-size: 17px; font-weight: 700; color: var(--primary);"><?php echo format_currency($materialExpensesTotal); ?></div>
                    </div>
                    <div style="flex: 1; min-width: 150px; background: #f8fafc; padding: 14px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                        <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Other Site Expenses</div>
                        <div style="font-size: 17px; font-weight: 700; color: var(--primary);"><?php echo format_currency($otherExpensesTotal); ?></div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <a href="admin/expenses.php" class="btn btn-sm btn-outline">Go to Expenses Manager &rarr;</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Recent Activity Timeline -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <?php echo render_icon('dashboard'); ?>
                    Recent Activity
                </div>
            </div>
            <div class="card-body" style="padding: 16px 20px;">
                <?php if (empty($recentActivities)): ?>
                    <p style="color: var(--text-muted); font-size: 13.5px; text-align: center; padding: 20px;">
                        No recent site activity logged.
                    </p>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <?php foreach ($recentActivities as $act): ?>
                            <div style="display: flex; align-items: flex-start; gap: 12px; font-size: 13.5px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                                <div style="margin-top: 2px;">
                                    <?php if ($act['act_type'] === 'attendance'): ?>
                                        <span class="badge badge-success" style="padding: 4px; border-radius: 6px;"><?php echo render_icon('attendance'); ?></span>
                                    <?php elseif ($act['act_type'] === 'material'): ?>
                                        <span class="badge badge-warning" style="padding: 4px; border-radius: 6px;"><?php echo render_icon('materials'); ?></span>
                                    <?php elseif ($act['act_type'] === 'payout'): ?>
                                        <span class="badge badge-info" style="padding: 4px; border-radius: 6px;"><?php echo render_icon('payouts'); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral" style="padding: 4px; border-radius: 6px;"><?php echo render_icon('workers'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: var(--text-primary); line-height: 1.3;">
                                        <?php echo e($act['description']); ?>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-top: 3px; font-size: 12px; color: var(--text-muted);">
                                        <span><?php echo e($act['site_name']); ?></span>
                                        <span><?php echo date('d M, h:i A', strtotime($act['created_at'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
