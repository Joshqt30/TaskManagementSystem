<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$host = 'localhost';
$db   = 'tms_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

define('MAX_USERNAME_LENGTH', 30);
define('MIN_USERNAME_LENGTH', 3);
define('EMAIL_MAX_LENGTH', 100);

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection error. Please try again later.");
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
?>