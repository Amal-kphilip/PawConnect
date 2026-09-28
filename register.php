<?php
/**
 * PawConnect - User & Shelter Registration
 */
$page_title = "Create an Account";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    header("Location: " . url('index.php'));
    exit;
}

$errors = [];
$role = isset($_GET['role']) && $_GET['role'] === 'shelter' ? 'shelter' : 'adopter';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = clean_input($_POST['role'] ?? 'adopter');
    $name = clean_input($_POST['name'] ?? '');
    $email = clean_input($_POST['email'] ?? '');
    $phone = clean_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Shelter specific fields
    $shelter_name = clean_input($_POST['shelter_name'] ?? '');
    $city = clean_input($_POST['city'] ?? '');
    $address = clean_input($_POST['address'] ?? '');
    $description = clean_input($_POST['description'] ?? '');

    // CSRF verification
    if (!verify_csrf_token()) {
        $errors[] = "Invalid or expired security token. Please try again.";
    }

    // Common validations
    if (empty($name)) {
        $errors[] = "Please provide your full name / representative name.";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    }
    if (empty($phone)) {
        $errors[] = "Please provide your contact phone number.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Role specific validations
    if ($role === 'shelter') {
        if (empty($shelter_name)) {
            $errors[] = "Please provide the official Shelter / Rescue Organization name.";
        }
        if (empty($city)) {
            $errors[] = "Please specify the city where the shelter is located.";
        }
        if (empty($address)) {
            $errors[] = "Please provide the physical shelter address.";
        }
    } else {
        $role = 'adopter';
    }

    // Check if email already exists
    if (empty($errors)) {
        $check_stmt = mysqli_prepare($conn, "SELECT user_id FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($check_stmt, "s", $email);
        mysqli_stmt_execute($check_stmt);
        mysqli_stmt_store_result($check_stmt);
        if (mysqli_stmt_num_rows($check_stmt) > 0) {
            $errors[] = "An account with this email address already exists.";
        }
        mysqli_stmt_close($check_stmt);
    }

    // Insert user and shelter if no errors
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Start transaction
        mysqli_begin_transaction($conn);
        try {
            $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $hashed_password, $phone, $role);
            mysqli_stmt_execute($stmt);
            $user_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt);

            if ($role === 'shelter') {
                $shelter_stmt = mysqli_prepare($conn, "INSERT INTO shelters (user_id, shelter_name, address, city, phone, description) VALUES (?, ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($shelter_stmt, "isssss", $user_id, $shelter_name, $address, $city, $phone, $description);
                mysqli_stmt_execute($shelter_stmt);
                mysqli_stmt_close($shelter_stmt);
            }

            mysqli_commit($conn);
            set_flash('success', 'Registration successful! You can now log in with your credentials.');
            header("Location: " . url('login.php'));
            exit;
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $errors[] = "Registration failed due to a database error: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 600px; margin: 20px auto;">
    <div class="form-card">
        <h2 class="text-center" style="margin-bottom: 8px;">Create an Account</h2>
        <p class="text-muted text-center" style="margin-bottom: 25px;">
            Join PawConnect to adopt loving pets or register your animal rescue shelter.
        </p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Role Selector Tabs -->
        <div style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid var(--border-color); padding-bottom: 15px;">
            <button type="button" id="tabAdopter" class="btn <?php echo $role === 'adopter' ? 'btn-primary' : 'btn-outline'; ?> btn-block" onclick="switchRole('adopter')">
                🐶 Adopter
            </button>
            <button type="button" id="tabShelter" class="btn <?php echo $role === 'shelter' ? 'btn-primary' : 'btn-outline'; ?> btn-block" onclick="switchRole('shelter')">
                🏢 Shelter / Organization
            </button>
        </div>

        <form action="<?php echo url('register.php'); ?>" method="POST" id="regForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="role" id="roleInput" value="<?php echo e($role); ?>">

            <!-- Shelter Specific Fields -->
            <div id="shelterFields" style="<?php echo $role === 'shelter' ? 'display:block;' : 'display:none;'; ?>">
                <div class="form-group">
                    <label class="form-label required">Shelter / Organization Name</label>
                    <input type="text" name="shelter_name" class="form-control" placeholder="e.g. Happy Tails Animal Sanctuary" value="<?php echo e($_POST['shelter_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label required">City</label>
                    <input type="text" name="city" class="form-control" placeholder="e.g. Kochi" value="<?php echo e($_POST['city'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label required">Physical Address</label>
                    <input type="text" name="address" class="form-control" placeholder="Street, landmark, pincode" value="<?php echo e($_POST['address'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Shelter Overview / Mission</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Briefly describe your shelter and animals in care..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- Common Fields -->
            <div class="form-group">
                <label class="form-label required" id="nameLabel">
                    <?php echo $role === 'shelter' ? 'Representative Full Name' : 'Full Name'; ?>
                </label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Anjali Nair" value="<?php echo e($_POST['name'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label required">Email Address</label>
                <input type="email" name="email" class="form-control" required placeholder="name@example.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label required">Phone Number</label>
                <input type="tel" name="phone" class="form-control" required placeholder="+91 9876543210" value="<?php echo e($_POST['phone'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label required">Password</label>
                <input type="password" name="password" class="form-control" required placeholder="At least 6 characters" minlength="6">
            </div>

            <div class="form-group">
                <label class="form-label required">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter password" minlength="6">
            </div>

            <div style="margin-top: 25px;">
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Register Account
                </button>
            </div>
        </form>

        <p class="text-center text-muted" style="margin-top: 20px; font-size: 0.9rem;">
            Already have an account? <a href="<?php echo url('login.php'); ?>">Log in here</a>.
        </p>
    </div>
</div>

<script>
function switchRole(role) {
    document.getElementById('roleInput').value = role;
    const shelterFields = document.getElementById('shelterFields');
    const tabAdopter = document.getElementById('tabAdopter');
    const tabShelter = document.getElementById('tabShelter');
    const nameLabel = document.getElementById('nameLabel');

    if (role === 'shelter') {
        shelterFields.style.display = 'block';
        tabShelter.className = 'btn btn-primary btn-block';
        tabAdopter.className = 'btn btn-outline btn-block';
        nameLabel.textContent = 'Representative Full Name *';
    } else {
        shelterFields.style.display = 'none';
        tabAdopter.className = 'btn btn-primary btn-block';
        tabShelter.className = 'btn btn-outline btn-block';
        nameLabel.textContent = 'Full Name *';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
