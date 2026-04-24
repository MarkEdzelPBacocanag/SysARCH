<?php
session_start();
require 'database.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lab = trim($_POST['lab'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $date = $_POST['reservation_date'] ?? '';
    $time = $_POST['reservation_time'] ?? '';

    $student_id = $_SESSION['user_id'];

    // Basic Validation
    if (empty($lab) || empty($purpose) || empty($date) || empty($time)) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: dashboard_student.php');
        exit;
    }

    $reservation_datetime = $date . ' ' . $time;

    // Ensure date is in the future
    if (strtotime($reservation_datetime) <= time()) {
        $_SESSION['error'] = 'Reservation date & time must be in the future.';
        header('Location: dashboard_student.php');
        exit;
    }

    try {
        // Check if student has remaining sessions
        $stmt = $pdo->prepare("SELECT remaining_session FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if (!$student || $student['remaining_session'] <= 0) {
            $_SESSION['error'] = 'You have no remaining sessions available.';
            header('Location: dashboard_student.php');
            exit;
        }

        // Insert Reservation with 'Unassigned' PC and 'pending' status
        // We do NOT deduct the session here. Admin does it upon acceptance.
        $stmt = $pdo->prepare("INSERT INTO reservations (student_id, lab, pc_number, purpose, reservation_datetime, status)
                               VALUES (?, ?, 'Unassigned', ?, ?, 'pending')");

        $stmt->execute([$student_id, $lab, $purpose, $reservation_datetime]);

        $_SESSION['success'] = '✅ Reservation request submitted! Waiting for admin approval.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Failed to create reservation: ' . $e->getMessage();
        error_log('Reservation Error: ' . $e->getMessage());
    }
}

header('Location: dashboard_student.php');
exit;
