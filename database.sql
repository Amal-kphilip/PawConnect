-- ============================================================================
-- PawConnect - Pet Adoption Portal Database Dump
-- Database Name: pet_adoption
-- Target: MySQL / MariaDB (XAMPP / WAMP)
-- All dummy user accounts have password: password123
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `pet_adoption` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pet_adoption`;

SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------------------------
-- Table structure for table `users`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `role` ENUM('adopter', 'shelter', 'admin') NOT NULL DEFAULT 'adopter',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table structure for table `shelters`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `shelters`;
CREATE TABLE `shelters` (
    `shelter_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `shelter_name` VARCHAR(150) NOT NULL,
    `address` VARCHAR(255) NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `description` TEXT,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table structure for table `pets`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `pets`;
CREATE TABLE `pets` (
    `pet_id` INT AUTO_INCREMENT PRIMARY KEY,
    `shelter_id` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `species` ENUM('Dog', 'Cat', 'Rabbit', 'Bird', 'Other') NOT NULL DEFAULT 'Dog',
    `breed` VARCHAR(100) NOT NULL,
    `age_years` DECIMAL(3, 1) NOT NULL,
    `gender` ENUM('Male', 'Female') NOT NULL,
    `size` ENUM('Small', 'Medium', 'Large') NOT NULL DEFAULT 'Medium',
    `vaccinated` TINYINT(1) NOT NULL DEFAULT 1,
    `description` TEXT NOT NULL,
    `image` VARCHAR(255) NULL,
    `status` ENUM('Available', 'Pending', 'Adopted') NOT NULL DEFAULT 'Available',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`shelter_id`) REFERENCES `shelters`(`shelter_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table structure for table `applications`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `applications`;
CREATE TABLE `applications` (
    `app_id` INT AUTO_INCREMENT PRIMARY KEY,
    `pet_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `reason` TEXT NOT NULL,
    `home_type` ENUM('Apartment', 'House', 'Farm') NOT NULL DEFAULT 'Apartment',
    `has_other_pets` TINYINT(1) NOT NULL DEFAULT 0,
    `experience` TEXT NOT NULL,
    `status` ENUM('Pending', 'Approved', 'Rejected') NOT NULL DEFAULT 'Pending',
    `applied_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Table structure for table `visits`
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `visits`;
CREATE TABLE `visits` (
    `visit_id` INT AUTO_INCREMENT PRIMARY KEY,
    `pet_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `visit_date` DATE NOT NULL,
    `visit_time` TIME NOT NULL,
    `status` ENUM('Scheduled', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DUMMY DATA INSERTS
-- Default Password for all accounts: password123
-- Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- ============================================================================

-- 1. Users (1 Admin, 6 Shelters, 10 Adopters)
INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `role`) VALUES
(1, 'Admin Officer', 'admin@pawconnect.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98000 00001', 'admin'),
(2, 'Suresh Kumar', 'shelter1@happytails.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98470 12345', 'shelter'),
(3, 'Priya Nambiar', 'shelter2@safepaws.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 94471 23456', 'shelter'),
(4, 'Vinod Raj', 'shelter3@secondchance.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98950 34567', 'shelter'),
(5, 'Dr. Lakshmi Varma', 'shelter4@karuna.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98460 78901', 'shelter'),
(6, 'Mathew George', 'shelter5@pawscare.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 94472 89012', 'shelter'),
(7, 'Deepa Radhakrishnan', 'shelter6@greenvalley.org', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98951 90123', 'shelter'),
(8, 'Anjali Nair', 'anjali@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98460 11223', 'adopter'),
(9, 'Rahul Menon', 'rahul@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 94470 22334', 'adopter'),
(10, 'Fathima Rasheed', 'fathima@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 97450 33445', 'adopter'),
(11, 'Arjun Das', 'arjun@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 99950 44556', 'adopter'),
(12, 'Meera Krishnan', 'meera@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98475 55667', 'adopter'),
(13, 'Joseph Thomas', 'joseph@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 94465 66778', 'adopter'),
(14, 'Kavya Pillai', 'kavya@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 98472 77889', 'adopter'),
(15, 'Nikhil Varma', 'nikhil@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 94478 88990', 'adopter'),
(16, 'Sneha Paul', 'sneha@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 97455 99001', 'adopter'),
(17, 'Rohan Kulkarni', 'rohan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+91 99958 12340', 'adopter');

-- 2. Shelters
INSERT INTO `shelters` (`shelter_id`, `user_id`, `shelter_name`, `address`, `city`, `phone`, `description`) VALUES
(1, 2, 'Happy Tails Shelter', 'Panampilly Nagar, Near Central Park', 'Kochi', '+91 98470 12345', 'Happy Tails rescues, rehabilitates, and finds loving homes for abandoned and injured companion animals in Ernakulam and Kochi.'),
(2, 3, 'Safe Paws Rescue', 'Civil Station Road, Near Police Ground', 'Kannur', '+91 94471 23456', 'A volunteer-driven non-profit animal rescue providing medical treatment and compassionate adoption services in Kannur.'),
(3, 4, 'Second Chance Animal Home', 'Beach Road, Behind Old Lighthouse', 'Kozhikode', '+91 98950 34567', 'Dedicated to giving vulnerable dogs and cats a second chance at happiness through community foster and adoption programs.'),
(4, 5, 'Karuna Animal Sanctuary', 'Vellayambalam, Near Museum Junction', 'Thiruvananthapuram', '+91 98460 78901', 'State capital rescue facility offering 24/7 emergency medical care, rehabilitation, and ethical adoption screening.'),
(5, 6, 'Paws & Care Foundation', 'Round West, Near Swaraj Round', 'Thrissur', '+91 94472 89012', 'Community rescue shelter providing lifetime sterilization, vaccination drives, and loving homes for local rescue animals.'),
(6, 7, 'Green Valley Animal Rescue', 'Kanjikuzhy, Near Collectorate', 'Kottayam', '+91 98951 90123', 'Passionate team of veterinarians and animal lovers rehoming orphaned puppies, kittens, rabbits, and rescued native birds.');

-- 3. Pets (36 Diverse Pets across all 5 Species, 3 Sizes, 4 Age groups, 6 Cities, Both Genders)
INSERT INTO `pets` (`pet_id`, `shelter_id`, `name`, `species`, `breed`, `age_years`, `gender`, `size`, `vaccinated`, `description`, `image`, `status`) VALUES
-- Dogs
(1, 1, 'Bruno', 'Dog', 'Golden Retriever', 2.5, 'Male', 'Large', 1, 'Bruno is an energetic, affectionate Golden Retriever who loves morning walks, fetch, and belly rubs. Great with children and other friendly dogs.', 'https://images.unsplash.com/photo-1552053831-71594a27632d?auto=format&fit=crop&w=600&q=80', 'Available'),
(2, 1, 'Luna', 'Dog', 'Indian Pariah Puppy', 0.6, 'Female', 'Medium', 1, 'Luna is a bright, playful native Indian puppy. Super smart, highly trainable, alert, and eager to bond with a warm family.', 'https://images.unsplash.com/photo-1543466835-00a7907e9de1?auto=format&fit=crop&w=600&q=80', 'Available'),
(3, 2, 'Coco', 'Dog', 'Beagle', 2.0, 'Male', 'Small', 1, 'Coco is a curious Beagle with big warm eyes and a loving heart. He enjoys scent tracking games and cuddle sessions on the sofa.', 'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?auto=format&fit=crop&w=600&q=80', 'Available'),
(4, 2, 'Max', 'Dog', 'Labrador Retriever', 4.5, 'Male', 'Large', 1, 'Max is a gentle giant who loves swimming and calm evening strolls. Very well-behaved, leash trained, and polite around visitors.', 'https://images.unsplash.com/photo-1587300003388-59208cc962cb?auto=format&fit=crop&w=600&q=80', 'Available'),
(5, 3, 'Bella', 'Dog', 'Cocker Spaniel', 1.8, 'Female', 'Medium', 1, 'Bella has a gorgeous silky coat and a joyful, sunny personality. She loves playing in grassy yards and greeting family members.', 'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?auto=format&fit=crop&w=600&q=80', 'Available'),
(6, 3, 'Rocky', 'Dog', 'German Shepherd Mix', 5.0, 'Male', 'Large', 1, 'Rocky is a loyal, protective, and intelligent companion. Knows basic obedience commands and thrives with an active owner or yard.', 'https://images.unsplash.com/photo-1589941013453-ec89f33b5455?auto=format&fit=crop&w=600&q=80', 'Available'),
(7, 1, 'Daisy', 'Dog', 'Pug', 2.0, 'Female', 'Small', 1, 'Daisy is a charming lap dog who loves quiet apartments, squeaky toys, and snuggling under blankets. Very gentle and affectionate.', 'https://images.unsplash.com/photo-1517423440428-a5a00ad493e8?auto=format&fit=crop&w=600&q=80', 'Pending'),
(8, 2, 'Toby', 'Dog', 'Dachshund', 5.0, 'Male', 'Small', 1, 'Toby is a spirited little guy with lots of character. He has found his dream family and is happily settled in his new home!', 'https://images.unsplash.com/photo-1612195583950-b8fd34c87093?auto=format&fit=crop&w=600&q=80', 'Adopted'),
(9, 4, 'Buster', 'Dog', 'Boxer Mix', 8.5, 'Male', 'Large', 1, 'Buster is a gentle senior dog looking for a peaceful retirement home. Very calm, house-trained, and enjoys leisurely yard naps.', 'https://images.unsplash.com/photo-1535930891776-0c2dfb7fda1a?auto=format&fit=crop&w=600&q=80', 'Available'),
(10, 5, 'Ruby', 'Dog', 'Pomeranian Mix', 0.5, 'Female', 'Small', 1, 'Ruby is an adorable fluffy puppy with endless charm and curiosity. Great for indoor apartment living with plenty of cuddles.', 'https://images.unsplash.com/photo-1583337130417-3346a1be7dee?auto=format&fit=crop&w=600&q=80', 'Available'),
(11, 6, 'Sheru', 'Dog', 'Indian Mastiff Indie', 9.0, 'Male', 'Large', 1, 'Sheru is a noble senior guardian who loves quiet company and gentle head pats. Low energy and deeply loyal.', 'https://images.unsplash.com/photo-1561037404-61cd46aa615b?auto=format&fit=crop&w=600&q=80', 'Available'),
(12, 4, 'Stella', 'Dog', 'Border Collie Mix', 3.5, 'Female', 'Medium', 1, 'Stella is highly attentive, agile, and eager to learn tricks. Perfect for an active family who loves jogging and agility games.', 'https://images.unsplash.com/photo-1503256207526-0d5d80fa2f47?auto=format&fit=crop&w=600&q=80', 'Available'),
(13, 5, 'Leo', 'Dog', 'Shih Tzu', 8.0, 'Male', 'Small', 1, 'Leo is a quiet, friendly senior Shih Tzu who gets along great with cats and older adults. Fully vaccinated and groomed.', 'https://images.unsplash.com/photo-1591769225440-811ad7d6eab2?auto=format&fit=crop&w=600&q=80', 'Available'),
(14, 6, 'Tara', 'Dog', 'Labrador Indie Mix', 0.8, 'Female', 'Medium', 0, 'Tara was rescued near a tea estate. She is cheerful, healthy, vaccinated for first rounds, and loves playing with rubber balls.', 'https://images.unsplash.com/photo-1560807707-8cc77767d783?auto=format&fit=crop&w=600&q=80', 'Available'),

-- Cats
(15, 1, 'Simba', 'Cat', 'Ginger Tabby', 1.5, 'Male', 'Medium', 1, 'Simba is a playful ginger boy who purrs instantly when stroked. Loves feather wands, sunny windowsills, and climbing cat trees.', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?auto=format&fit=crop&w=600&q=80', 'Available'),
(16, 1, 'Milo', 'Cat', 'Siamese Mix', 2.0, 'Male', 'Medium', 1, 'Milo has striking sapphire blue eyes and a vocal, affectionate nature. He loves following people around and snoozing nearby.', 'https://images.unsplash.com/photo-1513360309081-38f0762daed1?auto=format&fit=crop&w=600&q=80', 'Available'),
(17, 2, 'Oreo', 'Cat', 'Tuxedo Domestic', 1.2, 'Female', 'Small', 1, 'Oreo wears a dapper black-and-white tuxedo coat. Sweet, polite, litter-trained, and well-mannered in apartments.', 'https://images.unsplash.com/photo-1533738363-b7f9aef128ce?auto=format&fit=crop&w=600&q=80', 'Available'),
(18, 2, 'Shadow', 'Cat', 'Persian', 3.0, 'Male', 'Medium', 1, 'Shadow is an elegant, fluffy Persian cat who loves being brushed. He is very tranquil and prefers a calm indoor environment.', 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?auto=format&fit=crop&w=600&q=80', 'Pending'),
(19, 3, 'Hazel', 'Cat', 'Calico Kitten', 0.6, 'Female', 'Small', 1, 'Hazel is a sweet tricolor kitten full of energy and fun. Loves chasing cardboard balls and cuddling on warm laps.', 'https://images.unsplash.com/photo-1561948955-570b270e7c36?auto=format&fit=crop&w=600&q=80', 'Available'),
(20, 3, 'Cookie', 'Cat', 'British Shorthair Mix', 2.5, 'Female', 'Medium', 1, 'Cookie has found her forever home and is enjoying peaceful days with her new family in Kozhikode.', 'https://images.unsplash.com/photo-1543852786-1cf6624b9987?auto=format&fit=crop&w=600&q=80', 'Adopted'),
(21, 4, 'Oliver', 'Cat', 'Domestic Grey Tabby', 8.0, 'Male', 'Medium', 1, 'Oliver is a wise, gentle senior feline who spends his afternoons sleeping in sunbeams. Litter trained and very docile.', 'https://images.unsplash.com/photo-1518791841217-8f162f1e1131?auto=format&fit=crop&w=600&q=80', 'Available'),
(22, 5, 'Cleo', 'Cat', 'Snow White Domestic', 0.4, 'Female', 'Small', 1, 'Cleo is a pristine white kitten with boundless energy and a soft motor-like purr. Looking for an indoor home.', 'https://images.unsplash.com/photo-1574158622682-e40e69881006?auto=format&fit=crop&w=600&q=80', 'Available'),
(23, 6, 'Jasper', 'Cat', 'Maine Coon Mix', 4.0, 'Male', 'Medium', 1, 'Jasper is a majestic, long-haired cat with a bushy tail. Friendly with respectful dogs and very affectionate.', 'https://images.unsplash.com/photo-1535930891776-0c2dfb7fda1a?auto=format&fit=crop&w=600&q=80', 'Available'),
(24, 5, 'Maya', 'Cat', 'Tortoiseshell', 7.5, 'Female', 'Small', 1, 'Maya has a stunning tortoiseshell coat and a serene personality. Loves quiet companionship and gentle brushing.', 'https://images.unsplash.com/photo-1548802673-380ab8ebc7b7?auto=format&fit=crop&w=600&q=80', 'Available'),

-- Rabbits
(25, 1, 'Thumper', 'Rabbit', 'Holland Lop', 1.2, 'Male', 'Small', 1, 'Thumper is a dwarf lop-eared rabbit with soft grey fur. Loves munching fresh timothy hay and forehead strokes.', 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?auto=format&fit=crop&w=600&q=80', 'Available'),
(26, 3, 'Snowball', 'Rabbit', 'Netherland Dwarf', 1.5, 'Female', 'Small', 1, 'Snowball is a pristine white dwarf bunny. Gentle, litter-box trained, and thrives in a safe indoor enclosure.', 'https://images.unsplash.com/photo-1535241749838-299277b6305f?auto=format&fit=crop&w=600&q=80', 'Available'),
(27, 4, 'Clover', 'Rabbit', 'Mini Rex Baby', 0.5, 'Female', 'Small', 1, 'Clover has velvety plush fur and an inquisitive nature. Hand-raised and enjoys fresh coriander and leafy greens.', 'https://images.unsplash.com/photo-1591382364584-a0777b8c3934?auto=format&fit=crop&w=600&q=80', 'Available'),
(28, 5, 'Bunbun', 'Rabbit', 'Lionhead', 3.2, 'Male', 'Small', 1, 'Bunbun has a distinctive fluffy lion mane and a calm temperament. Used to quiet indoor living spaces.', 'https://images.unsplash.com/photo-1518796745738-41048802f99a?auto=format&fit=crop&w=600&q=80', 'Available'),

-- Birds
(29, 2, 'Kiwi', 'Bird', 'Budgerigar Parakeet', 1.0, 'Male', 'Small', 0, 'Kiwi is a cheerful green budgie who loves chirping along to light acoustic music and learning whistle tunes.', 'https://images.unsplash.com/photo-1552728089-57bdde30beb3?auto=format&fit=crop&w=600&q=80', 'Available'),
(30, 4, 'Sunny', 'Bird', 'Cockatiel', 3.5, 'Female', 'Small', 0, 'Sunny is a lovely yellow-crested Cockatiel. She is hand-tame, steps onto fingers easily, and loves apple slices.', 'https://images.unsplash.com/photo-1522858547137-f1dcec554f55?auto=format&fit=crop&w=600&q=80', 'Available'),
(31, 1, 'Mango', 'Bird', 'Sun Conure', 4.0, 'Male', 'Medium', 1, 'Mango is a brightly colored sun conure with vibrant orange and green plumage. Highly social and seeks human interaction.', 'https://images.unsplash.com/photo-1452570053594-1b985d6ea890?auto=format&fit=crop&w=600&q=80', 'Available'),
(32, 6, 'Pippin', 'Bird', 'Lovebird', 1.5, 'Female', 'Small', 0, 'Pippin is an adorable peach-faced lovebird with an active, cheerful personality. Very playful with wooden bird toys.', 'https://images.unsplash.com/photo-1549608276-5786777e6587?auto=format&fit=crop&w=600&q=80', 'Available'),

-- Other Pets
(33, 3, 'Rio', 'Other', 'Guinea Pig', 1.2, 'Male', 'Small', 1, 'Rio is a friendly tricolor Guinea Pig who wheeks happily whenever bell peppers are chopped. Gentle and easy to hold.', 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=600&q=80', 'Available'),
(34, 5, 'Peanut', 'Other', 'Syrian Hamster', 0.4, 'Female', 'Small', 1, 'Peanut is a cute golden Syrian hamster. Nocturnal, loves running on silent exercise wheels and snacking on sunflower seeds.', 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=600&q=80', 'Available'),
(35, 6, 'Sheldon', 'Other', 'Indian Star Turtle (Rescued)', 10.0, 'Male', 'Small', 1, 'Sheldon was rescued from illegal trade and rehabilitated. Needs a secure sunny garden enclosure with fresh green vegetation.', 'https://images.unsplash.com/photo-1437622368342-7a3d73a34c8f?auto=format&fit=crop&w=600&q=80', 'Available'),
(36, 4, 'Cinnamon', 'Other', 'Abyssinian Guinea Pig', 1.8, 'Female', 'Small', 1, 'Cinnamon has wonderful rosettes in her warm brown fur. Very tame, enjoys being brushed, and loves spinach leaves.', 'https://images.unsplash.com/photo-1548767797-d8c844163c4c?auto=format&fit=crop&w=600&q=80', 'Available');

-- 4. Applications (14 Records)
INSERT INTO `applications` (`app_id`, `pet_id`, `user_id`, `reason`, `home_type`, `has_other_pets`, `experience`, `status`, `applied_at`) VALUES
(1, 1, 8, 'We have a spacious house with a secure fenced garden in Kochi and love outdoor walks. Bruno would be our beloved family companion.', 'House', 0, 'Raised Golden Retrievers for over 10 years; experienced with routine grooming and veterinary visits.', 'Pending', NOW() - INTERVAL 2 DAY),
(2, 2, 9, 'Looking to welcome an energetic native Indian rescue puppy into our home.', 'House', 0, 'Cared for our indie rescue dog for 13 years.', 'Pending', NOW() - INTERVAL 1 DAY),
(3, 15, 10, 'I work from home and would love to have an affectionate indoor feline like Simba.', 'Apartment', 0, 'First-time cat adopter, researched feline nutrition and enrichment thoroughly.', 'Pending', NOW() - INTERVAL 3 DAY),
(4, 3, 11, 'We adore hounds and have plenty of time for daily scent exploration walks.', 'House', 1, 'Currently have a 4-year-old friendly rescue indie dog.', 'Pending', NOW() - INTERVAL 4 DAY),
(5, 8, 12, 'Toby will receive continuous care and lap cuddles at our quiet family home.', 'House', 0, 'Extensive experience caring for senior and small-breed dogs.', 'Approved', NOW() - INTERVAL 10 DAY),
(6, 20, 13, 'Looking for a calm, friendly cat to share my quiet apartment with.', 'Apartment', 0, 'Volunteered at local animal rescues during college.', 'Approved', NOW() - INTERVAL 12 DAY),
(7, 7, 9, 'Daisy is such a sweet dog. We want to give her all the cozy care and love she deserves.', 'Apartment', 0, 'Experienced in caring for brachycephalic small breeds.', 'Pending', NOW() - INTERVAL 1 DAY),
(8, 4, 10, 'Applying for Max, but currently live in a small studio with strict landlord size rules.', 'Apartment', 0, 'None.', 'Rejected', NOW() - INTERVAL 6 DAY),
(9, 18, 8, 'Shadow would be very happy in our calm, air-conditioned home in Kochi.', 'Apartment', 0, 'Experienced in daily brushing and grooming long-haired Persian cats.', 'Pending', NOW() - INTERVAL 2 DAY),
(10, 25, 11, 'Our family has set up a spacious rabbit enclosure and is excited to welcome Thumper.', 'House', 0, 'Researched rabbit dietary hay requirements and rabbit-safe homes.', 'Pending', NOW() - INTERVAL 3 DAY),
(11, 9, 14, 'Looking for a gentle senior dog to enjoy quiet garden afternoons together.', 'House', 0, 'Experienced with senior dog joint health and care.', 'Pending', NOW() - INTERVAL 2 DAY),
(12, 10, 15, 'We have a pet-friendly apartment in Thrissur and work flexible hours.', 'Apartment', 0, 'Grew up with family Pomeranians.', 'Pending', NOW() - INTERVAL 1 DAY),
(13, 30, 16, 'Have a large indoor aviary and love social hand-tame cockatiels.', 'House', 0, 'Kept cockatiels and budgies for over 8 years.', 'Pending', NOW() - INTERVAL 2 DAY),
(14, 33, 17, 'Have a large multi-tier guinea pig enclosure with plenty of fresh vegetables.', 'Apartment', 0, 'Knowledgeable on guinea pig vitamin C and socialization needs.', 'Pending', NOW() - INTERVAL 1 DAY);

-- 5. Visits (14 Records)
INSERT INTO `visits` (`visit_id`, `pet_id`, `user_id`, `visit_date`, `visit_time`, `status`, `created_at`) VALUES
(1, 1, 8, CURDATE() + INTERVAL 2 DAY, '10:00:00', 'Scheduled', NOW() - INTERVAL 1 DAY),
(2, 2, 9, CURDATE() + INTERVAL 2 DAY, '14:00:00', 'Scheduled', NOW() - INTERVAL 1 DAY),
(3, 15, 10, CURDATE() + INTERVAL 4 DAY, '11:00:00', 'Scheduled', NOW() - INTERVAL 2 DAY),
(4, 3, 11, CURDATE() + INTERVAL 4 DAY, '15:00:00', 'Scheduled', NOW() - INTERVAL 3 DAY),
(5, 25, 11, CURDATE() + INTERVAL 2 DAY, '16:00:00', 'Scheduled', NOW() - INTERVAL 1 DAY),
(6, 9, 14, CURDATE() + INTERVAL 5 DAY, '10:00:00', 'Scheduled', NOW() - INTERVAL 1 DAY),
(7, 10, 15, CURDATE() + INTERVAL 5 DAY, '14:00:00', 'Scheduled', NOW() - INTERVAL 1 DAY),
(8, 30, 16, CURDATE() + INTERVAL 4 DAY, '12:00:00', 'Scheduled', NOW() - INTERVAL 2 DAY),
(9, 8, 12, CURDATE() - INTERVAL 4 DAY, '10:00:00', 'Completed', NOW() - INTERVAL 10 DAY),
(10, 20, 13, CURDATE() - INTERVAL 10 DAY, '14:00:00', 'Completed', NOW() - INTERVAL 15 DAY),
(11, 5, 12, CURDATE() - INTERVAL 4 DAY, '11:00:00', 'Completed', NOW() - INTERVAL 8 DAY),
(12, 12, 14, CURDATE() - INTERVAL 10 DAY, '15:00:00', 'Completed', NOW() - INTERVAL 12 DAY),
(13, 4, 10, CURDATE() - INTERVAL 10 DAY, '15:00:00', 'Cancelled', NOW() - INTERVAL 14 DAY),
(14, 6, 13, CURDATE() - INTERVAL 4 DAY, '12:00:00', 'Cancelled', NOW() - INTERVAL 7 DAY);
