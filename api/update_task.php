<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$taskId = $_POST['task_id'] ?? null;

try {
    // Verify ownership
    $stmt = $pdo->prepare("SELECT user_id FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch();

    if (!$task || $task['user_id'] != $_SESSION['user_id']) {
        throw new Exception('You dont own this task');
    }

    // Update task
    $stmt = $pdo->prepare("UPDATE tasks SET 
        title = ?, 
        status = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['title'],
        $_POST['status'],
        $taskId
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}