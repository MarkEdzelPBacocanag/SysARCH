<?php
session_start();
// This file handles the registration form submission
// It connects to the database, validates the input fields, and inserts the data if valid

// Database configuration variables
$host = 'localhost'; // Database host
$db = 'mydatabest'; // Database name (change to your actual database name)
$user = 'root'; // Database username (default for XAMPP)
$pass = ''; // Database password (leave empty for XAMPP default)
$charset = 'utf8mb4'; // Character set for the connection

// Data Source Name (DSN) for PDO connection
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Options for PDO connection
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Throw exceptions on errors
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays
    PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
];

// Attempt to connect to the database
try {
    $pdo = new PDO($dsn, $user, $pass, $options); // Create PDO instance
} catch (\PDOException $e) {
    // If connection fails, display error and stop execution
    die('Database connection failed: ' . $e->getMessage());
}

// Check if the form was submitted via POST method
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data, using null coalescing operator to avoid undefined index errors
    $id = $_POST['id'] ?? ''; // ID number
    $fname = $_POST['fname'] ?? ''; // First name
    $lname = $_POST['lname'] ?? ''; // Last name
    $mname = $_POST['mname'] ?? ''; // Middle name (optional)
    $course_level = $_POST['course_level'] ?? ''; // Course level (1st, 2nd, etc.)
    $password = $_POST['password'] ?? ''; // Password
    $confirm_password = $_POST['confirm-password'] ?? ''; // Confirm password
    $email = $_POST['email'] ?? ''; // Email
    $course = $_POST['course'] ?? ''; // Course (BSCS or BSIT)
    $address = $_POST['address'] ?? ''; // Address

    // Array to hold validation errors keyed by field
    $errors = [];

    // Validate each required field (check if not empty)
    if (empty($id)) {
        $errors['id'] = 'ID number is required';
    }
    if (empty($fname)) {
        $errors['fname'] = 'First name is required';
    }
    if (empty($lname)) {
        $errors['lname'] = 'Last name is required';
    }
    if (empty($course_level)) {
        $errors['course_level'] = 'Course level is required';
    }
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    if (empty($confirm_password)) {
        $errors['confirm-password'] = 'Confirm password is required';
    }
    if (!empty($password) && $password !== $confirm_password) {
        $errors['confirm-password'] = 'Passwords do not match';
    }
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    }
    if (empty($course)) {
        $errors['course'] = 'Course is required';
    }
    if (empty($address)) {
        $errors['address'] = 'Address is required';
    }

    // If there are validation errors, save them and the old values, then redirect back
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        // keep all posted values except passwords for security
        $old = $_POST;
        unset($old['password'], $old['confirm-password']);
        $_SESSION['old'] = $old;
        header('Location: register.php');
        exit;
    }

    // Otherwise, proceed with database insertion
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare SQL statement to insert data into 'students' table
        // Assumes table 'students' exists with columns: id, fname, lname, mname, course_level, password, email, course, address
        $stmt = $pdo->prepare("INSERT INTO students (id, fname, lname, mname, course_level, password, email, course, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Execute the statement with the form data
        $stmt->execute([$id, $fname, $lname, $mname, $course_level, $hashed_password, $email, $course, $address]);

        // Display success message
        // you could redirect to login/index page instead
        echo 'Registration successful!';
}
?>