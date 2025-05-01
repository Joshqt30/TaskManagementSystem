<?php
session_start();
header('Content-Type: application/json');    // 1️⃣ Tell the client we’re returning JSON

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method Not Allowed']));
}

include __DIR__ . '/../config.php';         // 2️⃣ Make sure this points at your PDO $pdo

// Validate session
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Not logged in']));
}

$userId = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Fetch current filename
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!empty($user['profile_pic'])) {
        $uploadDir = realpath(__DIR__ . '/../uploads/profile_pics/');
        $filename  = basename($user['profile_pic']);
        $filePath  = $uploadDir . DIRECTORY_SEPARATOR . $filename;

        // Security check: must exist and live under the upload dir
        if ($uploadDir 
            && $filePath 
            && realpath($filePath) 
            && strpos(realpath($filePath), $uploadDir) === 0 
            && file_exists($filePath)) 
        {
            if (!unlink($filePath)) {
                throw new Exception('Could not delete file from disk');
            }
        }

        // Clear the DB
        $pdo->prepare("UPDATE users SET profile_pic = NULL WHERE id = ?")
            ->execute([$userId]);
    }

    $pdo->commit();

    // Also clear from session so any UI logic based on it picks up “no pic”
    $_SESSION['profile_pic'] = null;

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Profile Removal Error: ' . $e->getMessage());

    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error'   => 'Server error removing profile picture',
        'details' => $e->getMessage()
    ]));
}
