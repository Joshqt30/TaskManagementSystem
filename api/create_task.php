<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);
ob_start();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require __DIR__ . '/../vendor/autoload.php';
echo "Config file path: " . realpath(__DIR__ . '/../config.php');  // Debug the file path
include __DIR__ . '/../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    if (empty($_POST['title']) || empty($_POST['due_date'])) {
        throw new Exception('Title and Due Date are required');
    }
    
    error_log("[DEBUG] Title: " . ($_POST['title'] ?? 'NOT_PROVIDED'));
    error_log("[DEBUG] Due Date: " . ($_POST['due_date'] ?? 'NOT_PROVIDED'));
    error_log("[DEBUG] Received status: " . ($_POST['status'] ?? 'STATUS_NOT_FOUND'));

    // Insert task
    $stmt = $pdo->prepare("INSERT INTO tasks 
                          (user_id, title, description, due_date, status)
                          VALUES (?, ?, ?, ?, ?)
                          ");
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['title'],
        $_POST['description'] ?? '',
        $_POST['due_date'],
        $_POST['status']   // ← grab it from the form!
    ]);
    $task_id = $pdo->lastInsertId();

    $collaboratorsAdded = 0;

    if (!empty($_POST['collaborators'])) {
        foreach ($_POST['collaborators'] as $email) {
            $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $stmtUser = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
            $stmtUser->execute([$email]);
            $user = $stmtUser->fetch();
            if (!$user) continue;

            $collabId = $user['id'];
            $collabName = $user['username'];

            $stmtCollab = $pdo->prepare("
                INSERT INTO collaborators (task_id, user_id, status)
                VALUES (?, ?, 'pending')
                ON DUPLICATE KEY UPDATE status = 'pending'
            ");
            $stmtCollab->execute([$task_id, $collabId]);
            $collaboratorsAdded++;

            // Send notification
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
                $mail->addAddress($email, $collabName);
                $mail->isHTML(true);
                $mail->Subject = 'Collaboration Invitation';
                $mail->Body    = sprintf(
                    'You have been invited to collaborate on:<br>
                    <strong>%s</strong><br>
                    Due: %s<br><br>
                    <a href="http://localhost/TaskManagementSystem/mytasks.php?id=%d">View Task</a>',
                    htmlspecialchars($_POST['title']),
                    htmlspecialchars($_POST['due_date']),
                    $task_id
                );
                $mail->send();
            } catch (Exception $e) {
                error_log('Mailer Error: ' . $e->getMessage());
            }
        }
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'task_id' => $task_id,
        'collaborators_added' => $collaboratorsAdded
    ]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
