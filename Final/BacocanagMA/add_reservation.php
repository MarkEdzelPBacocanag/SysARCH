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

    // 3. Basic Input Validation
    if (empty($lab) || empty($pc_number) || empty($purpose) || empty($date) || empty($start_time) || empty($end_time)) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    if ($start_time >= $end_time) {
        $_SESSION['error'] = 'End time must be strictly after start time.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // 4. Date Validity (Cannot book in the past)
    $today = date('Y-m-d');
    if ($date < $today) {
        $_SESSION['error'] = 'You cannot reserve for a past date.';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // 5. Business Hours Check (Optional but Recommended)
    // Assuming Lab hours are 07:00 to 22:00
    $start_hour = (int)date('H', strtotime($start_time));
    $end_hour   = (int)date('H', strtotime($end_time));
    if ($start_hour < 7 || $end_hour > 22) {
        $_SESSION['error'] = 'Reservations must be within Lab hours (07:00 AM - 10:00 PM).';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    try {
        // 🔑 CRITICAL: Start Transaction to prevent Race Conditions
        $pdo->beginTransaction();

        // A. Check if student has an active/pending sit-in record
        $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status IN ('active', 'pending') LIMIT 1");
        $stmt->execute([$student_id]);
        if ($stmt->fetch()) {
            throw new Exception('You already have an active session. Please end it before reserving again.');
        }

        // B. Check if student has a PENDING reservation for this time (Self-Duplicate)
        $stmt = $pdo->prepare("
            SELECT id FROM reservations
            WHERE student_id = ?
            AND DATE(reservation_datetime) = ?
            AND status IN ('pending', 'confirmed')
            AND start_time < ? AND end_time > ?
        ");
        $stmt->execute([$student_id, $date, $end_time, $start_time]);
        if ($stmt->fetch()) {
            throw new Exception('You already have a pending reservation for this time slot.');
        }

        // C. Check if ANOTHER student reserved this PC at this time (Cross-Student Duplicate)
        // This handles the "Lab 543 / PC-01" conflict
        $stmt = $pdo->prepare("
            SELECT r.id, s.fname, s.lname
            FROM reservations r
            JOIN students s ON r.student_id = s.id
            WHERE r.lab = ?
            AND r.pc_number = ?
            AND DATE(r.reservation_datetime) = ?
            AND r.status IN ('pending', 'confirmed')
            AND r.student_id != ?
            AND r.start_time < ? AND r.end_time > ?
        ");
        $stmt->execute([$lab, $pc_number, $date, $student_id, $end_time, $start_time]);

        $conflict = $stmt->fetch();
        if ($conflict) {
            throw new Exception("❌ Conflict detected! This PC is reserved by {$conflict['fname']} {$conflict['lname']} for this time.");
        }

        // D. Check Remaining Sessions
        $stmt = $pdo->prepare("SELECT remaining_session FROM students WHERE id = ? FOR UPDATE"); // FOR UPDATE locks the row
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if (!$student || $student['remaining_session'] <= 0) {
            throw new Exception('You have no remaining sessions.');
        }

        // E. Insert Reservation
        $reservation_datetime = $date . ' ' . $start_time;
        $stmt = $pdo->prepare("
            INSERT INTO reservations (student_id, lab, pc_number, purpose, reservation_datetime, start_time, end_time, remaining_session, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$student_id, $lab, $pc_number, $purpose, $reservation_datetime, $start_time, $end_time, $student['remaining_session']]);

        $pdo->commit();
        $_SESSION['success'] = '✅ Reservation submitted successfully! Waiting for admin approval.';
    } catch (Exception $e) {
        $pdo->rollBack(); // Undo everything if any error happens
        $_SESSION['error'] = $e->getMessage();
    }

    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
