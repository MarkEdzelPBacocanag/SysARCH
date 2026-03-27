<?php
session_start();
require 'database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $announcement = trim($_POST['announcement'] ?? '');
    
    if (!empty($announcement)) {
        try {
            // Use the logged-in admin's ID from session
            $stmt = $pdo->prepare("INSERT INTO announcements (admin_id, content) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $announcement]);
            
            $_SESSION['success'] = 'Announcement posted successfully!';
            header('Location: dashboard_admin.php');
            exit;
        } catch(PDOException $e) {
            $_SESSION['error'] = 'Error posting announcement: ' . $e->getMessage();
            header('Location: dashboard_admin.php');
            exit;
        }
    } else {
        $_SESSION['error'] = 'Announcement content is required';
        header('Location: dashboard_admin.php');
        exit;
    }
}

header('Location: dashboard_admin.php');
exit;
?>