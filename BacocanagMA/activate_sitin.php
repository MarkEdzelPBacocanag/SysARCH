<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? '';
if ($id) {
    try {
        $stmt = $pdo->prepare("UPDATE sitin_records SET status = 'active', date_created = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Session started successfully.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error starting session.';
    }
}
header('Location: sitin_records.php');
exit;
