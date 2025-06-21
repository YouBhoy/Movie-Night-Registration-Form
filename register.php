<?php
require_once 'config.php';

header('Content-Type: application/json');

// Rate limiting
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!SecurityManager::checkRateLimit("register_$clientIP", 10, 300)) { // 10 registrations per 5 minutes
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many registration attempts. Please try again later.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Enhanced input validation
    $rules = [
        'staff_name' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 2,
            'max_length' => 255,
            'pattern' => '/^[a-zA-Z\s\-\.\']+$/',
            'pattern_message' => 'Name can only contain letters, spaces, hyphens, dots, and apostrophes'
        ],
        'number_of_pax' => [
            'required' => true,
            'type' => 'int',
            'min' => 1,
            'max' => 4
        ],
        'shift_preference' => [
            'required' => true,
            'type' => 'string',
            'pattern' => '/^(normal|crew_c)$/',
            'pattern_message' => 'Invalid shift preference'
        ],
        'selected_seats' => [
            'type' => 'string',
            'max_length' => 100,
            'pattern' => '/^[A-L][0-9]{1,2}(,[A-L][0-9]{1,2})*$/',
            'pattern_message' => 'Invalid seat format'
        ]
    ];

    $validationErrors = SecurityManager::validateInput($_POST, $rules);
    
    if (!empty($validationErrors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $validationErrors)]);
        exit;
    }

    // Sanitize inputs
    $staff_name = SecurityManager::sanitizeInput($_POST['staff_name']);
    $number_of_pax = (int)$_POST['number_of_pax'];
    $selected_seats = SecurityManager::sanitizeInput($_POST['selected_seats'] ?? '');
    $shift_preference = SecurityManager::sanitizeInput($_POST['shift_preference']);

    // Additional business logic validation
    $seats_array = [];
    if (!empty($selected_seats)) {
        $seats_array = explode(',', $selected_seats);
        if (count($seats_array) !== $number_of_pax) {
            echo json_encode(['success' => false, 'message' => 'Number of selected seats must match number of attendees']);
            exit;
        }
        
        // Validate seat format and range
        foreach ($seats_array as $seat) {
            if (!preg_match('/^[A-L](1[01]|[1-9])$/', $seat)) {
                echo json_encode(['success' => false, 'message' => 'Invalid seat format']);
                exit;
            }
            
            $row = substr($seat, 0, 1);
            $seatNum = (int)substr($seat, 1);
            
            // Check seat range based on shift
            if ($shift_preference === 'normal' && ($seatNum < 1 || $seatNum > 6)) {
                echo json_encode(['success' => false, 'message' => 'Normal shift seats must be 1-6']);
                exit;
            }
            if ($shift_preference === 'crew_c' && ($seatNum < 7 || $seatNum > 11)) {
                echo json_encode(['success' => false, 'message' => 'Crew C shift seats must be 7-11']);
                exit;
            }
        }
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        // Check for duplicate registration (same name)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE staff_name = ?");
        $stmt->execute([$staff_name]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('A registration with this name already exists');
        }

        // Check if selected seats are still available
        if (!empty($seats_array)) {
            $placeholders = str_repeat('?,', count($seats_array) - 1) . '?';
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as occupied_count 
                FROM seats 
                WHERE CONCAT(row_letter, seat_number) IN ($placeholders) 
                AND shift_type = ? 
                AND is_occupied = 1
            ");
            $params = array_merge($seats_array, [$shift_preference]);
            $stmt->execute($params);
            
            if ($stmt->fetch()['occupied_count'] > 0) {
                throw new Exception('Some selected seats are no longer available');
            }
        }

        // Insert registration with prepared statement
        $stmt = $pdo->prepare("
            INSERT INTO registrations (staff_name, number_of_pax, selected_seats, shift_preference) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$staff_name, $number_of_pax, $selected_seats, $shift_preference]);
        
        $registration_id = $pdo->lastInsertId();

        // Update seat availability
        if (!empty($seats_array)) {
            foreach ($seats_array as $seat) {
                $row_letter = substr($seat, 0, 1);
                $seat_number = (int)substr($seat, 1);
                
                $stmt = $pdo->prepare("
                    UPDATE seats 
                    SET is_occupied = 1, registration_id = ? 
                    WHERE row_letter = ? AND seat_number = ? AND shift_type = ?
                ");
                $stmt->execute([$registration_id, $row_letter, $seat_number, $shift_preference]);
            }
        }

        // Get event settings for confirmation
        $settings_stmt = $pdo->prepare("SELECT setting_key, setting_value FROM event_settings");
        $settings_stmt->execute();
        $event_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        $pdo->commit();
        
        // Log successful registration
        SecurityManager::logSecurityEvent('REGISTRATION_SUCCESS', "Name: $staff_name, Seats: $selected_seats");
        
        // Return registration details for confirmation
        echo json_encode([
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
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (PDOException $e) {
    SecurityManager::logSecurityEvent('DATABASE_ERROR', $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    SecurityManager::logSecurityEvent('REGISTRATION_ERROR', $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
