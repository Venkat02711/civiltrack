<?php
/**
 * CivilTrack - Attendance & Daily Work Management
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();

// Filter parameters
$filter_date = trim($_GET['date'] ?? '');
$filter_site_id = (int)($_GET['site_id'] ?? 0);
$filter_worker_id = (int)($_GET['worker_id'] ?? 0);
$filter_status = trim($_GET['status'] ?? '');

$activeSites = get_active_sites($conn);
$activeWorkers = get_active_workers($conn);

// Query Attendance Records
$sql = "
    SELECT a.*, w.name as worker_name, w.worker_type, w.daily_wage as standard_wage, s.site_name, p.payment_status, p.id as payout_id
    FROM attendance a
    JOIN workers w ON a.worker_id = w.id
    JOIN sites s ON a.site_id = s.id
    LEFT JOIN payouts p ON p.attendance_id = a.id
    WHERE 1=1
";

if (!empty($filter_date)) {
    $safeDate = mysqli_real_escape_string($conn, $filter_date);
    $sql .= " AND a.work_date = '$safeDate'";
}

if ($filter_site_id > 0) {
    $sql .= " AND a.site_id = $filter_site_id";
}

if ($filter_worker_id > 0) {
    $sql .= " AND a.worker_id = $filter_worker_id";
}

if (!empty($filter_status)) {
    $safeStatus = mysqli_real_escape_string($conn, $filter_status);
    $sql .= " AND a.status = '$safeStatus'";
}

$sql .= " ORDER BY a.work_date DESC, a.id DESC";

$res = mysqli_query($conn, $sql);
$attendanceRecords = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Calculate total wages for filtered list
$totalCalculatedWage = 0;
foreach ($attendanceRecords as $rec) {
    $totalCalculatedWage += (float)$rec['calculated_wage'];
}

$page_title = 'Attendance & Daily Work';
include __DIR__ . '/includes/header.php';
?>

<!-- Action Bar & Tabs -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--primary);">Daily Work & Wage Attendance</h2>
        <p style="font-size: 13px; color: var(--text-muted);">
            Log temporary worker attendance. Present earns 100%, Half Day earns 50%, Absent earns ₹0.
        </p>
    </div>

    <div style="display: flex; gap: 10px;">
        <button type="button" class="btn btn-primary" onclick="openModal('singleAttendanceModal')">
            <?php echo render_icon('attendance'); ?>
            Record Single Attendance
        </button>
        <button type="button" class="btn btn-secondary" onclick="openModal('batchAttendanceModal')">
            <?php echo render_icon('workers'); ?>
            Batch Site Attendance
        </button>
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
                        <?php echo e($w['name']); ?> (<?php echo e($w['worker_type']); ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="status" class="filter-select">
                <option value="">All Statuses</option>
                <option value="Present" <?php echo ($filter_status === 'Present') ? 'selected' : ''; ?>>Present</option>
                <option value="Half Day" <?php echo ($filter_status === 'Half Day') ? 'selected' : ''; ?>>Half Day</option>
                <option value="Absent" <?php echo ($filter_status === 'Absent') ? 'selected' : ''; ?>>Absent</option>
            </select>

            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            <?php if (!empty($filter_date) || $filter_site_id > 0 || $filter_worker_id > 0 || !empty($filter_status)): ?>
                <a href="attendance.php" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>

        <div style="font-weight: 700; color: var(--primary); font-size: 14px;">
            Total Wage in View: <span style="color: var(--accent-hover);"><?php echo format_currency($totalCalculatedWage); ?></span>
        </div>
    </form>
</div>

<!-- Attendance Log Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php echo render_icon('attendance'); ?>
            Attendance Log (<?php echo count($attendanceRecords); ?> Records)
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Work Date</th>
                        <th>Worker Name & Trade</th>
                        <th>Site</th>
                        <th>Attendance</th>
                        <th>Standard Rate</th>
                        <th>Calculated Wage</th>
                        <th>Payout Status</th>
                        <th>Work Notes</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceRecords)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 36px;">
                                No attendance records found matching current criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendanceRecords as $row): ?>
                            <tr>
                                <td>
                                    <strong><?php echo format_date($row['work_date']); ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--primary);">
                                        <a href="worker_details.php?id=<?php echo $row['worker_id']; ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo e($row['worker_name']); ?>
                                        </a>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">
                                        <?php echo e($row['worker_type']); ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="site_details.php?id=<?php echo $row['site_id']; ?>" style="color: var(--primary); text-decoration: none; font-size: 13px;">
                                        <?php echo e($row['site_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Present'): ?>
                                        <span class="badge badge-success">Present (100%)</span>
                                    <?php elseif ($row['status'] === 'Half Day'): ?>
                                        <span class="badge badge-warning">Half Day (50%)</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Absent (0%)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12.5px; color: var(--text-muted);">
                                    <?php echo format_currency($row['standard_wage']); ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary); font-size: 14.5px;">
                                        <?php echo format_currency($row['calculated_wage']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($row['payment_status'] === 'Paid'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 12.5px; color: var(--text-secondary); max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    <?php echo e($row['notes'] ?: '-'); ?>
                                </td>
                                <td style="text-align: right;">
                                    <form method="POST" action="actions/attendance_actions.php" style="display: inline;" onsubmit="return confirm('Delete this attendance record? This will also remove the associated payout.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444;" title="Delete Record">
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

<!-- Modal: Record Single Attendance -->
<div class="modal-overlay" id="singleAttendanceModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Record Daily Work & Attendance</h3>
            <button type="button" class="modal-close" onclick="closeModal('singleAttendanceModal')">&times;</button>
        </div>
        <form method="POST" action="actions/attendance_actions.php">
            <input type="hidden" name="action" value="record_single">
            <input type="hidden" id="att_default_wage" value="0">
            <input type="hidden" id="att_calculated_wage" name="calculated_wage" value="0">

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="work_date">Work Date <span class="required">*</span></label>
                    <input type="date" id="work_date" name="work_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="site_id">Construction Site <span class="required">*</span></label>
                    <select id="site_id" name="site_id" class="form-control" required>
                        <option value="">-- Choose Construction Site --</option>
                        <?php foreach ($activeSites as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($filter_site_id == $s['id']) ? 'selected' : ''; ?>>
                                <?php echo e($s['site_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="att_worker_select">Worker <span class="required">*</span></label>
                    <select id="att_worker_select" name="worker_id" class="form-control" required>
                        <option value="" data-wage="0">-- Select Daily Worker --</option>
                        <?php foreach ($activeWorkers as $w): ?>
                            <option value="<?php echo $w['id']; ?>" data-wage="<?php echo $w['daily_wage']; ?>" <?php echo ($filter_worker_id == $w['id']) ? 'selected' : ''; ?>>
                                <?php echo e($w['name']); ?> (<?php echo e($w['worker_type']); ?> - ₹<?php echo (float)$w['daily_wage']; ?>/day)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Attendance Status <span class="required">*</span></label>
                    <div style="display: flex; gap: 20px; margin-top: 6px;">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="status" value="Present" checked> Present (100% Wage)
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="status" value="Half Day"> Half Day (50% Wage)
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; font-weight: 500;">
                            <input type="radio" name="status" value="Absent"> Absent (₹0)
                        </label>
                    </div>
                </div>

                <!-- Auto-Calculated Wage Live Preview -->
                <div style="background: #f8fafc; border: 1px solid var(--border); padding: 14px 18px; border-radius: 8px; margin: 16px 0; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 13.5px; color: var(--text-secondary);">Calculated Daily Payout:</span>
                    <span id="wage_preview_text" style="font-size: 20px; font-weight: 700; color: var(--accent-hover);">₹ 0.00</span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notes">Work Description / Site Notes</label>
                    <input type="text" id="notes" name="notes" class="form-control" placeholder="e.g. Masonry wall second floor, excavation helper">
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('singleAttendanceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Attendance & Payout</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Batch Attendance -->
<div class="modal-overlay" id="batchAttendanceModal">
    <div class="modal-container" style="max-width: 780px;">
        <div class="modal-header">
            <h3 class="modal-title">Batch Site Attendance (Multiple Workers)</h3>
            <button type="button" class="modal-close" onclick="closeModal('batchAttendanceModal')">&times;</button>
        </div>
        <form method="POST" action="actions/attendance_actions.php">
            <input type="hidden" name="action" value="record_batch">
            <div class="modal-body">
                <div class="form-grid" style="margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label" for="batch_date">Work Date <span class="required">*</span></label>
                        <input type="date" id="batch_date" name="work_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="batch_site">Site <span class="required">*</span></label>
                        <select id="batch_site" name="site_id" class="form-control" required>
                            <option value="">-- Choose Construction Site --</option>
                            <?php foreach ($activeSites as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['site_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 12px;">
                    Mark attendance for workers below. Calculated wages will automatically create pending payouts.
                </p>

                <div class="table-responsive" style="max-height: 380px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px;">
                    <table class="data-table" style="font-size: 13px;">
                        <thead>
                            <tr>
                                <th>Worker</th>
                                <th>Skill Rate</th>
                                <th style="text-align: center;">Present (100%)</th>
                                <th style="text-align: center;">Half Day (50%)</th>
                                <th style="text-align: center;">Absent (₹0)</th>
                                <th style="text-align: right;">Day Wage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activeWorkers as $w): ?>
                                <tr data-daily-wage="<?php echo (float)$w['daily_wage']; ?>">
                                    <td>
                                        <strong><?php echo e($w['name']); ?></strong>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?php echo e($w['worker_type']); ?></div>
                                    </td>
                                    <td>₹<?php echo (float)$w['daily_wage']; ?></td>
                                    <td style="text-align: center;">
                                        <input type="radio" name="worker_status[<?php echo $w['id']; ?>]" value="Present" checked onchange="updateBatchRowWage(this)">
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="radio" name="worker_status[<?php echo $w['id']; ?>]" value="Half Day" onchange="updateBatchRowWage(this)">
                                    </td>
                                    <td style="text-align: center;">
                                        <input type="radio" name="worker_status[<?php echo $w['id']; ?>]" value="Absent" onchange="updateBatchRowWage(this)">
                                    </td>
                                    <td style="text-align: right; font-weight: 700;">
                                        <span class="row-calculated-wage">₹ <?php echo number_format((float)$w['daily_wage'], 2); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('batchAttendanceModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save All Attendance Records</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
