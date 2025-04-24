<?php
session_start();
include __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$adminId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$email = trim(strtolower($data['email'] ?? ''));
$password = $data['password'] ?? null;

if (empty($email)) {
    echo json_encode(['error' => 'Email is required']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Invalid email format']);
    exit();
}

// Check for email conflict
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$stmt->execute([$email, $adminId]);
if ($stmt->fetch()) {
    echo json_encode(['error' => 'Email is already taken']);
    exit();
}

try {
    $sql = "UPDATE users SET email = ?";
    $params = [$email];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $sql .= ", password = ?";
        $params[] = $hashedPassword;
    }

    $sql .= " WHERE id = ? AND role = 'admin'";
    $params[] = $adminId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['email'] = $email;

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Update failed']);
}
?>
