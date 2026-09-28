<?php
/**
 * PawConnect - Simple Admin Dashboard
 * Overview of all users, shelters, and pets
 */
$page_title = "Admin Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('admin');

// Handle User Deletion by Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    if (!verify_csrf_token()) {
        set_flash('error', 'Invalid security token.');
    } else {
        $del_user_id = (int)($_POST['user_id'] ?? 0);
        // Prevent deleting self or other admins
        if ($del_user_id === current_user('user_id')) {
            set_flash('error', 'You cannot delete your own admin account.');
        } else {
            $del_stmt = mysqli_prepare($conn, "DELETE FROM users WHERE user_id = ? AND role != 'admin'");
            mysqli_stmt_bind_param($del_stmt, "i", $del_user_id);
            if (mysqli_stmt_execute($del_stmt) && mysqli_stmt_affected_rows($del_stmt) > 0) {
                set_flash('success', 'User account and associated records were successfully removed.');
            } else {
                set_flash('error', 'Unable to delete user.');
            }
            mysqli_stmt_close($del_stmt);
        }
    }
    header("Location: " . url('admin/dashboard.php'));
    exit;
}

// 1. Fetch Global System Metrics
$metrics = [
    'users' => 0,
    'shelters' => 0,
    'pets' => 0,
    'applications' => 0,
    'visits' => 0
];

$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM users"); if ($row = mysqli_fetch_assoc($r)) $metrics['users'] = $row['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM shelters"); if ($row = mysqli_fetch_assoc($r)) $metrics['shelters'] = $row['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM pets"); if ($row = mysqli_fetch_assoc($r)) $metrics['pets'] = $row['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM applications"); if ($row = mysqli_fetch_assoc($r)) $metrics['applications'] = $row['c'];
$r = mysqli_query($conn, "SELECT COUNT(*) AS c FROM visits"); if ($row = mysqli_fetch_assoc($r)) $metrics['visits'] = $row['c'];

// 2. Fetch All Users
$users_res = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
$all_users = [];
while ($u = mysqli_fetch_assoc($users_res)) {
    $all_users[] = $u;
}

// 3. Fetch All Shelters with Pet Count
$shelters_query = "
    SELECT s.*, u.name AS rep_name, u.email AS rep_email, COUNT(p.pet_id) AS total_pets
    FROM shelters s
    JOIN users u ON s.user_id = u.user_id
    LEFT JOIN pets p ON s.shelter_id = p.shelter_id
    GROUP BY s.shelter_id
    ORDER BY s.shelter_name ASC
";
$shelters_res = mysqli_query($conn, $shelters_query);
$all_shelters = [];
while ($s = mysqli_fetch_assoc($shelters_res)) {
    $all_shelters[] = $s;
}

// 4. Fetch All Pets
$pets_query = "
    SELECT p.*, s.shelter_name, s.city
    FROM pets p
    JOIN shelters s ON p.shelter_id = s.shelter_id
    ORDER BY p.created_at DESC
";
$pets_res = mysqli_query($conn, $pets_query);
$all_pets = [];
while ($p = mysqli_fetch_assoc($pets_res)) {
    $all_pets[] = $p;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div style="margin-bottom: 25px;">
    <h1>Administrator Portal</h1>
    <p class="text-muted">Master overview of all portal accounts, partner shelters, and pet listings.</p>
</div>

<!-- Stats Counter Section -->
<div class="stats-section">
    <div class="stat-card">
        <span class="stat-icon">👥</span>
        <div class="stat-number"><?php echo $metrics['users']; ?></div>
        <div class="stat-label">Total Users</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🏢</span>
        <div class="stat-number" style="color: var(--accent);"><?php echo $metrics['shelters']; ?></div>
        <div class="stat-label">Verified Shelters</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🐾</span>
        <div class="stat-number"><?php echo $metrics['pets']; ?></div>
        <div class="stat-label">All Pets Listed</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📋</span>
        <div class="stat-number" style="color: #F39C12;"><?php echo $metrics['applications']; ?></div>
        <div class="stat-label">Adoption Requests</div>
    </div>
</div>

<!-- Section 1: All Users -->
<section style="margin-bottom: 45px;">
    <h2 style="margin-bottom: 15px;">All Registered Users (<?php echo count($all_users); ?>)</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Registered At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_users as $u): ?>
                    <tr>
                        <td>#<?php echo $u['user_id']; ?></td>
                        <td><strong><?php echo e($u['name']); ?></strong></td>
                        <td><?php echo e($u['email']); ?></td>
                        <td><?php echo e($u['phone']); ?></td>
                        <td>
                            <span class="user-role-tag" style="background: <?php echo $u['role'] === 'admin' ? '#34495E' : ($u['role'] === 'shelter' ? '#2A9D8F' : '#F28C28'); ?>;">
                                <?php echo e($u['role']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <?php if ($u['role'] !== 'admin'): ?>
                                <form action="<?php echo url('admin/dashboard.php'); ?>" method="POST" onsubmit="return confirm('Delete this user account? (This cascades all shelter/applications/visits records)');" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="delete_user">
                                    <input type="hidden" name="user_id" value="<?php echo $u['user_id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted" style="font-size: 0.8rem;">Protected</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Section 2: All Shelters -->
<section style="margin-bottom: 45px;">
    <h2 style="margin-bottom: 15px;">Partner Animal Shelters (<?php echo count($all_shelters); ?>)</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Shelter ID</th>
                    <th>Shelter Name</th>
                    <th>City & Address</th>
                    <th>Representative Contact</th>
                    <th>Pets Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_shelters as $s): ?>
                    <tr>
                        <td>#<?php echo $s['shelter_id']; ?></td>
                        <td><strong><?php echo e($s['shelter_name']); ?></strong></td>
                        <td>
                            📍 <?php echo e($s['city']); ?><br>
                            <span class="text-muted" style="font-size: 0.82rem;"><?php echo e($s['address']); ?></span>
                        </td>
                        <td>
                            <?php echo e($s['rep_name']); ?><br>
                            <span class="text-muted" style="font-size: 0.82rem;">✉️ <?php echo e($s['rep_email']); ?> &bull; 📞 <?php echo e($s['phone']); ?></span>
                        </td>
                        <td>
                            <strong style="color: var(--primary); font-size: 1.1rem;"><?php echo $s['total_pets']; ?></strong> pets
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Section 3: All Pets -->
<section>
    <h2 style="margin-bottom: 15px;">All Pet Listings in System (<?php echo count($all_pets); ?>)</h2>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Pet Name</th>
                    <th>Species & Breed</th>
                    <th>Shelter & City</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_pets as $p): ?>
                    <tr>
                        <td>
                            <img src="<?php echo e(get_pet_image_url($p['image'])); ?>" class="table-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                        </td>
                        <td><strong><?php echo e($p['name']); ?></strong></td>
                        <td><?php echo e($p['breed']); ?> (<?php echo e($p['species']); ?>)</td>
                        <td>
                            <strong><?php echo e($p['shelter_name']); ?></strong><br>
                            <span class="text-muted" style="font-size: 0.82rem;">📍 <?php echo e($p['city']); ?></span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo strtolower($p['status']); ?>">
                                <?php echo e($p['status']); ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?php echo url('pet_details.php?id=' . $p['pet_id']); ?>" class="btn btn-outline btn-sm" target="_blank">
                                View Profile
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
