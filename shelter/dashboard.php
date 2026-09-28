<?php
/**
 * PawConnect - Shelter Portal Dashboard
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('shelter');

$user_id = current_user('user_id');
$shelter_id = get_shelter_id_for_user($conn, $user_id);

if (!$shelter_id) {
    set_flash('error', 'Shelter profile could not be located for your account.');
    header("Location: " . url('index.php'));
    exit;
}

// 1. Fetch Shelter Info
$shelter_stmt = mysqli_prepare($conn, "SELECT * FROM shelters WHERE shelter_id = ? LIMIT 1");
mysqli_stmt_bind_param($shelter_stmt, "i", $shelter_id);
mysqli_stmt_execute($shelter_stmt);
$shelter = mysqli_fetch_assoc(mysqli_stmt_get_result($shelter_stmt));
mysqli_stmt_close($shelter_stmt);

// 2. Fetch Stats
$stats = [
    'total_pets' => 0,
    'available_pets' => 0,
    'pending_apps' => 0,
    'upcoming_visits' => 0
];

// Total Pets
$res1 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pets WHERE shelter_id = $shelter_id");
if ($r = mysqli_fetch_assoc($res1)) $stats['total_pets'] = $r['total'];

// Available Pets
$res2 = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pets WHERE shelter_id = $shelter_id AND status = 'Available'");
if ($r = mysqli_fetch_assoc($res2)) $stats['available_pets'] = $r['total'];

// Pending Applications
$res3 = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM applications a 
    JOIN pets p ON a.pet_id = p.pet_id 
    WHERE p.shelter_id = $shelter_id AND a.status = 'Pending'
");
if ($r = mysqli_fetch_assoc($res3)) $stats['pending_apps'] = $r['total'];

// Upcoming Visits (Scheduled)
$res4 = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM visits v 
    JOIN pets p ON v.pet_id = p.pet_id 
    WHERE p.shelter_id = $shelter_id AND v.status = 'Scheduled' AND v.visit_date >= CURDATE()
");
if ($r = mysqli_fetch_assoc($res4)) $stats['upcoming_visits'] = $r['total'];

// 3. Fetch Shelter's Pet Inventory (Latest 8)
$pets_stmt = mysqli_prepare($conn, "SELECT * FROM pets WHERE shelter_id = ? ORDER BY created_at DESC LIMIT 8");
mysqli_stmt_bind_param($pets_stmt, "i", $shelter_id);
mysqli_stmt_execute($pets_stmt);
$pets_res = mysqli_stmt_get_result($pets_stmt);
$pets = [];
while ($row = mysqli_fetch_assoc($pets_res)) {
    $pets[] = $row;
}
mysqli_stmt_close($pets_stmt);

// 4. Fetch Recent Pending Applications (Latest 5)
$apps_stmt = mysqli_prepare($conn, "
    SELECT a.*, p.name AS pet_name, p.image AS pet_image, u.name AS applicant_name, u.email AS applicant_email, u.phone AS applicant_phone
    FROM applications a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN users u ON a.user_id = u.user_id
    WHERE p.shelter_id = ?
    ORDER BY a.applied_at DESC
    LIMIT 5
");
mysqli_stmt_bind_param($apps_stmt, "i", $shelter_id);
mysqli_stmt_execute($apps_stmt);
$apps_res = mysqli_stmt_get_result($apps_stmt);
$recent_apps = [];
while ($row = mysqli_fetch_assoc($apps_res)) {
    $recent_apps[] = $row;
}
mysqli_stmt_close($apps_stmt);

$page_title = "Shelter Dashboard - " . ($shelter['shelter_name'] ?? 'PawConnect');
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Shelter Header Overview -->
<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 25px;">
    <div>
        <h1><?php echo e($shelter['shelter_name']); ?></h1>
        <p class="text-muted" style="margin-bottom: 0;">
            📍 <?php echo e($shelter['address']); ?>, <?php echo e($shelter['city']); ?> &bull; 📞 <?php echo e($shelter['phone']); ?>
        </p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="<?php echo url('shelter/add_pet.php'); ?>" class="btn btn-primary">+ Add New Pet</a>
        <a href="<?php echo url('shelter/view_applications.php'); ?>" class="btn btn-outline-accent">View All Applications</a>
        <a href="<?php echo url('shelter/manage_visits.php'); ?>" class="btn btn-outline">Manage Visits</a>
    </div>
</div>

<!-- Stats Counter Grid -->
<div class="stats-section">
    <div class="stat-card">
        <span class="stat-icon">🐾</span>
        <div class="stat-number"><?php echo $stats['total_pets']; ?></div>
        <div class="stat-label">Total Rescues</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🟢</span>
        <div class="stat-number" style="color: #1E8449;"><?php echo $stats['available_pets']; ?></div>
        <div class="stat-label">Available for Adoption</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📋</span>
        <div class="stat-number" style="color: #F39C12;"><?php echo $stats['pending_apps']; ?></div>
        <div class="stat-label">Pending Applications</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📅</span>
        <div class="stat-number" style="color: var(--accent);"><?php echo $stats['upcoming_visits']; ?></div>
        <div class="stat-label">Upcoming Visits</div>
    </div>
</div>

<!-- Recent Applications Section -->
<section style="margin-bottom: 45px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3>Recent Adoption Applications</h3>
        <a href="<?php echo url('shelter/view_applications.php'); ?>" class="btn btn-outline-accent btn-sm">View All &rarr;</a>
    </div>

    <?php if (!empty($recent_apps)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pet</th>
                        <th>Applicant</th>
                        <th>Home / Pets</th>
                        <th>Date Applied</th>
                        <th>Status</th>
                        <th>Quick Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_apps as $app): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo e(get_pet_image_url($app['pet_image'])); ?>" class="table-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                                    <strong><?php echo e($app['pet_name']); ?></strong>
                                </div>
                            </td>
                            <td>
                                <strong><?php echo e($app['applicant_name']); ?></strong><br>
                                <span class="text-muted" style="font-size: 0.82rem;"><?php echo e($app['applicant_email']); ?> &bull; <?php echo e($app['applicant_phone']); ?></span>
                            </td>
                            <td>
                                <?php echo e($app['home_type']); ?><br>
                                <span class="text-muted" style="font-size: 0.8rem;">
                                    <?php echo $app['has_other_pets'] ? 'Has other pets' : 'No other pets'; ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($app['status']); ?>">
                                    <?php echo e($app['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo url('shelter/view_applications.php#app-' . $app['app_id']); ?>" class="btn btn-outline btn-sm">
                                    Review / Decision
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="background: var(--bg-surface); padding: 25px; border-radius: var(--radius-md); border: 1px dashed var(--border-color); text-align: center; color: var(--text-muted);">
            No adoption applications received yet.
        </div>
    <?php endif; ?>
</section>

<!-- Pet Inventory Table -->
<section>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <h3>Pet Inventory</h3>
        <a href="<?php echo url('shelter/add_pet.php'); ?>" class="btn btn-primary btn-sm">+ Add Pet</a>
    </div>

    <?php if (!empty($pets)): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Name</th>
                        <th>Species / Breed</th>
                        <th>Age & Gender</th>
                        <th>Vaccinated</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pets as $p): ?>
                        <tr>
                            <td>
                                <img src="<?php echo e(get_pet_image_url($p['image'])); ?>" class="table-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                            </td>
                            <td>
                                <strong><?php echo e($p['name']); ?></strong>
                            </td>
                            <td>
                                <?php echo e($p['breed']); ?> (<?php echo e($p['species']); ?>)
                            </td>
                            <td>
                                <?php echo e($p['age_years']); ?> yrs &bull; <?php echo e($p['gender']); ?>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: <?php echo $p['vaccinated'] ? '#1E8449' : '#C0392B'; ?>;">
                                    <?php echo $p['vaccinated'] ? '✓ Yes' : '✗ No'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?php echo strtolower($p['status']); ?>">
                                    <?php echo e($p['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="<?php echo url('pet_details.php?id=' . $p['pet_id']); ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
                                    <a href="<?php echo url('shelter/edit_pet.php?id=' . $p['pet_id']); ?>" class="btn btn-outline-accent btn-sm">Edit</a>
                                    <form action="<?php echo url('shelter/delete_pet.php'); ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete <?php echo e($p['name']); ?>? This will also remove all associated applications and visits.');" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="pet_id" value="<?php echo $p['pet_id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🐶</div>
            <h3>No pets in your inventory</h3>
            <p>Start listing your shelter's animals to connect them with potential adopters.</p>
            <a href="<?php echo url('shelter/add_pet.php'); ?>" class="btn btn-primary">+ Add Your First Pet</a>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
