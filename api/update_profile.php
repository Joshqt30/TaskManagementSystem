<?php 
/*
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username']);
$email = trim(strtolower($data['email']));
$password = $data['password'] ?? null;

if (empty($username) || empty($email)) {
    echo json_encode(['error' => 'Username and email are required']);
    exit();
}

try {
    // Basic email format validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['error' => 'Invalid email format']);
        exit();
    }

    // Check if email is used by someone else
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $userId]);
    if ($stmt->fetch()) {
        echo json_encode(['error' => 'Email is already taken']);
        exit();
    }

    // Update query
    $sql = "UPDATE users SET username = ?, email = ?";
    $params = [$username, $email];

    if (!empty($password)) {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $sql .= ", password = ?";
        $params[] = $hashedPassword;
    }

    $sql .= " WHERE id = ?";
    $params[] = $userId;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Update session data
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Update failed']); 
} 
*/
?>
