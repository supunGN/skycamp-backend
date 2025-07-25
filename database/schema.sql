-- SkyCamp Database Schema
-- Create database
CREATE DATABASE IF NOT EXISTS skycamp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE skycamp_db;

-- Users table (base table for all users)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('Male', 'Female', 'Other') NOT NULL,
    address TEXT NOT NULL,
    profile_picture VARCHAR(255) NULL,
    nic_front VARCHAR(255) NULL,
    nic_back VARCHAR(255) NULL,
    password VARCHAR(255) NOT NULL,
    user_role ENUM('customer', 'service_provider', 'admin') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_user_role (user_role),
    INDEX idx_created_at (created_at)
);

-- Customers table (extends users for customers)
CREATE TABLE customers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    travel_buddy_option ENUM('yes', 'no') DEFAULT 'no',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_travel_buddy (travel_buddy_option)
);

-- Service Providers table (extends users for service providers)
CREATE TABLE service_providers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    provider_type ENUM('Equipment Renter', 'Local Guide') NOT NULL,
    camping_locations JSON NULL,
    stargazing_locations JSON NULL,
    available_districts JSON NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_reviews INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_provider_type (provider_type),
    INDEX idx_is_verified (is_verified),
    INDEX idx_rating (rating)
);

-- Password resets table
CREATE TABLE password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_email (email),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at)
);

-- User roles table (for future role management)
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default roles
INSERT INTO user_roles (role_name, description, permissions) VALUES
('customer', 'Regular customer who books services', '["view_destinations", "book_services", "leave_reviews", "use_travel_buddy"]'),
('service_provider', 'Equipment renter or local guide', '["manage_listings", "view_bookings", "respond_to_inquiries"]'),
('admin', 'System administrator', '["manage_users", "manage_content", "view_analytics", "system_settings"]');

-- Sessions table (optional, for database session storage)
CREATE TABLE user_sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload TEXT,
    last_activity INT,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
);

-- Create upload directories (to be created by PHP)
-- uploads/profile_pictures/
-- uploads/nic_documents/
