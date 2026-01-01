-- Migration: Add amendment feature to churches table
-- Run this SQL to add the needs_amendment status and notes column

-- Step 1: Modify the status ENUM to include 'needs_amendment'
ALTER TABLE churches MODIFY COLUMN status ENUM('active', 'inactive', 'pending', 'needs_amendment') DEFAULT 'active';

-- Step 2: Add amendment_notes column
ALTER TABLE churches ADD COLUMN amendment_notes TEXT NULL AFTER status;

-- Step 3: Add amendment_reporter_email column (optional contact)
ALTER TABLE churches ADD COLUMN amendment_reporter_email VARCHAR(255) NULL AFTER amendment_notes;

-- Step 4: Add amendment_date column
ALTER TABLE churches ADD COLUMN amendment_date TIMESTAMP NULL AFTER amendment_reporter_email;
