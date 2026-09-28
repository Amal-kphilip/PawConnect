<?php
/**
 * PawConnect - Database Connection Configuration
 * Procedural PHP with MySQLi
 */

// Error reporting for development (can be turned off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pet_adoption');
define('DB_PORT', 3306);

// Attempt database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Check connection
if (!$conn) {
    // If database does not exist yet, show a helpful message
    $error_msg = mysqli_connect_error();
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 25px; border: 1px solid #f5c6cb; background-color: #f8d7da; color: #721c24; border-radius: 8px;'>
        <h2 style='margin-top:0;'>⚠️ Database Connection Failed</h2>
        <p><strong>Error:</strong> " . htmlspecialchars($error_msg) . "</p>
        <p>Please make sure MySQL is running in your XAMPP / WAMP control panel and the database <code>pet_adoption</code> exists.</p>
        <p>You can create and populate the database by running the setup script:</p>
        <p><a href='seed.php' style='display: inline-block; padding: 10px 18px; background: #e02d3c; color: #fff; text-decoration: none; border-radius: 5px; font-weight: bold;'>Run seed.php Setup Now</a></p>
    </div>
    ");
}

// Set character set to UTF-8 for internationalization support
mysqli_set_charset($conn, 'utf8mb4');

// Define Base URL helper dynamically for any subfolder (e.g. /petadoption or /Pawconnect)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
// Ensure trailing slash or empty
$base_path = rtrim($script_name, '/');
// Find project root if in a subfolder like /shelter or /admin
if (basename($base_path) === 'shelter' || basename($base_path) === 'admin' || basename($base_path) === 'includes' || basename($base_path) === 'config') {
    $base_path = dirname($base_path);
}
$base_path = rtrim($base_path, '/');

if (!defined('BASE_URL')) {
    define('BASE_URL', $protocol . $host . $base_path);
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $base_path);
}
