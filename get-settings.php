<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM event_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo json_encode(['success' => true, 'settings' => $settings]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load settings']);
}
?>
