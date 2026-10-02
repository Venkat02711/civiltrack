<?php
/**
 * CivilTrack - Worker Management Actions
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $worker_type = trim($_POST['worker_type'] ?? 'General Worker');
    $daily_wage = (float)($_POST['daily_wage'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');

    if (empty($name) || empty($phone) || $daily_wage <= 0) {
        set_flash('error', 'Worker Name, Phone, and a valid Daily Wage (> 0) are required.');
        header("Location: ../workers.php");
        exit;
    }

    $safe_name = mysqli_real_escape_string($conn, $name);
    $safe_phone = mysqli_real_escape_string($conn, $phone);
    $safe_address = mysqli_real_escape_string($conn, $address);
    $safe_type = mysqli_real_escape_string($conn, $worker_type);
    $safe_status = mysqli_real_escape_string($conn, $status);

    $sql = "INSERT INTO workers (name, phone, address, worker_type, daily_wage, status) 
            VALUES ('$safe_name', '$safe_phone', '$safe_address', '$safe_type', $daily_wage, '$safe_status')";
    mysqli_query($conn, $sql);

    set_flash('success', "Worker '{$name}' registered successfully with daily wage ₹{$daily_wage}.");
    header("Location: ../workers.php");
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $worker_type = trim($_POST['worker_type'] ?? 'General Worker');
    $daily_wage = (float)($_POST['daily_wage'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');

    if ($id <= 0 || empty($name) || empty($phone) || $daily_wage <= 0) {
        set_flash('error', 'Invalid worker details provided.');
        header("Location: ../workers.php");
        exit;
    }

    $safe_name = mysqli_real_escape_string($conn, $name);
    $safe_phone = mysqli_real_escape_string($conn, $phone);
    $safe_address = mysqli_real_escape_string($conn, $address);
    $safe_type = mysqli_real_escape_string($conn, $worker_type);
    $safe_status = mysqli_real_escape_string($conn, $status);

    $sql = "UPDATE workers SET 
            name = '$safe_name', 
            phone = '$safe_phone', 
            address = '$safe_address', 
            worker_type = '$safe_type', 
            daily_wage = $daily_wage, 
            status = '$safe_status' 
            WHERE id = $id";
    mysqli_query($conn, $sql);

    set_flash('success', "Worker '{$name}' updated successfully.");
    $redirect = !empty($_POST['redirect_details']) ? "../worker_details.php?id=" . $id : "../workers.php";
    header("Location: " . $redirect);
    exit;
}

if ($action === 'toggle_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');

    if ($id > 0 && in_array($status, ['Active', 'Inactive'])) {
        $safe_status = mysqli_real_escape_string($conn, $status);
        mysqli_query($conn, "UPDATE workers SET status = '$safe_status' WHERE id = $id");
        set_flash('success', "Worker status changed to {$status}.");
    }

    header("Location: ../workers.php");
    exit;
}

header("Location: ../workers.php");
exit;
