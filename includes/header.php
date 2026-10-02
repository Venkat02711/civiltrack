<?php
/**
 * CivilTrack - Page Top Header
 */
require_once __DIR__ . '/auth.php';
require_login();

$root = (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) ? '../' : './';
$page_title = $page_title ?? 'Dashboard';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> - CivilTrack Management</title>
    <link rel="stylesheet" href="<?php echo $root; ?>assets/css/style.css">
</head>
<body>

<div class="app-container">
    <!-- Sidebar Navigation -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Main Working Area -->
    <div class="main-content">
        <!-- Top App Bar -->
        <header class="top-header">
            <div class="header-left">
                <h1 class="page-title"><?php echo e($page_title); ?></h1>
            </div>
            <div class="header-right">
                <div class="date-pill">
                    <?php echo render_icon('calendar'); ?>
                    <span><?php echo date('l, d M Y'); ?></span>
                </div>
            </div>
        </header>

        <!-- Page Body Content Area -->
        <main class="page-body">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo e($flash['type']); ?>">
                    <?php if ($flash['type'] === 'success'): ?>
                        <?php echo render_icon('check'); ?>
                    <?php elseif ($flash['type'] === 'error'): ?>
                        <?php echo render_icon('x'); ?>
                    <?php endif; ?>
                    <div><?php echo e($flash['message']); ?></div>
                </div>
            <?php endif; ?>
