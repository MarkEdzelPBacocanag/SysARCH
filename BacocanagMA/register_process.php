<?php
// ---- register_process.php ----

session_start();
// Include the central database connection file
require 'database.php';

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data, using null coalescing operator to avoid undefined index errors
    $id = $_POST['id'] ?? '';
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $mname = $_POST['mname'] ?? '';
    $course_level = $_POST['course_level'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm-password'] ?? '';
    $email = $_POST['email'] ?? '';
    $course = $_POST['course'] ?? '';
    $address = $_POST['address'] ?? '';

    // Array to hold validation errors
    $errors = [];

    // --- All your validation logic stays the same ---
    if (empty($id)) $errors['id'] = 'ID number is required';
    if (empty($fname)) $errors['fname'] = 'First name is required';
    if (empty($lname)) $errors['lname'] = 'Last name is required';
    if (empty($course_level)) $errors['course_level'] = 'Course level is required';
    if (empty($password)) $errors['password'] = 'Password is required';
    if (empty($confirm_password)) $errors['confirm-password'] = 'Confirm password is required';
    if (!empty($password) && $password !== $confirm_password) {
        $errors['confirm-password'] = 'Passwords do not match';
    }
    if (empty($email)) $errors['email'] = 'Email is required';
    if (empty($course)) $errors['course'] = 'Course is required';
    if (empty($address)) $errors['address'] = 'Address is required';
    // --- End of validation ---

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $old = $_POST;
        unset($old['password'], $old['confirm-password']);
        $_SESSION['old'] = $old;
        header('Location: register.php');
        exit;
    }

    // If no errors, proceed with database insertion
    try {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement
        $stmt = $pdo->prepare("INSERT INTO students (id, fname, lname, mname, course_level, password, email, course, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Execute the statement with the form data
        $stmt->execute([$id, $fname, $lname, $mname, $course_level, $hashed_password, $email, $course, $address]);

        // Redirect to the login page with a success message
        $_SESSION['success_message'] = 'Registration successful! Please log in.';
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        // Check for a duplicate entry error
        if ($e->getCode() == 23000) {
            $errors['id'] = 'This ID number is already registered.';
        } else {
            // For other database errors, you can show a generic message
            $errors['db_error'] = 'An error occurred. Please try again.';
            // Log the detailed error for the admin to see
            error_log('Registration Error: ' . $e->getMessage());
        }

        $_SESSION['errors'] = $errors;
        $old = $_POST;
        unset($old['password'], $old['confirm-password']);
        $_SESSION['old'] = $old;
        header('Location: register.php');
        exit;
    }
}
