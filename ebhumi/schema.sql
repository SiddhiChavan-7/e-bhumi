-- e-BhumiAbhilekhan Database Schema
-- Database Name: ebhumi_db

CREATE DATABASE IF NOT EXISTS ebhumi_db;
USE ebhumi_db;

-- 1. Users Table (Admin / Agents)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Store hashed passwords
    role ENUM('admin', 'agent') DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1b. Citizens Table (Public Users)
CREATE TABLE IF NOT EXISTS citizens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL UNIQUE,
    aadhaar_number VARCHAR(12) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 1c. Property Listings (Buy/Sell Marketplace)
CREATE TABLE IF NOT EXISTS property_listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seller_id INT NOT NULL,
    survey_number VARCHAR(50),
    village VARCHAR(100),
    address_details TEXT,
    total_area VARCHAR(100),
    area_type ENUM('Highway Touch', 'Roadside', 'Village Interior', 'Agricultural') NOT NULL,
    asking_price DECIMAL(15,2) NOT NULL,
    description TEXT,
    layout_pdf_path VARCHAR(255),
    image_path VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('Available', 'Sold') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES citizens(id) ON DELETE CASCADE
);

-- 1d. Purchase Requests (Marketplace Offers)
CREATE TABLE IF NOT EXISTS purchase_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    buyer_id INT NOT NULL,
    status ENUM('Pending', 'Accepted', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES property_listings(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES citizens(id) ON DELETE CASCADE
);

-- 2. Properties Table (Core Land Records)
CREATE TABLE IF NOT EXISTS properties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    survey_number VARCHAR(50) NOT NULL,
    village VARCHAR(100) NOT NULL,
    taluka VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    area_sq_meters DECIMAL(10,2) NOT NULL,
    map_coordinates VARCHAR(255) NULL, -- e.g., "19.0760,72.8777" for Google Maps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(survey_number, village, taluka) -- A survey number is unique per village
);

-- 3. 7/12 Records Table (Structured Data from the extract)
CREATE TABLE IF NOT EXISTS seven_twelve_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    owner_name VARCHAR(255) NOT NULL,
    khata_number VARCHAR(50) NOT NULL,
    cultivator_name VARCHAR(255) NULL,
    mutation_notes TEXT NULL, -- Any specific notes from the document
    record_date DATE NOT NULL, -- The date this 7/12 was issued
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 4. Ownership History Table (Tracking previous owners)
CREATE TABLE IF NOT EXISTS ownership_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    previous_owner_name VARCHAR(255) NOT NULL,
    new_owner_name VARCHAR(255) NOT NULL,
    transfer_date DATE NOT NULL,
    transfer_type ENUM('Sale', 'Gift', 'Inheritance', 'Auction') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 5. Encumbrances Table (Loans, Bojha, Legal Disputes)
CREATE TABLE IF NOT EXISTS encumbrances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    property_id INT NOT NULL,
    encumbrance_type ENUM('Bank Loan', 'Private Mortgage', 'Legal Dispute', 'Government Lien') NOT NULL,
    description TEXT NOT NULL,
    severity_level ENUM('Low', 'Medium', 'High') NOT NULL, -- Helps calculate Trust Score
    status ENUM('Active', 'Resolved') DEFAULT 'Active',
    date_recorded DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- 6. Risk Rules Table (For the Trust Score Engine)
CREATE TABLE IF NOT EXISTS risk_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(100) NOT NULL,
    condition_type VARCHAR(100) NOT NULL,
    penalty_points INT NOT NULL, -- How many points to deduct from the perfect score of 10
    description TEXT NOT NULL
);

-- Insert Default Risk Rules for testing
INSERT INTO risk_rules (rule_name, condition_type, penalty_points, description) VALUES
('Active Bank Loan', 'Bank Loan', 2, 'Property has an active bank loan (Bojha).'),
('Active Legal Dispute', 'Legal Dispute', 5, 'Property is involved in an active court case.'),
('Private Mortgage', 'Private Mortgage', 3, 'Property has a private mortgage.'),
('Government Lien', 'Government Lien', 4, 'Government has a claim or tax lien on this property.');

-- Insert a default Admin user (Password: admin123)
-- Note: In a real production app, password must be hashed using password_hash()
-- The hash below is for 'admin123' using BCRYPT
INSERT INTO users (username, password, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
