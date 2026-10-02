<?php
/**
 * CivilTrack - Site Expense Management Actions
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_login();

$conn = get_db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'create') {
    $site_id = (int)($_POST['site_id'] ?? 0);
    $expense_type = trim($_POST['expense_type'] ?? 'Other');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $expense_date = trim($_POST['expense_date'] ?? date('Y-m-d'));

    if ($site_id <= 0 || empty($description) || $amount <= 0) {
        set_flash('error', 'Please fill in Site, Description, and valid Amount (> 0).');
        header("Location: ../admin/expenses.php");
        exit;
    }

    $safe_type = mysqli_real_escape_string($conn, $expense_type);
    $safe_desc = mysqli_real_escape_string($conn, $description);
    $safe_date = mysqli_real_escape_string($conn, $expense_date);

    $sql = "INSERT INTO expenses (site_id, expense_type, description, amount, expense_date) 
            VALUES ($site_id, '$safe_type', '$safe_desc', $amount, '$safe_date')";
    mysqli_query($conn, $sql);

    set_flash('success', "Site expense of ₹{$amount} recorded successfully.");
    header("Location: ../admin/expenses.php?site_id=" . $site_id);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id > 0) {
        mysqli_query($conn, "DELETE FROM expenses WHERE id = $id");
        set_flash('success', 'Expense entry removed successfully.');
    }
    header("Location: ../admin/expenses.php");
    exit;
}

header("Location: ../admin/expenses.php");
exit;
