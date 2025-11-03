CREATE DATABASE IF NOT EXISTS green_directory;

-- SELECT @@version, @@version_comment, @@hostname; checked for debugging
-- DROP DATABASE green_directory;

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

select * from users;

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

-- REMOVED: user_interests table - not needed, only saved_companies is used
-- CREATE TABLE IF NOT EXISTS user_interests (
--     user_id INT,
--     interest_type ENUM('category', 'topic', 'location'),
--     interest_id INT,
--     PRIMARY KEY (user_id, interest_type, interest_id),
--     FOREIGN KEY (user_id) REFERENCES users(user_id)
-- );

-- ========================================
-- BUSINESS TABLES (From actlgreenbiz.sql + your system)
-- ========================================

-- Main businesses table (combines both systems)
-- IMPORTANT: This table works with BOTH PHP and Node.js backends

-- DROP TABLE businesses;
CREATE TABLE IF NOT EXISTS businesses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE NULL,  -- NULL if business hasn't registered for account yet (allows both systems to work)
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
);

INSERT INTO businesses (id, name, category, address, lat, lng, description, longDescription, sustainability_score) VALUES
(1, 'EcoTech Solutions', 'Technology', '1 Marina Boulevard, Singapore 018989', 1.2821, 103.8545, 'Sustainable IT solutions and green technology provider', "We specialize in helping businesses transition to eco-friendly technology infrastructure. Our services include energy-efficient data center solutions, carbon-neutral cloud computing, e-waste recycling programs, and sustainable IT consulting. Since 2015, we've helped over 500 companies reduce their carbon footprint while improving operational efficiency.", 92),
(2, 'Green Harvest Cafe', 'Food and Beverage', '100 Orchard Road, Singapore 238840', 1.3048, 103.8318, 'Farm-to-table organic restaurant with sustainable practices', "Our cafe serves 100% organic, locally-sourced ingredients with a zero food waste policy. We compost all organic waste, use only biodegradable packaging, and support local farmers. Our menu changes seasonally to reduce carbon footprint and ensure the freshest ingredients." , 88),
(3, 'Solar Power Plus', 'Energy', '50 Jurong Gateway Road, Singapore 608549', 1.3339, 103.7436, 'Renewable energy installations and solar panel solutions', "Leading provider of solar energy solutions for residential and commercial properties. We have installed over 500 solar systems across Singapore, generating over 50MW of clean energy annually. Our team of certified engineers ensures optimal system design and installation.", 95),
(4, 'EcoMart Retail', 'Retail', '10 Tampines Central, Singapore 529536', 1.3538, 103.9446, 'Zero-waste retail store offering sustainable products', "Singapore's first zero-waste retail store. We offer package-free shopping with bulk bins, refill stations, and eco-friendly alternatives to everyday products. Our mission is to make sustainable living accessible and affordable for everyone.", 85),
(5, 'GreenBuild Manufacturing', 'Manufacturing', '15 Woodlands Industrial Park, Singapore 738322', 1.4501, 103.7949, 'Sustainable manufacturing with eco-friendly materials', "We manufacture construction materials using recycled and sustainable resources. Our factory runs on 100% renewable energy and implements circular economy principles. We're committed to revolutionizing the construction industry with sustainable alternatives.", 90),
(6, 'Eco Consulting Services', 'Services', '20 Cecil Street, Singapore 049705', 1.2825, 103.8499, 'Environmental consulting and sustainability advisory services', "We help businesses achieve their sustainability goals through expert consulting, audits, and strategic planning. Our team of environmental specialists has helped over 200 companies reduce their environmental impact while improving profitability.", 87);

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

-- DROP TABLE greenpns;
-- Green products/services
CREATE TABLE IF NOT EXISTS greenpns (
    pid INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    productname VARCHAR(255) NOT NULL,
    descript TEXT,
    pvalue varchar(50) not null,
    bid INT NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bid) REFERENCES businesses(id) ON DELETE CASCADE
);

