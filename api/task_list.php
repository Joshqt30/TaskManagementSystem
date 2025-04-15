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

// Get latest 5 tasks (same as main.php)
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC, due_date ASC LIMIT 5");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();
?>

<!-- Same task list HTML as in main.php -->
<?php if (!empty($tasks)): ?>
  <?php foreach ($tasks as $task): ?>
    <div class="task-item">
      <h6><?= htmlspecialchars($task['title']) ?></h6>
      <p><?= htmlspecialchars($task['description']) ?></p>
      <div class="task-meta">
        <span class="badge <?= $task['status'] ?>">
          <?= ucfirst(str_replace('_', ' ', $task['status'])) ?>
        </span>
        <?php if ($task['due_date']): ?>
          <span class="due-date">
            <i class="fas fa-calendar-day"></i>
            <?= date('M j, Y', strtotime($task['due_date'])) ?>
          </span>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="no-tasks">
    <i class="fas fa-clipboard-list"></i>
    <p>No tasks found. Start by creating one!</p>
  </div>
<?php endif;
?>