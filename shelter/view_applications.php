<?php
/**
 * PawConnect - View & Process Adoption Applications (Shelter)
 */
$page_title = "Manage Adoption Applications";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('shelter');

$shelter_id = get_shelter_id_for_user($conn, current_user('user_id'));
if (!$shelter_id) {
    set_flash('error', 'Shelter profile not found.');
    header("Location: " . url('index.php'));
    exit;
}

// Handle Approve / Reject Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Invalid security token.');
        header("Location: " . url('shelter/view_applications.php'));
        exit;
    }

    $app_id = (int)($_POST['app_id'] ?? 0);
    $action = clean_input($_POST['action'] ?? '');

    // Verify application belongs to this shelter
    $verify_stmt = mysqli_prepare($conn, "
        SELECT a.app_id, a.pet_id, p.name AS pet_name, u.name AS applicant_name 
        FROM applications a 
        JOIN pets p ON a.pet_id = p.pet_id 
        JOIN users u ON a.user_id = u.user_id
        WHERE a.app_id = ? AND p.shelter_id = ? 
        LIMIT 1
    ");
    mysqli_stmt_bind_param($verify_stmt, "ii", $app_id, $shelter_id);
    mysqli_stmt_execute($verify_stmt);
    $res = mysqli_stmt_get_result($verify_stmt);
    $app_data = mysqli_fetch_assoc($res);
    mysqli_stmt_close($verify_stmt);

    if ($app_data) {
        $pet_id = $app_data['pet_id'];

        if ($action === 'approve') {
            mysqli_begin_transaction($conn);
            try {
                // 1. Approve this application
                $up1 = mysqli_prepare($conn, "UPDATE applications SET status = 'Approved' WHERE app_id = ?");
                mysqli_stmt_bind_param($up1, "i", $app_id);
                mysqli_stmt_execute($up1);
                mysqli_stmt_close($up1);

                // 2. Mark Pet as Adopted
                $up2 = mysqli_prepare($conn, "UPDATE pets SET status = 'Adopted' WHERE pet_id = ?");
                mysqli_stmt_bind_param($up2, "i", $pet_id);
                mysqli_stmt_execute($up2);
                mysqli_stmt_close($up2);

                // 3. Reject other pending applications for this pet
                $up3 = mysqli_prepare($conn, "UPDATE applications SET status = 'Rejected' WHERE pet_id = ? AND app_id != ? AND status = 'Pending'");
                mysqli_stmt_bind_param($up3, "ii", $pet_id, $app_id);
                mysqli_stmt_execute($up3);
                $other_rejected_count = mysqli_stmt_affected_rows($up3);
                mysqli_stmt_close($up3);

                mysqli_commit($conn);
                $msg = "Application from " . $app_data['applicant_name'] . " for " . $app_data['pet_name'] . " was APPROVED. Pet is now marked as ADOPTED.";
                if ($other_rejected_count > 0) {
                    $msg .= " (" . $other_rejected_count . " other pending applications were automatically updated to Rejected).";
                }
                set_flash('success', $msg);
            } catch (Exception $e) {
                mysqli_rollback($conn);
                set_flash('error', 'Error approving application: ' . $e->getMessage());
            }
        } elseif ($action === 'reject') {
            $up = mysqli_prepare($conn, "UPDATE applications SET status = 'Rejected' WHERE app_id = ?");
            mysqli_stmt_bind_param($up, "i", $app_id);
            if (mysqli_stmt_execute($up)) {
                set_flash('info', 'Application from ' . $app_data['applicant_name'] . ' for ' . $app_data['pet_name'] . ' was marked as REJECTED.');
            } else {
                set_flash('error', 'Failed to update application status.');
            }
            mysqli_stmt_close($up);
        }
    } else {
        set_flash('error', 'Application not found or unauthorized.');
    }

    header("Location: " . url('shelter/view_applications.php'));
    exit;
}

// Filter parameter
$filter_status = clean_input($_GET['status'] ?? 'all');
$where_status = "";
if (in_array($filter_status, ['Pending', 'Approved', 'Rejected'])) {
    $where_status = "AND a.status = '" . mysqli_real_escape_string($conn, $filter_status) . "'";
}

// Fetch all applications for shelter
$query = "
    SELECT a.*, p.name AS pet_name, p.breed, p.species, p.image AS pet_image, p.status AS pet_status,
           u.name AS applicant_name, u.email AS applicant_email, u.phone AS applicant_phone
    FROM applications a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN users u ON a.user_id = u.user_id
    WHERE p.shelter_id = ? $where_status
    ORDER BY a.applied_at DESC
";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $shelter_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$applications = [];
while ($row = mysqli_fetch_assoc($res)) {
    $applications[] = $row;
}
mysqli_stmt_close($stmt);

require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
    <div>
        <h1>Adoption Applications</h1>
        <p class="text-muted" style="margin-bottom: 0;">Review applicant profiles and make adoption decisions for your shelter's animals.</p>
    </div>
    <a href="<?php echo url('shelter/dashboard.php'); ?>" class="btn btn-outline">&larr; Back to Dashboard</a>
