<?php
session_start();
require 'database.php';

// 1. Admin Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// 2. Handle Reward Action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');

    if (empty($student_id)) {
        $_SESSION['error'] = 'Invalid student ID.';
        header('Location: student.php');
        exit;
    }

    try {
        $pdo->beginTransaction();

        // Lock row to prevent duplicate clicks/race conditions
        $stmt = $pdo->prepare("SELECT reward_points, remaining_session, total_rewards_earned FROM students WHERE id = ? AND role = 'student' FOR UPDATE");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if (!$student) {
            throw new Exception('Student not found.');
        }

        $new_points = $student['reward_points'] + 1;
        $new_sessions = $student['remaining_session'];
        $total_rewards = $student['total_rewards_earned'] + 1; // ✅ Increment total
        $message = '✅ +1 Reward point added!';

        // 🏆 Convert 3 points to 1 session
        if ($new_points >= 3) {
            $new_points = 0;          // Reset points
            $new_sessions += 1;       // Add 1 session
            $message = '🏆 3 points reached! +1 session added. Points reset.';
        }

        // Update database with BOTH current points AND total rewards
        $stmt = $pdo->prepare("UPDATE students SET reward_points = ?, remaining_session = ?, total_rewards_earned = ? WHERE id = ?");
        $stmt->execute([$new_points, $new_sessions, $total_rewards, $student_id]);

        // ✅ INSERT NOTIFICATION FOR STUDENT (Safe inside transaction)
        $notif_title = ($new_points === 0) ? 'Session Awarded!' : 'Reward Point Added';
        $notif_msg   = ($new_points === 0)
            ? "Congratulations! Your 3 reward points converted into +1 free session."
            : "You earned +1 reward point! Collect 3 points total to get a free session.";

        $stmt_notif = $pdo->prepare("INSERT INTO notifications (student_id, title, message, type) VALUES (?, ?, ?, 'reward')");
        $stmt_notif->execute([$student_id, $notif_title, $notif_msg]);

        $pdo->commit();
        $_SESSION['success'] = $message;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

header('Location: student.php');
exit;
