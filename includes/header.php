<?php
/**
 * PawConnect - Header Template
 */
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/db.php';
}
require_once __DIR__ . '/auth.php';

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = isset($page_title) ? $page_title . ' - PawConnect' : 'PawConnect - Pet Adoption Portal';
$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?></title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="<?php echo url('css/style.css'); ?>">
</head>
<body>

    <!-- Sticky Navigation Bar -->
    <header class="navbar">
        <div class="container nav-container">
            <a href="<?php echo url('index.php'); ?>" class="brand">
                <span class="brand-icon">🐾</span>
                <span>PawConnect</span>
            </a>

            <!-- Mobile Hamburger Button -->
            <button class="hamburger" id="navToggle" aria-label="Toggle menu">
                ☰
            </button>

            <!-- Navigation Links -->
            <nav class="nav-menu" id="navMenu">
                <a href="<?php echo url('index.php'); ?>" class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">Home</a>
                <a href="<?php echo url('pets.php'); ?>" class="nav-link <?php echo $current_page === 'pets.php' ? 'active' : ''; ?>">Browse Pets</a>

                <?php if (is_logged_in()): ?>
                    <?php $role = user_role(); ?>

                    <?php if ($role === 'adopter'): ?>
                        <a href="<?php echo url('my_applications.php'); ?>" class="nav-link <?php echo $current_page === 'my_applications.php' ? 'active' : ''; ?>">My Applications</a>
                        <a href="<?php echo url('my_visits.php'); ?>" class="nav-link <?php echo $current_page === 'my_visits.php' ? 'active' : ''; ?>">My Visits</a>
                    <?php elseif ($role === 'shelter'): ?>
                        <a href="<?php echo url('shelter/dashboard.php'); ?>" class="nav-link <?php echo $current_page === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'shelter') !== false ? 'active' : ''; ?>">Shelter Dashboard</a>
                        <a href="<?php echo url('shelter/add_pet.php'); ?>" class="nav-link <?php echo $current_page === 'add_pet.php' ? 'active' : ''; ?>">+ Add Pet</a>
                        <a href="<?php echo url('shelter/view_applications.php'); ?>" class="nav-link <?php echo $current_page === 'view_applications.php' ? 'active' : ''; ?>">Applications</a>
                        <a href="<?php echo url('shelter/manage_visits.php'); ?>" class="nav-link <?php echo $current_page === 'manage_visits.php' ? 'active' : ''; ?>">Visits</a>
                    <?php elseif ($role === 'admin'): ?>
                        <a href="<?php echo url('admin/dashboard.php'); ?>" class="nav-link <?php echo $current_page === 'dashboard.php' && strpos($_SERVER['REQUEST_URI'], 'admin') !== false ? 'active' : ''; ?>">Admin Panel</a>
                    <?php endif; ?>

                    <div class="nav-actions">
                        <div class="user-badge">
                            <span>👤 <?php echo e(current_user('name')); ?></span>
                            <span class="user-role-tag"><?php echo e($role); ?></span>
                        </div>
                        <a href="<?php echo url('logout.php'); ?>" class="btn btn-outline btn-sm">Logout</a>
                    </div>

                <?php else: ?>
                    <div class="nav-actions">
                        <a href="<?php echo url('login.php'); ?>" class="btn btn-outline btn-sm">Login</a>
                        <a href="<?php echo url('register.php'); ?>" class="btn btn-primary btn-sm">Register</a>
                    </div>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Main Content Container -->
    <main class="main-content">
        <div class="container">
            <?php if ($flash): ?>
                <div class="flash-container">
                    <div class="alert alert-<?php echo e($flash['type']); ?>">
                        <span><?php echo e($flash['message']); ?></span>
                        <button type="button" class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
                    </div>
                </div>
            <?php endif; ?>
