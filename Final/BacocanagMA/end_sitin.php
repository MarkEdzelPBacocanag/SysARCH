<?php
session_start();
require 'database.php';

// 1. Admin Security Check
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'] ?? '';

if ($id) {
    try {
        $pdo->beginTransaction();

        // 2. Fetch record to check current status & get student_id
        $stmt = $pdo->prepare("SELECT student_id, status FROM sitin_records WHERE id = ?");
        $stmt->execute([$id]);
        $record = $stmt->fetch();

        if ($record && $record['status'] !== 'completed') {
            // 3. Update sit-in record to completed
            $stmt = $pdo->prepare("UPDATE sitin_records SET status = 'completed', date_ended = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            // 4. ✅ AUTOMATICALLY COUNT AS 1 COMPLETED TASK
            $stmt = $pdo->prepare("UPDATE students SET tasks_completed = tasks_completed + 1 WHERE id = ?");
            $stmt->execute([$record['student_id']]);

            $pdo->commit();
            $_SESSION['success'] = '✅ Session ended & task counted successfully.';
        } else {
            $pdo->rollBack();
            $_SESSION['error'] = '️ Session is already completed or invalid.';
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = '❌ Error ending session: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}

header('Location: sitin_records.php');
exit;
