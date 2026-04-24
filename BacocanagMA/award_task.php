<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? '';
    $tasks = intval($_POST['tasks'] ?? 1);

    if (!empty($student_id) && $tasks > 0) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET tasks_completed = tasks_completed + ? WHERE id = ? AND role = 'student'");
            $stmt->execute([$tasks, $student_id]);
            $_SESSION['success'] = "✅ Awarded $tasks task(s) to student $student_id";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error awarding task: " . $e->getMessage();
        }
    }
}
header('Location: student.php');
exit;