INSERT INTO greenpns (productname, descript, pvalue, bid, available) VALUES
-- bid 1
('Eco Server Hosting', 'Carbon-neutral cloud hosting. Comparison - Green: 50 kWh/month, 0 kg CO₂, 100% renewable energy. Conventional: 180 kWh/month, 95 kg CO₂, 0% renewable. Savings: 130 kWh & 95 kg CO₂ per month.', "99.00++/month", 1, TRUE),
('IT Sustainability Audit','Comprehensive assessment',"500.00",1,TRUE),
('Green IT Consulting','Expert advisory services',"150.00/hr",1,TRUE),
-- bid 2
('Organic Breakfast Set', 'Farm-fresh eggs and vegetables. Comparison - Green: 0.5 kg CO₂, 25 L water, 0 g pesticides. Conventional: 2.8 kg CO₂, 85 L water, 45 g pesticides. Savings: 2.3 kg CO₂, 60 L water, 45 g pesticides per meal.', 18.00, 2, TRUE),
('Sustainable Coffee', 'Fair trade organic beans. Comparison - Green: 0.2 kg CO₂, 140 L water, 0 m² deforestation. Conventional: 1.1 kg CO₂, 280 L water, 2.5 m² deforestation. Savings: 0.9 kg CO₂, 140 L water, 2.5 m² forest per kg.', 6.00, 2, TRUE),
('Zero-Waste Lunch Bowl', 'Seasonal local produce', 15.00, 2, TRUE),
-- bid 3
('Residential Solar System', 'Complete home installation. Comparison - Green: $30/month energy cost, 0 kg CO₂/year, 10% grid dependency. Conventional: $180/month energy cost, 4,200 kg CO₂/year, 100% grid dependency. Savings: $1,800/year & 4,200 kg CO₂ annually.', "8000.00++", 3, TRUE),
('Commercial Solar Solutions', 'Large-scale installations. Comparison - Green: $500/month energy cost, 0 kg CO₂/year, 5-7 years payback period. Conventional: $3,500/month energy cost, 48,000 kg CO₂/year, N/A payback period. Savings: $36,000/year & 48 tons CO₂ annually.', "Custom Quote", 3, TRUE),
('Solar Maintenance', 'Annual service package', "$500.00/yr", 3, TRUE),
-- bid 4
('Bulk Food Items', 'Organic grains, nuts, and dried goods. Comparison - Green: 0 g plastic packaging, 0.3 kg CO₂/kg, 50 km avg transport distance. Conventional: 85 g plastic packaging, 1.8 kg CO₂/kg, 2,500 km avg transport distance. Savings: 85 g plastic & 1.5 kg CO₂ per kg of food.', "Varies", 4, TRUE),
('Reusable Products', 'Bottles, bags, and containers. Comparison - Green: 5+ years lifespan, 1,500 items plastic saved, 2 kg CO₂. Conventional: Single use lifespan, 0 items plastic saved, 450 kg CO₂. Savings: 1,500 single-use items & 448 kg CO₂ over 5 years.', "5.00++", 4, TRUE),
('Natural Cleaning Products', 'Eco-friendly cleaning supplies', "8.00++", 4, TRUE),
-- bid 5
('Eco Concrete Blocks', 'Recycled aggregate blocks. Comparison - Green: 8 kg CO₂/m³, 65% recycled content, 40 MPa strength. Conventional: 410 kg CO₂/m³, 0% recycled content, 40 MPa strength. Savings: 402 kg CO₂ per cubic meter.', "5.00/block", 5, TRUE),
('Sustainable Insulation', 'Natural fiber insulation. Comparison - Green: 5 kg CO₂/m², 26 MJ/m² embodied energy, biodegradable. Conventional: 45 kg CO₂/m², 250 MJ/m² embodied energy, not biodegradable. Savings: 40 kg CO₂ & 224 MJ per square meter.', "15.00/sqm", 5, TRUE),
('Recycled Steel', 'Construction-grade steel', "$580/ton", 5, TRUE),
-- bid 6
('Sustainability Audit', 'Complete environmental assessment. Comparison - Green: 35% avg CO₂ reduction, $15K/year cost savings, 6 months ROI. Conventional: 0% avg CO₂ reduction, $0/year cost savings, N/A ROI. Savings: Typical client saves 150 tons CO₂ & $15K annually.', 2500.00, 6, TRUE),
('Strategy Development', 'Custom sustainability roadmap', 5000.00, 6, TRUE),
('Ongoing Consulting', 'Monthly advisory services', "1000.00/month", 6, TRUE);


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

