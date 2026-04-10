<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard_admin.php');
    exit;
}

// Get form data
$student_id = $_POST['student_id'] ?? '';
$purpose = $_POST['purpose'] ?? '';
$lab = $_POST['lab'] ?? '';
$remaining = $_POST['remaining_session'] ?? '';

// Validation
if (empty($student_id) || empty($purpose) || empty($lab)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: dashboard_admin.php');
    exit;
}

// Check if student exists and get their session count
$stmt = $pdo->prepare("SELECT id, remaining_session FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = 'Student not found';
    header('Location: dashboard_admin.php');
    exit;
}

if ($student['remaining_session'] <= 0) {
    $_SESSION['error'] = 'Student has no remaining sessions';
    header('Location: dashboard_admin.php');
    exit;
}

// 🔒 NEW CHECK: Does the student already have an ACTIVE session?
$stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status = 'active' LIMIT 1");
$stmt->execute([$student_id]);
$activeSession = $stmt->fetch();

if ($activeSession) {
    $_SESSION['error'] = 'Student already has an active sit-in session. Please end the current session first.';
    header('Location: dashboard_admin.php');
    exit;
}

// If all checks pass, proceed to create the sit-in
try {
    // Insert sit-in record
    $stmt = $pdo->prepare("INSERT INTO sitin_records (student_id, purpose, lab, date_created, status) VALUES (?, ?, ?, NOW(), 'active')");
    $stmt->execute([$student_id, $purpose, $lab]);

    // Decrease remaining session count
    $stmt = $pdo->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id = ?");
    $stmt->execute([$student_id]);

    $_SESSION['success'] = 'Sit-in started successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . htmlspecialchars($e->getMessage());
}

header('Location: dashboard_admin.php');
exit;
?>