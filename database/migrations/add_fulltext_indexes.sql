-- ============================================
-- CrossConnect MY - FULLTEXT Search Index Migration
-- Run this in your MySQL database
-- ============================================

-- Add FULLTEXT index to churches table
ALTER TABLE churches 
ADD FULLTEXT INDEX idx_churches_search (name, city, address);

-- Add FULLTEXT index to events table  
ALTER TABLE events
ADD FULLTEXT INDEX idx_events_search (name, organizer, venue);

-- Verify indexes were created
SHOW INDEX FROM churches WHERE Key_name = 'idx_churches_search';
SHOW INDEX FROM events WHERE Key_name = 'idx_events_search';
