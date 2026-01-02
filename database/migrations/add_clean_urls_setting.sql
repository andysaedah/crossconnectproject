INSERT INTO settings (setting_key, setting_value, setting_group, is_encrypted) VALUES ('clean_urls', '0', 'general', 0) ON DUPLICATE KEY UPDATE setting_key = setting_key;
