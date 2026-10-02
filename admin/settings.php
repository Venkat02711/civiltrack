<?php
/**
 * CivilTrack - System Settings & Maintenance
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();

// Handle Database Reset
if (isset($_POST['reseed_data'])) {
    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        if (mysqli_multi_query($conn, $sql)) {
            while (mysqli_more_results($conn) && mysqli_next_result($conn)) {;}
            set_flash('success', 'Database restored to initial state successfully.');
            header("Location: settings.php");
            exit;
        } else {
            set_flash('error', 'Database operation failed: ' . mysqli_error($conn));
        }
    } else {
        set_flash('error', 'schema.sql file not found.');
    }
}

// Table Record Counts
$tableCounts = [];
$tables = ['sites', 'workers', 'attendance', 'payouts', 'materials', 'expenses', 'users'];
foreach ($tables as $t) {
    $res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM `$t`");
    if ($res) {
        $row = mysqli_fetch_assoc($res);
        $tableCounts[$t] = $row['cnt'] ?? 0;
    } else {
        $tableCounts[$t] = 0;
    }
}

$page_title = 'System Settings';
include __DIR__ . '/../includes/header.php';
?>

<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 24px;">
    <!-- Left Column: System Configuration & Business Rules -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <?php echo render_icon('settings'); ?>
                    Application Configuration
                </div>
                <span class="badge badge-neutral">Build v1.0</span>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; border: 1px solid var(--border); padding: 12px 14px; border-radius: 8px;">
                        <div style="font-size: 11.5px; color: var(--text-muted); text-transform: uppercase;">Application Name</div>
                        <div style="font-weight: 600; color: var(--primary); margin-top: 2px;">CivilTrack</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid var(--border); padding: 12px 14px; border-radius: 8px;">
                        <div style="font-size: 11.5px; color: var(--text-muted); text-transform: uppercase;">Environment</div>
                        <div style="font-weight: 600; color: var(--primary); margin-top: 2px;">Localhost (XAMPP)</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid var(--border); padding: 12px 14px; border-radius: 8px;">
                        <div style="font-size: 11.5px; color: var(--text-muted); text-transform: uppercase;">Default Currency</div>
                        <div style="font-weight: 600; color: var(--primary); margin-top: 2px;">Indian Rupee (₹ INR)</div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid var(--border); padding: 12px 14px; border-radius: 8px;">
                        <div style="font-size: 11.5px; color: var(--text-muted); text-transform: uppercase;">Database Server</div>
                        <div style="font-weight: 600; color: var(--primary); margin-top: 2px;">MySQL / MariaDB</div>
                    </div>
                </div>

                <h4 style="font-size: 14.5px; margin: 18px 0 10px; color: var(--primary);">Wage & Cost Rules</h4>
                <ul style="padding-left: 20px; font-size: 13.5px; color: var(--text-secondary); line-height: 1.8;">
                    <li><strong>Present:</strong> Worker receives 100% of their registered daily wage.</li>
                    <li><strong>Half Day:</strong> Worker receives 50% of daily wage rate.</li>
                    <li><strong>Absent:</strong> Zero wage (₹0.00).</li>
                    <li><strong>Material Expense:</strong> Quantity multiplied by Unit Purchase Price.</li>
                    <li><strong>Total Project Spend:</strong> Sum of Labour Payouts + Material Bills + Miscellaneous Site Expenses.</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Right Column: Database Status & Maintenance -->
    <div>
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <?php echo render_icon('dashboard'); ?>
                    Database Status
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th style="text-align: right;">Total Records</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableCounts as $t => $count): ?>
                                <tr>
                                    <td><code><?php echo e($t); ?></code></td>
                                    <td style="text-align: right; font-weight: 700; color: var(--primary);"><?php echo $count; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Database Maintenance -->
        <div class="card">
            <div class="card-header">
                <div class="card-title" style="color: #b45309;">
                    <?php echo render_icon('settings'); ?>
                    Database Maintenance
                </div>
            </div>
            <div class="card-body">
                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 14px; line-height: 1.5;">
                    Restore database to default initial state. This re-applies schema definitions and resets sample data.
                </p>

                <form method="POST">
                    <button type="submit" name="reseed_data" class="btn btn-outline" style="color: #b45309; border-color: #f59e0b; width: 100%;" onclick="return confirm('Are you sure you want to reset all data back to default?');">
                        Reset Database to Initial State
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
