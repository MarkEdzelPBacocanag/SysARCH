<?php
session_start();

// Database configuration (same as registration)
$host = 'localhost';
$db = 'bacocanagma';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? '';
    $password = $_POST['password'] ?? '';

    $errors = [];

    if (empty($id)) {
        $errors['id'] = 'ID is required';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {
        // fetch user by id
        $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['password'] = 'Invalid ID or password';
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = ['id' => $id];
        header('Location: index.php');
        exit;
    }

    // login successful; for now just echo or redirect
    echo 'Login successful!';
}
?>