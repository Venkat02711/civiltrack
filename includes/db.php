<?php
/**
 * Database connection setup using MySQLi
 */

if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'civiltrack');

function get_db() {
    static $conn = null;

    if ($conn !== null) {
        return $conn;
    }

    // Connect to MySQL database
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // If database not found, attempt auto-create
    if (!$conn) {
        $rootConn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS);
        if ($rootConn) {
            mysqli_query($rootConn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            mysqli_close($rootConn);

            $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $schemaFile = __DIR__ . '/../database/schema.sql';
            if ($conn && file_exists($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                mysqli_multi_query($conn, $sql);
                while (mysqli_more_results($conn) && mysqli_next_result($conn)) {;} // drain multi query
            }
        }
    }

    if (!$conn) {
        render_db_error("Cannot connect to MySQL database. Please ensure Apache and MySQL are running in XAMPP.<br><small style='color:#64748b;'>Error: " . htmlspecialchars(mysqli_connect_error()) . "</small>");
    }

    mysqli_set_charset($conn, "utf8mb4");
    return $conn;
}

function render_db_error($message) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - CivilTrack</title>
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
            .error-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 32px; max-width: 560px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); }
            .error-icon { width: 48px; height: 48px; color: #f59e0b; margin-bottom: 16px; }
            h2 { margin: 0 0 12px; font-size: 22px; color: #ffffff; }
            p { margin: 0 0 20px; line-height: 1.6; color: #94a3b8; font-size: 15px; }
            .tip { background: #0f172a; border-left: 4px solid #f59e0b; padding: 12px 16px; border-radius: 0 6px 6px 0; margin-bottom: 24px; font-size: 13.5px; color: #cbd5e1; }
            .btn { display: inline-block; background: #f59e0b; color: #0f172a; font-weight: 600; padding: 10px 20px; border-radius: 6px; text-decoration: none; border: none; cursor: pointer; }
            .btn:hover { background: #d97706; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <svg class="error-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <h2>Database Connection Notice</h2>
            <p><?php echo $message; ?></p>
            <div class="tip">
                <strong>How to fix:</strong><br>
                1. Open the <strong>XAMPP Control Panel</strong>.<br>
                2. Click <strong>Start</strong> next to <strong>MySQL</strong> and <strong>Apache</strong>.<br>
                3. Refresh this page, or open <a href="setup.php" style="color:#f59e0b; text-decoration: underline;">setup.php</a>.
            </div>
            <a href="setup.php" class="btn">Open Database Setup (setup.php)</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}
