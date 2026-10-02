<?php
/**
 * CivilTrack - Simple Admin Login
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// If already logged in, redirect directly to dashboard
if (is_logged_in()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$redirect = $_GET['redirect'] ?? 'dashboard.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $conn = get_db();
        $safeUser = mysqli_real_escape_string($conn, $username);
        $res = mysqli_query($conn, "SELECT * FROM users WHERE username = '$safeUser' LIMIT 1");
        $user = mysqli_fetch_assoc($res);

        if ($user && password_verify($password, $user['password'])) {
            // Authentication successful
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Safe redirection
            $target = (!empty($redirect) && strpos($redirect, 'http') === false) ? $redirect : 'dashboard.php';
            header("Location: " . $target);
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - CivilTrack</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #0f172a;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-box {
            background: #ffffff;
            border-radius: var(--radius-lg);
            width: 100%;
            max-width: 420px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45);
            overflow: hidden;
            border: 1px solid #334155;
        }
        .login-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            padding: 28px 24px;
            text-align: center;
            border-bottom: 3px solid var(--accent);
        }
        .login-logo {
            width: 44px;
            height: 44px;
            background: var(--accent);
            color: #0f172a;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }
        .login-logo svg {
            width: 24px;
            height: 24px;
        }
        .login-header h1 {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .login-header p {
            font-size: 13.5px;
            color: #94a3b8;
        }
        .login-body {
            padding: 28px 24px;
        }
    </style>
</head>
<body>

<div class="login-box">
    <div class="login-header">
        <div class="login-logo">
            <?php echo render_icon('hard_hat'); ?>
        </div>
        <h1>CivilTrack</h1>
        <p>Construction-Site Supervisor Portal</p>
    </div>

    <div class="login-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo render_icon('x'); ?>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['logged_out'])): ?>
            <div class="alert alert-info">
                <?php echo render_icon('check'); ?>
                <span>You have been signed out safely.</span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php<?php echo !empty($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="Enter admin username" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required autocomplete="current-password">
            </div>

            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px;">
                    Sign In to Portal
                </button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
