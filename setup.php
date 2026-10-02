<?php
/**
 * CivilTrack - Database Setup & Migration
 */

$statusMessage = '';
$statusType = '';
$installedTables = [];

if (isset($_POST['run_setup'])) {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $dbname = 'civiltrack';

    // Connect to MySQL server
    $conn = @mysqli_connect($host, $user, $pass);
    if (!$conn) {
        $statusType = 'error';
        $statusMessage = "Cannot connect to MySQL server: " . mysqli_connect_error();
    } else {
        mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        mysqli_select_db($conn, $dbname);

        $schemaFile = __DIR__ . '/database/schema.sql';
        if (!file_exists($schemaFile)) {
            $statusType = 'error';
            $statusMessage = "schema.sql file not found in database/ directory.";
        } else {
            $sql = file_get_contents($schemaFile);
            if (mysqli_multi_query($conn, $sql)) {
                while (mysqli_more_results($conn) && mysqli_next_result($conn)) {;}
                $statusType = 'success';
                $statusMessage = "Database 'civiltrack' has been created and populated successfully with seed data!";
            } else {
                $statusType = 'error';
                $statusMessage = "Setup error: " . mysqli_error($conn);
            }
        }
        mysqli_close($conn);
    }
}

// Check current database status
$dbExists = false;
$stats = [];
$chkConn = @mysqli_connect('localhost', 'root', '', 'civiltrack');
if ($chkConn) {
    $tables = ['users', 'sites', 'workers', 'attendance', 'payouts', 'materials', 'expenses'];
    $hasAny = false;
    foreach ($tables as $t) {
        $res = mysqli_query($chkConn, "SELECT COUNT(*) as cnt FROM `$t`");
        if ($res !== false) {
            $hasAny = true;
            $row = mysqli_fetch_assoc($res);
            $stats[$t] = $row['cnt'] ?? 0;
        }
    }
    $dbExists = $hasAny;
    mysqli_close($chkConn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CivilTrack - Database Setup & Installer</title>
    <style>
        :root {
            --primary: #0f172a;
            --accent: #f59e0b;
            --accent-hover: #d97706;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --border: #e2e8f0;
            --text-dark: #1e293b;
            --text-muted: #64748b;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .container {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
            max-width: 640px;
            width: 100%;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            padding: 32px 36px;
            border-bottom: 3px solid var(--accent);
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 8px;
        }
        .brand-icon {
            background: var(--accent);
            color: #0f172a;
            padding: 8px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        .header p {
            color: #94a3b8;
            font-size: 14.5px;
        }
        .content {
            padding: 36px;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14.5px;
            line-height: 1.5;
        }
        .alert-success {
            background-color: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background-color: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .table-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin: 20px 0;
        }
        .table-item {
            background: #f8fafc;
            border: 1px solid var(--border);
            padding: 12px 16px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 14px;
        }
        .table-name { font-weight: 600; color: #334155; }
        .table-badge {
            background: #e2e8f0;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            color: #0f172a;
        }
        .table-badge.has-data {
            background: #dcfce7;
            color: #15803d;
        }
        .actions {
            margin-top: 32px;
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }
        .btn-primary {
            background: var(--accent);
            color: #0f172a;
        }
        .btn-primary:hover {
            background: var(--accent-hover);
        }
        .btn-outline {
            background: transparent;
            color: #0f172a;
            border: 2px solid #0f172a;
        }
        .btn-outline:hover {
            background: #f1f5f9;
        }
        .info-box {
            background: #f1f5f9;
            border-left: 4px solid #3b82f6;
            padding: 14px 16px;
            border-radius: 0 8px 8px 0;
            font-size: 13.5px;
            color: #334155;
            margin-top: 24px;
        }
        .info-box strong { color: #0f172a; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <div class="header-brand">
            <div class="brand-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                    <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path>
                </svg>
            </div>
            <h1>CivilTrack Setup & Installer</h1>
        </div>
        <p>Offline Construction Site, Worker & Material Management System</p>
    </div>

    <div class="content">
        <?php if (!empty($statusMessage)): ?>
            <div class="alert alert-<?php echo $statusType; ?>">
                <strong><?php echo $statusType === 'success' ? 'Success!' : 'Notice:'; ?></strong>
                <?php echo htmlspecialchars($statusMessage); ?>
            </div>
        <?php endif; ?>

        <h3 style="font-size: 16px; margin-bottom: 12px; color: #1e293b;">Database Status (civiltrack)</h3>
        
        <?php if ($dbExists): ?>
            <div style="display:flex; align-items:center; gap:8px; color:#10b981; font-weight:600; font-size:14px; margin-bottom:12px;">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" /></svg>
                Database is active and populated with data.
            </div>
            
            <div class="table-grid">
                <?php foreach ($stats as $tbl => $count): ?>
                    <div class="table-item">
                        <span class="table-name"><?php echo ucfirst($tbl); ?></span>
                        <span class="table-badge has-data"><?php echo $count; ?> records</span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #64748b; font-size: 14.5px; line-height: 1.5; margin-bottom: 16px;">
                The <code>civiltrack</code> database is currently uninitialized. Click the button below to create tables and load sample project records.
            </p>
        <?php endif; ?>

        <form method="POST" style="margin-top: 20px;">
            <div class="actions">
                <button type="submit" name="run_setup" class="btn btn-primary" onclick="return confirm('Initialize database tables and default data?')">
                    <svg style="margin-right:8px;" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    <?php echo $dbExists ? 'Re-initialize Database' : 'Initialize Database'; ?>
                </button>

                <a href="login.php" class="btn btn-outline">
                    Proceed to Login &rarr;
                </a>
            </div>
        </form>

        <div class="info-box">
            <strong>Default Administrator Account:</strong><br>
            Username: <code>admin</code> &bull; Password: <code>admin123</code>
        </div>
    </div>
</div>

</body>
</html>
