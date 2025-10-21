CREATE DATABASE IF NOT EXISTS green_directory;

USE green_directory;

-- ========================================
-- USER MANAGEMENT TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(191) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    location VARCHAR(100),
    user_type ENUM('consumer', 'business') DEFAULT 'consumer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_carbon_offsets (
    offset_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    amount_kg DECIMAL(10,2),
    source VARCHAR(100),
    business_id INT,
    product_name VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_business_id (business_id)
);

CREATE TABLE IF NOT EXISTS user_interests (
    user_id INT,
    interest_type ENUM('category', 'topic', 'location'),
    interest_id INT,
    PRIMARY KEY (user_id, interest_type, interest_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ========================================
-- BUSINESS TABLES (From actlgreenbiz.sql + your system)
-- ========================================

-- Main businesses table (combines both systems)
-- IMPORTANT: This table works with BOTH PHP and Node.js backends
CREATE TABLE IF NOT EXISTS businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NULL,  -- NULL if business hasn't registered for account yet (allows both systems to work)
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    address VARCHAR(255),
    lat DECIMAL(9,6),
    lng DECIMAL(9,6),
    description TEXT,
    phone VARCHAR(20),
    email VARCHAR(255),
    website VARCHAR(255),
    location VARCHAR(100),
    sustainability_score INT DEFAULT 0,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_verified (verified),
    INDEX idx_sustainability_score (sustainability_score)
);


-- Business certifications
CREATE TABLE IF NOT EXISTS certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    certification_name VARCHAR(255) NOT NULL,
    certificate_number VARCHAR(100),
    issue_date DATE,
    expiry_date DATE,
    verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Green products/services
CREATE TABLE IF NOT EXISTS greenpns (
    pid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    productname VARCHAR(255) NOT NULL,
    descript TEXT,
    pvalue DECIMAL(10,2) NOT NULL,
    bid INT NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bid) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Business locations (multiple locations per business)
CREATE TABLE IF NOT EXISTS business_locations (
    location_id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    location_name VARCHAR(255),
    address TEXT,
    lat DECIMAL(9,6),
    lng DECIMAL(9,6),
    operating_hours VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Business eco practices
CREATE TABLE IF NOT EXISTS business_practices (
    practice_id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    practice_title VARCHAR(255) NOT NULL,
    practice_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Business updates/news
CREATE TABLE IF NOT EXISTS business_updates (
    update_id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- User saved/bookmarked businesses
CREATE TABLE IF NOT EXISTS saved_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_save (user_id, business_id)
);
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS lat DECIMAL(9,6);
ALTER TABLE businesses ADD COLUMN IF NOT EXISTS lng DECIMAL(9,6);
-- ========================================
-- SAMPLE DATA (from actlgreenbiz.sql)
-- ========================================

INSERT INTO businesses (id, name, category, address, lat, lng, description, sustainability_score) VALUES
(1, 'EcoTech Solutions', 'Technology', '1 Marina Boulevard, Singapore 018989', 1.2821, 103.8545, 'Sustainable IT solutions and green technology provider', 92),
(2, 'Green Harvest Cafe', 'Food and Beverage', '100 Orchard Road, Singapore 238840', 1.3048, 103.8318, 'Farm-to-table organic restaurant with sustainable practices', 88),
(3, 'Solar Power Plus', 'Energy', '50 Jurong Gateway Road, Singapore 608549', 1.3339, 103.7436, 'Renewable energy installations and solar panel solutions', 95),
(4, 'EcoMart Retail', 'Retail', '10 Tampines Central, Singapore 529536', 1.3538, 103.9446, 'Zero-waste retail store offering sustainable products', 85),
(5, 'GreenBuild Manufacturing', 'Manufacturing', '15 Woodlands Industrial Park, Singapore 738322', 1.4501, 103.7949, 'Sustainable manufacturing with eco-friendly materials', 90),
(6, 'Eco Consulting Services', 'Services', '20 Cecil Street, Singapore 049705', 1.2825, 103.8499, 'Environmental consulting and sustainability advisory services', 87);

INSERT INTO certifications (business_id, certification_name) VALUES
(1, 'Green Mark Gold'),
(1, 'ISO 14001'),
(2, 'Green Mark Certified'),
(2, 'Zero Waste'),
(3, 'Green Mark Platinum'),
(3, 'BCA Green Mark'),
(4, 'Green Mark Gold'),
(4, 'Plastic-Free'),
(5, 'ISO 14001'),
(5, 'Carbon Neutral'),
(6, 'Green Mark Certified'),
(6, 'B Corp');


-- Add columns to users table if they don't exist
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS location VARCHAR(255);

-- Create saved_interests table
CREATE TABLE IF NOT EXISTS saved_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_save (user_id, business_id)
);

-- Create user_interactions table for tracking CO2 offset
CREATE TABLE IF NOT EXISTS user_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    interaction_type ENUM('visit', 'purchase', 'engagement') NOT NULL,
    co2_offset DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_user_offset (user_id, co2_offset),
    INDEX idx_interaction_date (created_at)
);

-- Create certifications table if it doesn't exist (for business certifications)
CREATE TABLE IF NOT EXISTS certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    business_id INT NOT NULL,
    certification_name VARCHAR(255) NOT NULL,
    certificate_number VARCHAR(100),
    issue_date DATE,
    expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Insert sample data for testing (optional)
-- Example: Add some CO2 offset data for user ID 1
-- INSERT INTO user_interactions (user_id, business_id, interaction_type, co2_offset) VALUES
-- (1, 1, 'visit', 5.50),
-- (1, 2, 'engagement', 2.50),
-- (1, 1, 'purchase', 15.00);

-- Example: Save some companies for user ID 1
-- INSERT INTO saved_interests (user_id, business_id) VALUES
-- (1, 1),
-- (1, 2);