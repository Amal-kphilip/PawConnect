<?php
/**
 * PawConnect - My Adoption Applications
 */
$page_title = "My Applications";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('adopter');

$user_id = current_user('user_id');

$stmt = mysqli_prepare($conn, "
    SELECT a.*, p.name AS pet_name, p.breed, p.species, p.image AS pet_image, p.status AS pet_status,
           s.shelter_name, s.city, s.phone AS shelter_phone
    FROM applications a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN shelters s ON p.shelter_id = s.shelter_id
    WHERE a.user_id = ?
    ORDER BY a.applied_at DESC
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$applications = [];
while ($row = mysqli_fetch_assoc($res)) {
    $applications[] = $row;
}
mysqli_stmt_close($stmt);

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 25px;">
    <h1>My Adoption Applications</h1>
    <p class="text-muted">Track the review status of all your pet adoption requests.</p>
</div>

<?php if (!empty($applications)): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Pet</th>
                    <th>Shelter & City</th>
                    <th>Home Type</th>
                    <th>Date Applied</th>
                    <th>Status</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applications as $app): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo e(get_pet_image_url($app['pet_image'])); ?>" alt="<?php echo e($app['pet_name']); ?>" class="table-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                                <div>
                                    <strong style="display: block;"><?php echo e($app['pet_name']); ?></strong>
                                    <span class="text-muted" style="font-size: 0.82rem;"><?php echo e($app['breed']); ?> (<?php echo e($app['species']); ?>)</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo e($app['shelter_name']); ?></strong>
                            <div class="text-muted" style="font-size: 0.82rem;">📍 <?php echo e($app['city']); ?> &bull; 📞 <?php echo e($app['shelter_phone']); ?></div>
                        </td>
                        <td>
                            <?php echo e($app['home_type']); ?>
                            <div class="text-muted" style="font-size: 0.8rem;">
                                <?php echo $app['has_other_pets'] ? 'Has other pets' : 'No other pets'; ?>
                            </div>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                            <div class="text-muted" style="font-size: 0.8rem;"><?php echo date('h:i A', strtotime($app['applied_at'])); ?></div>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($app['status']); ?>">
                                <?php echo e($app['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo url('pet_details.php?id=' . $app['pet_id']); ?>" class="btn btn-outline btn-sm">
                                View Pet
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No applications submitted yet</h3>
        <p>You have not submitted any adoption applications so far. Browse our available pets and find a match!</p>
        <a href="<?php echo url('pets.php'); ?>" class="btn btn-primary">Find Pets to Adopt</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
