<?php
// Error reporting and headers
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);
ob_start();
session_start();
header('Content-Type: application/json');

// Authentication check
if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

// Dependencies
require __DIR__ . '/../vendor/autoload.php';
include __DIR__ . '/../config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    // Validate required fields
    if (empty($_POST['title']) || empty($_POST['due_date'])) {
        throw new Exception('Title and Due Date are required');
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
    $validCollaborators = [];
    if (!empty($_POST['collaborators'])) {
        foreach ($_POST['collaborators'] as $email) {
            $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
            
            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) continue;
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Add to valid collaborators
                $validCollaborators[] = $user['id'];
                
                // Insert into collaborators table
                $stmt = $pdo->prepare("INSERT INTO collaborators 
                                      (task_id, user_id, status)
                                      VALUES (?, ?, 'pending')
                                      ON DUPLICATE KEY UPDATE status='pending'");
                $stmt->execute([$task_id, $user['id']]);

                // Send email invitation
                $mail = new PHPMailer(true);
                try {
                    // SMTP Configuration
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = $_ENV['EMAIL_USER'];
                    $mail->Password   = $_ENV['EMAIL_PASS'];
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = 587;

                    // Email Content
                    $mail->setFrom($_ENV['EMAIL_USER'], 'Task Management System');
                    $mail->addAddress($email);
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
                }
            }
        }
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'task_id' => $task_id,
        'collaborators_added' => count($validCollaborators)
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}