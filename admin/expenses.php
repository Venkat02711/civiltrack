<?php
/**
 * CivilTrack - Admin: Site Expenses Management
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();

$filter_site_id = (int)($_GET['site_id'] ?? 0);
$filter_type = trim($_GET['type'] ?? '');
$filter_date = trim($_GET['date'] ?? '');

$activeSites = get_all_sites($conn);

// Summary totals
$res = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM materials");
$row = mysqli_fetch_assoc($res);
$totalMaterialExpenses = (float)($row['total'] ?? 0);

$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM expenses");
$row = mysqli_fetch_assoc($res);
$totalOtherExpenses = (float)($row['total'] ?? 0);

$res = mysqli_query($conn, "SELECT COALESCE(SUM(amount), 0) as total FROM payouts");
$row = mysqli_fetch_assoc($res);
$totalLabourExpenses = (float)($row['total'] ?? 0);

$combinedTotal = $totalMaterialExpenses + $totalOtherExpenses + $totalLabourExpenses;

// Fetch other expenses list
$sql = "
    SELECT e.*, s.site_name
    FROM expenses e
    JOIN sites s ON e.site_id = s.id
    WHERE 1=1
";

if ($filter_site_id > 0) {
    $sql .= " AND e.site_id = $filter_site_id";
}

if (!empty($filter_type)) {
    $safe_type = mysqli_real_escape_string($conn, $filter_type);
    $sql .= " AND e.expense_type = '$safe_type'";
}

if (!empty($filter_date)) {
    $safe_date = mysqli_real_escape_string($conn, $filter_date);
    $sql .= " AND e.expense_date = '$safe_date'";
}

$sql .= " ORDER BY e.expense_date DESC, e.id DESC";

$expenses_res = mysqli_query($conn, $sql);
$expenses = mysqli_fetch_all($expenses_res, MYSQLI_ASSOC);

// Site expenses rollup
$rollups_res = mysqli_query($conn, "
    SELECT s.id, s.site_name, s.status,
        (SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE site_id = s.id) as site_labour,
        (SELECT COALESCE(SUM(total_amount), 0) FROM materials WHERE site_id = s.id) as site_materials,
        (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE site_id = s.id) as site_other
    FROM sites s
    ORDER BY s.id DESC
");
$siteRollups = mysqli_fetch_all($rollups_res, MYSQLI_ASSOC);

$page_title = 'Site Expenses';
include __DIR__ . '/../includes/header.php';
?>

<!-- Financial Summary Cards -->
<div class="stats-grid">
    <div class="stat-card card-orange">
        <div class="stat-icon icon-orange">
            <?php echo render_icon('materials'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Material Expenses</div>
            <div class="stat-value"><?php echo format_currency($totalMaterialExpenses); ?></div>
            <div class="stat-sub">From quick material purchases</div>
        </div>
    </div>

    <div class="stat-card card-blue">
        <div class="stat-icon icon-blue">
            <?php echo render_icon('expenses'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Other Site Expenses</div>
            <div class="stat-value"><?php echo format_currency($totalOtherExpenses); ?></div>
            <div class="stat-sub">Equipment, transport & logistics</div>
        </div>
    </div>

    <div class="stat-card card-green">
        <div class="stat-icon icon-green">
            <?php echo render_icon('workers'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Total Labour Expenses</div>
            <div class="stat-value"><?php echo format_currency($totalLabourExpenses); ?></div>
            <div class="stat-sub">Worker attendance payouts</div>
        </div>
    </div>

    <div class="stat-card card-purple">
        <div class="stat-icon icon-purple">
            <?php echo render_icon('dollar'); ?>
        </div>
        <div class="stat-details">
            <div class="stat-label">Grand Total Spend</div>
            <div class="stat-value"><?php echo format_currency($combinedTotal); ?></div>
            <div class="stat-sub">Labour + Materials + Other</div>
        </div>
    </div>
</div>

<!-- Action & Filter Bar -->
<div class="filter-bar">
    <form method="GET" class="filter-group">
        <select name="site_id" class="filter-select" onchange="this.form.submit()">
            <option value="0">All Construction Sites</option>
            <?php foreach ($activeSites as $s): ?>
                <option value="<?php echo $s['id']; ?>" <?php echo ($filter_site_id == $s['id']) ? 'selected' : ''; ?>>
                    <?php echo e($s['site_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="type" class="filter-select" onchange="this.form.submit()">
            <option value="">All Expense Types</option>
            <option value="Transportation" <?php echo ($filter_type === 'Transportation') ? 'selected' : ''; ?>>Transportation</option>
            <option value="Equipment" <?php echo ($filter_type === 'Equipment') ? 'selected' : ''; ?>>Equipment / Machinery</option>
            <option value="Labour Miscellaneous" <?php echo ($filter_type === 'Labour Miscellaneous') ? 'selected' : ''; ?>>Labour Miscellaneous</option>
            <option value="Utilities" <?php echo ($filter_type === 'Utilities') ? 'selected' : ''; ?>>Utilities</option>
            <option value="Other" <?php echo ($filter_type === 'Other') ? 'selected' : ''; ?>>Other</option>
        </select>

        <input type="date" name="date" class="form-control" style="padding: 6px 12px;" value="<?php echo e($filter_date); ?>" onchange="this.form.submit()">

        <?php if ($filter_site_id > 0 || !empty($filter_type) || !empty($filter_date)): ?>
            <a href="expenses.php" class="btn btn-outline btn-sm">Reset</a>
        <?php endif; ?>
    </form>

    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('addExpenseModal')">
            <?php echo render_icon('plus'); ?>
            Record Other Site Expense
        </button>
    </div>
</div>

<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 24px;">
    <!-- Left: Miscellaneous Site Expenses -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('expenses'); ?>
                Other Site Expenses Log (<?php echo count($expenses); ?>)
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Site</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 32px;">
                                    No miscellaneous expenses recorded.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $exp): ?>
                                <tr>
                                    <td><strong><?php echo format_date($exp['expense_date']); ?></strong></td>
                                    <td>
                                        <a href="../site_details.php?id=<?php echo $exp['site_id']; ?>" style="color: var(--primary); text-decoration: none; font-size: 13px;">
                                            <?php echo e($exp['site_name']); ?>
                                        </a>
                                    </td>
                                    <td><span class="badge badge-info"><?php echo e($exp['expense_type']); ?></span></td>
                                    <td style="font-size: 13px;"><?php echo e($exp['description']); ?></td>
                                    <td style="font-weight: 700; color: var(--primary);">
                                        <?php echo format_currency($exp['amount']); ?>
                                    </td>
                                    <td style="text-align: right;">
                                        <form method="POST" action="../actions/expense_actions.php" style="display: inline;" onsubmit="return confirm('Delete this expense record?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $exp['id']; ?>">
                                            <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444;" title="Delete Expense">
                                                <?php echo render_icon('trash'); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right: Site-Wise Expense Rollup -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <?php echo render_icon('briefcase'); ?>
                Site Expense Rollup
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Site</th>
                            <th>Materials</th>
                            <th>Labour</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siteRollups as $sr): 
                            $siteTotal = $sr['site_labour'] + $sr['site_materials'] + $sr['site_other'];
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary);">
                                        <a href="../site_details.php?id=<?php echo $sr['id']; ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo e($sr['site_name']); ?>
                                        </a>
                                    </div>
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        Other: <?php echo format_currency($sr['site_other']); ?>
                                    </div>
                                </td>
                                <td style="font-size: 13px;"><?php echo format_currency($sr['site_materials']); ?></td>
                                <td style="font-size: 13px;"><?php echo format_currency($sr['site_labour']); ?></td>
                                <td style="font-weight: 700; color: var(--accent-hover);">
                                    <?php echo format_currency($siteTotal); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Expense -->
<div class="modal-overlay" id="addExpenseModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Record Site Expense</h3>
            <button type="button" class="modal-close" onclick="closeModal('addExpenseModal')">&times;</button>
        </div>
        <form method="POST" action="../actions/expense_actions.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="site_id">Site Project <span class="required">*</span></label>
                    <select id="site_id" name="site_id" class="form-control" required>
                        <option value="">-- Choose Construction Site --</option>
                        <?php foreach ($activeSites as $s): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo e($s['site_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="expense_type">Expense Type <span class="required">*</span></label>
                        <select id="expense_type" name="expense_type" class="form-control" required>
                            <option value="Transportation">Transportation / Freight</option>
                            <option value="Equipment">Equipment / Machinery Rental</option>
                            <option value="Labour Miscellaneous">Labour Miscellaneous (Water, Food, Gear)</option>
                            <option value="Utilities">Utilities (Electricity, Generator Fuel)</option>
                            <option value="Other">Other Expenses</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="amount">Amount (₹) <span class="required">*</span></label>
                        <input type="number" id="amount" name="amount" class="form-control" placeholder="e.g. 3500" step="any" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="expense_date">Expense Date <span class="required">*</span></label>
                    <input type="date" id="expense_date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" class="form-control" placeholder="e.g. Concrete mixer rental for 3 days, diesel fuel for generator" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addExpenseModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Expense</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
