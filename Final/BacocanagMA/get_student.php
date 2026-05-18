<?php
session_start();
require 'database.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? '';
if (!$id) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, fname, lname, remaining_session FROM students WHERE id = ? AND role = 'student'");
$stmt->execute([$id]);
$student = $stmt->fetch();      

if ($student) {
    // Check if student has an active session
    $stmt_active = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status = 'active'");
    $stmt_active->execute([$id]);
    $activeSession = $stmt_active->fetch();

    echo json_encode([
        'success' => true,
        'name' => $student['fname'] . ' ' . $student['lname'],
        'remaining_session' => $student['remaining_session'] ?? 30,
        'has_active_session' => (bool)$activeSession // Returns true if active session exists
    ]);
} else {
    echo json_encode(['success' => false]);
}
