-- SkyCamp Database Schema
-- Run this script to create the required database tables

-- Create database (uncomment if needed)
-- CREATE DATABASE skycamp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE skycamp_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(36) PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'renter', 'guide', 'admin') NOT NULL DEFAULT 'customer',
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    profile_image VARCHAR(500) NULL,
    nic_front VARCHAR(500) NULL,
    nic_back VARCHAR(500) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    zip_code VARCHAR(20) NULL,
    country VARCHAR(100) NULL,
    emergency_contact VARCHAR(100) NULL,
    emergency_phone VARCHAR(20) NULL,
    preferences TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

-- Renters table
CREATE TABLE IF NOT EXISTS renters (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    business_name VARCHAR(255) NULL,
    business_type VARCHAR(100) NULL,
    business_address TEXT NULL,
    business_phone VARCHAR(20) NULL,
    business_email VARCHAR(255) NULL,
    business_license VARCHAR(100) NULL,
    tax_id VARCHAR(100) NULL,
    bank_account VARCHAR(100) NULL,
    bank_name VARCHAR(100) NULL,
    bank_branch VARCHAR(100) NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_verified (is_verified)
);

-- Guides table
CREATE TABLE IF NOT EXISTS guides (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    specialization VARCHAR(100) NULL,
    experience TEXT NULL,
    languages VARCHAR(255) NULL,
    certifications VARCHAR(500) NULL,
    bio TEXT NULL,
    availability VARCHAR(255) NULL,
    hourly_rate DECIMAL(10,2) NULL,
    daily_rate DECIMAL(10,2) NULL,
    areas VARCHAR(500) NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_verified (is_verified),
    INDEX idx_available (is_available),
    INDEX idx_specialization (specialization)
);

-- Insert sample admin user (password: admin123)
INSERT IGNORE INTO users (
    id, email, password, role, first_name, last_name, phone, is_active, is_verified
) VALUES (
    'admin-uuid-1234-5678-9012-345678901234',
    'admin@skycamp.com',
    '$argon2id$v=19$m=65536,t=4,p=3$dGVzdA$test',
    'admin',
    'Admin',
    'User',
    '+1234567890',
    TRUE,
    TRUE
);

-- Note: The password hash above is a placeholder. In production, use:
-- Password::hash('admin123') to generate the actual hash
