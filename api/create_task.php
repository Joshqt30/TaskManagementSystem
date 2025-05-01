<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');
error_reporting(E_ALL);
ob_start();
session_start();
error_log(print_r($_POST['collaborators'], true));
header('Content-Type: application/json');
include __DIR__ . '/../config.php';


if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

require __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    if (empty($_POST['title']) || empty($_POST['due_date'])) {
        throw new Exception('Title and Due Date are required');
    }

    // Sanitize user input
    $_POST['title'] = trim($_POST['title']);
    $_POST['description'] = trim($_POST['description'] ?? '');
    $_POST['due_date'] = trim($_POST['due_date']);
    $_POST['status'] = trim($_POST['status']);
        
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


    // ➕ ADD ACTIVITY LOGGING HERE
    try {
        $desc = "Created task: " . substr(htmlspecialchars($_POST['title']), 0, 40);
        $stmt = $pdo->prepare("
            INSERT INTO activity_log 
            (user_id, activity_type, description)
            VALUES (?, 'task_create', ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $desc]);
    } catch(PDOException $e) {
        error_log("Activity log failed: " . $e->getMessage());
    }

// ✅ STEP 1: Insert the task owner as a collaborator
$stmtOwner = $pdo->prepare("
    INSERT INTO collaborators (task_id, user_id, email, status)
    SELECT ?, id, email, 'accepted'
    FROM users
    WHERE id = ?
");
$stmtOwner->execute([$task_id, $_SESSION['user_id']]);

$collaboratorsAdded = 0;

// ✅ STEP 2: Handle actual collaborators
    $validCollaborators = json_decode($_POST['validCollaborators'] ?? '[]', true);
    if (!empty($validCollaborators)) {
        foreach ($validCollaborators as $email) {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        // ✅ Check if the collaborator is a registered user
        $stmtUser = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmtUser->execute([$email]);
        $user = $stmtUser->fetch();

        if (! $user) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode([
              'success' => false,
              'message' => "Collaborator not found: $email"
            ]);
            exit;
          }          

        $stmtCollab = $pdo->prepare("
        INSERT INTO collaborators (task_id, user_id, email, status)
        VALUES (?, ?, ?, 'pending')
        ON DUPLICATE KEY UPDATE status = 'pending'
      ");

      $stmtCollab->execute([
        $task_id,
        $user['id'],  // ← owner’s ID, not NULL
        $user['email']
      ]);

        $collaboratorsAdded++;

        // ✅ Send email invite
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
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Collaboration Invitation';
            $mail->Body = sprintf(
                'You have been invited to collaborate on:<br>
                <strong>%s</strong><br>
                Due: %s<br><br>
                <a href="http://localhost/TaskManagementSystem/login.php?task_id=%d">Accept & View Task</a>',
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
