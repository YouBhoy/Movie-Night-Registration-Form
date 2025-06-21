-- SQL commands to fix the registrations table
-- Run this in phpMyAdmin if the PHP script doesn't work

-- Add missing columns to registrations table
ALTER TABLE registrations 
ADD COLUMN selected_seats TEXT,
ADD COLUMN shift_preference ENUM('normal', 'crew_c') DEFAULT 'normal';

-- Verify the table structure
DESCRIBE registrations;

-- Test query
SELECT * FROM registrations LIMIT 1;
