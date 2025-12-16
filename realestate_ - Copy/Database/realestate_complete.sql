-- =================================================================
-- COMPREHENSIVE REAL ESTATE DATABASE SCHEMA
-- Professional, Secure, and Performance-Optimized
-- Version: 2.0.0 (September 21, 2025)
-- =================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Create database if it doesn't exist
DROP DATABASE IF EXISTS `realestate`;
CREATE DATABASE IF NOT EXISTS `realestate` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `realestate`;

-- =================================================================
-- CORE TABLES
-- =================================================================

-- Roles table (admin, agent, client)
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_name` (`role_name`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table with enhanced security
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL DEFAULT 3,
  `profile_image` varchar(500) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `email_verification_token` varchar(100) DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `bio` TEXT,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`),
  KEY `idx_email_active` (`email`, `is_active`),
  KEY `idx_role` (`role_id`),
  KEY `idx_user_search` (`first_name`, `last_name`, `email`),
  KEY `idx_user_status` (`is_active`, `role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property types
CREATE TABLE `property_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Locations
CREATE TABLE `locations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `city` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT 'USA',
  `postal_code` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_location` (`city`, `region`, `country`),
  KEY `idx_city` (`city`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pricing information
CREATE TABLE `prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `price_type` enum('sale','rent_monthly','rent_weekly','rent_daily') DEFAULT 'sale',
  `is_negotiable` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_amount` (`amount`),
  KEY `idx_price_type` (`price_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Main properties table
CREATE TABLE `properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `propertiesname` varchar(200) NOT NULL,
  `slug` varchar(220) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `property_type_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `status` enum('available','sold','rented','pending','inactive') DEFAULT 'available',
  `status_reason` varchar(255) DEFAULT NULL,
  `bedrooms` int(11) DEFAULT 0,
  `bathrooms` int(11) DEFAULT 0,
  `area_sqft` int(11) DEFAULT NULL,
  `year_built` year(4) DEFAULT NULL,
  `parking_spaces` int(11) DEFAULT 0,
  `images` text DEFAULT NULL,
  `video_url` varchar(500) DEFAULT NULL,
  `virtual_tour_url` varchar(500) DEFAULT NULL,
  `floor_plan_url` varchar(500) DEFAULT NULL,
  `energy_rating` varchar(10) DEFAULT NULL,
  `features` json DEFAULT NULL,
  `address_details` text DEFAULT NULL,
  `hoa_fees` decimal(10,2) DEFAULT NULL,
  `property_tax` decimal(10,2) DEFAULT NULL,
  `insurance_cost` decimal(10,2) DEFAULT NULL,
  `mls_number` varchar(50) DEFAULT NULL,
  `listing_date` date DEFAULT NULL,
  `open_house_date` date DEFAULT NULL,
  `open_house_time` time DEFAULT NULL,
  `is_featured` tinyint(1) DEFAULT 0,
  `views_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_slug` (`slug`),
  KEY `idx_user` (`user_id`),
  KEY `idx_property_type` (`property_type_id`),
  KEY `idx_location` (`location_id`),
  KEY `idx_price` (`price_id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`is_featured`),
  KEY `idx_status_featured` (`status`, `is_featured`),
  KEY `idx_type_location` (`property_type_id`, `location_id`),
  KEY `idx_price_bedrooms` (`price_id`, `bedrooms`),
  KEY `idx_search_filter` (`status`, `property_type_id`, `location_id`, `price_id`),
  KEY `idx_property_details` (`bedrooms`, `bathrooms`, `area_sqft`),
  KEY `idx_listing_date` (`listing_date`, `created_at`),
  FULLTEXT KEY `ft_search` (`propertiesname`, `description`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- ENHANCED FEATURES TABLES
-- =================================================================

-- Property images
CREATE TABLE `property_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `image_type` enum('main','interior','exterior','floorplan') DEFAULT 'interior',
  `title` varchar(200) DEFAULT NULL,
  `alt_text` varchar(200) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_property` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User bookmarks
CREATE TABLE `bookmarks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `property_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_property` (`user_id`, `property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property inquiries
CREATE TABLE `inquiries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `name` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('pending','responded','closed') DEFAULT 'pending',
  `response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_property` (`property_id`),
  KEY `idx_client` (`client_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contact messages
CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','responded') DEFAULT 'new',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property views tracking
CREATE TABLE `property_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_property` (`property_id`),
  KEY `idx_user_views` (`user_id`),
  KEY `idx_viewed_at` (`viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions
CREATE TABLE `user_sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `session_data` text NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin logs
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_activity` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property reviews
CREATE TABLE `property_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL CHECK (rating >= 1 AND rating <= 5),
  `review_text` text,
  `is_approved` tinyint(1) DEFAULT FALSE,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_property_user_review` (`property_id`, `user_id`),
  KEY `idx_property_reviews` (`property_id`),
  KEY `idx_user_reviews` (`user_id`),
  KEY `idx_rating` (`rating`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT FALSE,
  `action_url` varchar(500),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_notifications` (`user_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property amenities
CREATE TABLE `property_amenities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `amenity_name` varchar(100) NOT NULL,
  `amenity_category` varchar(50),
  `icon_class` varchar(100),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_amenity_name` (`amenity_name`),
  KEY `idx_amenity_search` (`amenity_category`, `amenity_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property amenity mappings
CREATE TABLE `property_amenity_mappings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `amenity_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_property_amenity` (`property_id`, `amenity_id`),
  KEY `idx_property_amenities` (`property_id`),
  KEY `idx_amenity_properties` (`amenity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site settings
CREATE TABLE `site_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- FOREIGN KEY CONSTRAINTS
-- =================================================================

ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

ALTER TABLE `properties`
  ADD CONSTRAINT `fk_properties_price` FOREIGN KEY (`price_id`) REFERENCES `prices` (`id`),
  ADD CONSTRAINT `fk_properties_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_properties_type` FOREIGN KEY (`property_type_id`) REFERENCES `property_types` (`id`),
  ADD CONSTRAINT `fk_properties_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

ALTER TABLE `property_images`
  ADD CONSTRAINT `fk_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

ALTER TABLE `bookmarks`
  ADD CONSTRAINT `fk_bookmarks_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_bookmarks_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE;

ALTER TABLE `inquiries`
  ADD CONSTRAINT `fk_inquiries_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_inquiries_client` FOREIGN KEY (`client_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inquiries_responded_by` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `contact_messages`
  ADD CONSTRAINT `fk_contact_responded_by` FOREIGN KEY (`responded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `property_views`
  ADD CONSTRAINT `fk_views_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `user_sessions`
  ADD CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

ALTER TABLE `property_reviews`
  ADD CONSTRAINT `fk_reviews_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_reviews_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `notifications`
  ADD CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

ALTER TABLE `property_amenity_mappings`
  ADD CONSTRAINT `fk_amenity_mappings_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_amenity_mappings_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `property_amenities` (`id`) ON DELETE CASCADE;

-- =================================================================
-- INITIAL DATA
-- =================================================================

-- Insert roles
INSERT INTO `roles` (`role_name`, `description`, `permissions`) VALUES
('admin', 'System administrator with full access', '{"users": "all", "properties": "all", "settings": "all"}'),
('agent', 'Real estate agent who can manage properties', '{"properties": "manage", "inquiries": "view"}'),
('client', 'Regular user who can browse and inquire', '{"properties": "view", "bookmarks": "manage"}');

-- Insert property types
INSERT INTO `property_types` (`type_name`, `description`, `icon`, `sort_order`) VALUES
('House', 'Single family houses and villas', 'fas fa-home', 1),
('Apartment', 'Apartments and condominiums', 'fas fa-building', 2),
('Condo', 'Condominium units', 'fas fa-city', 3),
('Commercial', 'Commercial properties', 'fas fa-briefcase', 4),
('Land', 'Empty land plots', 'fas fa-map', 5);

-- Insert sample locations
INSERT INTO `locations` (`city`, `region`, `country`, `postal_code`) VALUES
('New York', 'New York', 'USA', '10001'),
('Los Angeles', 'California', 'USA', '90001'),
('Chicago', 'Illinois', 'USA', '60601'),
('Houston', 'Texas', 'USA', '77001'),
('Miami', 'Florida', 'USA', '33101');

-- Insert sample prices
INSERT INTO `prices` (`amount`, `currency`, `price_type`, `is_negotiable`) VALUES
(250000.00, 'USD', 'sale', 1),
(450000.00, 'USD', 'sale', 1),
(750000.00, 'USD', 'sale', 0),
(2500.00, 'USD', 'rent_monthly', 1),
(3500.00, 'USD', 'rent_monthly', 1);

-- Agencies table
CREATE TABLE `agencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(200) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `description` text,
  `logo_url` varchar(500) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agency_name` (`agency_name`),
  KEY `idx_agency_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agency Members table
CREATE TABLE `agency_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('owner','manager','agent') NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `join_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agency_user` (`agency_id`, `user_id`),
  KEY `idx_agency_members` (`agency_id`),
  KEY `idx_member_user` (`user_id`),
  CONSTRAINT `fk_member_agency` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default users (password: admin123)
INSERT INTO `users` (`first_name`, `last_name`, `email`, `phone`, `password`, `role_id`, `email_verified`, `is_active`) VALUES
('System', 'Administrator', 'admin@realestate.com', '+1234567890', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1, 1),
('John', 'Agent', 'agent@realestate.com', '+1234567891', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 1, 1);

-- Insert sample properties
INSERT INTO `properties` (`propertiesname`, `slug`, `description`, `price_id`, `user_id`, `property_type_id`, `location_id`, `status`, `bedrooms`, `bathrooms`, `area_sqft`, `year_built`, `is_featured`) VALUES
('Beautiful Family House', 'beautiful-family-house-new-york', 'Spacious 3-bedroom house with garden', 1, 2, 1, 1, 'available', 3, 2, 1800, 2015, 1),
('Modern Downtown Apartment', 'modern-downtown-apartment-la', 'Luxury apartment in city center', 2, 2, 2, 2, 'available', 2, 2, 1200, 2020, 1),
('Prime Land Plot', 'prime-land-plot-chicago', 'Perfect for development', 3, 2, 5, 3, 'available', 0, 0, 5000, NULL, 0);

-- Insert default site settings
INSERT INTO `site_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_name', 'Real Estate', 'string', 'Website name'),
('site_description', 'Professional Real Estate Platform', 'string', 'Website description'),
('contact_email', 'info@realestate.com', 'string', 'Contact email address'),
('contact_phone', '+1 (555) 123-4567', 'string', 'Contact phone number'),
('items_per_page', '12', 'number', 'Number of items per page'),
('enable_registration', 'true', 'boolean', 'Allow user registration'),
('enable_reviews', 'true', 'boolean', 'Enable property reviews');

-- Insert default property amenities
INSERT INTO `property_amenities` (`amenity_name`, `amenity_category`, `icon_class`) VALUES
('Air Conditioning', 'Comfort', 'fas fa-snowflake'),
('Heating', 'Comfort', 'fas fa-thermometer-half'),
('Parking', 'Parking', 'fas fa-car'),
('Garden', 'Outdoor', 'fas fa-seedling'),
('Pool', 'Outdoor', 'fas fa-swimming-pool'),
('Gym', 'Fitness', 'fas fa-dumbbell'),
('Security System', 'Security', 'fas fa-shield-alt'),
('Elevator', 'Accessibility', 'fas fa-elevator'),
('Balcony', 'Outdoor', 'fas fa-home'),
('Fireplace', 'Comfort', 'fas fa-fire');


INSERT INTO `agency_members` (`agency_id`, `user_id`, `role`, `commission_rate`, `join_date`) VALUES
(1, 2, 'agent', 2.5, '2025-01-01');

-- Insert sample agencies
INSERT INTO `agencies` (`agency_name`, `license_number`, `description`, `email`, `phone`) VALUES
('Prime Real Estate', 'PRE12345', 'Leading luxury real estate agency', 'contact@primerealestate.com', '+1-555-0123'),
('City Homes Realty', 'CHR67890', 'Your trusted local real estate partner', 'info@cityhomes.com', '+1-555-0124');

-- Insert sample agency members
INSERT INTO `agency_members` (`agency_id`, `user_id`, `role`, `commission_rate`, `join_date`) VALUES
(1, 2, 'agent', 2.5, '2025-01-01');

-- Insert sample property amenity mappings
INSERT INTO `property_amenity_mappings` (`property_id`, `amenity_id`) VALUES
(1, 1), (1, 2), (1, 3), (1, 4),
(2, 1), (2, 2), (2, 8), (2, 9),
(3, 3), (3, 4);

-- =================================================================
-- ADVANCED ANALYTICS AND FEATURES
-- =================================================================

-- Search Analytics
CREATE TABLE `search_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `search_query` text,
  `filters` json DEFAULT NULL,
  `results_count` int(11) DEFAULT 0,
  `page_number` int(11) DEFAULT 1,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_searches` (`user_id`),
  KEY `idx_session_searches` (`session_id`),
  KEY `idx_search_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property Price History
CREATE TABLE `property_price_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `price_id` int(11) NOT NULL,
  `change_type` enum('initial','increase','decrease','special_offer') NOT NULL,
  `change_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_property_price_history` (`property_id`),
  KEY `idx_price_changes` (`price_id`),
  CONSTRAINT `fk_history_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_history_price` FOREIGN KEY (`price_id`) REFERENCES `prices` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property Alerts
CREATE TABLE `property_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `alert_name` varchar(100) NOT NULL,
  `search_criteria` json NOT NULL,
  `frequency` enum('daily','weekly','monthly') DEFAULT 'weekly',
  `last_sent` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_alerts` (`user_id`),
  KEY `idx_active_alerts` (`is_active`, `frequency`),
  CONSTRAINT `fk_alerts_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agency/Team Management
CREATE TABLE `agencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_name` varchar(200) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `description` text,
  `logo_url` varchar(500) DEFAULT NULL,
  `website` varchar(500) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agency_name` (`agency_name`),
  KEY `idx_agency_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agent-Agency Relationships
CREATE TABLE `agency_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agency_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('owner','manager','agent') NOT NULL,
  `commission_rate` decimal(5,2) DEFAULT NULL,
  `join_date` date NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_agency_user` (`agency_id`, `user_id`),
  KEY `idx_agency_members` (`agency_id`),
  KEY `idx_member_user` (`user_id`),
  CONSTRAINT `fk_member_agency` FOREIGN KEY (`agency_id`) REFERENCES `agencies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_member_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property Maintenance Requests
CREATE TABLE `maintenance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `property_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','scheduled','in_progress','completed','cancelled') DEFAULT 'pending',
  `scheduled_date` datetime DEFAULT NULL,
  `completed_date` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_maintenance_property` (`property_id`),
  KEY `idx_maintenance_user` (`user_id`),
  KEY `idx_maintenance_status` (`status`),
  KEY `idx_scheduled_date` (`scheduled_date`),
  CONSTRAINT `fk_maintenance_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maintenance_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance Service Providers
CREATE TABLE `service_providers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` varchar(200) NOT NULL,
  `service_type` varchar(100) NOT NULL,
  `contact_name` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_provider_type` (`service_type`),
  KEY `idx_provider_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =================================================================
-- VIEWS FOR COMMON QUERIES
-- =================================================================

-- Property details view
CREATE OR REPLACE VIEW `property_full_details` AS
SELECT 
    p.*,
    pr.amount as price, pr.currency, pr.price_type, pr.is_negotiable,
    pt.type_name as property_type, pt.icon as type_icon,
    l.city, l.region, l.country, l.postal_code,
    u.first_name as agent_first_name, u.last_name as agent_last_name, 
    u.email as agent_email, u.phone as agent_phone,
    COUNT(DISTINCT b.id) as bookmark_count,
    COUNT(DISTINCT pv.id) as total_views,
    AVG(rv.rating) as average_rating,
    COUNT(DISTINCT rv.id) as review_count
FROM properties p
LEFT JOIN prices pr ON p.price_id = pr.id
LEFT JOIN property_types pt ON p.property_type_id = pt.id
LEFT JOIN locations l ON p.location_id = l.id
LEFT JOIN users u ON p.user_id = u.id
LEFT JOIN bookmarks b ON p.id = b.property_id
LEFT JOIN property_views pv ON p.id = pv.property_id
LEFT JOIN property_reviews rv ON p.id = rv.property_id AND rv.is_approved = 1
WHERE p.status = 'available' AND u.is_active = 1
GROUP BY p.id;

-- User statistics view
CREATE OR REPLACE VIEW `user_statistics` AS
SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    u.role_id,
    r.role_name,
    u.created_at,
    u.last_login,
    COUNT(DISTINCT p.id) as properties_listed,
    COUNT(DISTINCT b.id) as bookmarks_count,
    COUNT(DISTINCT i.id) as inquiries_made,
    COUNT(DISTINCT rv.id) as reviews_written
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN properties p ON u.id = p.user_id
LEFT JOIN bookmarks b ON u.id = b.user_id
LEFT JOIN inquiries i ON u.id = i.client_id
LEFT JOIN property_reviews rv ON u.id = rv.user_id
WHERE u.is_active = 1
GROUP BY u.id;

-- Search analytics view
CREATE OR REPLACE VIEW `search_analytics` AS
SELECT 
    DATE(sl.created_at) as search_date,
    COUNT(*) as total_searches,
    COUNT(DISTINCT sl.user_id) as unique_users,
    COUNT(DISTINCT sl.session_id) as unique_sessions,
    AVG(sl.results_count) as avg_results,
    JSON_ARRAYAGG(sl.filters) as popular_filters
FROM search_logs sl
GROUP BY DATE(sl.created_at)
ORDER BY search_date DESC;

-- Property statistics view
CREATE OR REPLACE VIEW `property_statistics` AS
SELECT 
    p.id,
    p.propertiesname,
    p.status,
    p.is_featured,
    p.views_count,
    p.created_at,
    COUNT(DISTINCT b.id) as bookmarks_count,
    COUNT(DISTINCT i.id) as inquiries_count,
    COUNT(DISTINCT rv.id) as reviews_count,
    AVG(rv.rating) as average_rating
FROM properties p
LEFT JOIN bookmarks b ON p.id = b.property_id
LEFT JOIN inquiries i ON p.id = i.property_id
LEFT JOIN property_reviews rv ON p.id = rv.property_id
GROUP BY p.id;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;