<?php
/**
 * PawConnect - Home Page
 */
$page_title = "Find Your Loving Furry Companion";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

// Fetch Dynamic Stats
$stats = [
    'pets_count' => 0,
    'adoptions' => 0,
    'shelters' => 0,
    'visits' => 0
];

$res_pets = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pets");
if ($row = mysqli_fetch_assoc($res_pets)) $stats['pets_count'] = $row['total'];

$res_adoptions = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pets WHERE status = 'Adopted'");
if ($row = mysqli_fetch_assoc($res_adoptions)) $stats['adoptions'] = $row['total'];

$res_shelters = mysqli_query($conn, "SELECT COUNT(*) AS total FROM shelters");
if ($row = mysqli_fetch_assoc($res_shelters)) $stats['shelters'] = $row['total'];

$res_visits = mysqli_query($conn, "SELECT COUNT(*) AS total FROM visits");
if ($row = mysqli_fetch_assoc($res_visits)) $stats['visits'] = $row['total'];

// Fetch distinct cities for the search dropdown
$cities_res = mysqli_query($conn, "SELECT DISTINCT city FROM shelters ORDER BY city ASC");
$cities = [];
while ($c = mysqli_fetch_assoc($cities_res)) {
    if (!empty($c['city'])) $cities[] = $c['city'];
}

// Fetch 6 Latest Available Featured Pets
$featured_query = "
    SELECT p.*, s.shelter_name, s.city 
    FROM pets p 
    JOIN shelters s ON p.shelter_id = s.shelter_id 
    WHERE p.status = 'Available' 
    ORDER BY p.created_at DESC 
    LIMIT 6
";
$featured_res = mysqli_query($conn, $featured_query);
$featured_pets = [];
if ($featured_res) {
    while ($row = mysqli_fetch_assoc($featured_res)) {
        $featured_pets[] = $row;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero">
    <div class="hero-grid">
        <div class="hero-content">
            <h1>Adopt, Don't Shop.<br>Find Your <span>Forever Friend</span>.</h1>
            <p class="hero-subtitle">
                Connect with verified animal shelters across Kerala. Browse healthy pets, apply online, and schedule shelter visits in just a few clicks.
            </p>

            <!-- Quick Search Form -->
            <div class="hero-search-box">
                <form action="<?php echo url('pets.php'); ?>" method="GET" class="hero-search-form">
                    <div>
                        <label class="form-label" style="font-size:0.8rem; margin-bottom:2px;">Species</label>
                        <select name="species" class="form-control">
                            <option value="">All Species</option>
                            <option value="Dog">🐶 Dogs</option>
                            <option value="Cat">🐱 Cats</option>
                            <option value="Rabbit">🐰 Rabbits</option>
                            <option value="Bird">🦜 Birds</option>
                            <option value="Other">🐾 Other Pets</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label" style="font-size:0.8rem; margin-bottom:2px;">City</label>
                        <select name="city" class="form-control">
                            <option value="">All Cities</option>
                            <?php foreach ($cities as $city): ?>
                                <option value="<?php echo e($city); ?>"><?php echo e($city); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary" style="height: 42px; width: 100%;">
                            🔍 Search Pets
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="hero-img-wrapper">
            <img src="https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=700&q=80" alt="Happy adopted pets" class="hero-img">
        </div>
    </div>
</section>

<!-- Dynamic Stats Counter -->
<section class="stats-section">
    <div class="stat-card">
        <span class="stat-icon">🐕</span>
        <div class="stat-number"><?php echo $stats['pets_count']; ?></div>
        <div class="stat-label">Total Rescues Listed</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🏡</span>
        <div class="stat-number"><?php echo $stats['adoptions']; ?></div>
        <div class="stat-label">Happy Adoptions</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">🏢</span>
        <div class="stat-number"><?php echo $stats['shelters']; ?></div>
        <div class="stat-label">Partner Shelters</div>
    </div>
    <div class="stat-card">
        <span class="stat-icon">📅</span>
        <div class="stat-number"><?php echo $stats['visits']; ?></div>
        <div class="stat-label">Visits Scheduled</div>
    </div>
</section>

<!-- Featured Pets Section -->
<section style="margin-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;">
        <div>
            <span class="section-tag">Recently Added</span>
            <h2>Featured Available Pets</h2>
        </div>
        <a href="<?php echo url('pets.php'); ?>" class="btn btn-outline-accent btn-sm">View All Pets &rarr;</a>
    </div>

    <?php if (!empty($featured_pets)): ?>
        <div class="pets-grid">
            <?php foreach ($featured_pets as $pet): ?>
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
                                Meet <?php echo e($pet['name']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">🐾</div>
            <h3>No pets listed yet</h3>
            <p>Check back soon or run the database setup script to view demo pets!</p>
        </div>
    <?php endif; ?>
</section>

<!-- How It Works Section -->
<section class="how-it-works">
    <div class="section-header">
        <span class="section-tag">Simple & Transparent</span>
        <h2>How PawConnect Works</h2>
        <p class="text-muted">Adopting a companion animal through PawConnect is streamlined into three easy steps.</p>
    </div>

    <div class="steps-grid">
        <div class="step-card">
            <div class="step-number">1</div>
            <div class="step-icon">🔍</div>
            <h3>Search & Discover</h3>
            <p class="text-muted">
                Filter through pets by species, breed, age, size, and location to find the perfect match for your lifestyle.
            </p>
        </div>

        <div class="step-card">
            <div class="step-number">2</div>
            <div class="step-icon">📅</div>
            <h3>Meet & Apply</h3>
            <p class="text-muted">
                Schedule an in-person shelter visit or submit your adoption application directly online for the shelter's review.
            </p>
        </div>

        <div class="step-card">
            <div class="step-number">3</div>
            <div class="step-icon">❤️</div>
            <h3>Welcome Home</h3>
            <p class="text-muted">
                Once approved, finalize the adoption with the shelter and welcome your new family member into your home!
            </p>
        </div>
    </div>
</section>

<!-- Shelter Callout -->
<section style="background: #FFF5EC; padding: 40px; border-radius: var(--radius-md); border: 1px solid var(--primary-light); text-align: center; margin-bottom: 20px;">
    <h2 style="color: var(--primary);">Are You an Animal Shelter or Rescue Organization?</h2>
    <p class="text-muted" style="max-width: 600px; margin: 0 auto 20px auto;">
        Join PawConnect to list your rescue animals, manage visit bookings, and process adoption applications seamlessly.
    </p>
    <a href="<?php echo url('register.php?role=shelter'); ?>" class="btn btn-primary btn-lg">Register Your Shelter</a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
