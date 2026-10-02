<?php
/**
 * CivilTrack - Admin User Management Actions
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$current = current_user();

if ($action === 'create') {
    $username = trim($_POST['username'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'Administrator');

    if (empty($username) || empty($password) || empty($full_name)) {
        set_flash('error', 'All fields are required to create a new user.');
        header("Location: ../admin/users.php");
        exit;
    }

    $safe_username = mysqli_real_escape_string($conn, $username);

    // Check duplicate username
    $check_res = mysqli_query($conn, "SELECT id FROM users WHERE username = '$safe_username'");
    if (mysqli_fetch_assoc($check_res)) {
        set_flash('error', "Username '{$username}' is already taken.");
        header("Location: ../admin/users.php");
        exit;
    }

    $safe_name = mysqli_real_escape_string($conn, $full_name);
    $safe_role = mysqli_real_escape_string($conn, $role);
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $safe_hash = mysqli_real_escape_string($conn, $hash);

    mysqli_query($conn, "INSERT INTO users (username, password, full_name, role) VALUES ('$safe_username', '$safe_hash', '$safe_name', '$safe_role')");

    set_flash('success', "User '{$username}' created successfully.");
    header("Location: ../admin/users.php");
    exit;
}

if ($action === 'change_password') {
    $id = (int)($_POST['id'] ?? 0);
    $new_password = trim($_POST['new_password'] ?? '');

    if ($id <= 0 || empty($new_password)) {
        set_flash('error', 'Invalid password change request.');
        header("Location: ../admin/users.php");
        exit;
    }

    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    $safe_hash = mysqli_real_escape_string($conn, $hash);
    mysqli_query($conn, "UPDATE users SET password = '$safe_hash' WHERE id = $id");

    set_flash('success', 'User password updated successfully.');
    header("Location: ../admin/users.php");
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        set_flash('error', 'Invalid user specified.');
        header("Location: ../admin/users.php");
        exit;
    }

    if ($id == $current['id']) {
        set_flash('error', 'You cannot delete your own logged-in user account.');
        header("Location: ../admin/users.php");
        exit;
    }

    mysqli_query($conn, "DELETE FROM users WHERE id = $id");

    set_flash('success', 'User deleted successfully.');
    header("Location: ../admin/users.php");
    exit;
}

header("Location: ../admin/users.php");
exit;
