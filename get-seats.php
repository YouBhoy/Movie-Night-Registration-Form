<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    // Get all seats with their availability status
    $stmt = $pdo->query("
        SELECT row_letter, seat_number, shift_type, is_occupied, registration_id
        FROM seats 
        ORDER BY row_letter, seat_number
    ");
    
    $seats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize seats by shift and row
    $seatMap = [
        'normal' => [],
        'crew_c' => []
    ];
    
    foreach ($seats as $seat) {
        $row = $seat['row_letter'];
        $shift = $seat['shift_type'];
        
        if (!isset($seatMap[$shift][$row])) {
            $seatMap[$shift][$row] = [];
        }
        
        $seatMap[$shift][$row][] = [
            'number' => (int)$seat['seat_number'],
            'occupied' => (bool)$seat['is_occupied'],
            'registration_id' => $seat['registration_id']
        ];
    }
    
    // Sort seats by number within each row
    foreach ($seatMap as $shift => $rows) {
        foreach ($rows as $row => $rowSeats) {
            usort($seatMap[$shift][$row], function($a, $b) {
                return $a['number'] - $b['number'];
            });
        }
    }
    
    echo json_encode([
        'success' => true, 
        'seats' => $seatMap,
        'debug' => [
            'total_seats' => count($seats),
            'normal_count' => count(array_filter($seats, function($s) { return $s['shift_type'] === 'normal'; })),
            'crew_c_count' => count(array_filter($seats, function($s) { return $s['shift_type'] === 'crew_c'; }))
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Failed to load seats',
        'error' => $e->getMessage()
    ]);
}
?>
