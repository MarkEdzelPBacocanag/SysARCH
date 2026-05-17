<?php
session_start();
require 'database.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 2. Capture Inputs
    $lab         = trim($_POST['lab'] ?? '');
    $pc_number   = trim($_POST['pc_number'] ?? '');
    $purpose     = trim($_POST['purpose'] ?? '');
    $date        = $_POST['reservation_date'] ?? '';
    $start_time  = $_POST['start_time'] ?? '';
    $end_time    = $_POST['end_time'] ?? '';
    $student_id  = $_SESSION['user_id'];

    // 3. Validate Inputs
    if (empty($lab) || empty($pc_number) || empty($purpose) || empty($date) || empty($start_time) || empty($end_time)) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: dashboard_student.php');
        exit;
    }
    if ($start_time >= $end_time) {
        $_SESSION['error'] = 'End time must be strictly after start time.';
        header('Location: dashboard_student.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 4. Pre-Checks (MUST happen BEFORE any INSERT)

        // Check if student has an active or pending sit-in
        $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status IN ('active', 'pending') LIMIT 1");
        $stmt->execute([$student_id]);
        if ($stmt->fetch()) {
            throw new Exception(' You already have an active or pending session.');
        }

        // Check if student already has a pending reservation
        $stmt = $pdo->prepare("SELECT id FROM reservations WHERE student_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$student_id]);
        if ($stmt->fetch()) {
            throw new Exception('❌ You already have a pending reservation.');
        }

        // Check remaining sessions
        $stmt = $pdo->prepare("SELECT remaining_session FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        if (!$student || $student['remaining_session'] <= 0) {
            throw new Exception('❌ You have no remaining sessions.');
        }

        // 5. Time Overlap Check
        // Correct logic: (ExistingStart < NewEnd) AND (ExistingEnd > NewStart)
        $stmt = $pdo->prepare("
            SELECT id FROM reservations 
            WHERE lab = ? AND pc_number = ? AND DATE(reservation_datetime) = ? 
            AND status IN ('pending', 'confirmed')
            AND start_time < ? AND end_time > ?
            LIMIT 1
        ");
        $stmt->execute([$lab, $pc_number, $date, $end_time, $start_time]);
        if ($stmt->fetch()) {
            throw new Exception('❌ This time slot overlaps with an existing reservation.');
        }

        // 6. Insert Reservation
        $reservation_datetime = $date . ' ' . $start_time;
        $stmt = $pdo->prepare("
            INSERT INTO reservations (student_id, lab, pc_number, purpose, reservation_datetime, start_time, end_time, remaining_session, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$student_id, $lab, $pc_number, $purpose, $reservation_datetime, $start_time, $end_time, $student['remaining_session']]);

        $pdo->commit();
        $_SESSION['success'] = '✅ Reservation submitted successfully! Waiting for admin approval.';
    } catch (Exception $e) {
        $pdo->rollBack(); // Undo any partial changes if an error occurs
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: dashboard_student.php');
    exit;
}
