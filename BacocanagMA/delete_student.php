<?php
session_start();
require 'database.php';
// (right after session_start() & require 'database.php')
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}
$id = $_GET['id'] ?? '';

if (empty($id)) {
    $_SESSION['error'] = 'Invalid student ID';
    header('Location: students.php');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['success'] = 'Student deleted successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: student.php');
exit;
