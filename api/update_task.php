<?php
error_log("Update Task Request: " . print_r($_POST, true));
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include __DIR__ . '/../config.php';
require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

$taskId = $_POST['task_id'] ?? null;

file_put_contents('debug.txt', print_r($_POST, true));

try {
    // Verify ownership
    $stmt = $pdo->prepare("
    SELECT 
        t.user_id,
        EXISTS(
            SELECT 1 FROM collaborators 
            WHERE task_id = t.id 
            AND user_id = ?
        ) AS is_collaborator
    FROM tasks t
    WHERE t.id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $taskId]);
    $permission = $stmt->fetch();

    if (!$permission || ($permission['user_id'] != $_SESSION['user_id'] && !$permission['is_collaborator'])) {
        throw new Exception('You do not have permission to edit this task');
    }

    $isOwner = ($permission['user_id'] == $_SESSION['user_id']);

    // Expired check
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $currentTask = $stmt->fetch();

    if ($currentTask['status'] === 'expired') {
        throw new Exception('Expired tasks cannot be edited');
    }

    // Update task
    $stmt = $pdo->prepare("UPDATE tasks SET 
        title = ?, 
        description = ?,
        due_date = ?,
        status = ?
        WHERE id = ?
    ");
    
    $stmt->execute([
        $_POST['title'],
        $_POST['description'] ?? '',
        $_POST['due_date'],
        $_POST['status'],
        $taskId
    ]);


    // ======== COLLABORATOR HANDLING ========
    if (!empty($_POST['collaborators'])) {
        if (!$isOwner) {
            throw new Exception('Only task owners can manage collaborators');
        }

        // Get existing collaborators
        $stmtExisting = $pdo->prepare("
            SELECT u.email 
            FROM collaborators c
            JOIN users u ON c.user_id = u.id
            WHERE c.task_id = ?
        ");
        $stmtExisting->execute([$taskId]);
        $existingCollaborators = $stmtExisting->fetchAll(PDO::FETCH_COLUMN, 0);

        // Submitted emails from form
        $submitted = $_POST['collaborators'];


        // Determine removed and new emails
        $removedCollaborators = array_diff($existingCollaborators, $submitted);
        $newCollaborators = array_diff($submitted, $existingCollaborators);

        // 1️⃣ Delete removed collaborators
        if (!empty($removedCollaborators)) {
            $delStmt = $pdo->prepare("
                DELETE c
                FROM collaborators c
                JOIN users u ON c.user_id = u.id
                WHERE c.task_id = ?
                AND u.email = ?
            ");
            foreach ($removedCollaborators as $emailToRemove) {
                $delStmt->execute([$taskId, $emailToRemove]);
            }
        }

        
        if (!empty($newCollaborators)) {
            foreach ($newCollaborators as $email) {
                $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;

                // Check if user exists
                $stmtUser = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
                $stmtUser->execute([$email]);
                $user = $stmtUser->fetch();

                if ($user) {
                    // Add collaborator
                    $stmtCollab = $pdo->prepare("INSERT IGNORE INTO collaborators (task_id, user_id) 
                                                VALUES (?, ?)");
                    $stmtCollab->execute([$taskId, $user['id']]);

                    // Send email only for new additions
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
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    error_log("Update Task Error: " . $e->getMessage());
}