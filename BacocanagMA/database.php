<?php
// ---- database.php ----
// THIS IS NOW THE ONLY FILE WITH DATABASE CREDENTIALS

$host = 'localhost';
$db   = 'mydatabest';      // <-- IMPORTANT: Use this database for everything
$user = 'root';           // Your working username
$pass = '';               // Your working password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // For a real application, you might want to log this error instead of showing it to the user.
    error_log('Database Connection Error: ' . $e->getMessage());
    die('Could not connect to the database. Please try again later.');
}
