<?php
/**
 * CivilTrack - Materials Purchase Management
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_login();

$conn = get_db();

// Filters
$search = trim($_GET['search'] ?? '');
$filter_site_id = (int)($_GET['site_id'] ?? 0);
$filter_category = trim($_GET['category'] ?? '');
$filter_date = trim($_GET['date'] ?? '');

$activeSites = get_all_sites($conn);

$categories = ['Cement', 'Sand', 'Bricks', 'Steel', 'Pipes', 'Electrical', 'Hardware', 'Other'];

// Query Materials
$sql = "
    SELECT m.*, s.site_name
    FROM materials m
    JOIN sites s ON m.site_id = s.id
    WHERE 1=1
";

if (!empty($search)) {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (m.material_name LIKE '%$safe_search%' OR m.supplier LIKE '%$safe_search%' OR m.notes LIKE '%$safe_search%')";
}

if ($filter_site_id > 0) {
    $sql .= " AND m.site_id = $filter_site_id";
}

if (!empty($filter_category)) {
    $safe_cat = mysqli_real_escape_string($conn, $filter_category);
    $sql .= " AND m.category = '$safe_cat'";
}

if (!empty($filter_date)) {
    $safe_date = mysqli_real_escape_string($conn, $filter_date);
    $sql .= " AND m.purchase_date = '$safe_date'";
}

$sql .= " ORDER BY m.purchase_date DESC, m.id DESC";

$res = mysqli_query($conn, $sql);
$materials = mysqli_fetch_all($res, MYSQLI_ASSOC);

// Total in current view
$totalSpendInView = 0;
foreach ($materials as $m) {
    $totalSpendInView += (float)$m['total_amount'];
}

$page_title = 'Quick Construction Materials';
include __DIR__ . '/includes/header.php';
?>

<!-- Action Bar -->
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--primary);">Construction Material Purchases</h2>
        <p style="font-size: 13px; color: var(--text-muted);">
            Record site supplies, cement, aggregates, bricks, steel, and hardware.
        </p>
    </div>

    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('addMaterialModal')">
            <?php echo render_icon('plus'); ?>
            Record Material Purchase
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-bar">
    <form method="GET" class="filter-group" style="width: 100%; justify-content: space-between;">
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div class="search-box">
                <?php echo render_icon('search'); ?>
                <input type="text" name="search" value="<?php echo e($search); ?>" placeholder="Search material, supplier...">
            </div>

            <select name="site_id" class="filter-select">
                <option value="0">All Construction Sites</option>
                <?php foreach ($activeSites as $s): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($filter_site_id == $s['id']) ? 'selected' : ''; ?>>
                        <?php echo e($s['site_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <select name="category" class="filter-select">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo e($cat); ?>" <?php echo ($filter_category === $cat) ? 'selected' : ''; ?>>
                        <?php echo e($cat); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="date" name="date" class="form-control" style="padding: 6px 12px;" value="<?php echo e($filter_date); ?>" title="Filter by Purchase Date">

            <button type="submit" class="btn btn-outline btn-sm">Filter</button>
            <?php if (!empty($search) || $filter_site_id > 0 || !empty($filter_category) || !empty($filter_date)): ?>
                <a href="materials.php" class="btn btn-outline btn-sm">Reset</a>
            <?php endif; ?>
        </div>

        <div style="font-weight: 700; color: var(--primary); font-size: 14px;">
            Total in View: <span style="color: var(--accent-hover);"><?php echo format_currency($totalSpendInView); ?></span>
        </div>
    </form>
</div>

<!-- Materials Table -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php echo render_icon('materials'); ?>
            Material Purchase Register (<?php echo count($materials); ?> Bills)
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Material Details</th>
                        <th>Site Project</th>
                        <th>Category</th>
                        <th>Quantity & Unit</th>
                        <th>Unit Rate</th>
                        <th>Total Amount</th>
                        <th>Supplier</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 36px;">
                                No material purchases found. Click "Record Material Purchase" to add a purchase.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($materials as $m): ?>
                            <tr>
                                <td><strong><?php echo format_date($m['purchase_date']); ?></strong></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--primary);">
                                        <?php echo e($m['material_name']); ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: var(--text-muted);">
                                        #MAT-<?php echo str_pad($m['id'], 3, '0', STR_PAD_LEFT); ?>
                                        <?php if (!empty($m['notes'])): ?>
                                            &bull; <?php echo e($m['notes']); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <a href="site_details.php?id=<?php echo $m['site_id']; ?>" style="color: var(--primary); text-decoration: none;">
                                        <?php echo e($m['site_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?php echo e($m['category']); ?></span>
                                </td>
                                <td>
                                    <strong><?php echo (float)$m['quantity']; ?></strong> <?php echo e($m['unit']); ?>
                                </td>
                                <td><?php echo format_currency($m['unit_price']); ?></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--accent-hover); font-size: 14.5px;">
                                        <?php echo format_currency($m['total_amount']); ?>
                                    </div>
                                </td>
                                <td><?php echo e($m['supplier'] ?: '-'); ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button type="button" class="btn btn-outline btn-sm" onclick='editMaterial(<?php echo json_encode($m); ?>)' title="Edit Material Record">
                                        <?php echo render_icon('edit'); ?>
                                    </button>
                                    <form method="POST" action="actions/material_actions.php" style="display: inline;" onsubmit="return confirm('Delete this material purchase entry?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $m['id']; ?>">
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

<!-- Modal: Add Material Purchase -->
<div class="modal-overlay" id="addMaterialModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Record Material Purchase</h3>
            <button type="button" class="modal-close" onclick="closeModal('addMaterialModal')">&times;</button>
        </div>
        <form method="POST" action="actions/material_actions.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="site_id">Site Project <span class="required">*</span></label>
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
                        <label class="form-label" for="category">Category <span class="required">*</span></label>
                        <select id="category" name="category" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat); ?>"><?php echo e($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="material_name">Material Name & Specifications <span class="required">*</span></label>
                    <input type="text" id="material_name" name="material_name" class="form-control" placeholder="e.g. Ultratech 53-Grade Cement, 12mm TMT Steel" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="quantity">Quantity <span class="required">*</span></label>
                        <input type="number" id="quantity" name="quantity" class="form-control mat-qty-input" placeholder="e.g. 10" step="any" min="0.01" value="1" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="unit">Unit of Measure <span class="required">*</span></label>
                        <input type="text" id="unit" name="unit" class="form-control" placeholder="e.g. Bags, Trucks, Tons, Pieces, Meters" list="units_list" required>
                        <datalist id="units_list">
                            <option value="Bags">
                            <option value="Trucks">
                            <option value="Tons">
                            <option value="Pieces">
                            <option value="Meters">
                            <option value="Coils">
                            <option value="Brass">
                            <option value="Litres">
                        </datalist>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="unit_price">Unit Price (₹) <span class="required">*</span></label>
                        <input type="number" id="unit_price" name="unit_price" class="form-control mat-price-input" placeholder="e.g. 420" step="any" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="total_amount">Total Amount (₹) <span style="color:var(--text-muted);">(Auto-calculated)</span></label>
                        <input type="text" id="total_amount" name="total_amount" class="form-control mat-total-input" style="font-weight: 700; background: #f8fafc;" readonly>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="purchase_date">Purchase Date <span class="required">*</span></label>
                        <input type="date" id="purchase_date" name="purchase_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="supplier">Supplier / Shop Name</label>
                        <input type="text" id="supplier" name="supplier" class="form-control" placeholder="e.g. Sri Lakshmi Hardware & Cement">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="notes">Notes / Bill Reference</label>
                    <input type="text" id="notes" name="notes" class="form-control" placeholder="e.g. Invoice #8491, unloaded at terrace">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addMaterialModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Material Purchase</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Material Purchase -->
<div class="modal-overlay" id="editMaterialModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Edit Material Purchase</h3>
            <button type="button" class="modal-close" onclick="closeModal('editMaterialModal')">&times;</button>
        </div>
        <form method="POST" action="actions/material_actions.php">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_mat_id" name="id" value="">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="edit_mat_site">Site Project <span class="required">*</span></label>
                        <select id="edit_mat_site" name="site_id" class="form-control" required>
                            <?php foreach ($activeSites as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo e($s['site_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_mat_category">Category <span class="required">*</span></label>
                        <select id="edit_mat_category" name="category" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo e($cat); ?>"><?php echo e($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_mat_name">Material Name <span class="required">*</span></label>
                    <input type="text" id="edit_mat_name" name="material_name" class="form-control" required>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="edit_mat_qty">Quantity <span class="required">*</span></label>
                        <input type="number" id="edit_mat_qty" name="quantity" class="form-control mat-qty-input" step="any" min="0.01" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_mat_unit">Unit <span class="required">*</span></label>
                        <input type="text" id="edit_mat_unit" name="unit" class="form-control" required>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="edit_mat_price">Unit Price (₹) <span class="required">*</span></label>
                        <input type="number" id="edit_mat_price" name="unit_price" class="form-control mat-price-input" step="any" min="0" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_mat_total">Total Amount (₹)</label>
                        <input type="text" id="edit_mat_total" name="total_amount" class="form-control mat-total-input" style="font-weight: 700; background: #f8fafc;" readonly>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="edit_mat_date">Purchase Date <span class="required">*</span></label>
                        <input type="date" id="edit_mat_date" name="purchase_date" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="edit_mat_supplier">Supplier / Shop Name</label>
                        <input type="text" id="edit_mat_supplier" name="supplier" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="edit_mat_notes">Notes / Bill Reference</label>
                    <input type="text" id="edit_mat_notes" name="notes" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editMaterialModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editMaterial(m) {
    document.getElementById('edit_mat_id').value = m.id;
    document.getElementById('edit_mat_site').value = m.site_id;
    document.getElementById('edit_mat_category').value = m.category;
    document.getElementById('edit_mat_name').value = m.material_name;
    document.getElementById('edit_mat_qty').value = m.quantity;
    document.getElementById('edit_mat_unit').value = m.unit;
    document.getElementById('edit_mat_price').value = m.unit_price;
    document.getElementById('edit_mat_total').value = m.total_amount;
    document.getElementById('edit_mat_date').value = m.purchase_date;
    document.getElementById('edit_mat_supplier').value = m.supplier || '';
    document.getElementById('edit_mat_notes').value = m.notes || '';
    openModal('editMaterialModal');
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
