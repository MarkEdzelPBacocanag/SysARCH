<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab = trim($_POST['lab'] ?? '');
    $pc_number = trim($_POST['pc_number'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $date = $_POST['reservation_date'] ?? '';
    $time = $_POST['reservation_time'] ?? '';
    $student_id = $_SESSION['user_id'];

    if (empty($lab) || empty($pc_number) || empty($purpose) || empty($date) || empty($time)) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: dashboard_student.php');
        exit;
    }

    $reservation_datetime = $date . ' ' . $time;
    if (strtotime($reservation_datetime) <= time()) {
        $_SESSION['error'] = 'Reservation date & time must be in the future.';
        header('Location: dashboard_student.php');
        exit;
    }

    try {
        // ✅ FIX 1: Block if student has an ACTIVE or PENDING sit-in.
        // 'Pending' sit-in means Admin accepted a reservation, but student hasn't started yet.
        $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status IN ('active', 'pending') LIMIT 1");
        $stmt->execute([$student_id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = '❌ You have a sit-in session currently assigned or active.';
            header('Location: dashboard_student.php');
            exit;
        }

        // ✅ FIX 2: Block if student has a PENDING reservation (waiting for admin).
        // We removed 'confirmed' because a confirmed reservation is handled by the Sit-in check above.
        // Checking for 'confirmed' here blocks students forever after their first success.
        $stmt = $pdo->prepare("SELECT id FROM reservations WHERE student_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$student_id]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = '❌ You already have a pending reservation.';
            header('Location: dashboard_student.php');
            exit;
        }

        // Check remaining sessions
        $stmt = $pdo->prepare("SELECT remaining_session FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        if (!$student || $student['remaining_session'] <= 0) {
            $_SESSION['error'] = 'You have no remaining sessions available.';
            header('Location: dashboard_student.php');
            exit;
        }

        // Server-side double-booking check (Checks if another student booked this PC)
        $stmt = $pdo->prepare("SELECT id FROM reservations WHERE lab = ? AND pc_number = ? AND DATE(reservation_datetime) = ? AND status IN ('pending', 'confirmed') LIMIT 1");
        $stmt->execute([$lab, $pc_number, $date]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = '❌ This Lab & PC is already reserved for that date.';
            header('Location: dashboard_student.php');
            exit;
        }

        // Insert reservation
        $stmt = $pdo->prepare("INSERT INTO reservations (student_id, lab, pc_number, purpose, reservation_datetime, remaining_session, status)
                               VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$student_id, $lab, $pc_number, $purpose, $reservation_datetime, $student['remaining_session']]);

        $_SESSION['success'] = '✅ Reservation submitted successfully! Waiting for admin approval.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to create reservation: ' . $e->getMessage();
        error_log('Reservation Error: ' . $e->getMessage());
    }
}
header('Location: dashboard_student.php');
exit;
