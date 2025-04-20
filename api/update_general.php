<?php
session_start();
include(__DIR__ . '/../config.php');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');

if (empty($username)) {
    echo json_encode(['error' => 'Username is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $stmt->execute([$username, $userId]);

    $_SESSION['username'] = $username;

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Update failed']);
}
?>
