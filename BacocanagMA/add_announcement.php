<?php
session_start();
require 'database.php';

// 1. Verify Admin Access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $announcement = trim($_POST['announcement'] ?? '');

    if (!empty($announcement)) {
        try {
            // MySQL will AUTOMATICALLY generate a unique 'id' because of AUTO_INCREMENT
            $stmt = $pdo->prepare("INSERT INTO announcements (admin_id, content) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $announcement]);

            // Optional: Log the newly created ID for debugging
            // $new_id = $pdo->lastInsertId();

            $_SESSION['success'] = 'Announcement posted successfully!';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error posting announcement: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Announcement content is required';
    }
}

// 3. Redirect back to dashboard (shows updated list + toast message)
header('Location: dashboard_admin.php');
exit;
