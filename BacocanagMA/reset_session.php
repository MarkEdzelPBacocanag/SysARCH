<?php
session_start();
require 'database.php';

try {
    $pdo->exec("UPDATE students SET remaining_session = 30");
    $_SESSION['success'] = 'All sessions have been reset to 30';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error: ' . $e->getMessage();
}

header('Location: students.php');
exit;
?>