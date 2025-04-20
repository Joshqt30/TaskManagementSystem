<?php
session_start();
require_once 'config.php';

// Set timezone to ensure consistent timestamps
date_default_timezone_set('UTC');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';

if ($receiver_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid receiver ID']);
    exit();
}

try {
    $file_path = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = uniqid() . '_' . basename($_FILES['file']['name']);
        $file_path = $upload_dir . $file_name;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            throw new Exception('Failed to upload file');
        }
    }

    // Insert the message into the database
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, file_path, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$user_id, $receiver_id, $content, $file_path]);

    // Return success response with file path if applicable
    echo json_encode(['success' => true, 'file_path' => $file_path]);
} catch (PDOException $e) {
    error_log("PDOException in send_message.php: " . $e->getMessage(), 3, "error.log");
    http_response_code(500);
    echo json_encode(['error' => 'Database error while sending message']);
} catch (Exception $e) {
    error_log("Exception in send_message.php: " . $e->getMessage(), 3, "error.log");
    http_response_code(500);
    echo json_encode(['error' => 'Unexpected error while sending message']);
}
?>