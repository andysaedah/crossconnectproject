-- =====================================================
-- Migration: Add Telegram Bot Integration Settings
-- Run: mysql -u [user] -p [database] < database/migrations/add_telegram_settings.sql
-- =====================================================

INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) VALUES 
    ('telegram_enabled', '0', 'telegram', 0),
    ('telegram_bot_token', '', 'telegram', 1),
    ('telegram_chat_id', '', 'telegram', 0)
ON DUPLICATE KEY UPDATE setting_key = setting_key;