INSERT INTO business_locations (business_id, location_name, address, lat, lng, operating_hours) VALUES
(1, 'Marina Bay Office', '1 Marina Boulevard, #12-01, Singapore 018989', 1.282191, 103.852514, 'Mon-Fri: 9:00 AM - 6:00 PM'),
(1, 'Jurong Facility', '50 Jurong Gateway Road, #05-01, Singapore 608549', 1.333011, 103.743310, 'Mon-Fri: 8:00 AM - 5:00 PM'),
-- Business ID 2 Location
(2, 'Orchard Outlet', '100 Orchard Road, #01-05, Singapore 238840', 1.300871, 103.842238, 'Daily: 8:00 AM - 9:00 PM'),
-- Business ID 3 Locations
(3, 'Jurong Headquarters', '50 Jurong Gateway Road, #10-15, Singapore 608549', 1.333021, 103.743706, 'Mon-Fri: 9:00 AM - 6:00 PM'),
(3, 'East Branch', '10 Tampines Central, #03-20, Singapore 529536', 1.354201, 103.945022, 'Mon-Sat: 9:00 AM - 5:00 PM'),
(3, 'North Showroom', '15 Woodlands Avenue, #01-10, Singapore 738322', 1.448792, 103.809045, 'Tue-Sat: 10:00 AM - 6:00 PM'),
-- Business ID 4 Locations
(4, 'Tampines Outlet', '10 Tampines Central, #02-15, Singapore 529536', 1.354201, 103.945023, 'Daily: 10:00 AM - 9:00 PM'),
(4, 'Orchard Branch', '100 Orchard Road, #B1-20, Singapore 238840', 1.300821, 103.842237, 'Daily: 11:00 AM - 8:00 PM'),
-- Business ID 5 Location
(5, 'Woodlands Factory', '15 Woodlands Industrial Park, Singapore 738322', 1.453861, 103.795706, 'Mon-Fri: 8:00 AM - 5:00 PM'),
-- Business ID 6 Location
(6, 'Cecil Street Office', '20 Cecil Street, #15-01, Singapore 049705', 1.282985, 103.850522, 'Mon-Fri: 9:00 AM - 6:00 PM');

-- Business eco practices
CREATE TABLE IF NOT EXISTS business_practices (
    practice_id INT PRIMARY KEY AUTO_INCREMENT,
    business_id INT NOT NULL,
    practice_title VARCHAR(255) NOT NULL,
    practice_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE
);

-- Business ID 1: GreenTech Solutions
INSERT INTO business_practices (business_id, practice_title, practice_description) VALUES
(1, '100% Renewable Energy', 'All our operations run on solar and wind power, with zero reliance on fossil fuels.'),
(1, 'E-Waste Recycling Program', 'We recycle and refurbish electronic equipment, preventing 50 tons of e-waste annually.'),
(1, 'Carbon Offset Initiatives', 'We offset 150% of our carbon emissions through verified reforestation projects.'),
(1, 'Green Supply Chain', 'All our suppliers meet strict environmental standards and sustainability criteria.'),
-- Business ID 2: Organic Restaurant/Cafe
(2, '100% Organic Ingredients', 'All ingredients sourced from certified organic local farms.'),
(2, 'Zero Food Waste', 'Composting program and food donation to eliminate waste.'),
(2, 'Biodegradable Packaging', 'All takeaway containers are fully compostable.'),
(2, 'Water Conservation', 'Rainwater harvesting and grey water recycling systems.'),
-- Business ID 3: Solar Energy Company
(3, 'Clean Energy Generation', 'Installing solar systems that prevent 25,000 tons of CO2 annually.'),
(3, 'Recycling Program', '100% recycling of old solar panels and equipment.'),
(3, 'Energy Storage Solutions', 'Battery systems to maximize renewable energy usage.'),
(3, 'Education Initiatives', 'Free workshops on renewable energy for communities.'),
-- Business ID 4: Zero-Waste Store
(4, 'Package-Free Shopping', 'Customers bring their own containers to reduce packaging waste.'),
(4, 'Plastic-Free Store', 'Zero single-use plastics throughout the entire store.'),
(4, 'Local Sourcing', '80% of products sourced from local sustainable suppliers.'),
(4, 'Education Programs', 'Weekly workshops on sustainable living practices.'),
-- Business ID 5: Eco-Friendly Manufacturing
(5, 'Recycled Materials', '90% of our materials are recycled or sustainably sourced.'),
(5, 'Zero Emissions Factory', 'Carbon-neutral production powered by renewable energy.'),
(5, 'Circular Economy', 'Take-back program for end-of-life products.'),
(5, 'Water Recycling', '95% of water used in production is recycled.'),
-- Business ID 6: Sustainability Consulting
(6, 'Carbon Footprint Analysis', 'Comprehensive assessments to identify reduction opportunities.'),
(6, 'Sustainability Strategy', 'Custom roadmaps for achieving environmental goals.'),
(6, 'Green Certifications', 'Guidance through certification processes.'),
(6, 'Employee Training', 'Sustainability workshops and education programs.');


