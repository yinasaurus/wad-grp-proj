
-- ========================================
-- GREEN DIRECTORY DATABASE (InfinityFree Compatible)
-- ========================================

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_interests (
    user_id INT,
    interest_type ENUM('category', 'topic', 'location'),
    interest_id INT,
    PRIMARY KEY (user_id, interest_type, interest_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- BUSINESS TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NULL,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    address VARCHAR(255),
    lat DECIMAL(9,6),
    lng DECIMAL(9,6),
    description TEXT,
    longDescription TEXT,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO businesses (id, name, category, address, lat, lng, description, longDescription, sustainability_score) VALUES
(1, 'EcoTech Solutions', 'Technology', '1 Marina Boulevard, Singapore 018989', 1.2821, 103.8545, 'Sustainable IT solutions and green technology provider', "We specialize in helping businesses transition to eco-friendly technology infrastructure. Our services include energy-efficient data center solutions, carbon-neutral cloud computing, e-waste recycling programs, and sustainable IT consulting.", 92),
(2, 'Green Harvest Cafe', 'Food and Beverage', '100 Orchard Road, Singapore 238840', 1.3048, 103.8318, 'Farm-to-table organic restaurant with sustainable practices', "Our cafe serves 100% organic, locally-sourced ingredients with a zero food waste policy. We compost all organic waste, use only biodegradable packaging, and support local farmers.", 88),
(3, 'Solar Power Plus', 'Energy', '50 Jurong Gateway Road, Singapore 608549', 1.3339, 103.7436, 'Renewable energy installations and solar panel solutions', "Leading provider of solar energy solutions for residential and commercial properties.", 95),
(4, 'EcoMart Retail', 'Retail', '10 Tampines Central, Singapore 529536', 1.3538, 103.9446, 'Zero-waste retail store offering sustainable products', "Singapore's first zero-waste retail store. We offer package-free shopping, refill stations, and eco-friendly alternatives.", 85),
(5, 'GreenBuild Manufacturing', 'Manufacturing', '15 Woodlands Industrial Park, Singapore 738322', 1.4501, 103.7949, 'Sustainable manufacturing with eco-friendly materials', "We manufacture construction materials using recycled and sustainable resources.", 90),
(6, 'Eco Consulting Services', 'Services', '20 Cecil Street, Singapore 049705', 1.2825, 103.8499, 'Environmental consulting and sustainability advisory services', "We help businesses achieve their sustainability goals through expert consulting and audits.", 87);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO certifications (business_id, certification_name) VALUES
(1, 'Green Mark Gold'),
(1, 'ISO 14001'),
(2, 'Green Mark Certified'),
(2, 'Zero Waste'),
(3, 'Green Mark Platinum'),
(4, 'Plastic-Free'),
(5, 'Carbon Neutral'),
(6, 'B Corp');

CREATE TABLE IF NOT EXISTS greenpns (
    pid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    productname VARCHAR(255) NOT NULL,
    descript TEXT,
    pvalue VARCHAR(50) NOT NULL,
    bid INT NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bid) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- (split large inserts into smaller sets)
INSERT INTO greenpns (productname, descript, pvalue, bid, available) VALUES
('Eco Server Hosting', 'Carbon-neutral cloud hosting.', '99.00++/month', 1, TRUE),
('IT Sustainability Audit','Comprehensive assessment','500.00',1,TRUE),
('Green IT Consulting','Expert advisory services','150.00/hr',1,TRUE),
('Organic Breakfast Set', 'Farm-fresh eggs and vegetables.', '18.00', 2, TRUE),
('Sustainable Coffee', 'Fair trade organic beans.', '6.00', 2, TRUE);

INSERT INTO greenpns (productname, descript, pvalue, bid, available) VALUES
('Residential Solar System', 'Complete home installation.', '8000.00++', 3, TRUE),
('Commercial Solar Solutions', 'Large-scale installations.', 'Custom Quote', 3, TRUE),
('Solar Maintenance', 'Annual service package', '$500.00/yr', 3, TRUE),
('Bulk Food Items', 'Organic grains, nuts, and dried goods.', 'Varies', 4, TRUE),
('Reusable Products', 'Bottles, bags, and containers.', '5.00++', 4, TRUE);

INSERT INTO greenpns (productname, descript, pvalue, bid, available) VALUES
('Eco Concrete Blocks', 'Recycled aggregate blocks.', '5.00/block', 5, TRUE),
('Sustainable Insulation', 'Natural fiber insulation.', '15.00/sqm', 5, TRUE),
('Recycled Steel', 'Construction-grade steel', '$580/ton', 5, TRUE),
('Sustainability Audit', 'Complete environmental assessment.', '2500.00', 6, TRUE),
('Strategy Development', 'Custom sustainability roadmap', '5000.00', 6, TRUE);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_practices (
    practice_id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    practice_title VARCHAR(255) NOT NULL,
    practice_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    name VARCHAR(100) NOT NULL,
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rating INT,
    b_id INT,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    FOREIGN KEY (b_id) REFERENCES businesses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_updates (
    update_id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_companies (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_save (user_id, business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saved_interests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_save (user_id, business_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_interactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_id INT NOT NULL,
    interaction_type ENUM('visit', 'purchase', 'engagement') NOT NULL,
    co2_offset DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
    INDEX idx_user_offset (user_id, co2_offset),
    INDEX idx_interaction_date (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- FORUM TABLES
-- ========================================

CREATE TABLE IF NOT EXISTS posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image_url VARCHAR(500),
    likes_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

