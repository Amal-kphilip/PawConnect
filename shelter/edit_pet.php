<?php
/**
 * PawConnect - Edit Pet (Shelter)
 */
$page_title = "Edit Pet Profile";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('shelter');

$shelter_id = get_shelter_id_for_user($conn, current_user('user_id'));
$pet_id = (int)($_GET['id'] ?? $_POST['pet_id'] ?? 0);

if ($pet_id <= 0 || !$shelter_id) {
    set_flash('error', 'Invalid pet ID.');
    header("Location: " . url('shelter/dashboard.php'));
    exit;
}

// Fetch Pet and verify shelter ownership
$stmt = mysqli_prepare($conn, "SELECT * FROM pets WHERE pet_id = ? AND shelter_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "ii", $pet_id, $shelter_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pet = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$pet) {
    set_flash('error', 'Pet not found or you do not have permission to edit this profile.');
    header("Location: " . url('shelter/dashboard.php'));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $species = clean_input($_POST['species'] ?? 'Dog');
    $breed = clean_input($_POST['breed'] ?? '');
    $age_years = (float)($_POST['age_years'] ?? 0);
    $gender = clean_input($_POST['gender'] ?? 'Male');
    $size = clean_input($_POST['size'] ?? 'Medium');
    $status = clean_input($_POST['status'] ?? 'Available');
    $vaccinated = isset($_POST['vaccinated']) ? 1 : 0;
    $description = clean_input($_POST['description'] ?? '');
    $image_url = clean_input($_POST['image_url'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = "Security token invalid. Please resubmit.";
    }

    if (empty($name)) {
        $errors[] = "Please provide the pet's name.";
    }
    if (empty($breed)) {
        $errors[] = "Please provide the breed.";
    }
    if ($age_years <= 0 || $age_years > 30) {
        $errors[] = "Please enter a valid age.";
    }
    if (!in_array($status, ['Available', 'Pending', 'Adopted'])) {
        $errors[] = "Invalid status selected.";
    }

    $final_image = $pet['image'];

    // Handle File Upload if new file provided
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['pet_image']['tmp_name'];
        $file_name = $_FILES['pet_image']['name'];
        $file_size = $_FILES['pet_image']['size'];
        $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array($ext, $allowed_exts)) {
            $errors[] = "Invalid image format. Allowed formats: JPG, PNG, WEBP.";
        } elseif ($file_size > $max_size) {
            $errors[] = "Image size exceeds 2 MB.";
        } else {
            $upload_dir = __DIR__ . '/../uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $new_filename = uniqid('pet_', true) . '.' . $ext;
            $dest_path = $upload_dir . $new_filename;

            if (move_uploaded_file($file_tmp, $dest_path)) {
                $final_image = $new_filename;
            } else {
                $errors[] = "Failed to upload image file.";
            }
        }
    } elseif (!empty($image_url)) {
        if (filter_var($image_url, FILTER_VALIDATE_URL)) {
            $final_image = $image_url;
        }
    }

    // Update Pet
    if (empty($errors)) {
        $update_stmt = mysqli_prepare($conn, "
            UPDATE pets 
            SET name = ?, species = ?, breed = ?, age_years = ?, gender = ?, size = ?, vaccinated = ?, description = ?, image = ?, status = ?
            WHERE pet_id = ? AND shelter_id = ?
        ");
        mysqli_stmt_bind_param($update_stmt, "sssdssisssii", $name, $species, $breed, $age_years, $gender, $size, $vaccinated, $description, $final_image, $status, $pet_id, $shelter_id);

        if (mysqli_stmt_execute($update_stmt)) {
            mysqli_stmt_close($update_stmt);
            set_flash('success', 'Pet details updated successfully!');
            header("Location: " . url('shelter/dashboard.php'));
            exit;
        } else {
            $errors[] = "Database update error: " . mysqli_error($conn);
            mysqli_stmt_close($update_stmt);
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 680px; margin: 20px auto;">
    <div style="margin-bottom: 15px;">
        <a href="<?php echo url('shelter/dashboard.php'); ?>" style="color: var(--text-muted); font-size: 0.9rem;">
            &larr; Back to Shelter Dashboard
        </a>
    </div>

    <div class="form-card">
        <h2 style="margin-bottom: 6px;">Edit Pet Profile: <?php echo e($pet['name']); ?></h2>
        <p class="text-muted" style="margin-bottom: 25px;">Update pet health status, adoption availability, or description.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('shelter/edit_pet.php'); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pet_id" value="<?php echo $pet['pet_id']; ?>">

            <div class="form-group">
                <label class="form-label required">Pet Name</label>
                <input type="text" name="name" class="form-control" required value="<?php echo e($_POST['name'] ?? $pet['name']); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label required">Species</label>
                    <?php $sp = $_POST['species'] ?? $pet['species']; ?>
                    <select name="species" class="form-control" required>
                        <option value="Dog" <?php echo $sp === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                        <option value="Cat" <?php echo $sp === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                        <option value="Rabbit" <?php echo $sp === 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                        <option value="Bird" <?php echo $sp === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                        <option value="Other" <?php echo $sp === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Breed</label>
                    <input type="text" name="breed" class="form-control" required value="<?php echo e($_POST['breed'] ?? $pet['breed']); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label required">Age (Years)</label>
                    <input type="number" step="0.1" min="0.1" max="30" name="age_years" class="form-control" required value="<?php echo e($_POST['age_years'] ?? $pet['age_years']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label required">Gender</label>
                    <?php $gen = $_POST['gender'] ?? $pet['gender']; ?>
                    <select name="gender" class="form-control" required>
                        <option value="Male" <?php echo $gen === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $gen === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Size</label>
                    <?php $sz = $_POST['size'] ?? $pet['size']; ?>
                    <select name="size" class="form-control" required>
                        <option value="Small" <?php echo $sz === 'Small' ? 'selected' : ''; ?>>Small</option>
                        <option value="Medium" <?php echo $sz === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="Large" <?php echo $sz === 'Large' ? 'selected' : ''; ?>>Large</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label required">Adoption Status</label>
                    <?php $st = $_POST['status'] ?? $pet['status']; ?>
                    <select name="status" class="form-control" required>
                        <option value="Available" <?php echo $st === 'Available' ? 'selected' : ''; ?>>Available</option>
                        <option value="Pending" <?php echo $st === 'Pending' ? 'selected' : ''; ?>>Pending Adoption</option>
                        <option value="Adopted" <?php echo $st === 'Adopted' ? 'selected' : ''; ?>>Adopted</option>
                    </select>
                </div>

                <div class="form-group" style="display: flex; align-items: center; margin-top: 25px;">
                    <?php $vac = isset($_POST['name']) ? (isset($_POST['vaccinated']) ? 1 : 0) : $pet['vaccinated']; ?>
                    <label class="form-check">
                        <input type="checkbox" name="vaccinated" value="1" <?php echo $vac ? 'checked' : ''; ?> class="form-check-input">
                        <span style="font-weight: 500;">💉 Fully Vaccinated</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label required">Description</label>
                <textarea name="description" class="form-control" rows="4" required><?php echo e($_POST['description'] ?? $pet['description']); ?></textarea>
            </div>

            <!-- Current Image & Change Image -->
            <div style="background: var(--bg-subtle); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 25px;">
                <label class="form-label" style="font-weight: 600;">Current Photo</label>
                <div style="display: flex; gap: 15px; align-items: center; margin-bottom: 15px;">
                    <img src="<?php echo e(get_pet_image_url($pet['image'])); ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border-color);" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">
                        To keep this image, leave the fields below blank.
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 10px;">
                    <label class="form-label" style="font-size: 0.85rem;">Upload New Photo (JPG, PNG, WEBP, Max 2MB)</label>
                    <input type="file" name="pet_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="text-center" style="font-size: 0.85rem; color: var(--text-muted); margin: 6px 0;">— OR —</div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 0.85rem;">Update Image URL</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://..." value="<?php echo e(preg_match('/^https?:\/\//i', $pet['image']) ? $pet['image'] : ''); ?>">
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
