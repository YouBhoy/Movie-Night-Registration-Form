-- Create database (run this in phpMyAdmin)
CREATE DATABASE IF NOT EXISTS movie_night_db;
USE movie_night_db;

-- Create registrations table
CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(255) NOT NULL,
    number_of_pax INT NOT NULL CHECK (number_of_pax >= 1 AND number_of_pax <= 4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create index for better performance
CREATE INDEX idx_created_at ON registrations(created_at DESC);
