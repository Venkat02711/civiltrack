<?php
/**
 * CivilTrack - Site Management Actions
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    $site_name = trim($_POST['site_name'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $expected_end_date = trim($_POST['expected_end_date'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if (empty($site_name) || empty($location) || empty($start_date)) {
        set_flash('error', 'Site Name, Location, and Start Date are required.');
        header("Location: ../sites.php");
        exit;
    }

    $safe_site_name = mysqli_real_escape_string($conn, $site_name);
    $safe_client_name = mysqli_real_escape_string($conn, $client_name);
    $safe_location = mysqli_real_escape_string($conn, $location);
    $safe_start_date = mysqli_real_escape_string($conn, $start_date);
    $safe_end_date = !empty($expected_end_date) ? "'" . mysqli_real_escape_string($conn, $expected_end_date) . "'" : "NULL";
    $safe_status = mysqli_real_escape_string($conn, $status);

    $sql = "INSERT INTO sites (site_name, client_name, location, start_date, expected_end_date, status) 
            VALUES ('$safe_site_name', '$safe_client_name', '$safe_location', '$safe_start_date', $safe_end_date, '$safe_status')";
    mysqli_query($conn, $sql);

    set_flash('success', "Site '{$site_name}' created successfully.");
    header("Location: ../sites.php");
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $site_name = trim($_POST['site_name'] ?? '');
    $client_name = trim($_POST['client_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $expected_end_date = trim($_POST['expected_end_date'] ?? '');
    $status = trim($_POST['status'] ?? 'Active');

    if ($id <= 0 || empty($site_name) || empty($location)) {
        set_flash('error', 'Invalid site information provided.');
        header("Location: ../sites.php");
        exit;
    }

    $safe_site_name = mysqli_real_escape_string($conn, $site_name);
    $safe_client_name = mysqli_real_escape_string($conn, $client_name);
    $safe_location = mysqli_real_escape_string($conn, $location);
    $safe_start_date = mysqli_real_escape_string($conn, $start_date);
    $safe_end_date = !empty($expected_end_date) ? "'" . mysqli_real_escape_string($conn, $expected_end_date) . "'" : "NULL";
    $safe_status = mysqli_real_escape_string($conn, $status);

    $sql = "UPDATE sites SET 
            site_name = '$safe_site_name', 
            client_name = '$safe_client_name', 
            location = '$safe_location', 
            start_date = '$safe_start_date', 
            expected_end_date = $safe_end_date, 
            status = '$safe_status' 
            WHERE id = $id";
    mysqli_query($conn, $sql);

    set_flash('success', "Site '{$site_name}' updated successfully.");
    $redirect = !empty($_POST['redirect_details']) ? "../site_details.php?id=" . $id : "../sites.php";
    header("Location: " . $redirect);
    exit;
}

if ($action === 'toggle_status') {
    $id = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? 'Active');

    if ($id > 0 && in_array($status, ['Active', 'Completed', 'On Hold'])) {
        $safe_status = mysqli_real_escape_string($conn, $status);
        mysqli_query($conn, "UPDATE sites SET status = '$safe_status' WHERE id = $id");
        set_flash('success', "Site status updated to {$status}.");
    }

    header("Location: ../sites.php");
    exit;
}

header("Location: ../sites.php");
exit;
