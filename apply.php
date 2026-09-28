<?php
/**
 * PawConnect - Submit Adoption Application
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('adopter');

$user_id = current_user('user_id');
$pet_id = (int)($_GET['pet_id'] ?? $_POST['pet_id'] ?? 0);

if ($pet_id <= 0) {
    set_flash('error', 'Please select a pet to apply for adoption.');
    header("Location: " . url('pets.php'));
    exit;
}

// Fetch Pet details
$stmt = mysqli_prepare($conn, "
    SELECT p.*, s.shelter_name, s.city 
    FROM pets p 
    JOIN shelters s ON p.shelter_id = s.shelter_id 
    WHERE p.pet_id = ? 
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $pet_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pet = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$pet) {
    set_flash('error', 'Pet not found.');
    header("Location: " . url('pets.php'));
    exit;
}

if ($pet['status'] === 'Adopted') {
    set_flash('error', 'This pet has already been adopted.');
    header("Location: " . url('pet_details.php?id=' . $pet_id));
    exit;
}

// Check for existing pending or approved application
$check_stmt = mysqli_prepare($conn, "SELECT app_id, status FROM applications WHERE pet_id = ? AND user_id = ? AND status IN ('Pending', 'Approved') LIMIT 1");
mysqli_stmt_bind_param($check_stmt, "ii", $pet_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_res = mysqli_stmt_get_result($check_stmt);
if ($existing = mysqli_fetch_assoc($check_res)) {
    mysqli_stmt_close($check_stmt);
    set_flash('info', 'You already have an active application (' . $existing['status'] . ') for ' . $pet['name'] . '.');
    header("Location: " . url('my_applications.php'));
    exit;
}
mysqli_stmt_close($check_stmt);

$errors = [];
$home_type = $_POST['home_type'] ?? 'Apartment';
$has_other_pets = $_POST['has_other_pets'] ?? '0';
$experience = $_POST['experience'] ?? '';
$reason = $_POST['reason'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $home_type = clean_input($_POST['home_type'] ?? '');
    $has_other_pets = (int)($_POST['has_other_pets'] ?? 0);
    $experience = clean_input($_POST['experience'] ?? '');
    $reason = clean_input($_POST['reason'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = "Invalid security token. Please resubmit the form.";
    }
    if (!in_array($home_type, ['Apartment', 'House', 'Farm'])) {
        $errors[] = "Please select a valid home type.";
    }
    if (empty($experience)) {
        $errors[] = "Please briefly share your previous pet care experience.";
    }
    if (empty($reason)) {
        $errors[] = "Please tell the shelter why you would like to adopt this pet.";
    }

    if (empty($errors)) {
        $insert_stmt = mysqli_prepare($conn, "
            INSERT INTO applications (pet_id, user_id, home_type, has_other_pets, experience, reason, status)
            VALUES (?, ?, ?, ?, ?, ?, 'Pending')
        ");
        mysqli_stmt_bind_param($insert_stmt, "iisis", $pet_id, $user_id, $home_type, $has_other_pets, $experience, $reason);
        
        if (mysqli_stmt_execute($insert_stmt)) {
            mysqli_stmt_close($insert_stmt);
            set_flash('success', 'Your adoption application for ' . $pet['name'] . ' has been submitted successfully! The shelter will review it.');
            header("Location: " . url('my_applications.php'));
            exit;
        } else {
            $errors[] = "Failed to submit application due to a database error: " . mysqli_error($conn);
            mysqli_stmt_close($insert_stmt);
        }
    }
}

$page_title = "Apply to Adopt " . $pet['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 680px; margin: 20px auto;">
    <div style="margin-bottom: 15px;">
        <a href="<?php echo url('pet_details.php?id=' . $pet['pet_id']); ?>" style="color: var(--text-muted); font-size: 0.9rem;">
            &larr; Back to <?php echo e($pet['name']); ?>'s profile
        </a>
    </div>

    <div class="form-card">
        <!-- Pet Quick Card Preview -->
        <div style="display: flex; gap: 16px; align-items: center; background: var(--bg-subtle); padding: 14px; border-radius: var(--radius-sm); margin-bottom: 25px; border: 1px solid var(--border-color);">
            <img src="<?php echo e(get_pet_image_url($pet['image'])); ?>" alt="<?php echo e($pet['name']); ?>" style="width: 70px; height: 70px; object-fit: cover; border-radius: var(--radius-sm);" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
            <div>
                <h3 style="margin-bottom: 2px; font-size: 1.2rem;">Adoption Application for <?php echo e($pet['name']); ?></h3>
                <div class="text-muted" style="font-size: 0.88rem;">
                    <?php echo e($pet['breed']); ?> &bull; <?php echo e($pet['species']); ?> &bull; 📍 <?php echo e($pet['city']); ?> (<?php echo e($pet['shelter_name']); ?>)
                </div>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('apply.php'); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">

            <div class="form-group">
                <label class="form-label">Applicant Name</label>
                <input type="text" class="form-control" value="<?php echo e(current_user('name')); ?> (<?php echo e(current_user('email')); ?>)" disabled style="background-color: var(--bg-subtle);">
            </div>

            <div class="form-group">
                <label class="form-label required">What type of home do you live in?</label>
                <select name="home_type" class="form-control" required>
                    <option value="Apartment" <?php echo $home_type === 'Apartment' ? 'selected' : ''; ?>>Apartment / Flat</option>
                    <option value="House" <?php echo $home_type === 'House' ? 'selected' : ''; ?>>Independent House with Yard</option>
                    <option value="Farm" <?php echo $home_type === 'Farm' ? 'selected' : ''; ?>>Farm / Gated Estate</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label required">Do you currently have any other pets at home?</label>
                <div style="display: flex; gap: 20px; margin-top: 6px;">
                    <label class="form-check">
                        <input type="radio" name="has_other_pets" value="1" <?php echo $has_other_pets == '1' ? 'checked' : ''; ?> class="form-check-input">
                        <span>Yes, I have other pets</span>
                    </label>
                    <label class="form-check">
                        <input type="radio" name="has_other_pets" value="0" <?php echo $has_other_pets == '0' ? 'checked' : ''; ?> class="form-check-input">
                        <span>No, currently no pets</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">Pet Care Experience</label>
                <textarea name="experience" class="form-control" rows="3" required placeholder="Describe any previous experience you or your family have caring for animals..."><?php echo e($experience); ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label required">Why would you like to adopt <?php echo e($pet['name']); ?>?</label>
                <textarea name="reason" class="form-control" rows="4" required placeholder="Tell the shelter about your home routine, why you chose <?php echo e($pet['name']); ?>, and how you plan to care for them..."><?php echo e($reason); ?></textarea>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Submit Adoption Application
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
