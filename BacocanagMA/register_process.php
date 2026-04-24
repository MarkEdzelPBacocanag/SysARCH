<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$id = trim($_POST['id'] ?? '');
$fname = trim($_POST['fname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$mname = trim($_POST['mname'] ?? '');
$course_level = $_POST['course_level'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm-password'] ?? '';
$email = trim($_POST['email'] ?? '');
$course = $_POST['course'] ?? '';
$address = trim($_POST['address'] ?? '');

$errors = [];

// Validation
if ($id === '') $errors['id'] = 'ID number is required';
if ($fname === '') $errors['fname'] = 'First name is required';
if ($lname === '') $errors['lname'] = 'Last name is required';
if ($course_level === '') $errors['course_level'] = 'Year Level is required';
if ($password === '') $errors['password'] = 'Password is required';
if ($confirm_password === '') $errors['confirm-password'] = 'Confirm password is required';
if ($password !== '' && $confirm_password !== '' && $password !== $confirm_password) {
    $errors['confirm-password'] = 'Passwords do not match';
}
if ($email === '') $errors['email'] = 'Email is required';
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email format';
if ($course === '') $errors['course'] = 'Course is required';
if ($address === '') $errors['address'] = 'Address is required';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $old = $_POST;
    unset($old['password'], $old['confirm-password']);
    $_SESSION['old'] = $old;
    header('Location: register.php');
    exit;
}

try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // FIX: Explicitly define role and remaining_session
    $stmt = $pdo->prepare("INSERT INTO students (
        id, fname, lname, mname, course_level, password, 
        email, course, address, role, remaining_session
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'student', 30)");

    $stmt->execute([
        $id,
        $fname,
        $lname,
        $mname,
        $course_level,
        $hashed_password,
        $email,
        $course,
        $address
    ]);

    $_SESSION['success_message'] = 'Registration successful! Please log in.';
    header('Location: index.php');
    exit;
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        $errors['id'] = 'This ID or Email is already registered.';
    } else {
        $errors['db_error'] = 'An error occurred. Please try again.';
        error_log('Registration Error: ' . $e->getMessage());
    }

    $_SESSION['errors'] = $errors;
    $old = $_POST;
    unset($old['password'], $old['confirm-password']);
    $_SESSION['old'] = $old;
    header('Location: register.php');
    exit;
}
