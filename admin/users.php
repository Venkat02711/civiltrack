<?php
/**
 * CivilTrack - Admin: User Management
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$currentUser = current_user();

// Fetch all users
$res = mysqli_query($conn, "SELECT id, username, full_name, role, created_at FROM users ORDER BY id ASC");
$users = mysqli_fetch_all($res, MYSQLI_ASSOC);

$page_title = 'User Management';
include __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <h2 style="font-size: 20px; font-weight: 700; color: var(--primary);">System Administrators & Supervisors</h2>
        <p style="font-size: 13px; color: var(--text-muted);">Manage users who have access to manage sites, workers, and payouts.</p>
    </div>

    <div>
        <button type="button" class="btn btn-primary" onclick="openModal('addUserModal')">
            <?php echo render_icon('plus'); ?>
            Add Administrator
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <?php echo render_icon('users'); ?>
            Authorized System Users (<?php echo count($users); ?>)
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Account Created</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($u['username']); ?></strong>
                                <?php if ($u['id'] == $currentUser['id']): ?>
                                    <span class="badge badge-info" style="font-size: 10px; margin-left: 6px;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($u['full_name']); ?></td>
                            <td><span class="badge badge-neutral"><?php echo e($u['role']); ?></span></td>
                            <td><?php echo format_date($u['created_at']); ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="btn btn-outline btn-sm" onclick='openPasswordModal(<?php echo $u['id']; ?>, "<?php echo e($u['username']); ?>")' title="Change Password">
                                    Change Password
                                </button>
                                <?php if ($u['id'] != $currentUser['id']): ?>
                                    <form method="POST" action="../actions/user_actions.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete user <?php echo e($u['username']); ?>?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-outline btn-sm" style="color: #ef4444;" title="Delete User">
                                            <?php echo render_icon('trash'); ?>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Add User -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Create System User</h3>
            <button type="button" class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" action="../actions/user_actions.php">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. supervisor1" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Rajesh Sharma" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Min 6 characters" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="role">Role</label>
                    <select id="role" name="role" class="form-control">
                        <option value="Administrator">Administrator</option>
                        <option value="Site Supervisor">Site Supervisor</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create User</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Change Password -->
<div class="modal-overlay" id="changePasswordModal">
    <div class="modal-container">
        <div class="modal-header">
            <h3 class="modal-title">Change Password for <span id="pw_user_label"></span></h3>
            <button type="button" class="modal-close" onclick="closeModal('changePasswordModal')">&times;</button>
        </div>
        <form method="POST" action="../actions/user_actions.php">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" id="pw_user_id" name="id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="new_password">New Password <span class="required">*</span></label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Enter new password" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('changePasswordModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPasswordModal(id, username) {
    document.getElementById('pw_user_id').value = id;
    document.getElementById('pw_user_label').textContent = username;
    openModal('changePasswordModal');
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
