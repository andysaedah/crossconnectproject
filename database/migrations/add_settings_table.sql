-- Migration: Add settings table for API keys and configuration
-- This table stores all configurable settings including API keys

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

-- Insert default settings for Email providers
INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) VALUES
    -- Admin Notification
    ('admin_notification_email', '', 'email', FALSE),
    
    -- Fallback Chain Options (0 = disabled, 1 = enabled)
    ('enable_brevo_fallback', '0', 'email', FALSE),
    ('enable_phpmail_fallback', '0', 'email', FALSE),
    
    -- SMTP2GO Settings (Always Primary)
    ('smtp2go_api_key', '', 'email', TRUE),
    ('smtp2go_sender_email', 'noreply@crossconnect.my', 'email', FALSE),
    ('smtp2go_sender_name', 'CrossConnect MY', 'email', FALSE),
    
    -- Brevo Settings (Optional Fallback)
    ('brevo_api_key', '', 'email', TRUE),
    ('brevo_sender_email', 'noreply@crossconnect.my', 'email', FALSE),
    ('brevo_sender_name', 'CrossConnect MY', 'email', FALSE)
ON DUPLICATE KEY UPDATE setting_key = setting_key;
