<?php
/**
 * PawConnect - Pet Details Page
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$pet_id = (int)($_GET['id'] ?? 0);
if ($pet_id <= 0) {
    set_flash('error', 'Invalid pet selected.');
    header("Location: " . url('pets.php'));
    exit;
}

// Fetch Pet details along with Shelter info
$stmt = mysqli_prepare($conn, "
    SELECT p.*, s.shelter_name, s.address, s.city, s.phone AS shelter_phone, s.description AS shelter_description
    FROM pets p
    JOIN shelters s ON p.shelter_id = s.shelter_id
    WHERE p.pet_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $pet_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$pet = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$pet) {
    set_flash('error', 'The requested pet profile could not be found.');
    header("Location: " . url('pets.php'));
    exit;
}

$page_title = $pet['name'] . " (" . $pet['breed'] . ") - PawConnect";

// Check if current user has an existing application or visit for this pet
$user_id = current_user('user_id');
$has_applied = false;
$has_visit = false;

if ($user_id && user_role() === 'adopter') {
    // Check application
    $app_check = mysqli_prepare($conn, "SELECT app_id, status FROM applications WHERE pet_id = ? AND user_id = ? ORDER BY applied_at DESC LIMIT 1");
    mysqli_stmt_bind_param($app_check, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($app_check);
    $app_res = mysqli_stmt_get_result($app_check);
    if ($app_row = mysqli_fetch_assoc($app_res)) {
        $has_applied = $app_row;
    }
    mysqli_stmt_close($app_check);

    // Check visit
    $vis_check = mysqli_prepare($conn, "SELECT visit_id, visit_date, visit_time, status FROM visits WHERE pet_id = ? AND user_id = ? AND status = 'Scheduled' LIMIT 1");
    mysqli_stmt_bind_param($vis_check, "ii", $pet_id, $user_id);
    mysqli_stmt_execute($vis_check);
    $vis_res = mysqli_stmt_get_result($vis_check);
    if ($vis_row = mysqli_fetch_assoc($vis_res)) {
        $has_visit = $vis_row;
    }
    mysqli_stmt_close($vis_check);
}

// Fetch similar pets from the same species or city
$similar_stmt = mysqli_prepare($conn, "
    SELECT p.*, s.city 
    FROM pets p 
    JOIN shelters s ON p.shelter_id = s.shelter_id 
    WHERE p.species = ? AND p.pet_id != ? AND p.status = 'Available' 
    LIMIT 3
");
mysqli_stmt_bind_param($similar_stmt, "si", $pet['species'], $pet_id);
mysqli_stmt_execute($similar_stmt);
$similar_res = mysqli_stmt_get_result($similar_stmt);
$similar_pets = [];
while ($s_row = mysqli_fetch_assoc($similar_res)) {
    $similar_pets[] = $s_row;
}
mysqli_stmt_close($similar_stmt);

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="<?php echo url('pets.php'); ?>" style="color: var(--text-muted); font-size: 0.9rem;">
        &larr; Back to all pets
    </a>
</div>

<div class="pet-details-layout">
    <!-- Left Column: Image -->
    <div class="details-image-box">
        <img src="<?php echo e(get_pet_image_url($pet['image'])); ?>" alt="<?php echo e($pet['name']); ?>" class="details-image" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
    </div>

    <!-- Right Column: Details & Actions -->
    <div class="details-info-box">
        <div class="details-title-row">
            <div>
                <h1 style="margin-bottom: 4px; font-size: 2.2rem;"><?php echo e($pet['name']); ?></h1>
                <p class="text-muted" style="margin-bottom: 0; font-size: 1.1rem;"><?php echo e($pet['breed']); ?> &bull; <?php echo e($pet['species']); ?></p>
            </div>
            <span class="badge badge-<?php echo strtolower($pet['status']); ?>" style="font-size: 0.95rem; padding: 6px 14px;">
                <?php echo e($pet['status']); ?>
            </span>
        </div>

        <!-- Specifications Grid -->
        <div class="specs-grid">
            <div class="spec-item">
                <span class="spec-label">Age</span>
                <span class="spec-val"><?php echo e($pet['age_years']); ?> Years</span>
            </div>
            <div class="spec-item">
                <span class="spec-label">Gender</span>
                <span class="spec-val"><?php echo e($pet['gender']); ?></span>
            </div>
            <div class="spec-item">
                <span class="spec-label">Size Category</span>
                <span class="spec-val"><?php echo e($pet['size']); ?></span>
            </div>
            <div class="spec-item">
                <span class="spec-label">Vaccination Status</span>
                <span class="spec-val" style="color: <?php echo $pet['vaccinated'] ? '#1E8449' : '#C0392B'; ?>;">
                    <?php echo $pet['vaccinated'] ? '✓ Fully Vaccinated' : '✗ Not Yet Vaccinated'; ?>
                </span>
            </div>
        </div>

        <!-- Description -->
        <div style="margin-bottom: 20px;">
            <h3>About <?php echo e($pet['name']); ?></h3>
            <p style="color: var(--text-main); white-space: pre-line; line-height: 1.7;">
                <?php echo e($pet['description']); ?>
            </p>
        </div>

        <!-- Shelter Contact Info -->
        <div class="shelter-contact-box">
            <div style="font-weight: 700; margin-bottom: 4px; color: var(--accent-hover);">
                🏢 Rescued by <?php echo e($pet['shelter_name']); ?>
            </div>
            <div>📍 <strong>Location:</strong> <?php echo e($pet['address']); ?>, <?php echo e($pet['city']); ?></div>
            <div>📞 <strong>Contact Phone:</strong> <?php echo e($pet['shelter_phone']); ?></div>
            <?php if (!empty($pet['shelter_description'])): ?>
                <div style="margin-top: 6px; font-size: 0.85rem; color: var(--text-muted);">
                    <em><?php echo e($pet['shelter_description']); ?></em>
                </div>
            <?php endif; ?>
        </div>

        <!-- Notification Banner if Already Applied or Booked -->
        <?php if ($has_applied): ?>
            <div class="alert alert-info" style="margin-top: 10px;">
                ℹ️ You have submitted an application for <?php echo e($pet['name']); ?> (Status: <strong><?php echo e($has_applied['status']); ?></strong>). Check <a href="<?php echo url('my_applications.php'); ?>">My Applications</a>.
            </div>
        <?php endif; ?>

        <?php if ($has_visit): ?>
            <div class="alert alert-success" style="margin-top: 10px;">
                📅 You have a scheduled visit on <strong><?php echo date('M d, Y', strtotime($has_visit['visit_date'])); ?></strong> at <strong><?php echo date('h:i A', strtotime($has_visit['visit_time'])); ?></strong>.
            </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="details-actions">
            <?php if ($pet['status'] === 'Adopted'): ?>
                <div class="alert alert-warning" style="width: 100%; text-align: center;">
                    🎉 <?php echo e($pet['name']); ?> has happily found a forever home!
                </div>
            <?php elseif (!is_logged_in()): ?>
                <a href="<?php echo url('login.php?redirect=pet_details.php?id=' . $pet['pet_id']); ?>" class="btn btn-primary btn-lg" style="flex: 1;">
                    Log In to Apply
                </a>
                <a href="<?php echo url('login.php?redirect=schedule_visit.php?pet_id=' . $pet['pet_id']); ?>" class="btn btn-accent btn-lg" style="flex: 1;">
                    Schedule a Visit
                </a>
            <?php elseif (user_role() === 'adopter'): ?>
                <?php if ($pet['status'] === 'Available'): ?>
                    <a href="<?php echo url('apply.php?pet_id=' . $pet['pet_id']); ?>" class="btn btn-primary btn-lg" style="flex: 1;">
                        ❤️ Apply to Adopt
                    </a>
                    <a href="<?php echo url('schedule_visit.php?pet_id=' . $pet['pet_id']); ?>" class="btn btn-accent btn-lg" style="flex: 1;">
                        📅 Schedule a Visit
                    </a>
                <?php else: ?>
                    <div class="alert alert-warning" style="width: 100%;">
                        ⚠️ This pet currently has an application pending review. You may still inquire with the shelter directly.
                    </div>
                <?php endif; ?>
            <?php elseif (user_role() === 'shelter'): ?>
                <p class="text-muted" style="font-size: 0.88rem;">
                    (Logged in as a Shelter account. To adopt or book a visit, log in with an Adopter account.)
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Similar Pets Section -->
<?php if (!empty($similar_pets)): ?>
    <div style="margin-top: 50px;">
        <h3 style="margin-bottom: 20px;">Other <?php echo e($pet['species']); ?>s Looking for Homes</h3>
        <div class="pets-grid">
            <?php foreach ($similar_pets as $sim): ?>
                <div class="pet-card">
                    <div class="pet-img-container" style="height: 180px;">
                        <img src="<?php echo e(get_pet_image_url($sim['image'])); ?>" alt="<?php echo e($sim['name']); ?>" class="pet-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                    </div>
                    <div class="pet-info">
                        <div class="pet-header">
                            <h4 class="pet-name"><?php echo e($sim['name']); ?></h4>
                            <span class="text-accent" style="font-size: 0.85rem;"><?php echo e($sim['gender']); ?></span>
                        </div>
                        <div class="pet-breed" style="margin-bottom: 10px;"><?php echo e($sim['breed']); ?> &bull; <?php echo e($sim['age_years']); ?> yrs</div>
                        <div class="pet-shelter-info">
                            <span>📍 <?php echo e($sim['city']); ?></span>
                        </div>
                        <div style="margin-top: 10px;">
                            <a href="<?php echo url('pet_details.php?id=' . $sim['pet_id']); ?>" class="btn btn-outline btn-sm btn-block">
                                View Profile
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
