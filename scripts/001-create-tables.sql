-- Create the registrations table
CREATE TABLE IF NOT EXISTS registrations (
    id SERIAL PRIMARY KEY,
    staff_name VARCHAR(255) NOT NULL,
    number_of_pax INTEGER NOT NULL CHECK (number_of_pax >= 1 AND number_of_pax <= 4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create an index for faster queries
CREATE INDEX IF NOT EXISTS idx_registrations_created_at ON registrations(created_at DESC);
