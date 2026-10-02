<?php
/**
 * CivilTrack - Sidebar Navigation
 */
$root = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : './';
$current_page = basename($_SERVER['PHP_SELF']);
$in_admin = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false);
$user = current_user();
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo-icon">
            <?php echo render_icon('hard_hat', 'icon'); ?>
        </div>
        <span class="brand-text">CivilTrack</span>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-category">Main Operations</li>
        
        <li>
            <a href="<?php echo $root; ?>dashboard.php" class="sidebar-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>">
                <?php echo render_icon('dashboard'); ?>
                <span>Dashboard</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>sites.php" class="sidebar-link <?php echo ($current_page === 'sites.php' || $current_page === 'site_details.php') ? 'active' : ''; ?>">
                <?php echo render_icon('sites'); ?>
                <span>Sites</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>workers.php" class="sidebar-link <?php echo ($current_page === 'workers.php' || $current_page === 'worker_details.php') ? 'active' : ''; ?>">
                <?php echo render_icon('workers'); ?>
                <span>Workers</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>attendance.php" class="sidebar-link <?php echo ($current_page === 'attendance.php') ? 'active' : ''; ?>">
                <?php echo render_icon('attendance'); ?>
                <span>Attendance / Daily</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>payouts.php" class="sidebar-link <?php echo ($current_page === 'payouts.php') ? 'active' : ''; ?>">
                <?php echo render_icon('payouts'); ?>
                <span>Payouts</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>materials.php" class="sidebar-link <?php echo ($current_page === 'materials.php') ? 'active' : ''; ?>">
                <?php echo render_icon('materials'); ?>
                <span>Materials</span>
            </a>
        </li>

        <li class="menu-category">Administration</li>

        <li>
            <a href="<?php echo $root; ?>admin/expenses.php" class="sidebar-link <?php echo ($in_admin && $current_page === 'expenses.php') ? 'active' : ''; ?>">
                <?php echo render_icon('expenses'); ?>
                <span>Site Expenses</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>admin/reports.php" class="sidebar-link <?php echo ($in_admin && $current_page === 'reports.php') ? 'active' : ''; ?>">
                <?php echo render_icon('reports'); ?>
                <span>Reports</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>admin/users.php" class="sidebar-link <?php echo ($in_admin && $current_page === 'users.php') ? 'active' : ''; ?>">
                <?php echo render_icon('users'); ?>
                <span>User Management</span>
            </a>
        </li>

        <li>
            <a href="<?php echo $root; ?>admin/settings.php" class="sidebar-link <?php echo ($in_admin && $current_page === 'settings.php') ? 'active' : ''; ?>">
                <?php echo render_icon('settings'); ?>
                <span>Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <div class="user-snippet">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['full_name'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="user-info-text">
                <div class="user-name"><?php echo e($user['full_name'] ?? 'Admin'); ?></div>
                <div class="user-role"><?php echo e($user['role'] ?? 'Supervisor'); ?></div>
            </div>
        </div>
        <a href="<?php echo $root; ?>logout.php" class="logout-btn" title="Sign out">
            <?php echo render_icon('logout'); ?>
        </a>
    </div>
</aside>
