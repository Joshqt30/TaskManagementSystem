<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) die(json_encode(['success' => false]));

$data = json_decode(file_get_contents('php://input'), true);

try {
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$data['taskId'], $_SESSION['user_id']]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false]);
}