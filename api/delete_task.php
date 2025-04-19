<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

// 1) Auth check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// 2) Parse JSON body
$input = json_decode(file_get_contents('php://input'), true);
$taskId = intval($input['taskId'] ?? 0);
if (!$taskId) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid task ID']));
}

try {
    // Step 1: Check if the task exists and is owned by this user
    $check = $pdo->prepare("SELECT id FROM tasks WHERE id = ? AND user_id = ?");
    $check->execute([$taskId, $_SESSION['user_id']]);
    if ($check->rowCount() === 0) {
        throw new Exception('Task not found or access denied');
    }

    // Step 2: Delete collaborators
    $delCollab = $pdo->prepare("DELETE FROM collaborators WHERE task_id = ?");
    $delCollab->execute([$taskId]);

    // Step 3: Delete the task
    $delTask = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
    $delTask->execute([$taskId]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
