<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

try {
    $stmt = $pdo->prepare("
        SELECT t.status, t.due_date
        FROM tasks t
        LEFT JOIN collaborators c ON t.id = c.task_id
        WHERE t.user_id = :user_id OR c.email = :user_email
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':user_email' => $userEmail
    ]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $counts = [
        'todo' => 0,
        'in_progress' => 0,
        'completed' => 0,
        'expired' => 0
    ];

    $now = strtotime(date('Y-m-d'));

    foreach ($tasks as $task) {
        $status = $task['status'];
        $dueDate = strtotime($task['due_date']);

        if ($status !== 'completed' && $dueDate < $now) {
            $counts['expired']++;
        } else {
            if ($status === 'inprogress') $status = 'in_progress'; // normalize
            if (isset($counts[$status])) $counts[$status]++;
        }
    }

    echo json_encode($counts);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
