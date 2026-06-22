<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';

// Security check: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['userid'])) {
    $userid = $_POST['userid'];

    try {
        // Update user status
        $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE userid = ?");
        $stmt->execute([$userid]);

        // Return a JSON success response
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Return a JSON error response if something fails
        echo json_encode(['success' => false, 'message' => 'Database error.']);
    }
} else {
    // Return error if not a POST request
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>