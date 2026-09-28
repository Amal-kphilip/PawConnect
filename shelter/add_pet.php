<?php
/**
 * PawConnect - Add New Pet (Shelter)
 */
$page_title = "Add New Pet";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('shelter');

$shelter_id = get_shelter_id_for_user($conn, current_user('user_id'));
if (!$shelter_id) {
    set_flash('error', 'Shelter profile not found.');
    header("Location: " . url('index.php'));
    exit;
}

$errors = [];
$name = '';
$species = 'Dog';
$breed = '';
$age_years = '1.0';
$gender = 'Male';
$size = 'Medium';
$vaccinated = 1;
$description = '';
$image_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name'] ?? '');
    $species = clean_input($_POST['species'] ?? 'Dog');
    $breed = clean_input($_POST['breed'] ?? '');
    $age_years = (float)($_POST['age_years'] ?? 0);
    $gender = clean_input($_POST['gender'] ?? 'Male');
    $size = clean_input($_POST['size'] ?? 'Medium');
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
        $errors[] = "Please provide the pet's breed.";
    }
    if ($age_years <= 0 || $age_years > 30) {
        $errors[] = "Please enter a realistic age in years (between 0.1 and 30).";
    }
    if (empty($description)) {
        $errors[] = "Please provide a friendly description for this pet.";
    }

    $final_image = '';

    // Handle File Upload if provided
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
            $errors[] = "Image size exceeds maximum limit of 2 MB.";
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
                $errors[] = "Failed to upload image file. Please check server permissions.";
            }
        }
    } elseif (!empty($image_url)) {
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
            $errors[] = "Please provide a valid web image URL.";
        } else {
            $final_image = $image_url;
        }
    }

    // Insert Pet
    if (empty($errors)) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO pets (shelter_id, name, species, breed, age_years, gender, size, vaccinated, description, image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Available')
        ");
        mysqli_stmt_bind_param($stmt, "isssdssiss", $shelter_id, $name, $species, $breed, $age_years, $gender, $size, $vaccinated, $description, $final_image);

        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            set_flash('success', 'Pet "' . $name . '" has been added successfully to your shelter listings!');
            header("Location: " . url('shelter/dashboard.php'));
            exit;
        } else {
            $errors[] = "Database insert error: " . mysqli_error($conn);
            mysqli_stmt_close($stmt);
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
        <h2 style="margin-bottom: 6px;">Add New Pet for Adoption</h2>
        <p class="text-muted" style="margin-bottom: 25px;">Enter the rescue animal's profile details and upload a photo.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('shelter/add_pet.php'); ?>" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label class="form-label required">Pet Name</label>
                <input type="text" name="name" class="form-control" required placeholder="e.g. Bruno" value="<?php echo e($name); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label required">Species</label>
                    <select name="species" class="form-control" required>
                        <option value="Dog" <?php echo $species === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                        <option value="Cat" <?php echo $species === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                        <option value="Rabbit" <?php echo $species === 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                        <option value="Bird" <?php echo $species === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                        <option value="Other" <?php echo $species === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Breed</label>
                    <input type="text" name="breed" class="form-control" required placeholder="e.g. Golden Retriever / Pariah" value="<?php echo e($breed); ?>">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                <div class="form-group">
                    <label class="form-label required">Age (Years)</label>
                    <input type="number" step="0.1" min="0.1" max="30" name="age_years" class="form-control" required value="<?php echo e($age_years); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label required">Gender</label>
                    <select name="gender" class="form-control" required>
                        <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label required">Size</label>
                    <select name="size" class="form-control" required>
                        <option value="Small" <?php echo $size === 'Small' ? 'selected' : ''; ?>>Small (&lt; 10kg)</option>
                        <option value="Medium" <?php echo $size === 'Medium' ? 'selected' : ''; ?>>Medium (10-25kg)</option>
                        <option value="Large" <?php echo $size === 'Large' ? 'selected' : ''; ?>>Large (25kg+)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-check">
                    <input type="checkbox" name="vaccinated" value="1" <?php echo $vaccinated ? 'checked' : ''; ?> class="form-check-input">
                    <span style="font-weight: 500;">💉 Pet is fully vaccinated and dewormed</span>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label required">Pet Story & Personality Description</label>
                <textarea name="description" class="form-control" rows="4" required placeholder="Describe temperament, friendliness with children/other animals, backstory, and special needs..."><?php echo e($description); ?></textarea>
            </div>

            <!-- Image Upload or URL -->
            <div style="background: var(--bg-subtle); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-color); margin-bottom: 25px;">
                <label class="form-label" style="font-weight: 600;">Pet Photo</label>
                <div class="form-group" style="margin-bottom: 12px;">
                    <label class="form-label" style="font-size: 0.85rem;">Upload Photo (JPG, PNG, WEBP, Max 2MB)</label>
                    <input type="file" name="pet_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                </div>
                <div class="text-center" style="font-size: 0.85rem; color: var(--text-muted); margin: 6px 0;">— OR —</div>
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" style="font-size: 0.85rem;">Paste Image Web URL (Unsplash, etc.)</label>
                    <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..." value="<?php echo e($image_url); ?>">
                </div>
            </div>

            <div>
                <button type="submit" class="btn btn-primary btn-lg btn-block">
                    Add Pet to Directory
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
