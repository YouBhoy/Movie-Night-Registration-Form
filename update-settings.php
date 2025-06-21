<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $settings = [
        'event_title' => sanitize_input($_POST['event_title'] ?? ''),
        'movie_title' => sanitize_input($_POST['movie_title'] ?? ''),
        'event_date' => sanitize_input($_POST['event_date'] ?? ''),
        'event_time' => sanitize_input($_POST['event_time'] ?? ''),
        'venue' => sanitize_input($_POST['venue'] ?? ''),
        'max_attendees_per_registration' => intval($_POST['max_attendees_per_registration'] ?? 4),
        'normal_shift_label' => sanitize_input($_POST['normal_shift_label'] ?? ''),
        'crew_shift_label' => sanitize_input($_POST['crew_shift_label'] ?? ''),
        'event_description' => sanitize_input($_POST['event_description'] ?? '')
    ];

    $stmt = $pdo->prepare("
        INSERT INTO event_settings (setting_key, setting_value) 
        VALUES (?, ?) 
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");

    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update settings']);
}
?>
