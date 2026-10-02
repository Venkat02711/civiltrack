<?php
/**
 * CivilTrack - Workers Management
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');
$type_filter = trim($_GET['type'] ?? '');

$sql = "SELECT w.*,
    (SELECT COUNT(*) FROM attendance WHERE worker_id = w.id AND status IN ('Present', 'Half Day')) as days_worked,
    (SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE worker_id = w.id AND payment_status = 'Paid') as total_paid,
    (SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE worker_id = w.id AND payment_status = 'Pending') as total_pending
FROM workers w WHERE 1=1";

if (!empty($search)) {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (w.name LIKE '%$safeSearch%' OR w.phone LIKE '%$safeSearch%' OR w.worker_type LIKE '%$safeSearch%')";
}

if (!empty($status_filter)) {
    $safeStatus = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND w.status = '$safeStatus'";
}

if (!empty($type_filter)) {
    $safeType = mysqli_real_escape_string($conn, $type_filter);
    $sql .= " AND w.worker_type = '$safeType'";
}

$sql .= " ORDER BY w.id DESC";
$res = mysqli_query($conn, $sql);
$workers = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Get unique worker types for filter dropdown
$workerTypes = [];
$res = mysqli_query($conn, "SELECT DISTINCT worker_type FROM workers ORDER BY worker_type ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $workerTypes[] = $row['worker_type'];
}

$page_title = 'Temporary / Daily-Wage Workers';
include __DIR__ . '/includes/header.php';
?>

<!-- Filter & Action Header -->
<div class="filter-bar">
    <form method="GET" class="filter-group">
        <div class="search-box">
            <?php echo render_icon('search'); ?>
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search name, phone, trade...">
        </div>

        <select name="type" class="filter-select" onchange="this.form.submit()">
            <option value="">All Trades / Skills</option>
            <?php foreach ($workerTypes as $t): ?>
                <option value="<?php echo e($t); ?>" <?php echo ($type_filter === $t) ? 'selected' : ''; ?>>
                    <?php echo e($t); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Active" <?php echo ($status_filter === 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="Inactive" <?php echo ($status_filter === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
        </select>

        <?php if (!empty($search) || !empty($status_filter) || !empty($type_filter)): ?>
            <a href="workers.php" class="btn btn-outline btn-sm">Clear Filters</a>
        <?php endif; ?>
    </form>

    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('addWorkerModal')">
            <?php echo render_icon('plus'); ?>
            Register New Worker
        </button>
    </div>
</div>

<!-- Workers List Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php echo render_icon('workers'); ?>
            Registered Workforce (<?php echo count($workers); ?>)
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Worker Name & ID</th>
                        <th>Trade / Skill</th>
                        <th>Phone & Address</th>
                        <th>Daily Wage Rate</th>
                        <th>Work Summary</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($workers)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 36px;">
                                No workers match your filter criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($workers as $worker): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary);">
                                        <a href="worker_details.php?id=<?php echo $worker['id']; ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo e($worker['name']); ?>
                                        </a>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        #WRK-<?php echo str_pad($worker['id'], 3, '0', STR_PAD_LEFT); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo e($worker['worker_type']); ?></span>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo e($worker['phone']); ?></div>
                                    <div style="font-size: 12px; color: var(--text-muted); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?php echo e($worker['address'] ?: '-'); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--accent-hover); font-size: 15px;">
                                        <?php echo format_currency($worker['daily_wage']); ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">per day (100%)</div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        Days Worked: <strong><?php echo $worker['days_worked']; ?></strong>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        Paid: <?php echo format_currency($worker['total_paid']); ?>
                                        <?php if ($worker['total_pending'] > 0): ?>
                                            &bull; <span style="color: #d97706; font-weight: 600;">Due: <?php echo format_currency($worker['total_pending']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($worker['status'] === 'Active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="worker_details.php?id=<?php echo $worker['id']; ?>" class="btn btn-outline btn-sm" title="View Profile & History">
                                        <?php echo render_icon('eye'); ?> View
                                    </a>
                                    <button type="button" class="btn btn-outline btn-sm" onclick='editWorker(<?php echo json_encode($worker); ?>)' title="Edit Details">
                                        <?php echo render_icon('edit'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add Worker -->
<div class="modal-overlay" id="addWorkerModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Register Temporary / Daily Worker</h3>
            <button type="button" class="modal-close" onclick="closeModal('addWorkerModal')">&times;</button>
        </div>
        <form method="POST" action="actions/worker_actions.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="name">Worker Full Name <span class="required">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Ravi Kumar" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number <span class="required">*</span></label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="e.g. 9876543210" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="worker_type">Trade / Skill <span class="required">*</span></label>
                        <select id="worker_type" name="worker_type" class="form-control" required>
                            <option value="Mason">Mason (Rajmistri)</option>
                            <option value="Helper">Helper (Beldar / Mazdoor)</option>
                            <option value="Carpenter">Carpenter (Badhai)</option>
                            <option value="Electrician">Electrician</option>
                            <option value="Plumber">Plumber</option>
                            <option value="Painter">Painter</option>
                            <option value="Bar Bender">Bar Bender (Steel Fitter)</option>
                            <option value="Welder">Welder / Fabricator</option>
                            <option value="General Worker">General Worker</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="daily_wage">Default Daily Wage (₹) <span class="required">*</span></label>
                        <input type="number" id="daily_wage" name="daily_wage" class="form-control" placeholder="e.g. 800" step="50" min="100" required>
                        <span class="form-hint">Standard rate for 1 full working day. Half day is 50%.</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="status">Workforce Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Address / Camp Details</label>
                    <textarea id="address" name="address" class="form-control" placeholder="e.g. Labour Colony Block B, Room 12"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addWorkerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Register Worker</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Worker -->
<div class="modal-overlay" id="editWorkerModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Edit Worker Profile</h3>
            <button type="button" class="modal-close" onclick="closeModal('editWorkerModal')">&times;</button>
        </div>
        <form method="POST" action="actions/worker_actions.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_name">Worker Full Name <span class="required">*</span></label>
                    <input type="text" id="edit_name" name="name" class="form-control" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="edit_phone">Phone Number <span class="required">*</span></label>
                        <input type="text" id="edit_phone" name="phone" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_worker_type">Trade / Skill <span class="required">*</span></label>
                        <select id="edit_worker_type" name="worker_type" class="form-control" required>
                            <option value="Mason">Mason (Rajmistri)</option>
                            <option value="Helper">Helper (Beldar / Mazdoor)</option>
                            <option value="Carpenter">Carpenter (Badhai)</option>
                            <option value="Electrician">Electrician</option>
                            <option value="Plumber">Plumber</option>
                            <option value="Painter">Painter</option>
                            <option value="Bar Bender">Bar Bender (Steel Fitter)</option>
                            <option value="Welder">Welder / Fabricator</option>
                            <option value="General Worker">General Worker</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="edit_daily_wage">Default Daily Wage (₹) <span class="required">*</span></label>
                        <input type="number" id="edit_daily_wage" name="daily_wage" class="form-control" step="50" min="100" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_status">Status</label>
                        <select id="edit_status" name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_address">Address / Camp Details</label>
                    <textarea id="edit_address" name="address" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editWorkerModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Worker</button>
            </div>
        </form>
    </div>
</div>

<script>
function editWorker(worker) {
    document.getElementById('edit_id').value = worker.id;
    document.getElementById('edit_name').value = worker.name;
    document.getElementById('edit_phone').value = worker.phone;
    document.getElementById('edit_worker_type').value = worker.worker_type;
    document.getElementById('edit_daily_wage').value = worker.daily_wage;
    document.getElementById('edit_status').value = worker.status;
    document.getElementById('edit_address').value = worker.address || '';
    openModal('editWorkerModal');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
