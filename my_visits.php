<?php
/**
 * PawConnect - My Scheduled Visits
 */
$page_title = "My Scheduled Visits";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('adopter');

$user_id = current_user('user_id');

// Handle Visit Cancellation by Adopter
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $visit_id = (int)($_POST['visit_id'] ?? 0);
    
    if (!verify_csrf_token()) {
        set_flash('error', 'Invalid security token.');
    } else {
        $cancel_stmt = mysqli_prepare($conn, "
            UPDATE visits 
            SET status = 'Cancelled' 
            WHERE visit_id = ? AND user_id = ? AND status = 'Scheduled'
        ");
        mysqli_stmt_bind_param($cancel_stmt, "ii", $visit_id, $user_id);
        if (mysqli_stmt_execute($cancel_stmt) && mysqli_stmt_affected_rows($cancel_stmt) > 0) {
            set_flash('success', 'Your visit has been successfully cancelled.');
        } else {
            set_flash('error', 'Unable to cancel visit or visit is already completed/cancelled.');
        }
        mysqli_stmt_close($cancel_stmt);
    }
    header("Location: " . url('my_visits.php'));
    exit;
}

// Fetch Visits
$stmt = mysqli_prepare($conn, "
    SELECT v.*, p.name AS pet_name, p.breed, p.species, p.image AS pet_image,
           s.shelter_name, s.address AS shelter_address, s.city AS shelter_city, s.phone AS shelter_phone
    FROM visits v
    JOIN pets p ON v.pet_id = p.pet_id
    JOIN shelters s ON p.shelter_id = s.shelter_id
    WHERE v.user_id = ?
    ORDER BY v.visit_date DESC, v.visit_time DESC
");
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$visits = [];
while ($row = mysqli_fetch_assoc($res)) {
    $visits[] = $row;
}
mysqli_stmt_close($stmt);

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 25px;">
    <h1>My Shelter Visits</h1>
    <p class="text-muted">Manage your upcoming and past shelter appointments to meet pets.</p>
</div>

<?php if (!empty($visits)): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Pet</th>
                    <th>Shelter & Location</th>
                    <th>Visit Date</th>
                    <th>Time Slot</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visits as $v): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo e(get_pet_image_url($v['pet_image'])); ?>" alt="<?php echo e($v['pet_name']); ?>" class="table-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                                <div>
                                    <strong style="display: block;"><?php echo e($v['pet_name']); ?></strong>
                                    <span class="text-muted" style="font-size: 0.82rem;"><?php echo e($v['breed']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo e($v['shelter_name']); ?></strong>
                            <div class="text-muted" style="font-size: 0.82rem;">
                                📍 <?php echo e($v['shelter_address']); ?>, <?php echo e($v['shelter_city']); ?><br>
                                📞 <?php echo e($v['shelter_phone']); ?>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo date('D, M d, Y', strtotime($v['visit_date'])); ?></strong>
                        </td>
                        <td>
                            <?php echo date('h:i A', strtotime($v['visit_time'])); ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($v['status']); ?>">
                                <?php echo e($v['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <a href="<?php echo url('pet_details.php?id=' . $v['pet_id']); ?>" class="btn btn-outline btn-sm">
                                    Pet Info
                                </a>

                                <?php if ($v['status'] === 'Scheduled'): ?>
                                    <form action="<?php echo url('my_visits.php'); ?>" method="POST" onsubmit="return confirm('Are you sure you want to cancel this scheduled visit?');" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="visit_id" value="<?php echo $v['visit_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Cancel Visit
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📅</div>
        <h3>No visits scheduled yet</h3>
        <p>You have not booked any shelter visits yet. Select a pet to schedule a meetup!</p>
        <a href="<?php echo url('pets.php'); ?>" class="btn btn-primary">Find Pets</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
