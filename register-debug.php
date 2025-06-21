<?php
// Debug version of register.php with detailed error reporting
require_once 'config.php';

header('Content-Type: application/json');

// Enable detailed error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>"; // For better formatting if viewed in browser

try {
    echo "=== REGISTRATION DEBUG LOG ===\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
    echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
    echo "Content Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'Not set') . "\n";
    
    // Check if it's POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method allowed');
    }
    
    echo "\n=== POST DATA ===\n";
    foreach ($_POST as $key => $value) {
        echo "$key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
    }
    
    // Check required fields
    $required_fields = ['staff_name', 'number_of_pax', 'shift_preference'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    echo "\n=== VALIDATION ===\n";
    $staff_name = trim($_POST['staff_name']);
    $number_of_pax = intval($_POST['number_of_pax']);
    $shift_preference = trim($_POST['shift_preference']);
    $selected_seats = trim($_POST['selected_seats'] ?? '');
    
    echo "Staff Name: '$staff_name' (length: " . strlen($staff_name) . ")\n";
    echo "Number of Pax: $number_of_pax\n";
    echo "Shift Preference: '$shift_preference'\n";
    echo "Selected Seats: '$selected_seats'\n";
    
    // Basic validation
    if (strlen($staff_name) < 2 || strlen($staff_name) > 255) {
        throw new Exception('Staff name must be between 2 and 255 characters');
    }
    
    if ($number_of_pax < 1 || $number_of_pax > 4) {
        throw new Exception('Number of pax must be between 1 and 4');
    }
    
    if (!in_array($shift_preference, ['normal', 'crew_c'])) {
        throw new Exception('Invalid shift preference');
    }
    
    echo "✅ Basic validation passed\n";
    
    echo "\n=== DATABASE OPERATIONS ===\n";
    
    // Start transaction
    $pdo->beginTransaction();
    echo "✅ Transaction started\n";
    
    try {
        // Check for duplicate registration
        echo "Checking for duplicate registration...\n";
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE staff_name = ?");
        $stmt->execute([$staff_name]);
        $duplicate_count = $stmt->fetchColumn();
        echo "Duplicate check result: $duplicate_count\n";
        
        if ($duplicate_count > 0) {
            throw new Exception('A registration with this name already exists');
        }
        
        // Validate seats if provided
        if (!empty($selected_seats)) {
            echo "Validating selected seats...\n";
            $seats_array = explode(',', $selected_seats);
            echo "Seats array: " . json_encode($seats_array) . "\n";
            
            if (count($seats_array) !== $number_of_pax) {
                throw new Exception('Number of selected seats must match number of attendees');
            }
            
            // Check seat availability
            $placeholders = str_repeat('?,', count($seats_array) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as occupied_count 
                FROM seats 
                WHERE CONCAT(row_letter, seat_number) IN ($placeholders) 
                AND shift_type = ? 
                AND is_occupied = 1
            ");
            $params = array_merge($seats_array, [$shift_preference]);
            echo "Seat availability query params: " . json_encode($params) . "\n";
            
            $stmt->execute($params);
            $occupied_count = $stmt->fetch()['occupied_count'];
            echo "Occupied seats count: $occupied_count\n";
            
            if ($occupied_count > 0) {
                throw new Exception('Some selected seats are no longer available');
            }
        }
        
        // Insert registration
        echo "Inserting registration...\n";
        $stmt = $pdo->prepare("
            INSERT INTO registrations (staff_name, number_of_pax, selected_seats, shift_preference) 
            VALUES (?, ?, ?, ?)
        ");
        $result = $stmt->execute([$staff_name, $number_of_pax, $selected_seats, $shift_preference]);
        echo "Insert result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        
        $registration_id = $pdo->lastInsertId();
        echo "New registration ID: $registration_id\n";
        
        // Update seat availability
        if (!empty($selected_seats)) {
            echo "Updating seat availability...\n";
            $seats_array = explode(',', $selected_seats);
            
            foreach ($seats_array as $seat) {
                $row_letter = substr($seat, 0, 1);
                $seat_number = intval(substr($seat, 1));
                
                echo "Updating seat: $row_letter$seat_number\n";
                
                $stmt = $pdo->prepare("
                    UPDATE seats 
                    SET is_occupied = 1, registration_id = ? 
                    WHERE row_letter = ? AND seat_number = ? AND shift_type = ?
                ");
                $result = $stmt->execute([$registration_id, $row_letter, $seat_number, $shift_preference]);
                echo "Seat update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
            }
        }
        
        // Get event settings
        echo "Fetching event settings...\n";
        $settings_stmt = $pdo->prepare("SELECT setting_key, setting_value FROM event_settings");
        $settings_stmt->execute();
        $event_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        echo "Event settings count: " . count($event_settings) . "\n";
        
        $pdo->commit();
        echo "✅ Transaction committed successfully\n";
        
        echo "\n=== SUCCESS ===\n";
        $response = [
            'success' => true, 
            'message' => 'Registration successful',
            'registration_data' => [
                'staff_name' => $staff_name,
                'number_of_pax' => $number_of_pax,
                'selected_seats' => $selected_seats,
                'shift_preference' => $shift_preference,
                'movie_title' => $event_settings['movie_title'] ?? 'Movie Night',
                'event_date' => $event_settings['event_date'] ?? 'TBA',
                'event_time' => $event_settings['event_time'] ?? 'TBA',
                'venue' => $event_settings['venue'] ?? 'TBA'
            ]
        ];
        
        echo "Final response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ Transaction rolled back\n";
        throw $e;
    }
    
} catch (PDOException $e) {
    echo "\n=== PDO EXCEPTION ===\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Error Info: " . json_encode($e->errorInfo ?? []) . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
    
} catch (Exception $e) {
    echo "\n=== GENERAL EXCEPTION ===\n";
    echo "Error Message: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END DEBUG LOG ===\n";
echo "</pre>";
?>
