-- DATABASE SCRIPT: tourism_portal
-- Online Tourism Portal - Complete Relational Schema & Seed Data
-- Target Server: MySQL 5.7+ / MariaDB 10.3+ / WAMP / XAMPP / phpMyAdmin
-- ===================================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS `tourism_portal`;
CREATE DATABASE `tourism_portal` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `tourism_portal`;

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `destinations`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `destinations`;
CREATE TABLE `destinations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'India',
  `description` TEXT NOT NULL,
  `attractions` TEXT NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `best_time_to_visit` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `packages`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `destination_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `duration` VARCHAR(50) NOT NULL,
  `itinerary` TEXT NOT NULL,
  `facilities` TEXT NOT NULL,
  `availability` TINYINT(1) DEFAULT 1,
  `image` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_destination` (`destination_id`),
  CONSTRAINT `fk_packages_destination` FOREIGN KEY (`destination_id`)
    REFERENCES `destinations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `bookings`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `bookings`;
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_reference` VARCHAR(50) NOT NULL UNIQUE,
  `user_id` INT NOT NULL,
  `package_id` INT NOT NULL,
  `number_of_people` INT NOT NULL DEFAULT 1,
  `travel_date` DATE NOT NULL,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `booking_status` ENUM('Pending', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
  `payment_id` VARCHAR(100) DEFAULT NULL,
  `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') DEFAULT 'Pending',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `invoice_number` VARCHAR(50) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user` (`user_id`),
  INDEX `idx_package` (`package_id`),
  INDEX `idx_status` (`booking_status`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_bookings_package` FOREIGN KEY (`package_id`)
    REFERENCES `packages` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `reviews`
-- --------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `destination_id` INT NOT NULL,
  `rating` INT NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_review_destination` (`destination_id`),
  CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_destination` FOREIGN KEY (`destination_id`)
    REFERENCES `destinations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `contacts` (Inquiries)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `contacts`;
CREATE TABLE `contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- SEED SAMPLE DATA
-- =========================================================

-- Passwords below are hashed using password_hash('Admin@123', PASSWORD_BCRYPT) and ('User@123', PASSWORD_BCRYPT)
-- Default Admin: admin@tourism.com / Admin@123
-- Default User: user@example.com / User@123
INSERT INTO `users` (`id`, `name`, `email`, `phone`, `password`, `role`) VALUES
(1, 'System Administrator', 'admin@tourism.com', '9876543210', '$2y$10$pL3YqK7022R/2lGv1nL2/e8fKz4m.h4R3E8D6x2uM2a5gR9m1v62m', 'admin'),
(2, 'Aditya Sharma', 'user@example.com', '9812345678', '$2y$10$iF6oWsm.Zc5X7/xkWGj.tO6H4t8Z6TfGk5B6f2gV8l1h5k9m3q2ea', 'user'),
(3, 'Sneha Patel', 'sneha.patel@example.com', '9898765432', '$2y$10$iF6oWsm.Zc5X7/xkWGj.tO6H4t8Z6TfGk5B6f2gV8l1h5k9m3q2ea', 'user'),
(4, 'Rohan Verma', 'rohan.verma@example.com', '9711223344', '$2y$10$iF6oWsm.Zc5X7/xkWGj.tO6H4t8Z6TfGk5B6f2gV8l1h5k9m3q2ea', 'user'),
(5, 'Mehul Agarwal', 'mehulagarwal11111@gmail.com', '916303831519', '$2y$10$iF6oWsm.Zc5X7/xkWGj.tO6H4t8Z6TfGk5B6f2gV8l1h5k9m3q2ea', 'user');

-- 10+ Tourist Destinations
INSERT INTO `destinations` (`id`, `name`, `state`, `country`, `description`, `attractions`, `image`, `best_time_to_visit`) VALUES
(1, 'Manali', 'Himachal Pradesh', 'India', 
 'Nestled in the Beas River valley, Manali is a high-altitude Himalayan resort town renowned for snow-clad peaks, pine forests, hot springs, and adventure sports.', 
 'Solang Valley, Rohtang Pass, Hadimba Temple, Jogini Waterfall, Old Manali Cafe Street', 
 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?auto=format&fit=crop&w=1200&q=80', 
 'October to June'),

(2, 'Goa', 'Goa', 'India', 
 'India premier coastal destination featuring golden sand shores, vibrant beach shacks, Portuguese colonial architecture, UNESCO cathedrals, and sunset cruises.', 
 'Calangute Beach, Baga Beach, Fort Aguada, Basilica of Bom Jesus, Dudhsagar Waterfalls', 
 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=1200&q=80', 
 'November to February'),

(3, 'Jaipur', 'Rajasthan', 'India', 
 'Known as the Pink City, Jaipur is a treasure trove of grand palaces, hilltop fortresses, bustling colorful bazaars, and rich Rajput culinary heritage.', 
 'Amber Palace, Hawa Mahal, City Palace, Jantar Mantar, Nahargarh Fort, Chokhi Dhani', 
 'https://images.unsplash.com/photo-1599661046289-e31897846e41?auto=format&fit=crop&w=1200&q=80', 
 'October to March'),

(4, 'Kashmir', 'Jammu & Kashmir', 'India', 
 'Often celebrated as Heaven on Earth, Kashmir dazzles with emerald lakes reflecting snow-capped peaks, shikara rides on Dal Lake, and blooming tulip gardens.', 
 'Dal Lake Shikara, Gulmarg Gondola, Pahalgam Betaab Valley, Sonamarg, Mughal Gardens', 
 'https://images.unsplash.com/photo-1595815771614-ade9d652a65d?auto=format&fit=crop&w=1200&q=80', 
 'March to October'),

(5, 'Rishikesh', 'Uttarakhand', 'India', 
 'The Yoga Capital of the World along the sacred Ganges river, offering thrilling white-water rafting, bungee jumping, and spiritually uplifting evening Ganga Aarti.', 
 'Triveni Ghat Evening Aarti, Lakshman Jhula, Shivpuri Rafting Camp, Beatles Ashram, Neer Garh Waterfall', 
 'https://images.unsplash.com/photo-1600100397608-f010f443b353?auto=format&fit=crop&w=1200&q=80', 
 'September to May'),

(6, 'Ooty', 'Tamil Nadu', 'India', 
 'The Queen of Hill Stations in the Nilgiri Hills, celebrated for tea plantations, botanical gardens, misty peaks, and the historic toy train ride.', 
 'Nilgiri Mountain Railway, Doddabetta Peak, Ooty Botanical Gardens, Pykara Lake & Falls, Emerald Lake', 
 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0?auto=format&fit=crop&w=1200&q=80', 
 'October to June'),

(7, 'Darjeeling', 'West Bengal', 'India', 
 'Sprawling over a steep mountain ridge surrounded by emerald-green tea plantations and dominated by the majestic Mt. Kanchenjunga peak.', 
 'Tiger Hill Sunrise, Batasia Loop & Toy Train, Happy Valley Tea Estate, Himalayan Mountaineering Institute', 
 'https://images.unsplash.com/photo-1544644181-1484b3fdfc62?auto=format&fit=crop&w=1200&q=80', 
 'March to May, September to November'),

(8, 'Varanasi', 'Uttar Pradesh', 'India', 
 'One of the world oldest living cities along the sacred Ganges River, celebrated for centuries-old ghats, spiritual chants, silk weaving, and vibrant culture.', 
 'Dashashwamedh Ghat Maha Aarti, Kashi Vishwanath Temple, Assi Ghat Sunrise Boat Tour, Sarnath', 
 'https://images.unsplash.com/photo-1561359313-0639aad49ca6?auto=format&fit=crop&w=1200&q=80', 
 'October to March'),

(9, 'Udaipur', 'Rajasthan', 'India', 
 'The City of Lakes, romantic Udaipur is adorned with marble palaces, calm shimmering waters, courtyards, luxury island hotels, and sunset boat cruises.', 
 'City Palace Complex, Lake Pichola & Jag Mandir, Saheliyon-ki-Bari, Monsoon Palace, Vintage Car Museum', 
 'https://images.unsplash.com/photo-1615836245337-f5b9b2303f10?auto=format&fit=crop&w=1200&q=80', 
 'September to March'),

(10, 'Munnar', 'Kerala', 'India', 
 'An idyllic hill station surrounded by rolling hills dotted with tea plantations established in the late 19th century, misty valleys, and exotic flora.', 
 'Eravikulam National Park, Mattupetty Dam, Tea Museum, Kundala Lake, Anamudi Peak Viewpoint', 
 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2?auto=format&fit=crop&w=1200&q=80', 
 'September to May');

-- 10+ Tour Packages
INSERT INTO `packages` (`id`, `destination_id`, `title`, `description`, `price`, `duration`, `itinerary`, `facilities`, `availability`, `image`) VALUES
(1, 1, 'Magical Manali Adventure & Snow Escape', 
 'Experience breathtaking Himalayan vistas, exhilarating adventure activities at Solang Valley, and relaxing strolls through Old Manali apple orchards.', 
 14999.00, '5 Days / 4 Nights', 
 'Day 1: Arrival in Manali & Hotel Check-in, Evening mall road exploration\nDay 2: Full day excursion to Solang Valley for paragliding and skiing\nDay 3: Visit Rohtang Pass snow point, Atal Tunnel & Sissu village\nDay 4: Hadimba Temple, Vashisht Hot Springs & Naggar Castle\nDay 5: Souvenir shopping in Old Manali & departure', 
 '3-Star Hotel Stay, Daily Breakfast & Dinner, AC Private Cab for Sightseeing, Solang Valley Permits, Professional Tour Guide', 
 1, 'https://images.unsplash.com/photo-1626621341517-bbf3d9990a23?auto=format&fit=crop&w=1000&q=80'),

(2, 2, 'Goa Sun, Sand & Coastal Cruise Delight', 
 'Soak up the sun on pristine beaches, enjoy watersports, explore colonial forts, and experience unforgettable evening sunset cruises on the Mandovi River.', 
 11499.00, '4 Days / 3 Nights', 
 'Day 1: Arrival at Goa Airport/Station, transfer to North Goa resort, evening beach walk\nDay 2: North Goa beaches tour - Calangute, Baga, Anjuna & Chapora Fort\nDay 3: South Goa heritage tour - Old Goa churches, Mangueshi temple, Miramar Beach & Mandovi sunset cruise\nDay 4: Morning watersports at Candolim & departure transfer', 
 'Beachfront Resort Stay, Complimentary Breakfast, Airport Transfers, Sunset Boat Cruise Ticket, Sightseeing Cab', 
 1, 'https://images.unsplash.com/photo-1512343879784-a960bf40e7f2?auto=format&fit=crop&w=1000&q=80'),

(3, 3, 'Royal Jaipur Heritage & Fort Odyssey', 
 'Step back into royal history with majestic forts, intricate palaces, vibrant colorful bazaars, and traditional Rajasthani dinner show.', 
 12800.00, '4 Days / 3 Nights', 
 'Day 1: Arrival in Jaipur, check-in, visit Birla Temple and Albert Hall Museum\nDay 2: Grand tour of Amber Fort, elephant ride, Jal Mahal, City Palace and Jantar Mantar\nDay 3: Hawa Mahal photo stop, Nahargarh Fort sunset view, evening cultural dinner at Chokhi Dhani\nDay 4: Bapu Bazaar shopping tour and departure transfer', 
 'Heritage Hotel Stay, Daily Breakfast, Chokhi Dhani Dinner Pass, AC Sedan Sightseeing Cab, Monument Entry Assistance', 
 1, 'https://images.unsplash.com/photo-1599661046289-e31897846e41?auto=format&fit=crop&w=1000&q=80'),

(4, 4, 'Heavenly Kashmir Valley & Gondola Odyssey', 
 'Cruise serenely on Dal Lake in a wooden shikara, ride the world second highest cable car in Gulmarg, and revel in Betaab Valley meadows.', 
 26999.00, '6 Days / 5 Nights', 
 'Day 1: Arrival in Srinagar, check-in to Luxury Houseboat on Dal Lake, sunset Shikara ride\nDay 2: Full day excursion to Gulmarg, ride Gulmarg Gondola Phase 1 & 2\nDay 3: Day trip to Pahalgam, visit Betaab Valley, Aru Valley and Chandanwari\nDay 4: Srinagar Mughal Gardens tour - Shalimar Bagh, Nishat Bagh, Chashme Shahi\nDay 5: Excursion to Sonamarg meadow of gold and Thajiwas Glacier\nDay 6: Morning floating market visit and departure', 
 'Deluxe Houseboat + 4-Star Hotels, Breakfast & Dinner, Shikara Ride on Dal Lake, Gulmarg Gondola Tickets, All Interstate Cab Permits', 
 1, 'https://images.unsplash.com/photo-1595815771614-ade9d652a65d?auto=format&fit=crop&w=1000&q=80'),

(5, 5, 'Spiritual Ganga & White Water Thrill Rishikesh', 
 'Balance adrenalized river rafting down the roaring Ganges with serene spiritual meditation, temple walks, and enchanting evening Ganga Aarti.', 
 7999.00, '3 Days / 2 Nights', 
 'Day 1: Arrival in Rishikesh, check-in to riverside luxury camp, evening Triveni Ghat Ganga Aarti\nDay 2: 16 KM White Water Rafting from Shivpuri to NIM Beach, cliff jumping, Beatles Ashram visit\nDay 3: Early morning yoga session, Lakshman Jhula walk & departure', 
 'Riverside Luxury Alpine Tent, All 3 Meals Included, 16km River Rafting with Safety Gear, Bonfire & Music Evening', 
 1, 'https://images.unsplash.com/photo-1600100397608-f010f443b353?auto=format&fit=crop&w=1000&q=80'),

(6, 6, 'Scenic Nilgiri & Ooty Tea Garden Retreat', 
 'Ride the UNESCO Heritage toy train through emerald green valleys, admire exotic flowers in botanical gardens, and gaze at panoramic views from Doddabetta.', 
 13500.00, '4 Days / 3 Nights', 
 'Day 1: Arrival in Coimbatore/Ooty, scenic drive through Nilgiris, hotel check-in\nDay 2: Ooty Lake boating, Government Botanical Garden, Rose Garden & Tea Factory\nDay 3: Nilgiri Mountain Railway ride to Coonoor, Sim Park, Dolphin Nose\nDay 4: Doddabetta Peak sunrise, Pykara Lake and departure transfer', 
 'Hill View Resort, Breakfast Included, Nilgiri Toy Train Booking, Private Cab for Coonoor & Ooty Sightseeing', 
 1, 'https://images.unsplash.com/photo-1589182373726-e4f658ab50f0?auto=format&fit=crop&w=1000&q=80'),

(7, 7, 'Enchanting Darjeeling Sunrise & Tea Estate Tour', 
 'Witness the golden sunrise illuminate Mt. Kanchenjunga from Tiger Hill, ride the heritage Himalayan toy train, and sip world-famous fresh Darjeeling tea.', 
 15500.00, '5 Days / 4 Nights', 
 'Day 1: Arrival at Bagdogra/NJP, scenic uphill transfer to Darjeeling\nDay 2: 4:00 AM Tiger Hill sunrise over Kanchenjunga, Ghoom Monastery & Batasia Loop\nDay 3: Tea garden guided tour, tea tasting session, Himalayan Mountaineering Institute & Zoo\nDay 4: Mirik Lake excursion, Indo-Nepal border viewpoint, boating\nDay 5: Mall Road souvenir shopping and departure transfer', 
 'Mountain View Premium Hotel, Daily Breakfast & Dinner, Toy Train Joyride, Private SUV Transfers, Tea Tasting Experience', 
 1, 'https://images.unsplash.com/photo-1544644181-1484b3fdfc62?auto=format&fit=crop&w=1000&q=80'),

(8, 8, 'Sacred Varanasi Ghats & Temple Pilgrimage', 
 'Experience profound spirituality with early morning boat cruises along the sacred Ganges ghats, private temple darshan, and world-renowned evening aarti.', 
 8499.00, '3 Days / 2 Nights', 
 'Day 1: Arrival in Varanasi, hotel check-in, evening VIP boat seating for Dashashwamedh Ghat Maha Aarti\nDay 2: Early sunrise boat tour from Assi Ghat to Manikarnika Ghat, Kashi Vishwanath temple darshan, excursion to Sarnath\nDay 3: Banarasi silk weaving artisan walk, local street food trail and departure', 
 'Ghat-Facing Hotel, Daily Breakfast, Private Sunrise & Sunset Boat Rides, Temple Darshan Guide, AC City Transfers', 
 1, 'https://images.unsplash.com/photo-1561359313-0639aad49ca6?auto=format&fit=crop&w=1000&q=80'),

(9, 9, 'Romantic Udaipur Lake Palace & Royal Haveli Escape', 
 'Sail past majestic island palaces on Lake Pichola, tour grand royal courtyards, and dine under candlelit stars overlooking illuminated water reflections.', 
 16200.00, '4 Days / 3 Nights', 
 'Day 1: Arrival in Udaipur, check-in, evening boat ride on Lake Pichola with Jag Mandir visit\nDay 2: Grand tour of City Palace, Crystal Gallery, Jagdish Temple & Saheliyon-ki-Bari\nDay 3: Excursion to Monsoon Palace (Sajjangarh) for sunset, Bagore ki Haveli cultural dance show\nDay 4: Vintage Car Collection visit and airport departure transfer', 
 'Lake View Heritage Stay, Breakfast & Candlelight Dinner, Lake Pichola Boat Cruise, Cultural Show Tickets, AC Cab', 
 1, 'https://images.unsplash.com/photo-1615836245337-f5b9b2303f10?auto=format&fit=crop&w=1000&q=80'),

(10, 10, 'Munnar Misty Tea Hills & Eravikulam Nature Trail', 
 'Breathe in pure mountain air amidst emerald tea plantations, track endangered Nilgiri Tahr at Eravikulam, and relax beside tranquil dams and waterfalls.', 
 13999.00, '4 Days / 3 Nights', 
 'Day 1: Scenic transfer from Kochi to Munnar, stopping at Cheeyappara and Valara waterfalls\nDay 2: Visit Eravikulam National Park (Rajamalai), Tea Museum & photo stop at Photo Point\nDay 3: Mattupetty Dam boating, Echo Point, Kundala Lake & Blossom International Park\nDay 4: Spice garden visit, shopping for fresh tea & spices, return to Kochi', 
 'Tea Plantation Resort Stay, Daily Breakfast, Eravikulam Safari Entry, Private AC Vehicle for all transfers, Spice Tour', 
 1, 'https://images.unsplash.com/photo-1593693397690-362cb9666fc2?auto=format&fit=crop&w=1000&q=80');

-- Sample Bookings
INSERT INTO `bookings` (`id`, `booking_reference`, `user_id`, `package_id`, `number_of_people`, `travel_date`, `total_amount`, `booking_status`, `payment_id`, `payment_status`, `payment_method`, `invoice_number`, `paid_at`, `created_at`) VALUES
(1, 'BK-2026-MNL74', 2, 1, 2, '2026-11-20', 29998.00, 'Confirmed', 'pay_live_mnl89341', 'Paid', 'UPI (Google Pay)', 'INV-2026-00101', '2026-11-01 10:20:00', '2026-11-01 10:15:00'),
(2, 'BK-2026-GOA89', 2, 2, 4, '2026-12-15', 45996.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, '2026-11-03 14:30:00'),
(3, 'BK-2026-KSH12', 3, 4, 2, '2026-11-28', 53998.00, 'Confirmed', 'pay_live_ksh90215', 'Paid', 'Credit Card (Visa)', 'INV-2026-00102', '2026-11-02 09:10:00', '2026-11-02 09:00:00'),
(4, 'BK-2026-RSH34', 4, 5, 3, '2026-12-05', 23997.00, 'Pending', NULL, 'Pending', NULL, NULL, NULL, '2026-11-04 16:45:00');

-- Sample Customer Reviews
INSERT INTO `reviews` (`id`, `user_id`, `destination_id`, `rating`, `comment`, `created_at`) VALUES
(1, 2, 1, 5, 'Manali exceeded all our expectations! The Solang Valley adventure and snow at Atal Tunnel were simply mesmerizing. The portal made booking seamless and hassle-free.', '2026-10-25 11:20:00'),
(2, 3, 2, 5, 'Goa sunset cruise and North Goa beaches tour were unforgettable. Hotel accommodations provided were top notch. Highly recommend this tourism portal!', '2026-10-28 15:10:00'),
(3, 4, 4, 5, 'Kashmir is truly paradise! Dal Lake Houseboat stay and Gulmarg Gondola ride were experiences of a lifetime. The tour itinerary was very well organized.', '2026-11-02 18:30:00'),
(4, 2, 3, 4, 'Amber Fort and Hawa Mahal in Jaipur are marvelous architectural wonders. Authentic Rajasthani food at Chokhi Dhani was delicious!', '2026-10-18 14:00:00'),
(5, 3, 5, 5, 'The 16 km white water rafting in Rishikesh was thrilling, and the evening Ganga Aarti brought tears of peace to my eyes. 10/10 service!', '2026-10-30 09:45:00');

SET FOREIGN_KEY_CHECKS = 1;
