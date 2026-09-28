<?php
/**
 * PawConnect - User Login
 */
$page_title = "Log In";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    if (user_role() === 'shelter') {
        header("Location: " . url('shelter/dashboard.php'));
    } elseif (user_role() === 'admin') {
        header("Location: " . url('admin/dashboard.php'));
    } else {
        header("Location: " . url('pets.php'));
    }
    exit;
}

$error = '';
$redirect_url = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect_url = $_POST['redirect'] ?? '';

    // Verify CSRF
    if (!verify_csrf_token()) {
        $error = "Security token invalid or expired. Please try again.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT user_id, name, email, password, phone, role FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID on login for security
                session_regenerate_id(true);

                // Set session variables
                $_SESSION['user_id'] = (int)$user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['user'] = [
                    'user_id' => $user['user_id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'role' => $user['role']
                ];

                // If shelter, fetch and store shelter_id
                if ($user['role'] === 'shelter') {
                    $shelter_id = get_shelter_id_for_user($conn, $user['user_id']);
                    $_SESSION['shelter_id'] = $shelter_id;
                }

                set_flash('success', 'Welcome back, ' . $user['name'] . '!');

                // Handle redirect
                if (!empty($redirect_url) && strpos($redirect_url, 'http') === false) {
                    header("Location: " . url($redirect_url));
                    exit;
                }

                // Default role redirects
                if ($user['role'] === 'shelter') {
                    header("Location: " . url('shelter/dashboard.php'));
                } elseif ($user['role'] === 'admin') {
                    header("Location: " . url('admin/dashboard.php'));
                } else {
                    header("Location: " . url('pets.php'));
                }
                exit;
            } else {
                $error = "Invalid password. Please verify your credentials.";
            }
        } else {
            $error = "No registered account found with that email address.";
        }
        mysqli_stmt_close($stmt);
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 480px; margin: 30px auto;">
    <div class="form-card">
        <h2 class="text-center" style="margin-bottom: 6px;">Welcome to PawConnect</h2>
        <p class="text-muted text-center" style="margin-bottom: 25px;">Log in to access your portal account</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('login.php'); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="redirect" value="<?php echo e($redirect_url); ?>">

            <div class="form-group">
                <label class="form-label required">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@example.com" value="<?php echo e($_POST['email'] ?? ''); ?>" autofocus>
            </div>

            <div class="form-group">
                <label class="form-label required">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="Enter password">
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Sign In
                </button>
            </div>
        </form>

        <p class="text-center text-muted" style="margin-top: 20px; font-size: 0.9rem;">
            Don't have an account? <a href="<?php echo url('register.php'); ?>">Register here</a>.
        </p>

        <!-- Demo credentials helper box -->
        <div style="margin-top: 25px; padding: 14px; background: var(--bg-subtle); border-radius: var(--radius-sm); border: 1px dashed var(--border-color); font-size: 0.82rem; color: var(--text-muted);">
            <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">💡 Demo Credentials (Password: <code>password123</code>):</strong>
            <div>• <strong>Admin:</strong> admin@pawconnect.com</div>
            <div>• <strong>Shelter:</strong> shelter1@happytails.org (or shelter2 / shelter3)</div>
            <div>• <strong>Adopter:</strong> anjali@gmail.com (or rahul@gmail.com)</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
