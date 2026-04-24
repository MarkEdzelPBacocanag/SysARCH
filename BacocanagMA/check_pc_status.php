<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
require 'database.php';

$lab = trim($_GET['lab'] ?? '');
$pc = trim($_GET['pc'] ?? '');
$target_date = $_GET['date'] ?? date('Y-m-d');

if (empty($lab) || empty($pc)) {
    echo json_encode(['status' => 'unknown', 'color' => '#6c757d']);
    exit;
}

try {
    // Check active sit-in
    $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE lab = ? AND pc_number = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$lab, $pc]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'Occupied', 'color' => '#dc3545']);
        exit;
    }

    // Check reservation for target date
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE lab = ? AND pc_number = ? AND DATE(reservation_datetime) = ? AND status IN ('pending','confirmed') LIMIT 1");
    $stmt->execute([$lab, $pc, $target_date]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'Reserved', 'color' => '#ffc107']);
        exit;
    }

    echo json_encode(['status' => 'Available', 'color' => '#28a745']);
} catch (PDOException $e) {
    error_log('PC Status Error: ' . $e->getMessage());
    echo json_encode(['status' => 'Error', 'color' => '#6c757d']);
}
