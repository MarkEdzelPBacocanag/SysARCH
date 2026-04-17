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
        // 🔒 SERVER-SIDE PC STATUS CHECK
        $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE lab = ? AND pc_number = ? AND status = 'active'");
        $stmt->execute([$lab, $pc_number]);
        if ($stmt->fetch()) {
            $_SESSION['error'] = '❌ Cannot reserve: This PC is currently OCCUPIED.';
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

        // Insert reservation
        $stmt = $pdo->prepare("INSERT INTO reservations (student_id, lab, pc_number, purpose, reservation_datetime, remaining_session, status) 
                               VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$student_id, $lab, $pc_number, $purpose, $reservation_datetime, $student['remaining_session']]);

        $_SESSION['success'] = '✅ Reservation submitted successfully!';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to create reservation.';
        error_log('Reservation Error: ' . $e->getMessage());
    }
}

header('Location: dashboard_student.php');
exit;