-- review table
CREATE TABLE IF NOT EXISTS review (
	review_id INT auto_increment primary key,
    user_id INT,
    name varchar(100) not null,
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    rating INT,
    b_id int,
    FOREIGN KEY (user_id) references users(user_id),
	FOREIGN KEY (b_id) references businesses(id)
);

select * from review;

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

-- ========================================
-- SAMPLE DATA (from actlgreenbiz.sql)
-- ========================================



-- REMOVED: saved_interests table - duplicate of saved_companies, not needed
-- CREATE TABLE IF NOT EXISTS saved_interests (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     user_id INT NOT NULL,
--     business_id INT NOT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
--     FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE,
--     UNIQUE KEY unique_save (user_id, business_id)
-- );



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


-- forum tables
-- CREATE TABLE IF NOT EXISTS users (
--     user_id INT PRIMARY KEY AUTO_INCREMENT,
--     email VARCHAR(191) UNIQUE NOT NULL,
--     password_hash VARCHAR(255) NOT NULL,
--     name VARCHAR(100) NOT NULL,
--     phone VARCHAR(20),
--     location VARCHAR(100),
--     user_type ENUM('consumer', 'business') DEFAULT 'consumer',
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     last_login TIMESTAMP
-- );

-- Create Posts Table
CREATE TABLE posts (
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
);

-- Create Comments Table
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    edited BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Create Likes Table
CREATE TABLE likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_like (post_id, user_id)
);

