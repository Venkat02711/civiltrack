<?php
/**
 * CivilTrack - Site Details & Financial Rollup
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();
$site_id = (int)($_GET['id'] ?? 0);

if ($site_id <= 0) {
    set_flash('error', 'Invalid site specified.');
    header("Location: sites.php");
    exit;
}

// Fetch site details
$res = mysqli_query($conn, "SELECT * FROM sites WHERE id = $site_id");
$site = mysqli_fetch_assoc($res);

if (!$site) {
    set_flash('error', 'Site not found.');
    header("Location: sites.php");
    exit;
}

// Site expense rollups
$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total_labour, COUNT(*) as payout_count FROM payouts WHERE site_id = $site_id");
$labourData = mysqli_fetch_assoc($res);
$totalLabour = (float)$labourData['total_labour'];

// Materials cost
$res = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total_material, COUNT(*) as material_count FROM materials WHERE site_id = $site_id");
$materialData = mysqli_fetch_assoc($res);
$totalMaterial = (float)$materialData['total_material'];

// Miscellaneous expenses
$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total_other, COUNT(*) as expense_count FROM expenses WHERE site_id = $site_id");
$otherData = mysqli_fetch_assoc($res);
$totalOther = (float)$otherData['total_other'];

$grandTotal = $totalLabour + $totalMaterial + $totalOther;

// Recent site attendance
$res = mysqli_query($conn, "
    SELECT a.*, w.name as worker_name, w.worker_type, w.daily_wage, p.payment_status, p.id as payout_id
    FROM attendance a
    JOIN workers w ON a.worker_id = w.id
    LEFT JOIN payouts p ON p.attendance_id = a.id
    WHERE a.site_id = $site_id
    ORDER BY a.work_date DESC, a.id DESC
    LIMIT 10
");
$recentAttendance = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Material purchases
$res = mysqli_query($conn, "
    SELECT * FROM materials WHERE site_id = $site_id ORDER BY purchase_date DESC, id DESC LIMIT 10
");
$recentMaterials = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Other site expenses
$res = mysqli_query($conn, "
    SELECT * FROM expenses WHERE site_id = $site_id ORDER BY expense_date DESC, id DESC LIMIT 10
");
$recentExpenses = mysqli_fetch_all($res, MYSQLI_ASSOC);

$page_title = $site['site_name'];
include __DIR__ . '/includes/header.php';
?>

<!-- Header & Quick Breadcrumb -->
<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="sites.php" style="color: var(--text-muted); text-decoration: none; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; margin-bottom: 6px;">
            &larr; Back to All Sites
        </a>
        <h2 style="font-size: 24px; font-weight: 700; color: var(--primary); display: flex; align-items: center; gap: 10px;">
            <?php echo e($site['site_name']); ?>
            <?php if ($site['status'] === 'Active'): ?>
                <span class="badge badge-success">Active</span>
            <?php elseif ($site['status'] === 'On Hold'): ?>
                <span class="badge badge-warning">On Hold</span>
            <?php else: ?>
                <span class="badge badge-neutral">Completed</span>
            <?php endif; ?>
        </h2>
        <div style="font-size: 13.5px; color: var(--text-secondary); margin-top: 4px;">
            Client: <strong><?php echo e($site['client_name'] ?: 'Not Specified'); ?></strong> &bull; Location: <strong><?php echo e($site['location']); ?></strong>
        </div>
    </div>

    <div style="display: flex; gap: 10px;">
        <a href="attendance.php?site_id=<?php echo $site['id']; ?>" class="btn btn-primary">
            <?php echo render_icon('attendance'); ?>
            Log Attendance
        </a>
        <a href="materials.php?site_id=<?php echo $site['id']; ?>" class="btn btn-secondary">
            <?php echo render_icon('materials'); ?>
            Add Material
        </a>
    </div>
</div>

<!-- Site Financial Summary Cards -->
<div class="stats-grid">
    <div class="stat-card card-green">
        <div class="stat-icon icon-green">
            <?php echo render_icon('workers'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Labour Expense</div>
            <div class="stat-value"><?php echo format_currency($totalLabour); ?></div>
            <div class="stat-sub">From <?php echo $labourData['payout_count']; ?> worker work sessions</div>
        </div>
    </div>

    <div class="stat-card card-orange">
        <div class="stat-icon icon-orange">
            <?php echo render_icon('materials'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Material Expense</div>
            <div class="stat-value"><?php echo format_currency($totalMaterial); ?></div>
            <div class="stat-sub">From <?php echo $materialData['material_count']; ?> material bills</div>
        </div>
    </div>

    <div class="stat-card card-blue">
        <div class="stat-icon icon-blue">
            <?php echo render_icon('expenses'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Other Expenses</div>
            <div class="stat-value"><?php echo format_currency($totalOther); ?></div>
            <div class="stat-sub">Machinery, transport & misc</div>
        </div>
    </div>

    <div class="stat-card card-purple">
        <div class="stat-icon icon-purple">
            <?php echo render_icon('briefcase'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Total Site Cost</div>
            <div class="stat-value"><?php echo format_currency($grandTotal); ?></div>
            <div class="stat-sub">Comprehensive site spend</div>
        </div>
    </div>
</div>

<!-- Details & Breakdown Tables -->
<div style="display: grid; grid-template-columns: 1fr; gap: 24px;">
    <!-- Recent Attendance on Site -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('attendance'); ?>
                Recent Daily Work & Attendance (Last 10 Records)
            </div>
            <a href="attendance.php?site_id=<?php echo $site['id']; ?>" class="btn btn-outline btn-sm">View Full Log</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Work Date</th>
                            <th>Worker Name</th>
                            <th>Trade / Skill</th>
                            <th>Attendance</th>
                            <th>Calculated Wage</th>
                            <th>Payout Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentAttendance)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                    No attendance logged yet for this site.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentAttendance as $att): ?>
                                <tr>
                                    <td><strong><?php echo format_date($att['work_date']); ?></strong></td>
                                    <td>
                                        <a href="worker_details.php?id=<?php echo $att['worker_id']; ?>" style="color: var(--primary); font-weight: 600; text-decoration: none;">
                                            <?php echo e($att['worker_name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo e($att['worker_type']); ?></td>
                                    <td>
                                        <?php if ($att['status'] === 'Present'): ?>
                                            <span class="badge badge-success">Present</span>
                                        <?php elseif ($att['status'] === 'Half Day'): ?>
                                            <span class="badge badge-warning">Half Day</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Absent</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo format_currency($att['calculated_wage']); ?></td>
                                    <td>
                                        <?php if ($att['payment_status'] === 'Paid'): ?>
                                            <span class="badge badge-success">Paid</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning">Pending</span>
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

    <!-- Material Purchases on Site -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('materials'); ?>
                Material Purchases for this Site
            </div>
            <a href="materials.php?site_id=<?php echo $site['id']; ?>" class="btn btn-outline btn-sm">Manage Materials</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Material Name</th>
                            <th>Category</th>
                            <th>Quantity & Rate</th>
                            <th>Total Amount</th>
                            <th>Supplier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentMaterials)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                    No material purchases recorded for this site yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentMaterials as $mat): ?>
                                <tr>
                                    <td><?php echo format_date($mat['purchase_date']); ?></td>
                                    <td><strong><?php echo e($mat['material_name']); ?></strong></td>
                                    <td><span class="badge badge-neutral"><?php echo e($mat['category']); ?></span></td>
                                    <td><?php echo (float)$mat['quantity']; ?> <?php echo e($mat['unit']); ?> &times; <?php echo format_currency($mat['unit_price']); ?></td>
                                    <td style="font-weight: 700; color: var(--primary);"><?php echo format_currency($mat['total_amount']); ?></td>
                                    <td><?php echo e($mat['supplier'] ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Other Site Expenses -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('expenses'); ?>
                Other Site Expenses (Machinery, Equipment, Transport)
            </div>
            <a href="admin/expenses.php?site_id=<?php echo $site['id']; ?>" class="btn btn-outline btn-sm">Manage Expenses</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Expense Date</th>
                            <th>Expense Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentExpenses)): ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                    No miscellaneous expenses recorded for this site.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentExpenses as $exp): ?>
                                <tr>
                                    <td><?php echo format_date($exp['expense_date']); ?></td>
                                    <td><span class="badge badge-info"><?php echo e($exp['expense_type']); ?></span></td>
                                    <td><?php echo e($exp['description']); ?></td>
                                    <td style="font-weight: 700; color: var(--primary);"><?php echo format_currency($exp['amount']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
