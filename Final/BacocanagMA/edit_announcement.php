<?php
session_start();
require 'database.php';

// 1. Verify Admin Access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $content = trim($_POST['content'] ?? '');

    if (!empty($id) && !empty($content)) {
        try {
            // Update the announcement content
            $stmt = $pdo->prepare("UPDATE announcements SET content = ? WHERE id = ?");
            $stmt->execute([$content, $id]);
            $_SESSION['success'] = 'Announcement updated successfully!';
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error updating announcement.';
        }
    } else {
        $_SESSION['error'] = 'Content cannot be empty.';
    }
}

// 3. Redirect back to dashboard
header('Location: dashboard_admin.php');
exit;
