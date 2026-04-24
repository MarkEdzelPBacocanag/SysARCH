<?php
session_start();
require 'database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student.php');
    exit;
}

$id = $_POST['id'] ?? '';
$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$mname = $_POST['mname'] ?? '';
$course = $_POST['course'] ?? '';
$course_level = $_POST['course_level'] ?? '';
$remaining = $_POST['remaining_session'] ?? 30;

if (empty($id)) {
    $_SESSION['error'] = 'Invalid student ID';
    header('Location: student.php');
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE students SET fname=?, lname=?, mname=?, course=?, course_level=?, remaining_session=? WHERE id=?");
    $stmt->execute([$fname, $lname, $mname, $course, $course_level, $remaining, $id]);
    $_SESSION['success'] = 'Student updated successfully';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: student.php');
exit;
