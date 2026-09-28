<?php
/**
 * PawConnect - Schedule Shelter Visit
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

require_role('adopter');

$user_id = current_user('user_id');
$pet_id = (int)($_GET['pet_id'] ?? $_POST['pet_id'] ?? 0);

if ($pet_id <= 0) {
    set_flash('error', 'Please select a pet to schedule a visit.');
    header("Location: " . url('pets.php'));
    exit;
}

// Fetch Pet and Shelter Details
$stmt = mysqli_prepare($conn, "
    SELECT p.*, s.shelter_name, s.address, s.city, s.phone AS shelter_phone
    FROM pets p 
    JOIN shelters s ON p.shelter_id = s.shelter_id 
    WHERE p.pet_id = ? 
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, "i", $pet_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$pet = mysqli_fetch_assoc($res);
mysqli_stmt_close($stmt);

if (!$pet) {
    set_flash('error', 'Pet not found.');
    header("Location: " . url('pets.php'));
    exit;
}

$min_date = date('Y-m-d', strtotime('+1 day'));
$errors = [];
$visit_date = $_POST['visit_date'] ?? $min_date;
$visit_time = $_POST['visit_time'] ?? '10:00';

$available_time_slots = [
    '10:00:00' => '10:00 AM - 11:00 AM',
    '11:00:00' => '11:00 AM - 12:00 PM',
    '12:00:00' => '12:00 PM - 01:00 PM',
    '14:00:00' => '02:00 PM - 03:00 PM',
    '15:00:00' => '03:00 PM - 04:00 PM',
    '16:00:00' => '04:00 PM - 05:00 PM',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_date = clean_input($_POST['visit_date'] ?? '');
    $visit_time = clean_input($_POST['visit_time'] ?? '');

    if (!verify_csrf_token()) {
        $errors[] = "Security token expired. Please try again.";
    }

    if (empty($visit_date) || strtotime($visit_date) < strtotime(date('Y-m-d', strtotime('+1 day')))) {
        $errors[] = "Visit date must be at least one day in advance (starting from tomorrow).";
    }

    // Check if selected date is Sunday
    $day_of_week = (int)date('w', strtotime($visit_date));
    if ($day_of_week === 0) {
        $errors[] = "Shelters are closed on Sundays for visits. Please pick Monday through Saturday.";
    }

    // Check valid time slot
    if (!array_key_exists($visit_time, $available_time_slots)) {
        $errors[] = "Please select a valid visiting time slot.";
    }

    // Check Double-Booking for this Pet on the specified Date and Time Slot
    if (empty($errors)) {
        $check_slot = mysqli_prepare($conn, "
            SELECT visit_id 
            FROM visits 
            WHERE pet_id = ? AND visit_date = ? AND visit_time = ? AND status = 'Scheduled' 
            LIMIT 1
        ");
        mysqli_stmt_bind_param($check_slot, "iss", $pet_id, $visit_date, $visit_time);
        mysqli_stmt_execute($check_slot);
        mysqli_stmt_store_result($check_slot);
        if (mysqli_stmt_num_rows($check_slot) > 0) {
            $errors[] = "This time slot on " . date('M d, Y', strtotime($visit_date)) . " is already booked for " . $pet['name'] . ". Please choose a different time or date.";
        }
        mysqli_stmt_close($check_slot);
    }

    // Insert visit if no errors
    if (empty($errors)) {
        $insert_visit = mysqli_prepare($conn, "
            INSERT INTO visits (pet_id, user_id, visit_date, visit_time, status)
            VALUES (?, ?, ?, ?, 'Scheduled')
        ");
        mysqli_stmt_bind_param($insert_visit, "iiss", $pet_id, $user_id, $visit_date, $visit_time);
        
        if (mysqli_stmt_execute($insert_visit)) {
            mysqli_stmt_close($insert_visit);
            set_flash('success', 'Your visit to meet ' . $pet['name'] . ' has been booked for ' . date('M d, Y', strtotime($visit_date)) . ' at ' . date('h:i A', strtotime($visit_time)) . '!');
            header("Location: " . url('my_visits.php'));
            exit;
        } else {
            $errors[] = "Database error: " . mysqli_error($conn);
            mysqli_stmt_close($insert_visit);
        }
    }
}

$page_title = "Schedule a Visit with " . $pet['name'];
require_once __DIR__ . '/includes/header.php';
?>

<div style="max-width: 600px; margin: 20px auto;">
    <div style="margin-bottom: 15px;">
        <a href="<?php echo url('pet_details.php?id=' . $pet['pet_id']); ?>" style="color: var(--text-muted); font-size: 0.9rem;">
            &larr; Back to <?php echo e($pet['name']); ?>'s profile
        </a>
    </div>

    <div class="form-card">
        <h2 style="margin-bottom: 6px;">Schedule a Shelter Visit</h2>
        <p class="text-muted" style="margin-bottom: 20px;">
            Book an in-person slot to interact with <strong><?php echo e($pet['name']); ?></strong> and meet the shelter team.
        </p>

        <!-- Shelter Location Card -->
        <div style="background: var(--bg-subtle); padding: 15px; border-radius: var(--radius-sm); margin-bottom: 20px; border: 1px solid var(--border-color); font-size: 0.9rem;">
            <div style="font-weight: 600; color: var(--accent); margin-bottom: 4px;">🏢 <?php echo e($pet['shelter_name']); ?></div>
            <div>📍 <strong>Address:</strong> <?php echo e($pet['address']); ?>, <?php echo e($pet['city']); ?></div>
            <div>📞 <strong>Shelter Contact:</strong> <?php echo e($pet['shelter_phone']); ?></div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin-left: 20px;">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo url('schedule_visit.php'); ?>" method="POST" id="visitForm">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="pet_id" value="<?php echo $pet_id; ?>">

            <div class="form-group">
                <label class="form-label required">Visit Date</label>
                <input type="date" name="visit_date" id="visitDate" class="form-control" required min="<?php echo $min_date; ?>" value="<?php echo e($visit_date); ?>">
                <div class="form-hint">Visits can be booked from tomorrow onward. Note: Shelters are closed on Sundays.</div>
            </div>

            <div class="form-group">
                <label class="form-label required">Available Time Slot</label>
                <select name="visit_time" class="form-control" required>
                    <?php foreach ($available_time_slots as $time_val => $time_label): ?>
                        <option value="<?php echo $time_val; ?>" <?php echo ($visit_time === $time_val) ? 'selected' : ''; ?>>
                            <?php echo $time_label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-hint">Each slot is 1 hour long with personalized attention from shelter staff.</div>
            </div>

            <div style="margin-top: 30px;">
                <button type="submit" class="btn btn-accent btn-lg btn-block">
                    Confirm Visit Booking
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Prevent selecting Sundays on client side
const dateInput = document.getElementById('visitDate');
if (dateInput) {
    dateInput.addEventListener('input', function(e) {
        const day = new Date(this.value).getUTCDay();
        if (day === 0) {
            alert('Shelters are closed on Sundays. Please select another day.');
            this.value = '';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
