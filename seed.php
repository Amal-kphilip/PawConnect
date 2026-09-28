<?php
/**
 * PawConnect - Database Seeder Script
 * Run this in your browser: http://localhost/Pawconnect/seed.php
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Connection parameters
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'pet_adoption';
$db_port = 3306;

$server_conn = mysqli_connect($db_host, $db_user, $db_pass, '', $db_port);
if (!$server_conn) {
    die("Connection to MySQL server failed: " . mysqli_connect_error());
}

// 1. Create database if not exists
$sql_createdb = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!mysqli_query($server_conn, $sql_createdb)) {
    die("Error creating database: " . mysqli_error($server_conn));
}
mysqli_select_db($server_conn, $db_name);
mysqli_set_charset($server_conn, 'utf8mb4');

// Disable Foreign Key checks for clean reset
mysqli_query($server_conn, "SET FOREIGN_KEY_CHECKS = 0");

// 2. Drop existing tables if re-seeding
mysqli_query($server_conn, "DROP TABLE IF EXISTS visits");
mysqli_query($server_conn, "DROP TABLE IF EXISTS applications");
mysqli_query($server_conn, "DROP TABLE IF EXISTS pets");
mysqli_query($server_conn, "DROP TABLE IF EXISTS shelters");
mysqli_query($server_conn, "DROP TABLE IF EXISTS users");

// 3. Create Tables
$table_users = "
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role ENUM('adopter', 'shelter', 'admin') NOT NULL DEFAULT 'adopter',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
";

$table_shelters = "
CREATE TABLE shelters (
    shelter_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shelter_name VARCHAR(150) NOT NULL,
    address VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    description TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
";

$table_pets = "
CREATE TABLE pets (
    pet_id INT AUTO_INCREMENT PRIMARY KEY,
    shelter_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    species ENUM('Dog', 'Cat', 'Rabbit', 'Bird', 'Other') NOT NULL DEFAULT 'Dog',
    breed VARCHAR(100) NOT NULL,
    age_years DECIMAL(3, 1) NOT NULL,
    gender ENUM('Male', 'Female') NOT NULL,
    size ENUM('Small', 'Medium', 'Large') NOT NULL DEFAULT 'Medium',
    vaccinated TINYINT(1) NOT NULL DEFAULT 1,
    description TEXT NOT NULL,
    image VARCHAR(255) NULL,
    status ENUM('Available', 'Pending', 'Adopted') NOT NULL DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shelter_id) REFERENCES shelters(shelter_id) ON DELETE CASCADE
) ENGINE=InnoDB;
";

$table_applications = "
CREATE TABLE applications (
    app_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    user_id INT NOT NULL,
    reason TEXT NOT NULL,
    home_type ENUM('Apartment', 'House', 'Farm') NOT NULL DEFAULT 'Apartment',
    has_other_pets TINYINT(1) NOT NULL DEFAULT 0,
    experience TEXT NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(pet_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
";

$table_visits = "
CREATE TABLE visits (
    visit_id INT AUTO_INCREMENT PRIMARY KEY,
    pet_id INT NOT NULL,
    user_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_time TIME NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pet_id) REFERENCES pets(pet_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;
";

mysqli_query($server_conn, $table_users) or die(mysqli_error($server_conn));
mysqli_query($server_conn, $table_shelters) or die(mysqli_error($server_conn));
mysqli_query($server_conn, $table_pets) or die(mysqli_error($server_conn));
mysqli_query($server_conn, $table_applications) or die(mysqli_error($server_conn));
mysqli_query($server_conn, $table_visits) or die(mysqli_error($server_conn));

mysqli_query($server_conn, "SET FOREIGN_KEY_CHECKS = 1");

// 4. Seed Users with real password_hash('password123', PASSWORD_DEFAULT)
$default_password = password_hash('password123', PASSWORD_DEFAULT);

$users_data = [
    // 1 Admin
    ['Admin Officer', 'admin@pawconnect.com', $default_password, '+91 98000 00001', 'admin'],
    
    // 6 Shelter Representatives (Across 6 Major Cities in Kerala)
    ['Suresh Kumar', 'shelter1@happytails.org', $default_password, '+91 98470 12345', 'shelter'],
    ['Priya Nambiar', 'shelter2@safepaws.org', $default_password, '+91 94471 23456', 'shelter'],
    ['Vinod Raj', 'shelter3@secondchance.org', $default_password, '+91 98950 34567', 'shelter'],
    ['Dr. Lakshmi Varma', 'shelter4@karuna.org', $default_password, '+91 98460 78901', 'shelter'],
    ['Mathew George', 'shelter5@pawscare.org', $default_password, '+91 94472 89012', 'shelter'],
    ['Deepa Radhakrishnan', 'shelter6@greenvalley.org', $default_password, '+91 98951 90123', 'shelter'],

    // 10 Adopters with realistic Indian names
    ['Anjali Nair', 'anjali@gmail.com', $default_password, '+91 98460 11223', 'adopter'],
    ['Rahul Menon', 'rahul@gmail.com', $default_password, '+91 94470 22334', 'adopter'],
    ['Fathima Rasheed', 'fathima@gmail.com', $default_password, '+91 97450 33445', 'adopter'],
    ['Arjun Das', 'arjun@gmail.com', $default_password, '+91 99950 44556', 'adopter'],
    ['Meera Krishnan', 'meera@gmail.com', $default_password, '+91 98475 55667', 'adopter'],
    ['Joseph Thomas', 'joseph@gmail.com', $default_password, '+91 94465 66778', 'adopter'],
    ['Kavya Pillai', 'kavya@gmail.com', $default_password, '+91 98472 77889', 'adopter'],
    ['Nikhil Varma', 'nikhil@gmail.com', $default_password, '+91 94478 88990', 'adopter'],
    ['Sneha Paul', 'sneha@gmail.com', $default_password, '+91 97455 99001', 'adopter'],
    ['Rohan Kulkarni', 'rohan@gmail.com', $default_password, '+91 99958 12340', 'adopter']
];

$user_stmt = mysqli_prepare($server_conn, "INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
foreach ($users_data as $u) {
    mysqli_stmt_bind_param($user_stmt, "sssss", $u[0], $u[1], $u[2], $u[3], $u[4]);
    mysqli_stmt_execute($user_stmt);
}
mysqli_stmt_close($user_stmt);

// 5. Seed 6 Shelters (Linked to user_id 2 through 7)
$shelters_data = [
    [2, 'Happy Tails Shelter', 'Panampilly Nagar, Near Central Park', 'Kochi', '+91 98470 12345', 'Happy Tails rescues, rehabilitates, and finds loving homes for abandoned and injured companion animals in Ernakulam and Kochi.'],
    [3, 'Safe Paws Rescue', 'Civil Station Road, Near Police Ground', 'Kannur', '+91 94471 23456', 'A volunteer-driven non-profit animal rescue providing medical treatment and compassionate adoption services in Kannur.'],
    [4, 'Second Chance Animal Home', 'Beach Road, Behind Old Lighthouse', 'Kozhikode', '+91 98950 34567', 'Dedicated to giving vulnerable dogs and cats a second chance at happiness through community foster and adoption programs.'],
    [5, 'Karuna Animal Sanctuary', 'Vellayambalam, Near Museum Junction', 'Thiruvananthapuram', '+91 98460 78901', 'State capital rescue facility offering 24/7 emergency medical care, rehabilitation, and ethical adoption screening.'],
    [6, 'Paws & Care Foundation', 'Round West, Near Swaraj Round', 'Thrissur', '+91 94472 89012', 'Community rescue shelter providing lifetime sterilization, vaccination drives, and loving homes for local rescue animals.'],
    [7, 'Green Valley Animal Rescue', 'Kanjikuzhy, Near Collectorate', 'Kottayam', '+91 98951 90123', 'Passionate team of veterinarians and animal lovers rehoming orphaned puppies, kittens, rabbits, and rescued native birds.']
];

$shelter_stmt = mysqli_prepare($server_conn, "INSERT INTO shelters (user_id, shelter_name, address, city, phone, description) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($shelters_data as $s) {
    mysqli_stmt_bind_param($shelter_stmt, "isssss", $s[0], $s[1], $s[2], $s[3], $s[4], $s[5]);
    mysqli_stmt_execute($shelter_stmt);
}
mysqli_stmt_close($shelter_stmt);

// 6. Seed 36 Diverse Pets ensuring ALL filter criteria have multiple matching rows
// Categories covered:
// Species: Dog, Cat, Rabbit, Bird, Other
// Genders: Male, Female
// Sizes: Small (<10kg), Medium (10-25kg), Large (>25kg)
// Age Groups: Puppy/Kitten (<=1yr), Young (1-3yrs), Adult (3-7yrs), Senior (>7yrs)
// Cities: Kochi, Kannur, Kozhikode, Thiruvananthapuram, Thrissur, Kottayam
// Statuses: Available, Pending, Adopted
$pets_data = [
    // ----------------- DOGS (Puppies, Young, Adult, Senior / Small, Medium, Large / Male & Female) -----------------
    // 1. Bruno - Large, Young, Male, Kochi, Available
    [1, 'Bruno', 'Dog', 'Golden Retriever', 2.5, 'Male', 'Large', 1, 'Bruno is an energetic, affectionate Golden Retriever who loves morning walks, fetch, and belly rubs. Great with children and other friendly dogs.', 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=600&q=80', 'Available'],
    
    // 2. Luna - Medium, Puppy, Female, Kochi, Available
    [1, 'Luna', 'Dog', 'Indian Pariah Puppy', 0.6, 'Female', 'Medium', 1, 'Luna is a bright, playful native Indian puppy. Super smart, highly trainable, alert, and eager to bond with a warm family.', 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 3. Coco - Small, Young, Male, Kannur, Available
    [2, 'Coco', 'Dog', 'Beagle', 2.0, 'Male', 'Small', 1, 'Coco is a curious Beagle with big warm eyes and a loving heart. He enjoys scent tracking games and cuddle sessions on the sofa.', 'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 4. Max - Large, Adult, Male, Kannur, Available
    [2, 'Max', 'Dog', 'Labrador Retriever', 4.5, 'Male', 'Large', 1, 'Max is a gentle giant who loves swimming and calm evening strolls. Very well-behaved, leash trained, and polite around visitors.', 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 5. Bella - Medium, Young, Female, Kozhikode, Available
    [3, 'Bella', 'Dog', 'Cocker Spaniel', 1.8, 'Female', 'Medium', 1, 'Bella has a gorgeous silky coat and a joyful, sunny personality. She loves playing in grassy yards and greeting family members.', 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 6. Rocky - Large, Adult, Male, Kozhikode, Available
    [3, 'Rocky', 'Dog', 'German Shepherd Mix', 5.0, 'Male', 'Large', 1, 'Rocky is a loyal, protective, and intelligent companion. Knows basic obedience commands and thrives with an active owner or yard.', 'https://images.unsplash.com/photo-1589941013453-ec89f33b5455?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 7. Daisy - Small, Young, Female, Kochi, Pending
    [1, 'Daisy', 'Dog', 'Pug', 2.0, 'Female', 'Small', 1, 'Daisy is a charming lap dog who loves quiet apartments, squeaky toys, and snuggling under blankets. Very gentle and affectionate.', 'https://images.unsplash.com/photo-1517423440428-a5a00ad493e8?auto=format&fit=crop&w=600&q=80', 'Pending'],

    // 8. Toby - Small, Adult, Male, Kannur, Adopted
    [2, 'Toby', 'Dog', 'Dachshund', 5.0, 'Male', 'Small', 1, 'Toby is a spirited little guy with lots of character. He has found his dream family and is happily settled in his new home!', 'https://images.unsplash.com/photo-1612195583950-b8fd34c87093?auto=format&fit=crop&w=600&q=80', 'Adopted'],

    // 9. Buster - Large, Senior, Male, Thiruvananthapuram, Available
    [4, 'Buster', 'Dog', 'Boxer Mix', 8.5, 'Male', 'Large', 1, 'Buster is a gentle senior dog looking for a peaceful retirement home. Very calm, house-trained, and enjoys leisurely yard naps.', 'https://images.unsplash.com/photo-1535930891776-0c2dfb7fda1a?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 10. Ruby - Small, Puppy, Female, Thrissur, Available
    [5, 'Ruby', 'Dog', 'Pomeranian Mix', 0.5, 'Female', 'Small', 1, 'Ruby is an adorable fluffy puppy with endless charm and curiosity. Great for indoor apartment living with plenty of cuddles.', 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 11. Sheru - Large, Senior, Male, Kottayam, Available
    [6, 'Sheru', 'Dog', 'Indian Mastiff Indie', 9.0, 'Male', 'Large', 1, 'Sheru is a noble senior guardian who loves quiet company and gentle head pats. Low energy and deeply loyal.', 'https://images.unsplash.com/photo-1561037404-61cd46aa615b?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 12. Stella - Medium, Adult, Female, Thiruvananthapuram, Available
    [4, 'Stella', 'Dog', 'Border Collie Mix', 3.5, 'Female', 'Medium', 1, 'Stella is highly attentive, agile, and eager to learn tricks. Perfect for an active family who loves jogging and agility games.', 'https://images.unsplash.com/photo-1503256207526-0d5d80fa2f47?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 13. Leo - Small, Senior, Male, Thrissur, Available
    [5, 'Leo', 'Dog', 'Shih Tzu', 8.0, 'Male', 'Small', 1, 'Leo is a quiet, friendly senior Shih Tzu who gets along great with cats and older adults. Fully vaccinated and groomed.', 'https://images.unsplash.com/photo-1591769225440-811ad7d6eab2?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 14. Tara - Medium, Puppy, Female, Kottayam, Available
    [6, 'Tara', 'Dog', 'Labrador Indie Mix', 0.8, 'Female', 'Medium', 0, 'Tara was rescued near a tea estate. She is cheerful, healthy, vaccinated for first rounds, and loves playing with rubber balls.', 'https://images.unsplash.com/photo-1560807707-8cc77767d783?auto=format&fit=crop&w=600&q=80', 'Available'],

    // ----------------- CATS (Kittens, Young, Adult, Senior / Small, Medium / Male & Female) -----------------
    // 15. Simba - Medium, Young, Male, Kochi, Available
    [1, 'Simba', 'Cat', 'Ginger Tabby', 1.5, 'Male', 'Medium', 1, 'Simba is a playful ginger boy who purrs instantly when stroked. Loves feather wands, sunny windowsills, and climbing cat trees.', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 16. Milo - Medium, Young, Male, Kochi, Available
    [1, 'Milo', 'Cat', 'Siamese Mix', 2.0, 'Male', 'Medium', 1, 'Milo has striking sapphire blue eyes and a vocal, affectionate nature. He loves following people around and snoozing nearby.', 'https://images.unsplash.com/photo-1513360309081-38f0762daed1?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 17. Oreo - Small, Young, Female, Kannur, Available
    [2, 'Oreo', 'Cat', 'Tuxedo Domestic', 1.2, 'Female', 'Small', 1, 'Oreo wears a dapper black-and-white tuxedo coat. Sweet, polite, litter-trained, and well-mannered in apartments.', 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 18. Shadow - Medium, Young, Male, Kannur, Pending
    [2, 'Shadow', 'Cat', 'Persian', 3.0, 'Male', 'Medium', 1, 'Shadow is an elegant, fluffy Persian cat who loves being brushed. He is very tranquil and prefers a calm indoor environment.', 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?auto=format&fit=crop&w=600&q=80', 'Pending'],

    // 19. Hazel - Small, Kitten, Female, Kozhikode, Available
    [3, 'Hazel', 'Cat', 'Calico Kitten', 0.6, 'Female', 'Small', 1, 'Hazel is a sweet tricolor kitten full of energy and fun. Loves chasing cardboard balls and cuddling on warm laps.', 'https://images.unsplash.com/photo-1561948955-570b270e7c36?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 20. Cookie - Medium, Young, Female, Kozhikode, Adopted
    [3, 'Cookie', 'Cat', 'British Shorthair Mix', 2.5, 'Female', 'Medium', 1, 'Cookie has found her forever home and is enjoying peaceful days with her new family in Kozhikode.', 'https://images.unsplash.com/photo-1543852786-1cf6624b9987?auto=format&fit=crop&w=600&q=80', 'Adopted'],

    // 21. Oliver - Medium, Senior, Male, Thiruvananthapuram, Available
    [4, 'Oliver', 'Cat', 'Domestic Grey Tabby', 8.0, 'Male', 'Medium', 1, 'Oliver is a wise, gentle senior feline who spends his afternoons sleeping in sunbeams. Litter trained and very docile.', 'https://images.unsplash.com/photo-1518791841217-8f162f1e1131?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 22. Cleo - Small, Kitten, Female, Thrissur, Available
    [5, 'Cleo', 'Cat', 'Snow White Domestic', 0.4, 'Female', 'Small', 1, 'Cleo is a pristine white kitten with boundless energy and a soft motor-like purr. Looking for an indoor home.', 'https://images.unsplash.com/photo-1574158622682-e40e69881006?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 23. Jasper - Medium, Adult, Male, Kottayam, Available
    [6, 'Jasper', 'Cat', 'Maine Coon Mix', 4.0, 'Male', 'Medium', 1, 'Jasper is a majestic, long-haired cat with a bushy tail. Friendly with respectful dogs and very affectionate.', 'https://images.unsplash.com/photo-1535930891776-0c2dfb7fda1a?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 24. Maya - Small, Senior, Female, Thrissur, Available
    [5, 'Maya', 'Cat', 'Tortoiseshell', 7.5, 'Female', 'Small', 1, 'Maya has a stunning tortoiseshell coat and a serene personality. Loves quiet companionship and gentle brushing.', 'https://images.unsplash.com/photo-1548802673-380ab8ebc7b7?auto=format&fit=crop&w=600&q=80', 'Available'],

    // ----------------- RABBITS (Kitten/Young/Adult / Male & Female / Small) -----------------
    // 25. Thumper - Small, Young, Male, Kochi, Available
    [1, 'Thumper', 'Rabbit', 'Holland Lop', 1.2, 'Male', 'Small', 1, 'Thumper is a dwarf lop-eared rabbit with soft grey fur. Loves munching fresh timothy hay and forehead strokes.', 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 26. Snowball - Small, Young, Female, Kozhikode, Available
    [3, 'Snowball', 'Rabbit', 'Netherland Dwarf', 1.5, 'Female', 'Small', 1, 'Snowball is a pristine white dwarf bunny. Gentle, litter-box trained, and thrives in a safe indoor enclosure.', 'https://images.unsplash.com/photo-1535241749838-299277b6305f?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 27. Clover - Small, Puppy/Kitten (<1yr), Female, Thiruvananthapuram, Available
    [4, 'Clover', 'Rabbit', 'Mini Rex Baby', 0.5, 'Female', 'Small', 1, 'Clover has velvety plush fur and an inquisitive nature. Hand-raised and enjoys fresh coriander and leafy greens.', 'https://images.unsplash.com/photo-1591382364584-a0777b8c3934?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 28. Bunbun - Small, Adult, Male, Thrissur, Available
    [5, 'Bunbun', 'Rabbit', 'Lionhead', 3.2, 'Male', 'Small', 1, 'Bunbun has a distinctive fluffy lion mane and a calm temperament. Used to quiet indoor living spaces.', 'https://images.unsplash.com/photo-1518796745738-41048802f99a?auto=format&fit=crop&w=600&q=80', 'Available'],

    // ----------------- BIRDS (Young, Adult, Senior / Small & Medium / Male & Female) -----------------
    // 29. Kiwi - Small, Young, Male, Kannur, Available
    [2, 'Kiwi', 'Bird', 'Budgerigar Parakeet', 1.0, 'Male', 'Small', 0, 'Kiwi is a cheerful green budgie who loves chirping along to light acoustic music and learning whistle tunes.', 'https://images.unsplash.com/photo-1552728089-57bdde30beb3?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 30. Sunny - Small, Adult, Female, Thiruvananthapuram, Available
    [4, 'Sunny', 'Bird', 'Cockatiel', 3.5, 'Female', 'Small', 0, 'Sunny is a lovely yellow-crested Cockatiel. She is hand-tame, steps onto fingers easily, and loves apple slices.', 'https://images.unsplash.com/photo-1522858547137-f1dcec554f55?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 31. Mango - Medium, Adult, Male, Kochi, Available
    [1, 'Mango', 'Bird', 'Sun Conure', 4.0, 'Male', 'Medium', 1, 'Mango is a brightly colored sun conure with vibrant orange and green plumage. Highly social and seeks human interaction.', 'https://images.unsplash.com/photo-1452570053594-1b985d6ea890?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 32. Pippin - Small, Young, Female, Kottayam, Available
    [6, 'Pippin', 'Bird', 'Lovebird', 1.5, 'Female', 'Small', 0, 'Pippin is an adorable peach-faced lovebird with an active, cheerful personality. Very playful with wooden bird toys.', 'https://images.unsplash.com/photo-1549608276-5786777e6587?auto=format&fit=crop&w=600&q=80', 'Available'],

    // ----------------- OTHER ANIMALS (Small / Male & Female / Various Ages & Cities) -----------------
    // 33. Rio - Small, Young, Male, Kozhikode, Available
    [3, 'Rio', 'Other', 'Guinea Pig', 1.2, 'Male', 'Small', 1, 'Rio is a friendly tricolor Guinea Pig who wheeks happily whenever bell peppers are chopped. Gentle and easy to hold.', 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 34. Peanut - Small, Puppy/Kitten (<1yr), Female, Thrissur, Available
    [5, 'Peanut', 'Other', 'Syrian Hamster', 0.4, 'Female', 'Small', 1, 'Peanut is a cute golden Syrian hamster. Nocturnal, loves running on silent exercise wheels and snacking on sunflower seeds.', 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 35. Sheldon - Small, Senior, Male, Kottayam, Available
    [6, 'Sheldon', 'Other', 'Indian Star Turtle (Rescued)', 10.0, 'Male', 'Small', 1, 'Sheldon was rescued from illegal trade and rehabilitated. Needs a secure sunny garden enclosure with fresh green vegetation.', 'https://images.unsplash.com/photo-1437622368342-7a3d73a34c8f?auto=format&fit=crop&w=600&q=80', 'Available'],

    // 36. Cinnamon - Small, Young, Female, Thiruvananthapuram, Available
    [4, 'Cinnamon', 'Other', 'Abyssinian Guinea Pig', 1.8, 'Female', 'Small', 1, 'Cinnamon has wonderful rosettes in her warm brown fur. Very tame, enjoys being brushed, and loves spinach leaves.', 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=600&q=80', 'Available']
];

$pet_stmt = mysqli_prepare($server_conn, "INSERT INTO pets (shelter_id, name, species, breed, age_years, gender, size, vaccinated, description, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($pets_data as $p) {
    mysqli_stmt_bind_param($pet_stmt, "isssdssisss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10]);
    mysqli_stmt_execute($pet_stmt);
}
mysqli_stmt_close($pet_stmt);

// 7. Seed 14 Believable Applications across users and pets
// User IDs: 8 (Anjali), 9 (Rahul), 10 (Fathima), 11 (Arjun), 12 (Meera), 13 (Joseph), 14 (Kavya), 15 (Nikhil), 16 (Sneha), 17 (Rohan)
$apps_data = [
    // 1. Anjali -> Bruno (Pet 1) - Pending
    [1, 8, 'We have a spacious house with a secure fenced garden in Kochi and love outdoor walks. Bruno would be our beloved family companion.', 'House', 0, 'Raised Golden Retrievers for over 10 years; experienced with routine grooming and veterinary visits.', 'Pending'],
    
    // 2. Rahul -> Luna (Pet 2) - Pending
    [2, 9, 'Looking to welcome an energetic native Indian rescue puppy into our home.', 'House', 0, 'Cared for our indie rescue dog for 13 years.', 'Pending'],

    // 3. Fathima -> Simba (Pet 15) - Pending
    [15, 10, 'I work from home and would love to have an affectionate indoor feline like Simba.', 'Apartment', 0, 'First-time cat adopter, researched feline nutrition and enrichment thoroughly.', 'Pending'],

    // 4. Arjun -> Coco (Pet 3) - Pending
    [3, 11, 'We adore hounds and have plenty of time for daily scent exploration walks.', 'House', 1, 'Currently have a 4-year-old friendly rescue indie dog.', 'Pending'],

    // 5. Meera -> Toby (Pet 8) - Approved (Toby is Adopted)
    [8, 12, 'Toby will receive continuous care and lap cuddles at our quiet family home.', 'House', 0, 'Extensive experience caring for senior and small-breed dogs.', 'Approved'],

    // 6. Joseph -> Cookie (Pet 20) - Approved (Cookie is Adopted)
    [20, 13, 'Looking for a calm, friendly cat to share my quiet apartment with.', 'Apartment', 0, 'Volunteered at local animal rescues during college.', 'Approved'],

    // 7. Rahul -> Daisy (Pet 7) - Pending
    [7, 9, 'Daisy is such a sweet dog. We want to give her all the cozy care and love she deserves.', 'Apartment', 0, 'Experienced in caring for brachycephalic small breeds.', 'Pending'],

    // 8. Fathima -> Max (Pet 4) - Rejected
    [4, 10, 'Applying for Max, but currently live in a small studio with strict landlord size rules.', 'Apartment', 0, 'None.', 'Rejected'],

    // 9. Anjali -> Shadow (Pet 18) - Pending
    [18, 8, 'Shadow would be very happy in our calm, air-conditioned home in Kochi.', 'Apartment', 0, 'Experienced in daily brushing and grooming long-haired Persian cats.', 'Pending'],

    // 10. Arjun -> Thumper (Pet 25) - Pending
    [25, 11, 'Our family has set up a spacious rabbit enclosure and is excited to welcome Thumper.', 'House', 0, 'Researched rabbit dietary hay requirements and rabbit-safe homes.', 'Pending'],

    // 11. Kavya -> Buster (Pet 9) - Pending
    [9, 14, 'Looking for a gentle senior dog to enjoy quiet garden afternoons together.', 'House', 0, 'Experienced with senior dog joint health and care.', 'Pending'],

    // 12. Nikhil -> Ruby (Pet 10) - Pending
    [10, 15, 'We have a pet-friendly apartment in Thrissur and work flexible hours.', 'Apartment', 0, 'Grew up with family Pomeranians.', 'Pending'],

    // 13. Sneha -> Sunny (Pet 30) - Pending
    [30, 16, 'Have a large indoor aviary and love social hand-tame cockatiels.', 'House', 0, 'Kept cockatiels and budgies for over 8 years.', 'Pending'],

    // 14. Rohan -> Rio (Pet 33) - Pending
    [33, 17, 'Have a large multi-tier guinea pig enclosure with plenty of fresh vegetables.', 'Apartment', 0, 'Knowledgeable on guinea pig vitamin C and socialization needs.', 'Pending']
];

$app_stmt = mysqli_prepare($server_conn, "INSERT INTO applications (pet_id, user_id, reason, home_type, has_other_pets, experience, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
foreach ($apps_data as $a) {
    mysqli_stmt_bind_param($app_stmt, "iisisss", $a[0], $a[1], $a[2], $a[3], $a[4], $a[5], $a[6]);
    mysqli_stmt_execute($app_stmt);
}
mysqli_stmt_close($app_stmt);

// 8. Seed 14 Visits across multiple dates and statuses
$future_date_1 = date('Y-m-d', strtotime('+2 days'));
$future_date_2 = date('Y-m-d', strtotime('+4 days'));
$future_date_3 = date('Y-m-d', strtotime('+5 days'));
$past_date_1 = date('Y-m-d', strtotime('-4 days'));
$past_date_2 = date('Y-m-d', strtotime('-10 days'));

$visits_data = [
    // Future Scheduled
    [1, 8, $future_date_1, '10:00:00', 'Scheduled'],
    [2, 9, $future_date_1, '14:00:00', 'Scheduled'],
    [15, 10, $future_date_2, '11:00:00', 'Scheduled'],
    [3, 11, $future_date_2, '15:00:00', 'Scheduled'],
    [25, 11, $future_date_1, '16:00:00', 'Scheduled'],
    [9, 14, $future_date_3, '10:00:00', 'Scheduled'],
    [10, 15, $future_date_3, '14:00:00', 'Scheduled'],
    [30, 16, $future_date_2, '12:00:00', 'Scheduled'],

    // Past Completed
    [8, 12, $past_date_1, '10:00:00', 'Completed'],
    [20, 13, $past_date_2, '14:00:00', 'Completed'],
    [5, 12, $past_date_1, '11:00:00', 'Completed'],
    [12, 14, $past_date_2, '15:00:00', 'Completed'],

    // Cancelled
    [4, 10, $past_date_2, '15:00:00', 'Cancelled'],
    [6, 13, $past_date_1, '12:00:00', 'Cancelled']
];

$visit_stmt = mysqli_prepare($server_conn, "INSERT INTO visits (pet_id, user_id, visit_date, visit_time, status) VALUES (?, ?, ?, ?, ?)");
foreach ($visits_data as $v) {
    mysqli_stmt_bind_param($visit_stmt, "iisss", $v[0], $v[1], $v[2], $v[3], $v[4]);
    mysqli_stmt_execute($visit_stmt);
}
mysqli_stmt_close($visit_stmt);

mysqli_close($server_conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Complete - PawConnect</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body style="background: #FFFDF9; padding: 40px 20px;">
    <div style="max-width: 800px; margin: 0 auto; background: #fff; padding: 35px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: 1px solid #E2E8F0;">
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="font-size: 3.5rem; margin-bottom: 10px;">🎉</div>
            <h1 style="color: #2A9D8F; margin-bottom: 8px;">PawConnect Database Initialized!</h1>
            <p style="color: #718096;">Database <code>pet_adoption</code> populated with <strong>36 realistic pets</strong> across all species, age categories, sizes, and 6 cities.</p>
        </div>

        <div style="background: #E8F8F5; border: 1px solid #A3E4D7; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <h3 style="color: #117A65; margin-bottom: 12px;">Demo Login Accounts (Password for all: <code>password123</code>)</h3>
            <table style="width: 100%; border-collapse: collapse; font-size: 0.92rem;">
                <tr style="border-bottom: 1px solid #D1F2EB;">
                    <th style="text-align: left; padding: 8px 0; color: #117A65;">Role</th>
                    <th style="text-align: left; padding: 8px 0; color: #117A65;">Organization / User</th>
                    <th style="text-align: left; padding: 8px 0; color: #117A65;">Email Address</th>
                </tr>
                <tr style="border-bottom: 1px solid #E8F8F5;">
                    <td style="padding: 8px 0;"><strong>Admin</strong></td>
                    <td>Main Admin Officer</td>
                    <td><code>admin@pawconnect.com</code></td>
                </tr>
                <tr style="border-bottom: 1px solid #E8F8F5;">
                    <td style="padding: 8px 0;"><strong>Shelter 1</strong></td>
                    <td>Happy Tails (Kochi)</td>
                    <td><code>shelter1@happytails.org</code></td>
                </tr>
                <tr style="border-bottom: 1px solid #E8F8F5;">
                    <td style="padding: 8px 0;"><strong>Shelter 2</strong></td>
                    <td>Safe Paws (Kannur)</td>
                    <td><code>shelter2@safepaws.org</code></td>
                </tr>
                <tr style="border-bottom: 1px solid #E8F8F5;">
                    <td style="padding: 8px 0;"><strong>Shelter 3</strong></td>
                    <td>Second Chance (Kozhikode)</td>
                    <td><code>shelter3@secondchance.org</code></td>
                </tr>
                <tr style="border-bottom: 1px solid #E8F8F5;">
                    <td style="padding: 8px 0;"><strong>Shelter 4</strong></td>
                    <td>Karuna Sanctuary (Thiruvananthapuram)</td>
                    <td><code>shelter4@karuna.org</code></td>
                </tr>
                <tr style="border-bottom: 1px solid #E8F8F5;">
                    <td style="padding: 8px 0;"><strong>Adopter</strong></td>
                    <td>Anjali Nair / Rahul Menon</td>
                    <td><code>anjali@gmail.com</code> / <code>rahul@gmail.com</code></td>
                </tr>
            </table>
        </div>

        <div style="text-align: center;">
            <a href="index.php" class="btn btn-primary btn-lg" style="margin-right: 10px;">Launch PawConnect &rarr;</a>
            <a href="login.php" class="btn btn-accent btn-lg">Go to Login</a>
        </div>
    </div>
</body>
</html>
