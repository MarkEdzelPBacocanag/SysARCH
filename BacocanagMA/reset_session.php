<?php
session_start();
require 'database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

try {
    $pdo->exec("UPDATE students SET remaining_session = 30");
    $_SESSION['success'] = 'All sessions have been reset to 30';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: student.php');
exit;
