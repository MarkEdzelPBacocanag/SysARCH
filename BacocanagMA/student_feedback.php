<?php
session_start();
require 'database.php';

// Check Login
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message)) {
        $error = "Please enter your feedback message.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO feedbacks (student_id, message, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$_SESSION['user_id'], $message]);
            $success = "Feedback submitted successfully!";
        } catch (PDOException $e) {
            $error = "Error submitting feedback.";
        }
    }
}

// Get Student Name
$stmt = $pdo->prepare("SELECT fname, lname FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Feedback</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .feedback-box { max-width: 600px; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        textarea { width: 100%; height: 150px; padding: 10px; border: 1px solid #ccc; border-radius: 5px; resize: vertical; }
    </style>
</head>
<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;"><h2>Student Feedback</h2></div>
        <div class="link-ref">
            <a href="dashboard_student.php"><p>Home</p></a>
            <button class="logout-button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <div class="feedback-box">
        <h3>Send Feedback</h3>
        <p>Welcome, <?= htmlspecialchars($student['fname']) ?>.</p>
        
        <?php if ($success): ?>
            <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <?= $success ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 10px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <textarea name="message" placeholder="Type your feedback, report, or suggestion here..." required></textarea>
            <div style="margin-top: 10px; text-align: right;">
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </div>
        </form>
    </div>
</body>
</html>