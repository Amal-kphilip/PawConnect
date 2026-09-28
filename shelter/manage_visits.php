<?php
/**
 * PawConnect - Shelter Visits Management
 */
$page_title = "Manage Shelter Visits";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('shelter');

$shelter_id = get_shelter_id_for_user($conn, current_user('user_id'));
if (!$shelter_id) {
    set_flash('error', 'Shelter profile not found.');
    header("Location: " . url('index.php'));
    exit;
}

// Handle Visit Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Invalid security token.');
        header("Location: " . url('shelter/manage_visits.php'));
        exit;
    }

    $visit_id = (int)($_POST['visit_id'] ?? 0);
    $new_status = clean_input($_POST['new_status'] ?? '');

    if (in_array($new_status, ['Completed', 'Cancelled'])) {
        // Verify visit belongs to this shelter
        $stmt = mysqli_prepare($conn, "
            SELECT v.visit_id 
            FROM visits v 
            JOIN pets p ON v.pet_id = p.pet_id 
            WHERE v.visit_id = ? AND p.shelter_id = ? 
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmt, "ii", $visit_id, $shelter_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $exists = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($exists) {
            $up_stmt = mysqli_prepare($conn, "UPDATE visits SET status = ? WHERE visit_id = ?");
            mysqli_stmt_bind_param($up_stmt, "si", $new_status, $visit_id);
            if (mysqli_stmt_execute($up_stmt)) {
                set_flash('success', 'Visit status updated to ' . $new_status . '.');
            } else {
                set_flash('error', 'Failed to update visit status.');
            }
            mysqli_stmt_close($up_stmt);
        } else {
            set_flash('error', 'Visit not found or unauthorized.');
        }
    }

    header("Location: " . url('shelter/manage_visits.php'));
    exit;
}

// Filter
$filter_status = clean_input($_GET['status'] ?? 'all');
$where_status = "";
if (in_array($filter_status, ['Scheduled', 'Completed', 'Cancelled'])) {
    $where_status = "AND v.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

// Fetch Visits for Shelter
$stmt = mysqli_prepare($conn, "
    SELECT v.*, p.name AS pet_name, p.breed, p.image AS pet_image,
           u.name AS visitor_name, u.email AS visitor_email, u.phone AS visitor_phone
    FROM visits v
    JOIN pets p ON v.pet_id = p.pet_id
    JOIN users u ON v.user_id = u.user_id
    WHERE p.shelter_id = ? $where_status
    ORDER BY v.visit_date DESC, v.visit_time DESC
");
mysqli_stmt_bind_param($stmt, "i", $shelter_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$visits = [];
while ($row = mysqli_fetch_assoc($res)) {
    $visits[] = $row;
}
mysqli_stmt_close($stmt);

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
    <div>
        <h1>Manage Shelter Visits</h1>
        <p class="text-muted" style="margin-bottom: 0;">View visitors scheduled to meet your rescue animals and update visit outcomes.</p>
    </div>
    <a href="<?php echo url('shelter/dashboard.php'); ?>" class="btn btn-outline">&larr; Back to Dashboard</a>
</div>

<!-- Filters -->
<div style="display: flex; gap: 10px; margin-bottom: 25px;">
    <a href="<?php echo url('shelter/manage_visits.php?status=all'); ?>" class="btn <?php echo $filter_status === 'all' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">All Visits (<?php echo count($visits); ?>)</a>
    <a href="<?php echo url('shelter/manage_visits.php?status=Scheduled'); ?>" class="btn <?php echo $filter_status === 'Scheduled' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Scheduled</a>
    <a href="<?php echo url('shelter/manage_visits.php?status=Completed'); ?>" class="btn <?php echo $filter_status === 'Completed' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Completed</a>
    <a href="<?php echo url('shelter/manage_visits.php?status=Cancelled'); ?>" class="btn <?php echo $filter_status === 'Cancelled' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Cancelled</a>
</div>

<?php if (!empty($visits)): ?>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Pet</th>
                    <th>Visitor Contact</th>
                    <th>Visit Date</th>
                    <th>Time Slot</th>
                    <th>Status</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($visits as $v): ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <img src="<?php echo e(get_pet_image_url($v['pet_image'])); ?>" class="table-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                                <div>
                                    <strong><?php echo e($v['pet_name']); ?></strong><br>
                                    <span class="text-muted" style="font-size: 0.82rem;"><?php echo e($v['breed']); ?></span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <strong><?php echo e($v['visitor_name']); ?></strong><br>
                            <span class="text-muted" style="font-size: 0.82rem;">
                                ✉️ <?php echo e($v['visitor_email']); ?><br>
                                📞 <?php echo e($v['visitor_phone']); ?>
                            </span>
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
                            <?php if ($v['status'] === 'Scheduled'): ?>
                                <div class="action-btns">
                                    <form action="<?php echo url('shelter/manage_visits.php'); ?>" method="POST" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="visit_id" value="<?php echo $v['visit_id']; ?>">
                                        <input type="hidden" name="new_status" value="Completed">
                                        <button type="submit" class="btn btn-accent btn-sm">
                                            ✓ Completed
                                        </button>
                                    </form>

                                    <form action="<?php echo url('shelter/manage_visits.php'); ?>" method="POST" onsubmit="return confirm('Cancel this visit?');" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="visit_id" value="<?php echo $v['visit_id']; ?>">
                                        <input type="hidden" name="new_status" value="Cancelled">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            ✗ Cancel
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.85rem;">No actions needed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📅</div>
        <h3>No visits found</h3>
        <p>No visits match the current filter selection.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
