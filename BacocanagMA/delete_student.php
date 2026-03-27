<?php
session_start();
require 'database.php';

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

header('Location: students.php');
exit;
?>