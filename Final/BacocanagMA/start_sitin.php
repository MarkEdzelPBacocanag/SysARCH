<?php
session_start();
require 'database.php';

// Security Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: dashboard_admin.php');
    exit;
}

$student_id = trim($_POST['student_id'] ?? '');
$purpose    = trim($_POST['purpose'] ?? '');
$lab        = trim($_POST['lab'] ?? '');
$pc_number  = trim($_POST['pc_number'] ?? 'N/A');

if (empty($student_id) || empty($purpose) || empty($lab)) {
    $_SESSION['error'] = 'Student ID, Purpose, and Lab are required';
    header('Location: dashboard_admin.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check if student exists
    $stmt = $pdo->prepare("SELECT remaining_session FROM students WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) throw new Exception('Student not found');
    if ($student['remaining_session'] <= 0) throw new Exception('Student has 0 remaining sessions');

    // 2. CHECK: Is student already active?
    $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$student_id]);
    if ($stmt->fetch()) throw new Exception('Student already has an active session');

    // 3. Insert Record & Deduct Session
    $stmt = $pdo->prepare("INSERT INTO sitin_records (student_id, purpose, lab, pc_number, date_created, status) VALUES (?, ?, ?, ?, NOW(), 'active')");
    $stmt->execute([$student_id, $purpose, $lab, $pc_number]);

    $stmt = $pdo->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id = ?");
    $stmt->execute([$student_id]);

    $pdo->commit();
    $_SESSION['success'] = 'Sit-in started successfully';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: dashboard_admin.php');
exit;
