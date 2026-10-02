<?php
/**
 * CivilTrack - Wage Payouts Management
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();

// Filters
$filter_date = trim($_GET['date'] ?? '');
$filter_site_id = (int)($_GET['site_id'] ?? 0);
$filter_worker_id = (int)($_GET['worker_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');

$activeSites = get_all_sites($conn);
$activeWorkers = get_active_workers($conn);

// Summary Totals
$totals_res = mysqli_query($conn, "
    SELECT 
        COALESCE(SUM(amount), 0) as grand_total,
        COALESCE(SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END), 0) as pending_total,
        COALESCE(SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END), 0) as paid_total,
        COUNT(CASE WHEN payment_status = 'Pending' THEN 1 END) as pending_count,
        COUNT(CASE WHEN payment_status = 'Paid' THEN 1 END) as paid_count
    FROM payouts
");
$totals = mysqli_fetch_assoc($totals_res);

// Payouts List Query
$sql = "
    SELECT p.*, w.name as worker_name, w.worker_type, w.phone, s.site_name, a.work_date, a.status as attendance_status
    FROM payouts p
    JOIN workers w ON p.worker_id = w.id
    JOIN sites s ON p.site_id = s.id
    JOIN attendance a ON p.attendance_id = a.id
    WHERE 1=1
";

if (!empty($filter_date)) {
    $safe_date = mysqli_real_escape_string($conn, $filter_date);
    $sql .= " AND a.work_date = '$safe_date'";
}

if ($filter_site_id > 0) {
    $sql .= " AND p.site_id = $filter_site_id";
}

if ($filter_worker_id > 0) {
    $sql .= " AND p.worker_id = $filter_worker_id";
}

if (!empty($filter_status)) {
    $safe_status = mysqli_real_escape_string($conn, $filter_status);
    $sql .= " AND p.payment_status = '$safe_status'";
}

$sql .= " ORDER BY a.work_date DESC, p.id DESC";

$payouts_res = mysqli_query($conn, $sql);
$payouts = mysqli_fetch_all($payouts_res, MYSQLI_ASSOC);

$page_title = 'Wage Payouts';
include __DIR__ . '/includes/header.php';
?>

<!-- Payout KPI Summary Cards -->
<div class="stats-grid">
    <div class="stat-card card-orange">
        <div class="stat-icon icon-orange">
            <?php echo render_icon('dollar'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Total Pending Wages</div>
            <div class="stat-value"><?php echo format_currency($totals['pending_total']); ?></div>
            <div class="stat-sub"><?php echo $totals['pending_count']; ?> payouts due to workers</div>
        </div>
    </div>

    <div class="stat-card card-green">
        <div class="stat-icon icon-green">
            <?php echo render_icon('check'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Total Wages Paid</div>
            <div class="stat-value"><?php echo format_currency($totals['paid_total']); ?></div>
            <div class="stat-sub"><?php echo $totals['paid_count']; ?> payments cleared</div>
        </div>
    </div>

    <div class="stat-card card-blue">
        <div class="stat-icon icon-blue">
            <?php echo render_icon('payouts'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Total Workforce Earnings</div>
            <div class="stat-value"><?php echo format_currency($totals['grand_total']); ?></div>
            <div class="stat-sub">Cumulated labour liability</div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" class="filter-group" style="width: 100%; justify-content: space-between;">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <input type="date" name="date" class="form-control" style="padding: 6px 12px;" value="<?php echo e($filter_date); ?>" title="Filter by Work Date">

            <select name="site_id" class="filter-select">
                <option value="0">All Construction Sites</option>
                <?php foreach ($activeSites as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($filter_site_id == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo e($s['site_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="worker_id" class="filter-select">
                <option value="0">All Workers</option>
                <?php foreach ($activeWorkers as $w): ?>
                    <option value="<?php echo $w['id']; ?>" <?php echo ($filter_worker_id == $w['id']) ? 'selected' : ''; ?>>
                        <?php echo e($w['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Payment Statuses</option>
                <option value="Pending" <?php echo ($filter_status === 'Pending') ? 'selected' : ''; ?>>Pending Only</option>
                <option value="Paid" <?php echo ($filter_status === 'Paid') ? 'selected' : ''; ?>>Paid Only</option>
            </select>

            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            <?php if (!empty($filter_date) || $filter_site_id > 0 || $filter_worker_id > 0 || !empty($filter_status)): ?>
                <a href="payouts.php" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Payouts Table -->
<form method="POST" action="actions/payout_actions.php">
    <input type="hidden" name="action" value="batch_pay">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('payouts'); ?>
                Worker Daily Payouts (<?php echo count($payouts); ?> records)
            </div>
            <div>
                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Mark all selected pending payouts as Paid today?');">
                    <?php echo render_icon('check'); ?>
                    Mark Selected as Paid
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll(this)">
                            </th>
                            <th>Worker</th>
                            <th>Site Project</th>
                            <th>Work Date</th>
                            <th>Attendance</th>
                            <th>Wage Amount</th>
                            <th>Payment Status</th>
                            <th>Payment Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payouts)): ?>
                            <tr>
                                <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 36px;">
                                    No payouts matching current filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payouts as $p): ?>
                                <tr>
                                    <td style="text-align: center;">
                                        <?php if ($p['payment_status'] === 'Pending'): ?>
                                            <input type="checkbox" name="payout_ids[]" value="<?php echo $p['id']; ?>" class="payout-row-checkbox">
                                        <?php else: ?>
                                            <span style="color: #cbd5e1;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--primary);">
                                            <a href="worker_details.php?id=<?php echo $p['worker_id']; ?>" style="color: inherit; text-decoration: none;">
                                                <?php echo e($p['worker_name']); ?>
                                            </a>
                                        </div>
                                        <div style="font-size: 11.5px; color: var(--text-muted);">
                                            <?php echo e($p['worker_type']); ?> &bull; <?php echo e($p['phone']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="site_details.php?id=<?php echo $p['site_id']; ?>" style="color: var(--primary); text-decoration: none; font-size: 13px;">
                                            <?php echo e($p['site_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo format_date($p['work_date']); ?></td>
                                    <td>
                                        <?php if ($p['attendance_status'] === 'Present'): ?>
                                            <span class="badge badge-success">Present</span>
                                        <?php elseif ($p['attendance_status'] === 'Half Day'): ?>
                                            <span class="badge badge-warning">Half Day</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Absent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="font-weight: 700; color: var(--primary); font-size: 14.5px;">
                                            <?php echo format_currency($p['amount']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($p['payment_status'] === 'Paid'): ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo $p['payment_date'] ? format_date($p['payment_date']) : '<span style="color:var(--text-muted);">-</span>'; ?>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <?php if ($p['payment_status'] === 'Pending'): ?>
                                            <a href="actions/payout_actions.php?action=mark_paid&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary" title="Disburse Cash / Mark as Paid">
                                                <?php echo render_icon('check'); ?> Pay Now
                                            </a>
                                        <?php else: ?>
                                            <a href="actions/payout_actions.php?action=revert_pending&id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline" style="font-size: 11.5px; color: var(--text-muted);" title="Revert to Pending">
                                                Revert
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</form>

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.payout-row-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
