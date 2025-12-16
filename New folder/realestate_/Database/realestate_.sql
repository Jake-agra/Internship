-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 15, 2025 at 11:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `realestate_`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bookmarks`
--

CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`id`, `name`, `phone`, `subject`, `email`, `message`, `created_at`) VALUES
(1, 'COMPANY_PHONE', '0598147331', '', 'agrajeff15@gmail.com', 'TEST RUN', '2025-08-19 06:36:36'),
(2, 'JEFFREY EYRAM KWEKU AGRA', '0598147331', '', 'agrajeff15@gmail.com', 'TEST RUN', '2025-08-19 06:58:30'),
(3, 'N.K', '0598147331', '', 'destinynana566@gmail.com', 'TEST RUN', '2025-08-19 07:00:11'),
(4, 'COMPANY_PHONE', '', '', 'agrajeff15@gmail.com', '23456', '2025-08-19 07:29:26'),
(5, 'COMPANY_PHONE', '', '', 'agrajeff15@gmail.com', '1234455', '2025-08-19 07:30:02'),
(6, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', 'TEST RUN', '2025-08-19 07:32:31'),
(7, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', 'test run', '2025-08-19 07:44:31'),
(8, 'N.K', '0598147331', '', 'agrajeff15@gmail.com', 'TEST RUN 1', '2025-08-19 07:50:12'),
(9, 'N.K', '0555382164', '', 'destinynana566@gmail.com', 'TEST RUN 1', '2025-08-19 07:53:25'),
(10, 'N.K', '', '', 'destinynana566@gmail.com', 'Test Run_2', '2025-08-19 07:53:41'),
(11, 'N.K', '', '', 'destinynana566@gmail.com', 'Test Run_2', '2025-08-19 07:53:55'),
(12, 'JEFFREY AGRA', '', '', '0322080283@htu.edu.gh', 'TEST RUN 03', '2025-08-19 07:55:30'),
(13, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', '1234455', '2025-08-20 09:33:54'),
(14, 'N.K', '0555382164', '', 'destinynana566@gmail.com', 'TEST RUN 1', '2025-08-20 09:34:45'),
(15, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', '1234455', '2025-08-20 09:53:07'),
(16, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', '1234455', '2025-08-20 09:53:47'),
(17, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', '1234455', '2025-08-20 09:54:48'),
(18, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', '1234455', '2025-08-20 09:55:13'),
(19, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', '1234455', '2025-08-20 09:56:28'),
(20, 'JEFFREY AGRA', '', '', '0322080283@htu.edu.gh', 'jeff', '2025-08-20 10:05:46'),
(21, 'JEFFREY AGRA', '', '', '0322080283@htu.edu.gh', 'test run', '2025-08-20 11:02:44'),
(22, 'JEFFREY AGRA', '', '', '0322080283@htu.edu.gh', 'okay', '2025-08-20 11:08:43'),
(23, 'JEFFREY AGRA', '', '', '0322080283@htu.edu.gh', 'kk', '2025-08-20 11:09:50'),
(24, 'JEFFREY AGRA', '', '', '0322080283@htu.edu.gh', 'ollkjhg', '2025-08-20 11:11:04'),
(25, 'JEFFREY EYRAM KWEKU AGRA', '', '', 'agrajeff15@gmail.com', 'zdfghj', '2025-08-20 11:11:59');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','responded') DEFAULT 'new',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inquiries`
--

CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `client_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `contact_email` varchar(150) DEFAULT NULL,
  `status` enum('pending','responded','closed') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inquiries`
--

INSERT INTO `inquiries` (`id`, `property_id`, `client_id`, `message`, `contact_phone`, `contact_email`, `status`, `response`, `responded_at`, `created_at`) VALUES
(1, 2, 5, 'sdfgh', '0598147331', 'agrajeff15@gmail.com', 'pending', NULL, NULL, '2025-09-20 21:27:03');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `city` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `postal_code` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `city`, `region`, `country`, `postal_code`, `created_at`) VALUES
(1, 'New York', 'New York', 'USA', '10001', '2025-07-31 02:05:05'),
(2, 'Los Angeles', 'California', 'USA', '90001', '2025-07-31 02:05:05'),
(3, 'Chicago', 'Illinois', 'USA', '60601', '2025-07-31 02:05:05'),
(4, 'Houston', 'Texas', 'USA', '77001', '2025-07-31 02:05:05'),
(5, 'Miami', 'Florida', 'USA', '33101', '2025-07-31 02:05:05');

-- --------------------------------------------------------

--
-- Table structure for table `prices`
--

