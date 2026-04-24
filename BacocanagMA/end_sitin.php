<?php
session_start();
require 'database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? '';
if ($id) {
    // ✅ Update status AND record exact end time
    $stmt = $pdo->prepare("UPDATE sitin_records SET status = 'completed', date_ended = NOW() WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: sitin_records.php');
exit;
