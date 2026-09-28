<?php
/**
 * PawConnect - Authentication & Utility Helpers
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a user is logged in
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get the current logged-in user array or a specific key
 * @param string|null $key
 * @return mixed
 */
function current_user($key = null) {
    if (!is_logged_in()) {
        return null;
    }
    if ($key !== null) {
        return $_SESSION['user'][$key] ?? null;
    }
    return $_SESSION['user'] ?? null;
}

/**
 * Get current user role
 * @return string|null 'adopter', 'shelter', 'admin', or null
 */
function user_role() {
    return $_SESSION['role'] ?? null;
}

/**
 * Require user to be logged in, otherwise redirect
 * @param string $redirect_to Path to redirect after login
 */
function require_login($redirect_to = null) {
    if (!is_logged_in()) {
        set_flash('error', 'Please login to access this page.');
        $target = 'login.php';
        if ($redirect_to) {
            $target .= '?redirect=' . urlencode($redirect_to);
        }
        header("Location: " . url($target));
        exit;
    }
}

/**
 * Require specific role(s) to access page
 * @param array|string $roles Allowed role or array of allowed roles
 */
function require_role($roles) {
    require_login();
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    if (!in_array(user_role(), $roles)) {
        set_flash('error', 'You do not have permission to view that page.');
        header("Location: " . url('index.php'));
        exit;
    }
}

/**
 * Get shelter_id for a given user_id (for shelter accounts)
 * @param mysqli $conn
 * @param int $user_id
 * @return int|null
 */
function get_shelter_id_for_user($conn, $user_id) {
    $stmt = mysqli_prepare($conn, "SELECT shelter_id FROM shelters WHERE user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        mysqli_stmt_close($stmt);
        return (int)$row['shelter_id'];
    }
    mysqli_stmt_close($stmt);
    return null;
}

/**
 * Generate or get CSRF token
 * @return string
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output a hidden CSRF input field
 * @return string HTML input tag
 */
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Verify submitted CSRF token
 * @param string|null $token
 * @return bool
 */
function verify_csrf_token($token = null) {
    if ($token === null) {
        $token = $_POST['csrf_token'] ?? '';
    }
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Set a flash message in session
 * @param string $type 'success', 'error', 'warning', 'info'
 * @param string $message
 */
function set_flash($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Retrieve and clear flash message
 * @return array|null
 */
function get_flash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Safe HTML output helper
 * @param string|null $data
 * @return string
 */
function e($data) {
    return htmlspecialchars((string)($data ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Build absolute URL relative to project root
 * @param string $path
 * @return string
 */
function url($path = '') {
    $path = ltrim($path, '/');
    if (defined('BASE_URL')) {
        return BASE_URL . '/' . $path;
    }
    return '/' . $path;
}

/**
 * Resolve pet image path or URL with fallback to default
 * @param string|null $image_path
 * @return string
 */
function get_pet_image_url($image_path) {
    if (empty($image_path)) {
        return url('uploads/default.png');
    }
    // Check if it's an external URL (http/https)
    if (preg_match('/^https?:\/\//i', $image_path)) {
        return $image_path;
    }
    // Check if it exists in uploads folder
    return url('uploads/' . $image_path);
}

/**
 * Clean user string input
 * @param string $input
 * @return string
 */
function clean_input($input) {
    return trim(strip_tags((string)$input));
}
