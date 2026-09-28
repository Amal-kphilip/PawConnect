<?php
/**
 * PawConnect - Logout
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Unset all session variables
$_SESSION = [];

// Destroy session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start a fresh session to set the flash message
session_start();
set_flash('success', 'You have been successfully logged out.');
header("Location: " . url('login.php'));
exit;
