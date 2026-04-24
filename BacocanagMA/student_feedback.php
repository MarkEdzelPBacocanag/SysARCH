<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $record_id = $_POST['record_id'] ?? null;

    if (!empty($message) && $record_id) {
        try {
            // Verify this record belongs to the logged-in student
            $check = $pdo->prepare("SELECT id FROM sitin_records WHERE id = ? AND student_id = ?");
            $check->execute([$record_id, $_SESSION['user_id']]);

            if ($check->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO feedbacks (student_id, record_id, message, status) VALUES (?, ?, ?, 'pending')");
                $stmt->execute([$_SESSION['user_id'], $record_id, $message]);
                $_SESSION['success'] = "✅ Feedback submitted successfully! The admin will review it.";
            } else {
                $_SESSION['error'] = "⚠️ Invalid record ID.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "❌ Error submitting feedback: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "⚠️ Feedback message cannot be empty or invalid.";
    }
    header("Location: student_history.php");
    exit;
}
