<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? '';
// ✅ ADD THIS BLOCK
if ($id) {
    try {
        // 1. Verify record exists & get student_id
        $stmt = $pdo->prepare("SELECT student_id FROM sitin_records WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();
        if (!$record) throw new Exception('Record not found.');

        // 2. Check if student already has an active session
        $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE student_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$record['student_id']]);
        if ($stmt->fetch()) throw new Exception('Student already has an active session.');

        // 3. Proceed to activate
        $stmt = $pdo->prepare("UPDATE sitin_records SET status = 'active', date_created = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Session started successfully.';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error starting session: ' . $e->getMessage();
    }
}
header('Location: sitin_records.php');
exit;