-- Insert Dummy Users (for testing forum)
-- NOTE: These are test accounts. Passwords are hashed using PHP password_hash().
-- Default password for all test accounts: 'password123'
-- SECURITY WARNING: These accounts are for testing only. Remove or change passwords before production!
INSERT INTO users (name, email, password_hash, phone, location, user_type) VALUES
('Sarah Chen', 'sarah.chen@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('Michael Wong', 'michael.wong@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('Rachel Ng', 'rachel.ng@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('James Lim', 'james.lim@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('Amanda Koh', 'amanda.koh@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('John Tan', 'john.tan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('Lisa Wong', 'lisa.wong@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('Emily Lim', 'emily.lim@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('David Lee', 'david.lee@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer'),
('Alex Tan', 'alex.tan@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, 'consumer');

ALTER TABLE users 
ADD COLUMN firebase_uid VARCHAR(255) UNIQUE NULL AFTER user_id,
ADD INDEX idx_firebase_uid (firebase_uid);

-- Insert Dummy Posts
INSERT INTO posts (user_id, category, title, content, likes_count, comments_count, created_at) VALUES
(1, 'Sustainability Tips', 'Top 5 Ways to Reduce Plastic Waste in Your Office', 'Just wanted to share some practical tips we implemented at our company to reduce plastic waste. We switched to reusable water bottles, eliminated single-use cups, and started composting!', 24, 2, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 'Business Spotlight', 'Amazing sustainable cafe in Tiong Bahru!', 'Just discovered this gem - they use zero plastic, source local ingredients, and have a community garden on their rooftop. Highly recommend checking them out!', 35, 3, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(3, 'Q&A', 'Looking for eco-friendly packaging suppliers', 'Hi everyone! I\'m starting a small online business and want to use sustainable packaging. Any recommendations for suppliers in Singapore?', 18, 2, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(4, 'Events', 'Green Business Networking Event Next Week', 'Join us for a networking session focused on sustainable business practices. Free entry, snacks provided. Great opportunity to connect with like-minded entrepreneurs!', 42, 0, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(5, 'General Discussion', 'Solar panels for small businesses - worth it?', 'Thinking about installing solar panels at my shop. Has anyone done this? What was your experience with costs, savings, and installation process?', 15, 1, DATE_SUB(NOW(), INTERVAL 3 DAY)),
(1, 'Sustainability Tips', 'Zero-waste grocery shopping tips', 'Been practicing zero-waste shopping for 6 months now. Here are my top tips: bring your own containers, shop at bulk stores, and meal plan to avoid food waste!', 28, 4, DATE_SUB(NOW(), INTERVAL 4 DAY)),
(2, 'Business Spotlight', 'Local brand making clothes from recycled materials', 'Check out this amazing local fashion brand that creates stylish clothes entirely from recycled plastic bottles and textile waste. Quality is excellent!', 31, 2, DATE_SUB(NOW(), INTERVAL 5 DAY)),
(4, 'Q&A', 'Best green certifications for small businesses?', 'Looking to get my business certified as green/sustainable. What certifications are recognized in Singapore and which ones are worth the investment?', 22, 5, DATE_SUB(NOW(), INTERVAL 6 DAY));

-- Insert Dummy Comments
INSERT INTO comments (post_id, user_id, content, created_at) VALUES
(1, 6, 'Great tips! We did something similar and reduced waste by 60%.', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 7, 'Love this! We need more companies doing this.', DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(2, 8, 'Love this place! Their coffee is amazing too.', DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(2, 9, 'Thanks for sharing! Will visit this weekend.', DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(2, 1, 'Been there! The rooftop garden is beautiful.', DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(3, 10, 'Check out GreenPack SG - they have great options and reasonable prices!', DATE_SUB(NOW(), INTERVAL 23 HOUR)),
(3, 2, 'EcoWrap Singapore is also good. They do custom printing too.', DATE_SUB(NOW(), INTERVAL 20 HOUR)),
(5, 1, 'We did it last year! Initial cost was high but we\'re already seeing 30% reduction in electricity bills.', DATE_SUB(NOW(), INTERVAL 70 HOUR)),
(6, 3, 'Which bulk stores do you recommend in Singapore?', DATE_SUB(NOW(), INTERVAL 4 DAY)),
(6, 4, 'UnPackt and Scoop Wholefoods are my go-to places!', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 5, 'Don\'t forget to bring your own bags too!', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(6, 7, 'This is so helpful, thank you!', DATE_SUB(NOW(), INTERVAL 3 DAY)),
(7, 6, 'What\'s the brand name? Would love to check them out!', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(7, 2, 'It\'s called ReThreads SG. They have an online store too!', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(8, 1, 'BizSafe and Green Mark are good starting points.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(8, 3, 'I got ISO 14001 certified. Took some work but clients love it!', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(8, 5, 'Singapore Green Label Scheme is also worth looking into.', DATE_SUB(NOW(), INTERVAL 6 DAY)),
(8, 8, 'Cost can vary a lot depending on certification type.', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(8, 9, 'Let me know if you need help with the application process!', DATE_SUB(NOW(), INTERVAL 5 DAY));



-- Insert Dummy Likes
INSERT INTO likes (post_id, user_id) VALUES
(1, 2), (1, 3), (1, 4), (1, 5),
(2, 1), (2, 3), (2, 4), (2, 5), (2, 6),
(3, 1), (3, 2), (3, 5), (3, 7),
(4, 1), (4, 2), (4, 3), (4, 5), (4, 6), (4, 7),
(5, 2), (5, 3), (5, 4),
(6, 2), (6, 3), (6, 4), (6, 5),
(7, 1), (7, 3), (7, 5), (7, 6),
(8, 1), (8, 2), (8, 4), (8, 6);

