<?php
/**
 * PawConnect - Delete Pet (Shelter)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

require_role('shelter');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shelter_id = get_shelter_id_for_user($conn, current_user('user_id'));
    $pet_id = (int)($_POST['pet_id'] ?? 0);

    if (!verify_csrf_token()) {
        set_flash('error', 'Invalid security token.');
        header("Location: " . url('shelter/dashboard.php'));
        exit;
    }

    if ($pet_id > 0 && $shelter_id) {
        // Fetch pet image to delete local file if any
        $stmt = mysqli_prepare($conn, "SELECT image, name FROM pets WHERE pet_id = ? AND shelter_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, "ii", $pet_id, $shelter_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $pet = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        if ($pet) {
            // Delete record
            $del_stmt = mysqli_prepare($conn, "DELETE FROM pets WHERE pet_id = ? AND shelter_id = ?");
            mysqli_stmt_bind_param($del_stmt, "ii", $pet_id, $shelter_id);
            if (mysqli_stmt_execute($del_stmt)) {
                // Remove local image file if exists
                if (!empty($pet['image']) && !preg_match('/^https?:\/\//i', $pet['image'])) {
                    $file_path = __DIR__ . '/../uploads/' . $pet['image'];
                    if (file_exists($file_path) && is_file($file_path)) {
                        @unlink($file_path);
                    }
                }
                set_flash('success', 'Pet "' . $pet['name'] . '" and all associated records have been deleted.');
            } else {
                set_flash('error', 'Failed to delete pet: ' . mysqli_error($conn));
            }
            mysqli_stmt_close($del_stmt);
        } else {
            set_flash('error', 'Pet not found or unauthorized.');
        }
    }
}

header("Location: " . url('shelter/dashboard.php'));
exit;
