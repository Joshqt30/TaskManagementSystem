<?php
session_start();
include __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$adminId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');

if (empty($username)) {
    echo json_encode(['error' => 'Username is required']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
    $success = $stmt->execute([$username, $adminId]);

    if ($success && $stmt->rowCount() > 0) {
        $_SESSION['username'] = $username;
        echo json_encode(["success" => true]);
    } else {
        echo json_encode([
            "error" => "Update failed or no changes made",
            "adminId" => $adminId,
            "username" => $username,
            "rowCount" => $stmt->rowCount()
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}
