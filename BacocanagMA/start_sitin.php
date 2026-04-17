<?php
session_start();
require 'database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: dashboard_admin.php');
    exit;
}

$student_id = trim($_POST['student_id'] ?? '');
$purpose    = trim($_POST['purpose'] ?? '');
$lab        = trim($_POST['lab'] ?? '');
$pc_number  = trim($_POST['pc_number'] ?? 'N/A');

if (empty($student_id) || empty($purpose) || empty($lab)) {
    $_SESSION['error'] = 'All fields are required';
    header('Location: dashboard_admin.php');
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check if student exists & has sessions
    $stmt = $pdo->prepare("SELECT remaining_session FROM students WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    if (!$student) throw new Exception('Student not found');
    if ($student['remaining_session'] <= 0) throw new Exception('No remaining sessions');

    // 2. Check for active session
    $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$student_id]);
    if ($stmt->fetch()) throw new Exception('Student already has an active session');

    // 3. Insert record & deduct session
    $stmt = $pdo->prepare("INSERT INTO sitin_records (student_id, purpose, lab, pc_number, date_created, status) VALUES (?, ?, ?, ?, NOW(), 'active')");
    $stmt->execute([$student_id, $purpose, $lab, $pc_number]);

    $pdo->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id = ?")->execute([$student_id]);
    $pdo->commit();

    $_SESSION['success'] = 'Sit-in started successfully';
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}
header('Location: dashboard_admin.php');
exit;
