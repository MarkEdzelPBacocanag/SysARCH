<?php
session_start();
require 'database.php';

// 1. Verify Admin Access
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// 2. Get Announcement ID from GET request (matches your <a href="...?id=..."> link)
$id = $_GET['id'] ?? '';

if (empty($id)) {
    $_SESSION['error'] = 'Invalid announcement ID.';
    header('Location: dashboard_admin.php');
    exit;
}

try {
    // 3. Safely delete using prepared statement
    $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$id]);

    $_SESSION['success'] = 'Announcement deleted successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to delete announcement.';
    error_log('Delete Announcement Error: ' . $e->getMessage());
}

// 4. Redirect back to admin dashboard
header('Location: dashboard_admin.php');
exit;
