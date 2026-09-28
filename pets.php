<?php
/**
 * PawConnect - Browse & Search Pets
 */
$page_title = "Browse Pets for Adoption";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Get Filter Parameters from GET
$keyword = clean_input($_GET['keyword'] ?? '');
$species = clean_input($_GET['species'] ?? '');
$gender = clean_input($_GET['gender'] ?? '');
$size = clean_input($_GET['size'] ?? '');
$age_range = clean_input($_GET['age_range'] ?? '');
$city = clean_input($_GET['city'] ?? '');
$status = clean_input($_GET['status'] ?? 'Available');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 9;
$offset = ($page - 1) * $limit;

// Fetch unique cities for filter dropdown
$cities_res = mysqli_query($conn, "SELECT DISTINCT city FROM shelters ORDER BY city ASC");
$cities = [];
while ($c = mysqli_fetch_assoc($cities_res)) {
    if (!empty($c['city'])) $cities[] = $c['city'];
}

// Build dynamic WHERE clause with prepared statements
$where_clauses = [];
$params = [];
$types = '';

if (!empty($keyword)) {
    $where_clauses[] = "(p.name LIKE ? OR p.breed LIKE ? OR p.description LIKE ?)";
    $keyword_param = '%' . $keyword . '%';
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $params[] = $keyword_param;
    $types .= 'sss';
}

if (!empty($species)) {
    $where_clauses[] = "p.species = ?";
    $params[] = $species;
    $types .= 's';
}

if (!empty($gender)) {
    $where_clauses[] = "p.gender = ?";
    $params[] = $gender;
    $types .= 's';
}

if (!empty($size)) {
    $where_clauses[] = "p.size = ?";
    $params[] = $size;
    $types .= 's';
}

if (!empty($city)) {
    $where_clauses[] = "s.city = ?";
    $params[] = $city;
    $types .= 's';
}

if (!empty($status) && $status !== 'all') {
    $where_clauses[] = "p.status = ?";
    $params[] = $status;
    $types .= 's';
}

