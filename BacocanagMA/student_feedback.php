<?php
session_start();
require 'database.php';

// Check Login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit;
}

// Handle Feedback Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $record_id = $_POST['record_id'] ?? null;

    if (!empty($message) && $record_id) {
        try {
            // Insert feedback into database with 'pending' status
            $stmt = $pdo->prepare("INSERT INTO feedbacks (student_id, record_id, message, status) VALUES (?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $record_id, $message]);
            $_SESSION['success'] = "✅ Feedback submitted successfully! The admin will review it.";
        } catch (PDOException $e) {
            $_SESSION['error'] = "❌ Error submitting feedback: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "⚠️ Feedback message cannot be empty or invalid record.";
    }

    // Redirect back to history page
    header("Location: student_history.php");
    exit;
}
