<?php
session_start();
include __DIR__ . '/../config.php';
require __DIR__ . '/../vendor/autoload.php'; // Add PHPMailer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

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

    // Expired check
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $currentTask = $stmt->fetch();

    if ($currentTask['status'] === 'expired') {
        throw new Exception('Expired tasks cannot be edited');
    }

    // Update task (modified to include description and due_date)
    $stmt = $pdo->prepare("UPDATE tasks SET 
        title = ?, 
        description = ?,
        due_date = ?,
        status = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['title'],
        $_POST['description'] ?? '', // Handle empty description
        $_POST['due_date'],
        $_POST['status'],
        $taskId
    ]);

    // Handle Collaborators (added section)
    if (!empty($_POST['collaborators'])) {
        foreach ($_POST['collaborators'] as $email) {
            $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

            // Check if user exists
            $stmtUser = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();
            if (!$user) continue;

            // Add collaborator if not exists
            $stmtCollab = $pdo->prepare("INSERT IGNORE INTO collaborators (task_id, user_id) 
                                        VALUES (?, ?)");
            $stmtCollab->execute([$taskId, $user['id']]);

                       // Only send email if collaborator was newly added
                       if ($stmtCollab->rowCount() > 0) { 
                        $mail = new PHPMailer(true);
                        try {
                            $mail->isSMTP();
                            $mail->Host       = 'smtp.gmail.com';
                            $mail->SMTPAuth   = true;
                            $mail->Username   = $_ENV['EMAIL_USER'];
                            $mail->Password   = $_ENV['EMAIL_PASS'];
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port       = 587;
        
                            $mail->setFrom($_ENV['EMAIL_USER'], 'Task Management System');
                            $mail->addAddress($email, $user['username']);
                            $mail->isHTML(true);
                            $mail->Subject = 'Collaboration Invitation (Updated Task)';
                            $mail->Body    = sprintf(
                                'You have been invited to collaborate on:<br>
                                <strong>%s</strong><br>
                                Due: %s<br><br>
                                <a href="http://localhost/TaskManagementSystem/login.php?id=%d">View Task</a>',
                                htmlspecialchars($_POST['title']),
                                htmlspecialchars($_POST['due_date']),
                                $taskId
                            );
                            $mail->send();
                        } catch (Exception $e) {
                            error_log('Mailer Error: ' . $e->getMessage());
                        }
                    }
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}