if (!empty($age_range)) {
    if ($age_range === 'puppy_kitten') {
        $where_clauses[] = "p.age_years <= 1.0";
    } elseif ($age_range === 'young') {
        $where_clauses[] = "p.age_years > 1.0 AND p.age_years <= 3.0";
    } elseif ($age_range === 'adult') {
        $where_clauses[] = "p.age_years > 3.0 AND p.age_years <= 7.0";
    } elseif ($age_range === 'senior') {
        $where_clauses[] = "p.age_years > 7.0";
    }
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// 1. Count Total Matching Pets for Pagination
$count_query = "
    SELECT COUNT(*) AS total 
    FROM pets p 
    JOIN shelters s ON p.shelter_id = s.shelter_id 
    $where_sql
";

$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_rows = mysqli_fetch_assoc($count_result)['total'] ?? 0;
mysqli_stmt_close($count_stmt);

$total_pages = ceil($total_rows / $limit);

// 2. Fetch Pets for Current Page
$pets_query = "
    SELECT p.*, s.shelter_name, s.city 
    FROM pets p 
    JOIN shelters s ON p.shelter_id = s.shelter_id 
    $where_sql 
    ORDER BY p.created_at DESC 
    LIMIT ? OFFSET ?
";

$pets_stmt = mysqli_prepare($conn, $pets_query);
$fetch_params = $params;
$fetch_params[] = $limit;
$fetch_params[] = $offset;
$fetch_types = $types . 'ii';

mysqli_stmt_bind_param($pets_stmt, $fetch_types, ...$fetch_params);
mysqli_stmt_execute($pets_stmt);
$pets_res = mysqli_stmt_get_result($pets_stmt);
$pets = [];
while ($row = mysqli_fetch_assoc($pets_res)) {
    $pets[] = $row;
}
mysqli_stmt_close($pets_stmt);

// Helper function to build pagination query string preserving other filters
function pagination_url($page_num) {
    $query = $_GET;
    $query['page'] = $page_num;
    return url('pets.php?' . http_build_query($query));
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 25px;">
    <h1>Meet Pets Available for Adoption</h1>
    <p class="text-muted">Explore rescued pets looking for their forever families. Use the filters below to find your match.</p>
</div>

<!-- Search & Filter Bar -->
<div class="filter-card">
    <form action="<?php echo url('pets.php'); ?>" method="GET">
        <div class="filter-grid">
            <div>
                <label class="form-label">Search Keyword</label>
                <input type="text" name="keyword" class="form-control" placeholder="Name, breed..." value="<?php echo e($keyword); ?>">
            </div>

            <div>
                <label class="form-label">Species</label>
                <select name="species" class="form-control">
                    <option value="">All Species</option>
                    <option value="Dog" <?php echo $species === 'Dog' ? 'selected' : ''; ?>>Dog</option>
                    <option value="Cat" <?php echo $species === 'Cat' ? 'selected' : ''; ?>>Cat</option>
                    <option value="Rabbit" <?php echo $species === 'Rabbit' ? 'selected' : ''; ?>>Rabbit</option>
                    <option value="Bird" <?php echo $species === 'Bird' ? 'selected' : ''; ?>>Bird</option>
                    <option value="Other" <?php echo $species === 'Other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div>
                <label class="form-label">Gender</label>
                <select name="gender" class="form-control">
                    <option value="">All Genders</option>
                    <option value="Male" <?php echo $gender === 'Male' ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo $gender === 'Female' ? 'selected' : ''; ?>>Female</option>
                </select>
            </div>

            <div>
                <label class="form-label">Size</label>
                <select name="size" class="form-control">
                    <option value="">All Sizes</option>
                    <option value="Small" <?php echo $size === 'Small' ? 'selected' : ''; ?>>Small (Under 10 kg)</option>
                    <option value="Medium" <?php echo $size === 'Medium' ? 'selected' : ''; ?>>Medium (10 - 25 kg)</option>
                    <option value="Large" <?php echo $size === 'Large' ? 'selected' : ''; ?>>Large (25+ kg)</option>
                </select>
            </div>

            <div>
                <label class="form-label">Age Group</label>
                <select name="age_range" class="form-control">
                    <option value="">All Ages</option>
                    <option value="puppy_kitten" <?php echo $age_range === 'puppy_kitten' ? 'selected' : ''; ?>>Puppy / Kitten (&le; 1 yr)</option>
                    <option value="young" <?php echo $age_range === 'young' ? 'selected' : ''; ?>>Young (1 - 3 yrs)</option>
                    <option value="adult" <?php echo $age_range === 'adult' ? 'selected' : ''; ?>>Adult (3 - 7 yrs)</option>
                    <option value="senior" <?php echo $age_range === 'senior' ? 'selected' : ''; ?>>Senior (7+ yrs)</option>
                </select>
            </div>

            <div>
                <label class="form-label">City</label>
                <select name="city" class="form-control">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $c): ?>
                        <option value="<?php echo e($c); ?>" <?php echo $city === $c ? 'selected' : ''; ?>><?php echo e($c); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-control">
                    <option value="Available" <?php echo $status === 'Available' ? 'selected' : ''; ?>>Available Only</option>
                    <option value="Pending" <?php echo $status === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Adopted" <?php echo $status === 'Adopted' ? 'selected' : ''; ?>>Adopted</option>
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary" style="flex: 1;">Filter</button>
                <a href="<?php echo url('pets.php'); ?>" class="btn btn-outline" title="Reset Filters">Reset</a>
            </div>
        </div>
    </form>
</div>

<!-- Pets Count and Grid -->
<div style="margin-bottom: 15px; color: var(--text-muted); font-size: 0.95rem;">
    Showing <strong><?php echo count($pets); ?></strong> of <strong><?php echo $total_rows; ?></strong> pets
</div>

<?php if (!empty($pets)): ?>
    <div class="pets-grid">
        <?php foreach ($pets as $pet): ?>
            <div class="pet-card">
                <div class="pet-img-container">
                    <img src="<?php echo e(get_pet_image_url($pet['image'])); ?>" alt="<?php echo e($pet['name']); ?>" class="pet-img" onerror="this.src='<?php echo url('uploads/default.png'); ?>';">
                    <span class="badge badge-<?php echo strtolower($pet['status']); ?> pet-status-badge">
                        <?php echo e($pet['status']); ?>
                    </span>
                    <span class="pet-species-badge"><?php echo e($pet['species']); ?></span>
                </div>

                <div class="pet-info">
                    <div class="pet-header">
                        <h3 class="pet-name"><?php echo e($pet['name']); ?></h3>
                        <span class="text-accent" style="font-weight: 600; font-size: 0.9rem;"><?php echo e($pet['gender']); ?></span>
                    </div>
                    <div class="pet-breed"><?php echo e($pet['breed']); ?> • <?php echo e($pet['age_years']); ?> yrs</div>

                    <div class="pet-attributes">
                        <span class="attribute-tag">Size: <?php echo e($pet['size']); ?></span>
                        <span class="attribute-tag"><?php echo $pet['vaccinated'] ? '💉 Vaccinated' : '⚠️ Not Vaccinated'; ?></span>
                    </div>

                    <div class="pet-shelter-info">
                        <span>📍 <?php echo e($pet['city']); ?> (<?php echo e($pet['shelter_name']); ?>)</span>
                    </div>

                    <div style="margin-top: 15px;">
                        <a href="<?php echo url('pet_details.php?id=' . $pet['pet_id']); ?>" class="btn btn-outline btn-sm btn-block">
                            View Profile
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <ul class="pagination">
            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo pagination_url($page - 1); ?>">&laquo; Prev</a>
            </li>

            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo pagination_url($i); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?php echo pagination_url($page + 1); ?>">Next &raquo;</a>
            </li>
        </ul>
    <?php endif; ?>

<?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">🔍</div>
        <h3>No pets found matching your criteria</h3>
        <p>Try broadening your search filters, selecting a different city, or resetting the filters to view all pets.</p>
        <a href="<?php echo url('pets.php'); ?>" class="btn btn-primary">Clear All Filters</a>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
