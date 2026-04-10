<?php
session_start();
require 'database.php';

// 1. Admin Check
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// 2. Get Filters from URL
$lab_filter = $_GET['lab'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// 3. Build Query (Same logic as sitin_reports.php)
$query = "SELECT r.*, s.fname, s.lname
          FROM sitin_records r
          JOIN students s ON r.student_id = s.id
          WHERE 1=1";
$params = [];

if (!empty($lab_filter)) {
    $query .= " AND r.lab = ?";
    $params[] = $lab_filter;
}
if (!empty($date_from)) {
    $query .= " AND DATE(r.date_created) >= ?";
    $params[] = $date_from;
}
if (!empty($date_to)) {
    $query .= " AND DATE(r.date_created) <= ?";
    $params[] = $date_to;
}
$query .= " ORDER BY r.date_created DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// 4. Output CSV Headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=sitin_report.csv');

// 5. Create File Handle
$output = fopen('php://output', 'w');

// 6. Write CSV Header Row
fputcsv($output, ['Date/Time', 'Student ID', 'Student Name', 'Purpose', 'Lab', 'Status']);

// 7. Write Data Rows
foreach ($reports as $row) {
    fputcsv($output, [
        $row['date_created'],
        $row['student_id'],
        $row['fname'] . ' ' . $row['lname'],
        $row['purpose'],
        $row['lab'],
        $row['status']
    ]);
}

fclose($output);
exit;
?>