</div>

<!-- Status Filter Tabs -->
<div style="display: flex; gap: 10px; margin-bottom: 25px;">
    <a href="<?php echo url('shelter/view_applications.php?status=all'); ?>" class="btn <?php echo $filter_status === 'all' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">All (<?php echo count($applications); ?>)</a>
    <a href="<?php echo url('shelter/view_applications.php?status=Pending'); ?>" class="btn <?php echo $filter_status === 'Pending' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Pending Only</a>
    <a href="<?php echo url('shelter/view_applications.php?status=Approved'); ?>" class="btn <?php echo $filter_status === 'Approved' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Approved</a>
    <a href="<?php echo url('shelter/view_applications.php?status=Rejected'); ?>" class="btn <?php echo $filter_status === 'Rejected' ? 'btn-primary' : 'btn-outline'; ?> btn-sm">Rejected</a>
</div>

<?php if (!empty($applications)): ?>
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <?php foreach ($applications as $app): ?>
            <div id="app-<?php echo $app['app_id']; ?>" style="background: var(--bg-surface); padding: 25px; border-radius: var(--radius-md); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                    <div style="display: flex; align-items: center; gap: 14px;">
                        <img src="<?php echo e(get_pet_image_url($app['pet_image'])); ?>" style="width: 60px; height: 60px; border-radius: var(--radius-sm); object-fit: cover;" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                        <div>
                            <h3 style="margin-bottom: 2px;">
                                Application for <?php echo e($app['pet_name']); ?>
                                <span class="text-muted" style="font-size: 0.85rem; font-weight: normal;">(<?php echo e($app['breed']); ?>)</span>
                            </h3>
                            <div class="text-muted" style="font-size: 0.85rem;">
                                Submitted on <?php echo date('M d, Y \a\t h:i A', strtotime($app['applied_at'])); ?>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="badge badge-<?php echo strtolower($app['status']); ?>" style="font-size: 0.9rem; padding: 6px 14px;">
                            <?php echo e($app['status']); ?>
                        </span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 18px;">
                    <!-- Applicant Information -->
                    <div style="background: var(--bg-subtle); padding: 15px; border-radius: var(--radius-sm); font-size: 0.9rem;">
                        <strong style="display: block; margin-bottom: 6px; color: var(--text-main);">👤 Applicant Details</strong>
                        <div><strong>Name:</strong> <?php echo e($app['applicant_name']); ?></div>
                        <div><strong>Email:</strong> <a href="mailto:<?php echo e($app['applicant_email']); ?>"><?php echo e($app['applicant_email']); ?></a></div>
                        <div><strong>Phone:</strong> <a href="tel:<?php echo e($app['applicant_phone']); ?>"><?php echo e($app['applicant_phone']); ?></a></div>
                        <div><strong>Home Type:</strong> <?php echo e($app['home_type']); ?></div>
                        <div><strong>Other Pets:</strong> <?php echo $app['has_other_pets'] ? 'Yes' : 'None'; ?></div>
                    </div>

                    <!-- Experience & Reason -->
                    <div style="background: var(--bg-subtle); padding: 15px; border-radius: var(--radius-sm); font-size: 0.9rem;">
                        <strong style="display: block; margin-bottom: 6px; color: var(--text-main);">📝 Application Answers</strong>
                        <div style="margin-bottom: 8px;">
                            <span class="text-muted" style="font-size: 0.8rem; display: block;">Past Pet Experience:</span>
                            <?php echo e($app['experience']); ?>
                        </div>
                        <div>
                            <span class="text-muted" style="font-size: 0.8rem; display: block;">Reason for Adopting <?php echo e($app['pet_name']); ?>:</span>
                            <?php echo e($app['reason']); ?>
                        </div>
                    </div>
                </div>

                <!-- Decision Action Buttons -->
                <?php if ($app['status'] === 'Pending'): ?>
                    <div style="display: flex; gap: 12px; justify-content: flex-end; padding-top: 10px; border-top: 1px dashed var(--border-color);">
                        <form action="<?php echo url('shelter/view_applications.php'); ?>" method="POST" onsubmit="return confirm('Approve this application? This will mark <?php echo e($app['pet_name']); ?> as Adopted and reject other pending applications for this pet.');" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="app_id" value="<?php echo $app['app_id']; ?>">
                            <input type="hidden" name="action" value="approve">
                            <button type="submit" class="btn btn-primary btn-sm">
                                ✓ Approve Adoption
                            </button>
                        </form>

                        <form action="<?php echo url('shelter/view_applications.php'); ?>" method="POST" onsubmit="return confirm('Are you sure you want to reject this application?');" style="display:inline;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="app_id" value="<?php echo $app['app_id']; ?>">
                            <input type="hidden" name="action" value="reject">
                            <button type="submit" class="btn btn-danger btn-sm">
                                ✗ Reject
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No applications found</h3>
        <p>There are no applications matching the selected filter status.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
