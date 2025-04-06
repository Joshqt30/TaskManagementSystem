<?php
$host = 'localhost';              // Host name (usually localhost)
$db   = 'tms_db';     // Replace with your database name
$user = 'root'; // Replace with your database username
$pass = ''; // Replace with your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throws exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Sets default fetch mode to associative array
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Disables emulation of prepared statements
];

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // If there's an error, output it
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>
