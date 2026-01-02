-- =====================================================
-- Migration: Add Clean URLs and Force HTTPS Settings
-- Run: mysql -u [user] -p [database] < database/migrations/add_clean_urls_setting.sql
-- =====================================================

INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) VALUES 
    ('clean_urls', '0', 'general', 0),
    ('force_https', '1', 'general', 0)
ON DUPLICATE KEY UPDATE setting_key = setting_key;
