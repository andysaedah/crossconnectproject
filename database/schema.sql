-- =====================================================
-- CrossConnect MY - Complete Database Schema
-- Church Directory for Malaysia
-- Last Updated: 2026-01-01
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- Users Table (For Authentication & Authorization)
-- =====================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    church_name VARCHAR(150) NULL,
    preferred_language ENUM('en', 'bm') DEFAULT 'en',
    role ENUM('user', 'admin') DEFAULT 'user',
    email_verified_at TIMESTAMP NULL,
    verification_token VARCHAR(64) NULL,
    reset_token VARCHAR(64) NULL,
    reset_expires TIMESTAMP NULL,
    is_active TINYINT(1) DEFAULT 1,
    avatar_color VARCHAR(7) DEFAULT '#0891b2',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Activity Logs Table (For Admin Audit Trail)
-- =====================================================
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id INT NULL,
    description TEXT,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- States Table
-- =====================================================
CREATE TABLE IF NOT EXISTS states (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    region ENUM('peninsular', 'east_malaysia', 'federal_territory') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Denominations Table
-- =====================================================
CREATE TABLE IF NOT EXISTS denominations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Churches Table (with amendment & service_languages)
-- =====================================================
CREATE TABLE IF NOT EXISTS churches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    denomination_id INT,
    state_id INT NOT NULL,
    city VARCHAR(100),
    address TEXT,
    postal_code VARCHAR(10),
    phone VARCHAR(50),
    email VARCHAR(255),
    website VARCHAR(255),
    facebook VARCHAR(255),
    instagram VARCHAR(255),
    youtube VARCHAR(255),
    twitter VARCHAR(255),
    image_url VARCHAR(500),
    description TEXT,
    service_times TEXT,
    service_languages VARCHAR(255) DEFAULT NULL COMMENT 'Comma-separated: bm,en,chinese,tamil,other',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_featured TINYINT(1) DEFAULT 0,
    status ENUM('active', 'inactive', 'pending', 'needs_amendment') DEFAULT 'active',
    amendment_notes TEXT NULL,
    amendment_reporter_email VARCHAR(255) NULL,
    amendment_date TIMESTAMP NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (denomination_id) REFERENCES denominations(id) ON DELETE SET NULL,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_state (state_id),
    INDEX idx_denomination (denomination_id),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured),
    INDEX idx_created_by (created_by),
    FULLTEXT INDEX idx_churches_search (name, city, address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Events Table (with FULLTEXT search)
-- =====================================================
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    poster_url VARCHAR(500),
    event_date DATE NOT NULL,
    event_end_date DATE,
    event_time VARCHAR(100),
    event_type VARCHAR(50) DEFAULT NULL COMMENT 'Event category: conference, worship, seminar, retreat, concert, prayer, outreach, other',
    event_format ENUM('in_person', 'online', 'hybrid') DEFAULT 'in_person' COMMENT 'Event format: in_person, online, or hybrid',
    meeting_url VARCHAR(500) DEFAULT NULL COMMENT 'Zoom, Google Meet, or other video conferencing link',
    livestream_url VARCHAR(500) DEFAULT NULL COMMENT 'YouTube Live, Facebook Live, or other streaming URL',
    description TEXT,
    organizer VARCHAR(255),
    venue VARCHAR(255),
    venue_address TEXT,
    state_id INT,
    website_url VARCHAR(255),
    registration_url VARCHAR(255),
    whatsapp VARCHAR(50),
    phone VARCHAR(50),
    email VARCHAR(255),
    is_featured TINYINT(1) DEFAULT 0,
    status ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (state_id) REFERENCES states(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_event_date (event_date),
    INDEX idx_status (status),
    INDEX idx_featured (is_featured),
    INDEX idx_created_by (created_by),
    INDEX idx_event_type (event_type),
    INDEX idx_event_format (event_format),
    FULLTEXT INDEX idx_events_search (name, organizer, venue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Email Logs Table (for tracking email delivery)
-- =====================================================
CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id VARCHAR(255) NULL COMMENT 'Provider message ID for webhook correlation',
    provider VARCHAR(50) NOT NULL DEFAULT 'smtp2go' COMMENT 'smtp2go, brevo, phpmail',
    recipient VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NULL,
    status ENUM('queued', 'sent', 'delivered', 'opened', 'clicked', 'bounced', 'failed', 'spam') DEFAULT 'queued',
    error_message TEXT NULL,
    metadata JSON NULL COMMENT 'Additional data like open count, click URLs, etc.',
    opened_count INT DEFAULT 0,
    clicked_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_message_id (message_id),
    INDEX idx_recipient (recipient),
    INDEX idx_status (status),
    INDEX idx_provider (provider),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Settings Table (for API keys and configuration)
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_group VARCHAR(50) DEFAULT 'general',
    is_encrypted BOOLEAN DEFAULT FALSE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_group (setting_group),
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SEED DATA: Malaysian States
-- =====================================================
INSERT INTO states (name, slug, region) VALUES
('Johor', 'johor', 'peninsular'),
('Kedah', 'kedah', 'peninsular'),
('Kelantan', 'kelantan', 'peninsular'),
('Melaka', 'melaka', 'peninsular'),
('Negeri Sembilan', 'negeri-sembilan', 'peninsular'),
('Pahang', 'pahang', 'peninsular'),
('Penang', 'penang', 'peninsular'),
('Perak', 'perak', 'peninsular'),
('Perlis', 'perlis', 'peninsular'),
('Sabah', 'sabah', 'east_malaysia'),
('Sarawak', 'sarawak', 'east_malaysia'),
('Selangor', 'selangor', 'peninsular'),
('Terengganu', 'terengganu', 'peninsular'),
('Kuala Lumpur', 'kuala-lumpur', 'federal_territory'),
('Labuan', 'labuan', 'federal_territory'),
('Putrajaya', 'putrajaya', 'federal_territory')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- SEED DATA: Denominations
-- =====================================================
INSERT INTO denominations (name, slug) VALUES
('Catholic', 'catholic'),
('Methodist', 'methodist'),
('Presbyterian', 'presbyterian'),
('Pentecostal', 'pentecostal'),
('Baptist', 'baptist'),
('Anglican', 'anglican'),
('Lutheran', 'lutheran'),
('Assemblies of God', 'assemblies-of-god'),
('Evangelical', 'evangelical'),
('Non-Denominational', 'non-denominational'),
('Charismatic', 'charismatic'),
('Brethren', 'brethren'),
('Full Gospel', 'full-gospel'),
('SIB (Borneo Evangelical)', 'sib')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- SEED DATA: Default Settings (Email Configuration)
-- =====================================================
INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) VALUES
    ('admin_notification_email', '', 'email', FALSE),
    ('enable_brevo_fallback', '0', 'email', FALSE),
    ('enable_phpmail_fallback', '0', 'email', FALSE),
    ('smtp2go_api_key', '', 'email', TRUE),
    ('smtp2go_sender_email', 'noreply@crossconnect.my', 'email', FALSE),
    ('smtp2go_sender_name', 'CrossConnect MY', 'email', FALSE),
    ('brevo_api_key', '', 'email', TRUE),
    ('brevo_sender_email', 'noreply@crossconnect.my', 'email', FALSE),
    ('brevo_sender_name', 'CrossConnect MY', 'email', FALSE),
    ('clean_urls', '0', 'general', FALSE),
    ('force_https', '1', 'general', FALSE),
    ('telegram_enabled', '0', 'telegram', FALSE),
    ('telegram_bot_token', '', 'telegram', TRUE),
    ('telegram_chat_id', '', 'telegram', FALSE),
    ('debug_mode', '0', 'general', FALSE)
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- =====================================================
-- SEED DATA: Default Admin User
-- Password: system32! (CHANGE THIS AFTER FIRST LOGIN!)
-- =====================================================
INSERT INTO users (username, email, password_hash, name, role, email_verified_at, is_active, avatar_color) VALUES
('admin', 'admin@crossconnect.my', '$2y$12$pDnFA4mL4aSrBzpCHgN7uezRa1dqAUySTAar/7CfY3aRUGvJ05fLK', 'System Admin', 'admin', NOW(), 1, '#0891b2')
ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), email_verified_at = NOW();

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- SCHEMA COMPLETE
-- This file contains ALL tables and seed data.
-- Run: mysql -u [user] -p [database] < database/schema.sql
-- =====================================================
