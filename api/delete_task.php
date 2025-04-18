<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$taskId = $_GET['id'] ?? null;

try {
    // Verify ownership
    $stmt = $pdo->prepare("DELETE tasks 
        FROM tasks
        WHERE id = ? AND user_id = ?");
        
    $stmt->execute([$taskId, $_SESSION['user_id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Task not found or access denied');
    }

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}