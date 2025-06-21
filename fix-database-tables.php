<?php
// Script to fix common database issues
require_once 'config.php';

echo "<h1>Database Fix Script</h1>";
echo "<style>body{font-family:Arial;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;}</style>";

try {
    echo "<h2>Fixing Database Issues...</h2>";
    
    // Fix 1: Ensure all tables exist with correct structure
    echo "<h3>1. Creating/Fixing Tables</h3>";
    
    // Drop and recreate tables to ensure correct structure
    $tables = [
        "DROP TABLE IF EXISTS registrations",
        "DROP TABLE IF EXISTS seats", 
        "DROP TABLE IF EXISTS event_settings"
    ];
    
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
            echo "<p class='warning'>Dropped existing table</p>";
        } catch (Exception $e) {
            // Table might not exist, that's OK
        }
    }
    
    // Create event_settings table
    $sql = "CREATE TABLE event_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p class='success'>✅ Created event_settings table</p>";
    
    // Create registrations table
    $sql = "CREATE TABLE registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        staff_name VARCHAR(255) NOT NULL,
        number_of_pax INT NOT NULL,
        selected_seats TEXT,
        shift_preference ENUM('normal', 'crew_c') DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "<p class='success'>✅ Created registrations table</p>";
    
    // Create seats table
    $sql = "CREATE TABLE seats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        row_letter CHAR(1) NOT NULL,
        seat_number INT NOT NULL,
        shift_type ENUM('normal', 'crew_c') NOT NULL,
        is_occupied BOOLEAN DEFAULT FALSE,
        registration_id INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_seat (row_letter, seat_number, shift_type)
    )";
    $pdo->exec($sql);
    echo "<p class='success'>✅ Created seats table</p>";
    
    // Fix 2: Insert default settings
    echo "<h3>2. Inserting Default Settings</h3>";
    
    $settings = [
        ['event_title', 'WD Movie Night Registration'],
        ['movie_title', 'Movie Night'],
        ['event_date', 'Coming Soon'],
        ['event_time', 'TBA'],
        ['venue', 'Cinema Hall 1'],
        ['max_attendees_per_registration', '4'],
        ['normal_shift_label', 'Normal Shift'],
        ['crew_shift_label', 'Crew C - Day Shift'],
        ['normal_shift_seats', '1-6'],
        ['crew_shift_seats', '7-11'],
        ['event_description', 'Questions? Contact the WD team for assistance.']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO event_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }
    echo "<p class='success'>✅ Inserted " . count($settings) . " default settings</p>";
    
    // Fix 3: Insert all seats
    echo "<h3>3. Inserting Seat Data</h3>";
    
    $rows = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L'];
    $stmt = $pdo->prepare("INSERT INTO seats (row_letter, seat_number, shift_type) VALUES (?, ?, ?)");
    
    $seat_count = 0;
    foreach ($rows as $row) {
        // Normal shift seats (1-6)
        for ($seat = 1; $seat <= 6; $seat++) {
            $stmt->execute([$row, $seat, 'normal']);
            $seat_count++;
        }
        
        // Crew C shift seats (7-11)
        for ($seat = 7; $seat <= 11; $seat++) {
            $stmt->execute([$row, $seat, 'crew_c']);
            $seat_count++;
        }
    }
    
    echo "<p class='success'>✅ Inserted $seat_count seats</p>";
    
    // Fix 4: Create index
    echo "<h3>4. Creating Performance Index</h3>";
    try {
        $pdo->exec("CREATE INDEX idx_registrations_created_at ON registrations(created_at DESC)");
        echo "<p class='success'>✅ Created performance index</p>";
    } catch (Exception $e) {
        echo "<p class='warning'>⚠️ Index might already exist: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2>✅ Database Fix Complete!</h2>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Test the registration form again</li>";
    echo "<li>If it still fails, run the debug script</li>";
    echo "<li>Check your website: <a href='https://company-movie.great-site.net/'>https://company-movie.great-site.net/</a></li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p class='error'>Please contact support with this error message.</p>";
}
?>
