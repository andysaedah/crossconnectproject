-- =====================================================
-- Migration: Add Event Format and Online Event Fields
-- Created: 2026-01-01
-- Description: Adds event_type, event_format, meeting_url, 
--              and livestream_url columns to events table
-- =====================================================

-- Add event_type column for categorization (conference, worship, prayer, etc)
ALTER TABLE events 
ADD COLUMN event_type VARCHAR(50) DEFAULT NULL 
COMMENT 'Event category: conference, worship, seminar, retreat, concert, prayer, outreach, other'
AFTER event_time;

-- Add event_format column for in-person/online/hybrid
ALTER TABLE events 
ADD COLUMN event_format ENUM('in_person', 'online', 'hybrid') DEFAULT 'in_person' 
COMMENT 'Event format: in_person, online, or hybrid'
AFTER event_type;

-- Add meeting_url for Zoom/Google Meet links
ALTER TABLE events 
ADD COLUMN meeting_url VARCHAR(500) DEFAULT NULL 
COMMENT 'Zoom, Google Meet, or other video conferencing link'
AFTER event_format;

-- Add livestream_url for YouTube Live/Facebook Live
ALTER TABLE events 
ADD COLUMN livestream_url VARCHAR(500) DEFAULT NULL 
COMMENT 'YouTube Live, Facebook Live, or other streaming URL'
AFTER meeting_url;

-- Add index for event_format to optimize queries
ALTER TABLE events 
ADD INDEX idx_event_format (event_format);

-- Add index for event_type
ALTER TABLE events 
ADD INDEX idx_event_type (event_type);
