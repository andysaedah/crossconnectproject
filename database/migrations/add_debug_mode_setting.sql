-- =====================================================
-- Migration: Add Debug Mode Setting
-- Run: mysql -u [user] -p [database] < database/migrations/add_debug_mode_setting.sql
-- =====================================================

INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) VALUES 
    ('debug_mode', '0', 'general', 0)
ON DUPLICATE KEY UPDATE setting_key = setting_key;
