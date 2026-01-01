-- Add service_languages column to churches table
-- Stores comma-separated language codes: bm, en, chinese, tamil, other
-- Example: "bm,en,chinese"

ALTER TABLE churches ADD COLUMN service_languages VARCHAR(255) DEFAULT NULL AFTER service_times;
