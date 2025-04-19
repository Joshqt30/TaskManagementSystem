<?php
// Start output buffering to prevent accidental output
error_reporting(0); // Temporarily disable PHP warnings
ob_start();
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

include __DIR__ . '/../config.php';

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['user_email'];

// Get status filter (if provided via GET) and validate
$status = $_GET['status'] ?? null;
$validStatuses = ['todo', 'in_progress', 'completed'];

// Build query with filter if a valid status is provided
$query = "
  SELECT DISTINCT t.*
  FROM tasks t
  LEFT JOIN collaborators c ON t.id = c.task_id
  WHERE t.user_id = :user_id OR c.email = :user_email
";

$params = [
  ':user_id' => $user_id,
  ':user_email' => $user_email
];


if ($status && in_array($status, $validStatuses)) {
    $query .= " AND t.status = :status";
    $params[':status'] = $status;
}

$query .= " ORDER BY t.created_at DESC, t.due_date ASC LIMIT 5";  

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();
?>

<!-- Same task list HTML as in main.php -->
<?php if (!empty($tasks)): ?>
  <?php foreach ($tasks as $task): ?>
    <div class="task-item" data-task-id="<?= htmlspecialchars($task['id']) ?>">
      <h6><?= htmlspecialchars($task['title']) ?></h6>
      <p><?= htmlspecialchars($task['description']) ?></p>
      <div class="task-meta">
      <?php
        $status = $task['status'];
        switch ($status) {
            case 'todo':
                $statusColor = 'bg-danger';       // red
                break;
            case 'inprogress':
            case 'in_progress': // handles snake_case too
                $statusColor = 'bg-warning';      // yellow
                break;
            case 'completed':
                $statusColor = 'bg-success';      // green
                break;
            default:
                $statusColor = 'bg-secondary';    // gray fallback
        }
        ?>
        <span class="badge <?= $statusColor ?>">
          <?= ucfirst(str_replace('_', ' ', $status)) ?>
        </span>

        <?php if ($task['due_date']): ?>
          <span class="due-date">
            <i class="fas fa-calendar-day"></i>
            <?= date('M j, Y', strtotime($task['due_date'])) ?>
          </span>
        <?php endif; ?>
      </div>

      <!-- Add Edit button -->
      <button class="btn btn-sm btn-outline-primary edit-task-btn" data-task-id="<?= htmlspecialchars($task['id']) ?>">
        <i class="fas fa-edit"></i> Edit
      </button>

    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="no-tasks">
    <i class="fas fa-clipboard-list"></i>
    <p>No tasks found. Start by creating one!</p>
  </div>
<?php endif; ?>
