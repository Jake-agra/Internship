-- Create realestate database with improved schema
DROP DATABASE IF EXISTS realestate;
CREATE DATABASE realestate;
USE realestate;

-- Roles table (admin, agent, client)
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Users table with role foreign key
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    INDEX idx_email (email),
    INDEX idx_role (role_id)
);

-- Property types table (Car, house, land)
CREATE TABLE property_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Locations table (city, region)
CREATE TABLE locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    country VARCHAR(100) DEFAULT 'USA',
    postal_code VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_location (city, region, country),
    INDEX idx_city (city),
    INDEX idx_region (region)
);

-- Prices table for price management
CREATE TABLE prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    price_type ENUM('sale', 'rent_monthly', 'rent_weekly', 'rent_daily') DEFAULT 'sale',
    is_negotiable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_amount (amount),
    INDEX idx_price_type (price_type)
);

-- Properties table with proper foreign keys
CREATE TABLE properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    propertiesname VARCHAR(200) NOT NULL,
    description TEXT,
    price_id INT NOT NULL,
    user_id INT NOT NULL,
    property_type_id INT NOT NULL,
    location_id INT NOT NULL,
    status ENUM('available', 'sold', 'rented', 'pending', 'inactive') DEFAULT 'available',
    bedrooms INT DEFAULT 0,
    bathrooms INT DEFAULT 0,
    area_sqft INT,
    year_built YEAR,
    parking_spaces INT DEFAULT 0,
    images TEXT, -- JSON array of image paths
    features TEXT, -- JSON array of features
    address_details TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (price_id) REFERENCES prices(id) ON DELETE RESTRICT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_type_id) REFERENCES property_types(id) ON DELETE RESTRICT,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE RESTRICT,
    INDEX idx_user (user_id),
    INDEX idx_property_type (property_type_id),
    INDEX idx_location (location_id),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured)
);

-- Property inquiries table
CREATE TABLE inquiries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    client_id INT NOT NULL,
    message TEXT,
    contact_phone VARCHAR(20),
    contact_email VARCHAR(150),
    status ENUM('pending', 'responded', 'closed') DEFAULT 'pending',
    response TEXT,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_property (property_id),
    INDEX idx_client (client_id),
    INDEX idx_status (status)
);

-- Insert default roles
INSERT INTO roles (role_name, description) VALUES 
('admin', 'System administrator with full access'),
('agent', 'Real estate agent who can manage properties'),
('client', 'Regular user who can browse and inquire about properties');

-- Insert default property types
INSERT INTO property_types (type_name, description) VALUES 
('house', 'Single family houses and villas'),
('land', 'Empty land plots and lots'),
('car', 'Vehicles and automobiles');

-- Insert sample locations
INSERT INTO locations (city, region, country, postal_code) VALUES 
('New York', 'New York', 'USA', '10001'),
('Los Angeles', 'California', 'USA', '90001'),
('Chicago', 'Illinois', 'USA', '60601'),
('Houston', 'Texas', 'USA', '77001'),
('Miami', 'Florida', 'USA', '33101');

-- Insert sample prices
INSERT INTO prices (amount, currency, price_type, is_negotiable) VALUES 
(250000.00, 'USD', 'sale', TRUE),
(450000.00, 'USD', 'sale', TRUE),
(750000.00, 'USD', 'sale', FALSE),
(2500.00, 'USD', 'rent_monthly', TRUE),
(3500.00, 'USD', 'rent_monthly', TRUE);

-- Insert sample admin user (password: admin123)
INSERT INTO users (first_name, last_name, email, phone, password, role_id) 
VALUES ('Admin', 'User', 'admin@realestate.com', '1234567890', 
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
(SELECT id FROM roles WHERE role_name = 'admin'));

-- Insert sample agent user (password: agent123)
INSERT INTO users (first_name, last_name, email, phone, password, role_id) 
VALUES ('John', 'Agent', 'agent@realestate.com', '1234567891', 
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
(SELECT id FROM roles WHERE role_name = 'agent'));

-- Insert sample properties
INSERT INTO properties (propertiesname, description, price_id, user_id, property_type_id, location_id, bedrooms, bathrooms, area_sqft, is_featured) 
VALUES 
('Beautiful Family House', 'Spacious 3-bedroom house with garden', 1, 
 (SELECT id FROM users WHERE email = 'agent@realestate.com'), 
 (SELECT id FROM property_types WHERE type_name = 'house'), 
 (SELECT id FROM locations WHERE city = 'New York'), 
 3, 2, 1800, TRUE),
('Modern Downtown Apartment', 'Luxury apartment in city center', 2, 
 (SELECT id FROM users WHERE email = 'agent@realestate.com'), 
 (SELECT id FROM property_types WHERE type_name = 'house'), 
 (SELECT id FROM locations WHERE city = 'Los Angeles'), 
 2, 2, 1200, TRUE),
('Prime Land Plot', 'Perfect for development', 3, 
 (SELECT id FROM users WHERE email = 'agent@realestate.com'), 
 (SELECT id FROM property_types WHERE type_name = 'land'), 
 (SELECT id FROM locations WHERE city = 'Miami'), 
 0, 0, 5000, FALSE);

-- Create views for easier querying
CREATE VIEW property_details AS
SELECT 
    p.id,
    p.propertiesname,
    p.description,
    pr.amount as price,
    pr.currency,
    pr.price_type,
    pt.type_name as property_type,
    l.city,
    l.region,
    l.country,
    u.first_name as agent_first_name,
    u.last_name as agent_last_name,
    u.email as agent_email,
    u.phone as agent_phone,
    p.bedrooms,
    p.bathrooms,
    p.area_sqft,
    p.status,
    p.is_featured,
    p.created_at
FROM properties p
JOIN prices pr ON p.price_id = pr.id
JOIN property_types pt ON p.property_type_id = pt.id
JOIN locations l ON p.location_id = l.id
JOIN users u ON p.user_id = u.id
JOIN roles r ON u.role_id = r.id
WHERE p.status = 'available' AND u.is_active = TRUE;
