<?php

// At the top
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);

// Start output buffering to prevent accidental output
ob_start();

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require __DIR__ . '/../vendor/autoload.php'; // Go up one directory
include __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Validate required fields
    if (empty($_POST['title']) || empty($_POST['due_date'])) {
        throw new Exception('Title and Due Date are required fields');
    }

    // Create Task
    $stmt = $pdo->prepare("INSERT INTO tasks 
                          (user_id, title, description, due_date, status)
                          VALUES (?, ?, ?, ?, 'todo')");
    $stmt->execute([
        $_SESSION['user_id'],
        $_POST['title'],
        $_POST['description'] ?? '',
        $_POST['due_date']
    ]);
    $task_id = $pdo->lastInsertId();

    // Process Collaborators
    if (!empty($_POST['collaborators'])) {
        foreach ($_POST['collaborators'] as $email) {
            $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($user = $stmt->fetch()) {
                // Add collaborator
                $stmt = $pdo->prepare("INSERT INTO collaborators 
                                      (task_id, user_id, status)
                                      VALUES (?, ?, 'pending')");
                $stmt->execute([$task_id, $user['id']]);

                // Send invitation email using Gmail SMTP
                $mail = new PHPMailer(true);
                try {
                    // Server settings
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['EMAIL_USER'];
                    $mail->Password   = $_ENV['EMAIL_PASS'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Recipients
                    $mail->setFrom($_ENV['EMAIL_USER'], 'Task Management System');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Collaboration Invitation';
                    $mail->Body    = sprintf(
                        'You have been invited to collaborate on:<br>
                        <strong>%s</strong><br>
                        Due: %s<br><br>
                        <a href="http://localhost/task.php?id=%d">View Task</a>',
                        htmlspecialchars($_POST['title']),
                        htmlspecialchars($_POST['due_date']),
                        $task_id
                    );

                    $mail->send();
                } catch (Exception $e) {
                    error_log('Mailer Error: ' . $e->getMessage());
                    // Continue processing even if email fails
                }
            }
        }
    }
    ob_end_clean(); 
    echo json_encode(['success' => true, 'task_id' => $task_id]);
    
} catch (Exception $e) {
    ob_end_clean(); 
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}