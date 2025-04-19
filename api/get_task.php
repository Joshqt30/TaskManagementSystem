<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../config.php';

// Authentication check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['error' => 'Unauthorized access']));
}

$taskId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$taskId) {
    http_response_code(400);
    die(json_encode(['error' => 'Invalid task ID']));
}

try {
    // Get task details and collaborators emails
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            u.username as owner_name,
            GROUP_CONCAT(c.user_id) AS collaborator_ids,
            GROUP_CONCAT(c.status) AS collaborator_statuses,
            GROUP_CONCAT(u2.email) AS collaborator_emails
        FROM tasks t
        LEFT JOIN collaborators c ON t.id = c.task_id
        LEFT JOIN users u ON t.user_id = u.id
        LEFT JOIN users u2 ON c.user_id = u2.id
        WHERE t.id = ? 
    ");
    
    $userId = $_SESSION['user_id'];
    $stmt->execute([$taskId]);

    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(404);
        die(json_encode(['error' => 'Task not found or access denied']));
    }

    // Format collaborators data
    $collaborators = [];
    if ($task['collaborator_ids']) {
        $ids = explode(',', $task['collaborator_ids']);
        $statuses = explode(',', $task['collaborator_statuses']);
        $emails = explode(',', $task['collaborator_emails']);
        
        $currentUserEmail = $_SESSION['email'];

        for ($i = 0; $i < count($ids); $i++) {
            // All collaborators will be shown their emails
            $collaborators[] = [
                'user_id' => $ids[$i],
                'email' => $emails[$i],
                'status' => $statuses[$i]
            ];
        }
    }

    // Structure final response
    $response = [
        'id' => $task['id'],
        'title' => $task['title'],
        'description' => $task['description'],
        'due_date' => $task['due_date'],
        'status' => $task['status'],
        'is_owner' => ($task['user_id'] == $_SESSION['user_id']),
        'owner' => [
            'id' => $task['user_id'],
            'name' => $task['owner_name']
        ],
        'collaborators' => $collaborators,
        'created_at' => $task['created_at']
    ];

    echo json_encode($response);

} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    die(json_encode(['error' => 'Database operation failed']));
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['error' => $e->getMessage()]));
}

?>