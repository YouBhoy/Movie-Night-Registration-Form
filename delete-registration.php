<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $registration_id = intval($_POST['registration_id'] ?? 0);
    
    if ($registration_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid registration ID']);
        exit;
    }
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Free up the seats first
        $stmt = $pdo->prepare("
            UPDATE seats 
            SET is_occupied = 0, registration_id = NULL 
            WHERE registration_id = ?
        ");
        $stmt->execute([$registration_id]);
        
        // Delete the registration
        $stmt = $pdo->prepare("DELETE FROM registrations WHERE id = ?");
        $stmt->execute([$registration_id]);
        
        if ($stmt->rowCount() > 0) {
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Registration deleted successfully']);
        } else {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Registration not found']);
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
