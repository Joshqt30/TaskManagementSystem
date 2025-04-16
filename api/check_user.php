<?php
include 'config.php';

$email = $_GET['email'] ?? '';
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
echo json_encode(['exists' => $stmt->fetch() ? true : false]);
?>