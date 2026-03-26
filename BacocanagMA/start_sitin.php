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

// Check if student has sessions remaining
if ($remaining <= 0) {
    $_SESSION['error'] = 'Student has no remaining sessions';
    header('Location: dashboard_admin.php');
    exit;
}

try {
    // Insert sit-in record
    $stmt = $pdo->prepare("INSERT INTO sitin_records (student_id, purpose, lab, date_created, status) VALUES (?, ?, ?, NOW(), 'active')");
    $stmt->execute([$student_id, $purpose, $lab]);

    // Decrease remaining session count
    $stmt = $pdo->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id = ?");
    $stmt->execute([$student_id]);

    $_SESSION['success'] = 'Sit-in started successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: dashboard_admin.php');
exit;
