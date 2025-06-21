-- Database setup for InfinityFree hosting
-- Run this in phpMyAdmin for database: if0_39260841_movienight

-- Create event settings table for admin configuration
CREATE TABLE IF NOT EXISTS event_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default event settings
INSERT INTO event_settings (setting_key, setting_value) VALUES
('event_title', 'WD Movie Night Registration'),
('movie_title', 'Movie Night'),
('event_date', 'Coming Soon'),
('event_time', 'TBA'),
('venue', 'Cinema Hall 1'),
('max_attendees_per_registration', '4'),
('normal_shift_label', 'Normal Shift'),
('crew_shift_label', 'Crew C - Day Shift'),
('normal_shift_seats', '1-6'),
('crew_shift_seats', '7-11'),
('event_description', 'Questions? Contact the WD team for assistance.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Create registrations table
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(255) NOT NULL,
    number_of_pax INT NOT NULL CHECK (number_of_pax >= 1 AND number_of_pax <= 4),
    selected_seats TEXT,
    shift_preference ENUM('normal', 'crew_c') DEFAULT 'normal',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create seats table
CREATE TABLE IF NOT EXISTS seats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    row_letter CHAR(1) NOT NULL,
    seat_number INT NOT NULL,
    shift_type ENUM('normal', 'crew_c') NOT NULL,
    is_occupied BOOLEAN DEFAULT FALSE,
    registration_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_seat (row_letter, seat_number, shift_type),
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE SET NULL
);

-- Insert all available seats
INSERT INTO seats (row_letter, seat_number, shift_type) VALUES
-- Normal Shift seats (1-6)
('A', 1, 'normal'), ('A', 2, 'normal'), ('A', 3, 'normal'), ('A', 4, 'normal'), ('A', 5, 'normal'), ('A', 6, 'normal'),
('B', 1, 'normal'), ('B', 2, 'normal'), ('B', 3, 'normal'), ('B', 4, 'normal'), ('B', 5, 'normal'), ('B', 6, 'normal'),
('C', 1, 'normal'), ('C', 2, 'normal'), ('C', 3, 'normal'), ('C', 4, 'normal'), ('C', 5, 'normal'), ('C', 6, 'normal'),
('D', 1, 'normal'), ('D', 2, 'normal'), ('D', 3, 'normal'), ('D', 4, 'normal'), ('D', 5, 'normal'), ('D', 6, 'normal'),
('E', 1, 'normal'), ('E', 2, 'normal'), ('E', 3, 'normal'), ('E', 4, 'normal'), ('E', 5, 'normal'), ('E', 6, 'normal'),
('F', 1, 'normal'), ('F', 2, 'normal'), ('F', 3, 'normal'), ('F', 4, 'normal'), ('F', 5, 'normal'), ('F', 6, 'normal'),
('G', 1, 'normal'), ('G', 2, 'normal'), ('G', 3, 'normal'), ('G', 4, 'normal'), ('G', 5, 'normal'), ('G', 6, 'normal'),
('H', 1, 'normal'), ('H', 2, 'normal'), ('H', 3, 'normal'), ('H', 4, 'normal'), ('H', 5, 'normal'), ('H', 6, 'normal'),
('J', 1, 'normal'), ('J', 2, 'normal'), ('J', 3, 'normal'), ('J', 4, 'normal'), ('J', 5, 'normal'), ('J', 6, 'normal'),
('K', 1, 'normal'), ('K', 2, 'normal'), ('K', 3, 'normal'), ('K', 4, 'normal'), ('K', 5, 'normal'), ('K', 6, 'normal'),
('L', 1, 'normal'), ('L', 2, 'normal'), ('L', 3, 'normal'), ('L', 4, 'normal'), ('L', 5, 'normal'), ('L', 6, 'normal'),
-- Crew C seats (7-11)
('A', 7, 'crew_c'), ('A', 8, 'crew_c'), ('A', 9, 'crew_c'), ('A', 10, 'crew_c'), ('A', 11, 'crew_c'),
('B', 7, 'crew_c'), ('B', 8, 'crew_c'), ('B', 9, 'crew_c'), ('B', 10, 'crew_c'), ('B', 11, 'crew_c'),
('C', 7, 'crew_c'), ('C', 8, 'crew_c'), ('C', 9, 'crew_c'), ('C', 10, 'crew_c'), ('C', 11, 'crew_c'),
('D', 7, 'crew_c'), ('D', 8, 'crew_c'), ('D', 9, 'crew_c'), ('D', 10, 'crew_c'), ('D', 11, 'crew_c'),
('E', 7, 'crew_c'), ('E', 8, 'crew_c'), ('E', 9, 'crew_c'), ('E', 10, 'crew_c'), ('E', 11, 'crew_c'),
('F', 7, 'crew_c'), ('F', 8, 'crew_c'), ('F', 9, 'crew_c'), ('F', 10, 'crew_c'), ('F', 11, 'crew_c'),
('G', 7, 'crew_c'), ('G', 8, 'crew_c'), ('G', 9, 'crew_c'), ('G', 10, 'crew_c'), ('G', 11, 'crew_c'),
('H', 7, 'crew_c'), ('H', 8, 'crew_c'), ('H', 9, 'crew_c'), ('H', 10, 'crew_c'), ('H', 11, 'crew_c'),
('J', 7, 'crew_c'), ('J', 8, 'crew_c'), ('J', 9, 'crew_c'), ('J', 10, 'crew_c'), ('J', 11, 'crew_c'),
('K', 7, 'crew_c'), ('K', 8, 'crew_c'), ('K', 9, 'crew_c'), ('K', 10, 'crew_c'), ('K', 11, 'crew_c'),
('L', 7, 'crew_c'), ('L', 8, 'crew_c'), ('L', 9, 'crew_c'), ('L', 10, 'crew_c'), ('L', 11, 'crew_c')
ON DUPLICATE KEY UPDATE row_letter = VALUES(row_letter);

-- Create index for better performance
CREATE INDEX IF NOT EXISTS idx_registrations_created_at ON registrations(created_at DESC);
