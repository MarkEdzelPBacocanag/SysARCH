<?php
session_start();
require 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $password = $_POST['password'] ?? '';

    $errors = [];

    if ($id === '') $errors['id'] = 'ID is required';
    if ($password === '') $errors['password'] = 'Password is required';

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT id, fname, password, role FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors['password'] = 'Invalid ID or password';
        }
    }

    if ($errors) {
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = ['id' => $id];
        header("Location: index.php");
        exit;
    }

    // Login OK
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['fname'];
    $_SESSION['role'] = $user['role']; // 'admin' or 'student'

    // Redirect based on role
    if ($user['role'] === 'admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_student.php");
    }
    exit;
}
