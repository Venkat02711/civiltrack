<?php
/**
 * CivilTrack - Admin: Reports Generator
 * Print-ready reports for Worker Payouts, Site Expenses, and Material Purchases
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();

$tab = trim($_GET['tab'] ?? 'payout'); // 'payout', 'site', 'material'
$start_date = trim($_GET['start_date'] ?? date('Y-m-01')); // 1st day of current month
$end_date = trim($_GET['end_date'] ?? date('Y-m-d'));
$filter_site_id = (int)($_GET['site_id'] ?? 0);
$filter_worker_id = (int)($_GET['worker_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');

$allSites = get_all_sites($conn);
$allWorkers = get_active_workers($conn);

$safe_start = mysqli_real_escape_string($conn, $start_date);
$safe_end = mysqli_real_escape_string($conn, $end_date);

// Worker payout report query
$workerReport = [];
if ($tab === 'payout') {
    $sql = "
        SELECT 
            w.id as worker_id,
            w.name as worker_name,
            w.worker_type,
            s.site_name,
            COUNT(DISTINCT a.work_date) as days_worked,
            COALESCE(SUM(a.calculated_wage), 0) as total_wage,
            COALESCE(SUM(CASE WHEN p.payment_status = 'Paid' THEN p.amount ELSE 0 END), 0) as amount_paid,
            COALESCE(SUM(CASE WHEN p.payment_status = 'Pending' THEN p.amount ELSE 0 END), 0) as amount_pending
        FROM workers w
        JOIN attendance a ON a.worker_id = w.id
        JOIN sites s ON a.site_id = s.id
        LEFT JOIN payouts p ON p.attendance_id = a.id
        WHERE a.work_date BETWEEN '$safe_start' AND '$safe_end'
    ";

    if ($filter_site_id > 0) {
        $sql .= " AND a.site_id = $filter_site_id";
    }
    if ($filter_worker_id > 0) {
        $sql .= " AND w.id = $filter_worker_id";
    }
    if (!empty($filter_status)) {
        $safe_status = mysqli_real_escape_string($conn, $filter_status);
        $sql .= " AND p.payment_status = '$safe_status'";
    }

    $sql .= " GROUP BY w.id, w.name, w.worker_type, s.id, s.site_name ORDER BY w.name ASC";
    $res = mysqli_query($conn, $sql);
    $workerReport = mysqli_fetch_all($res, MYSQLI_ASSOC);
}

// Site expense report query
$siteReport = [];
if ($tab === 'site') {
    $res = mysqli_query($conn, "
        SELECT s.id, s.site_name, s.client_name, s.location, s.status,
            (SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE site_id = s.id) as labour_expense,
            (SELECT COALESCE(SUM(total_amount), 0) FROM materials WHERE site_id = s.id) as material_expense,
            (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE site_id = s.id) as other_expense
        FROM sites s
        ORDER BY s.id DESC
    ");
    $siteReport = mysqli_fetch_all($res, MYSQLI_ASSOC);
}

// Material purchase report query
$materialReport = [];
if ($tab === 'material') {
    $sql = "
        SELECT m.*, s.site_name
        FROM materials m
        JOIN sites s ON m.site_id = s.id
        WHERE m.purchase_date BETWEEN '$safe_start' AND '$safe_end'
    ";

    if ($filter_site_id > 0) {
        $sql .= " AND m.site_id = $filter_site_id";
    }

    $sql .= " ORDER BY m.purchase_date DESC, m.id DESC";
    $res = mysqli_query($conn, $sql);
    $materialReport = mysqli_fetch_all($res, MYSQLI_ASSOC);
}

$page_title = 'Site & Financial Reports';
include __DIR__ . '/../includes/header.php';
?>

<!-- Print-Only Report Header -->
<div class="print-only" style="margin-bottom: 24px; border-bottom: 2px solid #0f172a; padding-bottom: 12px;">
    <h1 style="font-size: 20pt; margin: 0; color: #0f172a;">CivilTrack - Construction Site Management</h1>
    <h3 style="font-size: 14pt; margin: 6px 0 0; color: #475569;">
        <?php 
            if ($tab === 'payout') echo "Worker Wage & Payout Summary Report";
            elseif ($tab === 'site') echo "Site Comprehensive Expense Report";
            else echo "Material Purchases & Bills Report";
        ?>
    </h3>
    <p style="font-size: 10pt; color: #64748b; margin-top: 4px;">
        Generated On: <?php echo date('d M Y, h:i A'); ?> &bull; Period: <?php echo format_date($start_date); ?> to <?php echo format_date($end_date); ?>
    </p>
</div>

<!-- Screen Tabs & Print Controls -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;" class="no-print">
    <div class="tabs-nav" style="margin-bottom: 0;">
        <a href="reports.php?tab=payout" class="tab-btn <?php echo ($tab === 'payout') ? 'active' : ''; ?>">
            Worker Payout Report
        </a>
        <a href="reports.php?tab=site" class="tab-btn <?php echo ($tab === 'site') ? 'active' : ''; ?>">
            Site Expense Report
        </a>
        <a href="reports.php?tab=material" class="tab-btn <?php echo ($tab === 'material') ? 'active' : ''; ?>">
            Material Purchase Report
        </a>
    </div>

    <div>
        <button type="button" class="btn btn-outline" onclick="window.print()">
            <?php echo render_icon('printer'); ?>
            Print / Save PDF
        </button>
    </div>
</div>

<!-- Filters Bar (Screen Only) -->
<div class="filter-bar no-print">
    <form method="GET" class="filter-group">
        <input type="hidden" name="tab" value="<?php echo e($tab); ?>">

        <?php if ($tab !== 'site'): ?>
            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 12.5px; font-weight: 600;">From:</label>
                <input type="date" name="start_date" class="form-control" style="padding: 6px 10px;" value="<?php echo e($start_date); ?>">
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <label style="font-size: 12.5px; font-weight: 600;">To:</label>
                <input type="date" name="end_date" class="form-control" style="padding: 6px 10px;" value="<?php echo e($end_date); ?>">
            </div>
        <?php endif; ?>

        <select name="site_id" class="filter-select">
            <option value="0">All Construction Sites</option>
            <?php foreach ($allSites as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php echo ($filter_site_id == $s['id']) ? 'selected' : ''; ?>>
                    <?php echo e($s['site_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if ($tab === 'payout'): ?>
            <select name="worker_id" class="filter-select">
                <option value="0">All Workers</option>
                <?php foreach ($allWorkers as $w): ?>
                    <option value="<?php echo $w['id']; ?>" <?php echo ($filter_worker_id == $w['id']) ? 'selected' : ''; ?>>
                        <?php echo e($w['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Payment Statuses</option>
                <option value="Paid" <?php echo ($filter_status === 'Paid') ? 'selected' : ''; ?>>Paid Only</option>
                <option value="Pending" <?php echo ($filter_status === 'Pending') ? 'selected' : ''; ?>>Pending Only</option>
            </select>
        <?php endif; ?>

        <button type="submit" class="btn btn-outline btn-sm">Generate Report</button>
    </form>
</div>

<!-- TAB 1: WORKER PAYOUT REPORT -->
<?php if ($tab === 'payout'): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('workers'); ?>
                Worker Payout Summary Report
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Worker Name</th>
                            <th>Trade / Skill</th>
                            <th>Construction Site</th>
                            <th style="text-align: center;">Days Worked</th>
                            <th style="text-align: right;">Total Wage</th>
                            <th style="text-align: right;">Paid Amount</th>
                            <th style="text-align: right;">Pending Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($workerReport)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                    No worker payouts found for the selected period/filters.
                                </td>
                            </tr>
                        <?php else: 
                            $sumWage = 0; $sumPaid = 0; $sumPending = 0;
                            foreach ($workerReport as $row): 
                                $sumWage += (float)$row['total_wage'];
                                $sumPaid += (float)$row['amount_paid'];
                                $sumPending += (float)$row['amount_pending'];
                        ?>
                            <tr>
                                <td><strong><?php echo e($row['worker_name']); ?></strong></td>
                                <td><?php echo e($row['worker_type']); ?></td>
                                <td><?php echo e($row['site_name']); ?></td>
                                <td style="text-align: center;"><strong><?php echo $row['days_worked']; ?></strong></td>
                                <td style="text-align: right; font-weight: 600;"><?php echo format_currency($row['total_wage']); ?></td>
                                <td style="text-align: right; color: var(--success-text); font-weight: 600;"><?php echo format_currency($row['amount_paid']); ?></td>
                                <td style="text-align: right; color: var(--accent-hover); font-weight: 600;"><?php echo format_currency($row['amount_pending']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                            <tr style="background: #f8fafc; font-weight: 700;">
                                <td colspan="4" style="text-align: right;">Grand Total:</td>
                                <td style="text-align: right; color: var(--primary);"><?php echo format_currency($sumWage); ?></td>
                                <td style="text-align: right; color: var(--success-text);"><?php echo format_currency($sumPaid); ?></td>
                                <td style="text-align: right; color: var(--accent-hover);"><?php echo format_currency($sumPending); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- TAB 2: SITE EXPENSE REPORT -->
<?php if ($tab === 'site'): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('sites'); ?>
                Site Comprehensive Expense Report
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Construction Site</th>
                            <th>Client & Location</th>
                            <th>Status</th>
                            <th style="text-align: right;">Labour Expenses</th>
                            <th style="text-align: right;">Material Expenses</th>
                            <th style="text-align: right;">Other Expenses</th>
                            <th style="text-align: right;">Total Site Spend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grandLabour = 0; $grandMat = 0; $grandOther = 0; $grandAll = 0;
                        foreach ($siteReport as $row):
                            $siteTotal = $row['labour_expense'] + $row['material_expense'] + $row['other_expense'];
                            $grandLabour += (float)$row['labour_expense'];
                            $grandMat += (float)$row['material_expense'];
                            $grandOther += (float)$row['other_expense'];
                            $grandAll += $siteTotal;
                        ?>
                            <tr>
                                <td><strong><?php echo e($row['site_name']); ?></strong></td>
                                <td>
                                    <div><?php echo e($row['client_name'] ?: 'N/A'); ?></div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);"><?php echo e($row['location']); ?></div>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php elseif ($row['status'] === 'On Hold'): ?>
                                        <span class="badge badge-warning">On Hold</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;"><?php echo format_currency($row['labour_expense']); ?></td>
                                <td style="text-align: right;"><?php echo format_currency($row['material_expense']); ?></td>
                                <td style="text-align: right;"><?php echo format_currency($row['other_expense']); ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                    <?php echo format_currency($siteTotal); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background: #f8fafc; font-weight: 700;">
                            <td colspan="3" style="text-align: right;">Total Across All Sites:</td>
                            <td style="text-align: right;"><?php echo format_currency($grandLabour); ?></td>
                            <td style="text-align: right;"><?php echo format_currency($grandMat); ?></td>
                            <td style="text-align: right;"><?php echo format_currency($grandOther); ?></td>
                            <td style="text-align: right; color: var(--accent-hover); font-size: 15px;"><?php echo format_currency($grandAll); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- TAB 3: MATERIAL PURCHASE REPORT -->
<?php if ($tab === 'material'): ?>
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('materials'); ?>
                Material Purchase Register & Bills Report
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Purchase Date</th>
                            <th>Material Item</th>
                            <th>Category</th>
                            <th>Construction Site</th>
                            <th>Quantity & Rate</th>
                            <th>Supplier</th>
                            <th style="text-align: right;">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($materialReport)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                    No material purchases found for the selected period.
                                </td>
                            </tr>
                        <?php else: 
                            $grandMatTotal = 0;
                            foreach ($materialReport as $mat):
                                $grandMatTotal += (float)$mat['total_amount'];
                        ?>
                            <tr>
                                <td><?php echo format_date($mat['purchase_date']); ?></td>
                                <td><strong><?php echo e($mat['material_name']); ?></strong></td>
                                <td><span class="badge badge-info"><?php echo e($mat['category']); ?></span></td>
                                <td><?php echo e($mat['site_name']); ?></td>
                                <td><?php echo (float)$mat['quantity']; ?> <?php echo e($mat['unit']); ?> &times; <?php echo format_currency($mat['unit_price']); ?></td>
                                <td><?php echo e($mat['supplier'] ?: '-'); ?></td>
                                <td style="text-align: right; font-weight: 700; color: var(--primary);">
                                    <?php echo format_currency($mat['total_amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                            <tr style="background: #f8fafc; font-weight: 700;">
                                <td colspan="6" style="text-align: right;">Total Material Expenditure:</td>
                                <td style="text-align: right; color: var(--accent-hover); font-size: 15px;"><?php echo format_currency($grandMatTotal); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Sign-off Section on Printed Report -->
<div class="print-only" style="margin-top: 48px; display: flex; justify-content: space-between;">
    <div style="border-top: 1px solid #333; width: 200px; text-align: center; padding-top: 6px; font-size: 10pt;">
        Site Supervisor
    </div>
    <div style="border-top: 1px solid #333; width: 200px; text-align: center; padding-top: 6px; font-size: 10pt;">
        Project Manager / Auditor
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
