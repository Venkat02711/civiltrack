<?php
/**
 * CivilTrack - Materials Purchase Management Actions
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    $site_id = (int)($_POST['site_id'] ?? 0);
    $material_name = trim($_POST['material_name'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $quantity = (float)($_POST['quantity'] ?? 1);
    $unit = trim($_POST['unit'] ?? 'Pieces');
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $total_amount = round($quantity * $unit_price, 2);
    $supplier = trim($_POST['supplier'] ?? '');
    $purchase_date = trim($_POST['purchase_date'] ?? date('Y-m-d'));
    $notes = trim($_POST['notes'] ?? '');

    if ($site_id <= 0 || empty($material_name) || $quantity <= 0 || $unit_price <= 0) {
        set_flash('error', 'Please fill in Site, Material Name, Quantity (>0), and Unit Price (>0).');
        header("Location: ../materials.php");
        exit;
    }

    $safe_name = mysqli_real_escape_string($conn, $material_name);
    $safe_cat = mysqli_real_escape_string($conn, $category);
    $safe_unit = mysqli_real_escape_string($conn, $unit);
    $safe_supplier = mysqli_real_escape_string($conn, $supplier);
    $safe_date = mysqli_real_escape_string($conn, $purchase_date);
    $safe_notes = mysqli_real_escape_string($conn, $notes);

    $sql = "INSERT INTO materials (site_id, material_name, category, quantity, unit, unit_price, total_amount, supplier, purchase_date, notes) 
            VALUES ($site_id, '$safe_name', '$safe_cat', $quantity, '$safe_unit', $unit_price, $total_amount, '$safe_supplier', '$safe_date', '$safe_notes')";
    mysqli_query($conn, $sql);

    set_flash('success', "Material purchase '{$material_name}' (₹{$total_amount}) recorded successfully.");
    header("Location: ../materials.php?site_id=" . $site_id);
    exit;
}

if ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    $site_id = (int)($_POST['site_id'] ?? 0);
    $material_name = trim($_POST['material_name'] ?? '');
    $category = trim($_POST['category'] ?? 'Other');
    $quantity = (float)($_POST['quantity'] ?? 1);
    $unit = trim($_POST['unit'] ?? 'Pieces');
    $unit_price = (float)($_POST['unit_price'] ?? 0);
    $total_amount = round($quantity * $unit_price, 2);
    $supplier = trim($_POST['supplier'] ?? '');
    $purchase_date = trim($_POST['purchase_date'] ?? date('Y-m-d'));
    $notes = trim($_POST['notes'] ?? '');

    if ($id <= 0 || $site_id <= 0 || empty($material_name) || $quantity <= 0 || $unit_price <= 0) {
        set_flash('error', 'Invalid material data provided for update.');
        header("Location: ../materials.php");
        exit;
    }

    $safe_name = mysqli_real_escape_string($conn, $material_name);
    $safe_cat = mysqli_real_escape_string($conn, $category);
    $safe_unit = mysqli_real_escape_string($conn, $unit);
    $safe_supplier = mysqli_real_escape_string($conn, $supplier);
    $safe_date = mysqli_real_escape_string($conn, $purchase_date);
    $safe_notes = mysqli_real_escape_string($conn, $notes);

    $sql = "UPDATE materials SET 
            site_id = $site_id, 
            material_name = '$safe_name', 
            category = '$safe_cat', 
            quantity = $quantity, 
            unit = '$safe_unit', 
            unit_price = $unit_price, 
            total_amount = $total_amount, 
            supplier = '$safe_supplier', 
            purchase_date = '$safe_date', 
            notes = '$safe_notes' 
            WHERE id = $id";
    mysqli_query($conn, $sql);

    set_flash('success', "Material purchase updated successfully.");
    header("Location: ../materials.php");
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM materials WHERE id = $id");
        set_flash('success', 'Material record removed.');
    }
    header("Location: ../materials.php");
    exit;
}

header("Location: ../materials.php");
exit;
