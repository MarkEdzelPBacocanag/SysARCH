<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: student.php'); // ✅ Fixed: was students.php
    exit;
}

$id = $_POST['id'] ?? '';
$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$mname = $_POST['mname'] ?? '';
$email = $_POST['email'] ?? '';
$course = $_POST['course'] ?? '';
$course_level = $_POST['course_level'] ?? '';
$password = $_POST['password'] ?? '';
$address = $_POST['address'] ?? '';

if (empty($id) || empty($fname) || empty($lname) || empty($email) || empty($password)) {
    $_SESSION['error'] = 'Please fill all required fields';
    header('Location: student.php'); // ✅ Fixed
    exit;
}

try {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO students (id, fname, lname, mname, email, course, course_level, password, address, remaining_session, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 30, 'student')");
    $stmt->execute([$id, $fname, $lname, $mname, $email, $course, $course_level, $hashed, $address]);
    $_SESSION['success'] = 'Student added successfully';
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $_SESSION['error'] = 'Student ID or Email already exists';
    } else {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

header('Location: student.php'); // ✅ Fixed
exit;
