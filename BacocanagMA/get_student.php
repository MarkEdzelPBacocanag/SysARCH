<?php
session_start();
require 'database.php'; // Your PDO connection

header('Content-Type: application/json');

$id = $_GET['id'] ?? '';

if (!$id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, fname, lname, remaining_session FROM students WHERE id = ?");
$stmt->execute([$id]);
$student = $stmt->fetch();

if ($student) {
    echo json_encode([
        'success' => true,
        'name' => $student['fname'] . ' ' . $student['lname'],
        'remaining_session' => $student['remaining_session'] ?? 30 // default 30 if null
    ]);
} else {
    echo json_encode(['success' => false]);
}
