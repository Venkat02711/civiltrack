<?php
/**
 * CivilTrack - Construction Sites Management
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();

$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? '');

$sql = "SELECT s.*,
    (SELECT COUNT(DISTINCT worker_id) FROM attendance WHERE site_id = s.id) as total_workers_engaged,
    (SELECT COALESCE(SUM(total_amount), 0) FROM materials WHERE site_id = s.id) as material_expenses,
    (SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE site_id = s.id) as labour_expenses,
    (SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE site_id = s.id) as other_expenses
FROM sites s WHERE 1=1";

if (!empty($search)) {
    $safeSearch = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (s.site_name LIKE '%$safeSearch%' OR s.client_name LIKE '%$safeSearch%' OR s.location LIKE '%$safeSearch%')";
}

if (!empty($status_filter)) {
    $safeStatus = mysqli_real_escape_string($conn, $status_filter);
    $sql .= " AND s.status = '$safeStatus'";
}

$sql .= " ORDER BY s.id DESC";
$res = mysqli_query($conn, $sql);
$sites = mysqli_fetch_all($res, MYSQLI_ASSOC);

$page_title = 'Construction Sites';
include __DIR__ . '/includes/header.php';
?>

<!-- Filter & Action Header -->
<div class="filter-bar">
    <form method="GET" class="filter-group">
        <div class="search-box">
            <?php echo render_icon('search'); ?>
            <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search site, client, location...">
        </div>

        <select name="status" class="filter-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="Active" <?php echo ($status_filter === 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="On Hold" <?php echo ($status_filter === 'On Hold') ? 'selected' : ''; ?>>On Hold</option>
            <option value="Completed" <?php echo ($status_filter === 'Completed') ? 'selected' : ''; ?>>Completed</option>
        </select>

        <?php if (!empty($search) || !empty($status_filter)): ?>
            <a href="sites.php" class="btn btn-outline btn-sm">Clear Filters</a>
        <?php endif; ?>
    </form>

    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('addSiteModal')">
            <?php echo render_icon('plus'); ?>
            Add New Site
        </button>
    </div>
</div>

<!-- Sites List Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php echo render_icon('sites'); ?>
            All Construction Projects (<?php echo count($sites); ?>)
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Site Name</th>
                        <th>Client & Location</th>
                        <th>Timeline</th>
                        <th>Total Expenses</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sites)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 36px;">
                                No construction sites match your criteria.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sites as $site): 
                            $total_site_cost = $site['labour_expenses'] + $site['material_expenses'] + $site['other_expenses'];
                        ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary);">
                                        <a href="site_details.php?id=<?php echo $site['id']; ?>" style="color: inherit; text-decoration: none;">
                                            <?php echo e($site['site_name']); ?>
                                        </a>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        Site ID: #SITE-<?php echo str_pad($site['id'], 3, '0', STR_PAD_LEFT); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 500;"><?php echo e($site['client_name'] ?: 'N/A'); ?></div>
                                    <div style="font-size: 12.5px; color: var(--text-secondary); display: flex; align-items: center; gap: 4px; margin-top: 2px;">
                                        <?php echo render_icon('map_pin'); ?>
                                        <span><?php echo e($site['location']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 13px;">
                                        Start: <strong><?php echo format_date($site['start_date']); ?></strong>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">
                                        Target: <?php echo format_date($site['expected_end_date']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary);">
                                        <?php echo format_currency($total_site_cost); ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">
                                        Mat: <?php echo format_currency($site['material_expenses']); ?> | Lab: <?php echo format_currency($site['labour_expenses']); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($site['status'] === 'Active'): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php elseif ($site['status'] === 'On Hold'): ?>
                                        <span class="badge badge-warning">On Hold</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral">Completed</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <a href="site_details.php?id=<?php echo $site['id']; ?>" class="btn btn-outline btn-sm" title="View Site Details">
                                        <?php echo render_icon('eye'); ?> View
                                    </a>
                                    <button type="button" class="btn btn-outline btn-sm" onclick='editSite(<?php echo json_encode($site); ?>)' title="Edit Site">
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

<!-- Modal: Add New Site -->
<div class="modal-overlay" id="addSiteModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Create Construction Site</h3>
            <button type="button" class="modal-close" onclick="closeModal('addSiteModal')">&times;</button>
        </div>
        <form method="POST" action="actions/site_actions.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="site_name">Site Name <span class="required">*</span></label>
                    <input type="text" id="site_name" name="site_name" class="form-control" placeholder="e.g. Green Valley Villa - Phase 2" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="client_name">Client / Owner Name</label>
                    <input type="text" id="client_name" name="client_name" class="form-control" placeholder="e.g. Dr. Arvind Swamy">
                </div>
                <div class="form-group">
                    <label class="form-label" for="location">Location / Address <span class="required">*</span></label>
                    <input type="text" id="location" name="location" class="form-control" placeholder="e.g. Plot 42, Orchid Enclave, Whitefield" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="start_date">Start Date <span class="required">*</span></label>
                        <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="expected_end_date">Expected Completion</label>
                        <input type="date" id="expected_end_date" name="expected_end_date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="status">Project Status</label>
                    <select id="status" name="status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addSiteModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Site</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Site -->
<div class="modal-overlay" id="editSiteModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Edit Construction Site</h3>
            <button type="button" class="modal-close" onclick="closeModal('editSiteModal')">&times;</button>
        </div>
        <form method="POST" action="actions/site_actions.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_id" name="id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="edit_site_name">Site Name <span class="required">*</span></label>
                    <input type="text" id="edit_site_name" name="site_name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_client_name">Client / Owner Name</label>
                    <input type="text" id="edit_client_name" name="client_name" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_location">Location / Address <span class="required">*</span></label>
                    <input type="text" id="edit_location" name="location" class="form-control" required>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="edit_start_date">Start Date <span class="required">*</span></label>
                        <input type="date" id="edit_start_date" name="start_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="edit_expected_end_date">Expected Completion</label>
                        <input type="date" id="edit_expected_end_date" name="expected_end_date" class="form-control">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_status">Project Status</label>
                    <select id="edit_status" name="status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="On Hold">On Hold</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editSiteModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSite(site) {
    document.getElementById('edit_id').value = site.id;
    document.getElementById('edit_site_name').value = site.site_name;
    document.getElementById('edit_client_name').value = site.client_name || '';
    document.getElementById('edit_location').value = site.location;
    document.getElementById('edit_start_date').value = site.start_date;
    document.getElementById('edit_expected_end_date').value = site.expected_end_date || '';
    document.getElementById('edit_status').value = site.status;
    openModal('editSiteModal');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
