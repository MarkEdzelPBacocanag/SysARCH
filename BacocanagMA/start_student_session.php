<?php
session_start();
require 'database.php';

// 1. Student Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: index.php');
    exit;
}

// 2. Get Record ID
$record_id = $_POST['record_id'] ?? '';

if (!empty($record_id)) {
    try {
        // 3. Verify: Only the owner can start it, and only if status is 'pending'
        $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE id = ? AND student_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$record_id, $_SESSION['user_id']]);

        if ($stmt->fetch()) {
            // 4. Update status to 'active' and set exact start time to NOW()
            $stmt = $pdo->prepare("UPDATE sitin_records SET status = 'active', date_created = NOW() WHERE id = ?");
            $stmt->execute([$record_id]);
            $_SESSION['success'] = '✅ Session started successfully!';
        } else {
            $_SESSION['error'] = '❌ Invalid or already started session.';
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error starting session.';
        error_log($e->getMessage());
    }
}

// 5. Redirect back to dashboard
header('Location: dashboard_student.php');
exit;