CREATE TABLE `prices` (
  `id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `price_type` enum('sale','rent_monthly','rent_weekly','rent_daily') DEFAULT 'sale',
  `is_negotiable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prices`
--

INSERT INTO `prices` (`id`, `amount`, `currency`, `price_type`, `is_negotiable`, `created_at`, `updated_at`) VALUES
(1, 250000.00, 'USD', 'sale', 1, '2025-07-31 02:05:05', '2025-07-31 02:05:05'),
(2, 450000.00, 'USD', 'sale', 1, '2025-07-31 02:05:05', '2025-07-31 02:05:05'),
(3, 750000.00, 'USD', 'sale', 0, '2025-07-31 02:05:05', '2025-07-31 02:05:05'),
(4, 2500.00, 'USD', 'rent_monthly', 1, '2025-07-31 02:05:05', '2025-07-31 02:05:05'),
(5, 3500.00, 'USD', 'rent_monthly', 1, '2025-07-31 02:05:05', '2025-07-31 02:05:05');

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` int(11) NOT NULL,
  `propertiesname` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `price_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_type_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `status` enum('available','sold','rented','pending','inactive') DEFAULT 'available',
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `area_sqft` int(11) DEFAULT NULL,
  `year_built` year(4) DEFAULT NULL,
  `parking_spaces` int(11) DEFAULT 0,
  `images` text DEFAULT NULL,
  `features` text DEFAULT NULL,
  `address_details` text DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `propertiesname`, `description`, `price_id`, `user_id`, `property_type_id`, `location_id`, `status`, `bedrooms`, `bathrooms`, `area_sqft`, `year_built`, `parking_spaces`, `images`, `features`, `address_details`, `is_featured`, `views_count`, `created_at`, `updated_at`) VALUES
(1, 'Beautiful Family House', 'Spacious 3-bedroom house with garden', 1, 2, 1, 1, 'available', 3, 2, 1800, NULL, 0, NULL, NULL, NULL, 1, 0, '2025-07-31 02:05:05', '2025-08-12 15:35:46'),
(2, 'Modern Downtown Apartment', 'Luxury apartment in city center', 2, 2, 1, 2, 'available', 2, 2, 1200, NULL, 0, NULL, NULL, NULL, 1, 3, '2025-07-31 02:05:05', '2025-09-20 21:27:45'),
(3, 'Prime Land Plot', 'Perfect for development', 3, 2, 2, 5, 'available', 0, 0, 5000, NULL, 0, NULL, NULL, NULL, 0, 0, '2025-07-31 02:05:05', '2025-07-31 02:05:05');

-- --------------------------------------------------------

--
-- Stand-in structure for view `property_details`
-- (See below for the actual view)
--
CREATE TABLE `property_details` (
`id` int(11)
,`propertiesname` varchar(200)
,`description` text
,`price` decimal(15,2)
,`currency` varchar(3)
,`price_type` enum('sale','rent_monthly','rent_weekly','rent_daily')
,`property_type` varchar(50)
,`city` varchar(100)
,`region` varchar(100)
,`country` varchar(100)
,`agent_first_name` varchar(100)
,`agent_last_name` varchar(100)
,`agent_email` varchar(150)
,`agent_phone` varchar(20)
,`bedrooms` int(11)
,`bathrooms` int(11)
,`area_sqft` int(11)
,`status` enum('available','sold','rented','pending','inactive')
,`is_featured` tinyint(1)
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `property_images`
--

CREATE TABLE `property_images` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `image_type` enum('main','interior','exterior','floorplan') DEFAULT 'interior',
  `title` varchar(200) DEFAULT NULL,
  `alt_text` varchar(200) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_types`
--

CREATE TABLE `property_types` (
  `id` int(11) NOT NULL,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `property_types`
--

INSERT INTO `property_types` (`id`, `type_name`, `description`, `created_at`) VALUES
(1, 'house', 'Single family houses and villas', '2025-07-31 02:05:05'),
(2, 'land', 'Empty land plots and lots', '2025-07-31 02:05:05'),
(3, 'car', 'Vehicles and automobiles', '2025-07-31 02:05:05');

-- --------------------------------------------------------

--
-- Table structure for table `property_views`
--

CREATE TABLE `property_views` (
  `id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `created_at`) VALUES
(1, 'admin', 'System administrator with full access', '2025-07-31 02:05:05'),
(2, 'agent', 'Real estate agent who can manage properties', '2025-07-31 02:05:05'),
(3, 'client', 'Regular user who can browse and inquire about properties', '2025-07-31 02:05:05');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password`, `role_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'User', 'admin@realestate.com', '1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, '2025-07-31 02:05:05', '2025-07-31 02:05:05'),
(2, 'John', 'Agent', 'agent@realestate.com', '1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, '2025-07-31 02:05:05', '2025-07-31 02:05:05'),
(3, 'JEFFREY EYRAM KWEKU', 'AGRA', 'agrajef@gmail.com', '0598147331', '$2y$10$iZupXL.ZahfqPmGec3EASeYDuUz2tPw74mPkFS7uMmOzVBq2.ilx.', 3, 1, '2025-08-11 15:03:31', '2025-08-11 15:03:31'),
(4, 'james', 'bond', 'jamesbond@gmail.com', '0598147331', '$2y$10$FqL6agYlIL6AB19iImIokOsyqrfhToVYkNEIcP78f5cFdafxbp5RS', 3, 1, '2025-08-12 20:27:09', '2025-08-12 20:27:09'),
(5, 'JEFFREY EYRAM KWEKU', 'AGRA', 'agrajeff15@gmail.com', '0598147331', '$2y$10$2y/n9s943gf8kpdVGOra5.zveOKbMC2yxShCCg89JjIJaNrWYC89e', 3, 1, '2025-09-20 16:03:45', '2025-09-20 16:03:45');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `session_data` text NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `property_details`
--
DROP TABLE IF EXISTS `property_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `property_details`  AS SELECT `p`.`id` AS `id`, `p`.`propertiesname` AS `propertiesname`, `p`.`description` AS `description`, `pr`.`amount` AS `price`, `pr`.`currency` AS `currency`, `pr`.`price_type` AS `price_type`, `pt`.`type_name` AS `property_type`, `l`.`city` AS `city`, `l`.`region` AS `region`, `l`.`country` AS `country`, `u`.`first_name` AS `agent_first_name`, `u`.`last_name` AS `agent_last_name`, `u`.`email` AS `agent_email`, `u`.`phone` AS `agent_phone`, `p`.`bedrooms` AS `bedrooms`, `p`.`bathrooms` AS `bathrooms`, `p`.`area_sqft` AS `area_sqft`, `p`.`status` AS `status`, `p`.`is_featured` AS `is_featured`, `p`.`created_at` AS `created_at` FROM (((((`properties` `p` join `prices` `pr` on(`p`.`price_id` = `pr`.`id`)) join `property_types` `pt` on(`p`.`property_type_id` = `pt`.`id`)) join `locations` `l` on(`p`.`location_id` = `l`.`id`)) join `users` `u` on(`p`.`user_id` = `u`.`id`)) join `roles` `r` on(`u`.`role_id` = `r`.`id`)) WHERE `p`.`status` = 'available' AND `u`.`is_active` = 1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_admin` (`admin_id`);

--
-- Indexes for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_bookmark` (`user_id`,`property_id`),
  ADD KEY `fk_bookmarks_property` (`property_id`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_property` (`property_id`),
  ADD KEY `idx_client` (`client_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_location` (`city`,`region`,`country`),
  ADD KEY `idx_city` (`city`),
  ADD KEY `idx_region` (`region`);

--
-- Indexes for table `prices`
--
ALTER TABLE `prices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_amount` (`amount`),
  ADD KEY `idx_price_type` (`price_type`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `price_id` (`price_id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_property_type` (`property_type_id`),
  ADD KEY `idx_location` (`location_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Indexes for table `property_images`
--
ALTER TABLE `property_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_property` (`property_id`);

--
-- Indexes for table `property_types`
--
ALTER TABLE `property_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `type_name` (`type_name`);

--
-- Indexes for table `property_views`
--
ALTER TABLE `property_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_property` (`property_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bookmarks`
--
ALTER TABLE `bookmarks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inquiries`
--
ALTER TABLE `inquiries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `prices`
--
ALTER TABLE `prices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `property_images`
--
ALTER TABLE `property_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_types`
--
ALTER TABLE `property_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `property_views`
--
ALTER TABLE `property_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookmarks`
--
ALTER TABLE `bookmarks`
  ADD CONSTRAINT `bookmarks_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookmarks_ibfk_2` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookmarks_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inquiries`
--
ALTER TABLE `inquiries`
  ADD CONSTRAINT `inquiries_ibfk_1` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inquiries_ibfk_2` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `fk_properties_price` FOREIGN KEY (`price_id`) REFERENCES `prices` (`id`),
  ADD CONSTRAINT `fk_properties_type` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`),
  ADD CONSTRAINT `fk_properties_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `properties_ibfk_1` FOREIGN KEY (`price_id`) REFERENCES `prices` (`id`),
  ADD CONSTRAINT `properties_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `properties_ibfk_3` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`),
  ADD CONSTRAINT `properties_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `property_images`
--
ALTER TABLE `property_images`
  ADD CONSTRAINT `fk_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
