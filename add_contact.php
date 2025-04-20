<?php
session_start();
require_once 'config.php';

// Set timezone to ensure consistent timestamps
date_default_timezone_set('UTC');

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Test database connection
try {
    $pdo->query("SELECT 1");
    error_log("Database connection successful", 3, "error.log");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage(), 3, "error.log");
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Handle search request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'search') {
    $search_term = isset($_POST['search_term']) ? trim($_POST['search_term']) : '';
    
    if (empty($search_term)) {
        echo json_encode(['users' => []]);
        exit();
    }

    try {
        // Fetch existing contacts (two-way conversations)
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.id
            FROM users u
            INNER JOIN messages m1 
            ON u.id = m1.receiver_id
            INNER JOIN messages m2
            ON u.id = m2.sender_id
            WHERE m1.sender_id = ?
            AND m2.receiver_id = ?
            AND u.id != ?
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $existing_contacts = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        error_log("Existing contacts: " . json_encode($existing_contacts), 3, "error.log");

        // Search for users
        $search_term = "%$search_term%";
        if (empty($existing_contacts)) {
            $stmt = $pdo->prepare("
                SELECT id, username, email
                FROM users
                WHERE (username LIKE ? OR email LIKE ?)
                AND id != ?
            ");
            $stmt->execute([$search_term, $search_term, $user_id]);
        } else {
            $placeholders = implode(',', array_fill(0, count($existing_contacts), '?'));
            $query = "
                SELECT id, username, email
                FROM users
                WHERE (username LIKE ? OR email LIKE ?)
                AND id != ?
                AND id NOT IN ($placeholders)
            ";
            $stmt = $pdo->prepare($query);
            $params = [$search_term, $search_term, $user_id];
            foreach ($existing_contacts as $contact_id) {
                $params[] = $contact_id;
            }
            $stmt->execute($params);
        }
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("Search query executed successfully for term: $search_term", 3, "error.log");
        echo json_encode(['users' => $users]);
    } catch (PDOException $e) {
        error_log("PDOException in add_contact.php (search): " . $e->getMessage(), 3, "error.log");
        http_response_code(500);
        echo json_encode(['error' => 'Database error while searching users']);
    } catch (Exception $e) {
        error_log("Exception in add_contact.php (search): " . $e->getMessage(), 3, "error.log");
        http_response_code(500);
        echo json_encode(['error' => 'Unexpected error while searching users']);
    }
    exit();
}

// Handle adding a contact and starting a conversation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $contact_id = isset($_POST['contact_id']) ? (int)$_POST['contact_id'] : 0;

    if ($contact_id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid contact ID']);
        exit();
    }

    try {
        // Check if the contact exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $stmt->execute([$contact_id]);
        if (!$stmt->fetch()) {
            http_response_code(400);
            echo json_encode(['error' => 'User not found']);
            exit();
        }

        // Insert an initial message to start the conversation
        $stmt = $pdo->prepare("
            INSERT INTO messages (sender_id, receiver_id, content, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $contact_id,
            'Hello! Let’s start our conversation.'
        ]);

        // Fetch the contact's details to return
        $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
        $stmt->execute([$contact_id]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'contact' => $contact]);
    } catch (PDOException $e) {
        error_log("PDOException in add_contact.php (add): " . $e->getMessage(), 3, "error.log");
        http_response_code(500);
        echo json_encode(['error' => 'Database error while adding contact']);
    } catch (Exception $e) {
        error_log("Exception in add_contact.php (add): " . $e->getMessage(), 3, "error.log");
        http_response_code(500);
        echo json_encode(['error' => 'Unexpected error while adding contact']);
    }
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
?>