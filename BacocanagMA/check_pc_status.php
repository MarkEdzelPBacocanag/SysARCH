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
    // ✅ STEP 1: Check reservation status FIRST
    $stmt = $pdo->prepare("SELECT status FROM reservations WHERE lab = ? AND pc_number = ? AND DATE(reservation_datetime) = ? LIMIT 1");
    $stmt->execute([$lab, $pc, $target_date]);
    $reservation = $stmt->fetch();

    if ($reservation) {
        $res_status = $reservation['status'];

        // If pending → Reserved (waiting for admin approval)
        if ($res_status === 'pending') {
            echo json_encode(['status' => 'Reserved', 'color' => '#ffc107']);
            exit;
        }

        // If confirmed → Check if student has started the session
        if ($res_status === 'confirmed') {
            $stmt_session = $pdo->prepare("SELECT id FROM sitin_records WHERE lab = ? AND pc_number = ? AND status = 'active' LIMIT 1");
            $stmt_session->execute([$lab, $pc]);

            if ($stmt_session->fetch()) {
                // Confirmed + Active session = Occupied
                echo json_encode(['status' => 'Occupied', 'color' => '#dc3545']);
            } else {
                // Confirmed but not started yet = Available
                echo json_encode(['status' => 'Available', 'color' => '#28a745']);
            }
            exit;
        }
    }

    // ✅ STEP 2: No reservation found → Check active sit-in
    $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE lab = ? AND pc_number = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$lab, $pc]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'Occupied', 'color' => '#dc3545']);
        exit;
    }

    // ✅ STEP 3: No reservation, no active session → Available
    echo json_encode(['status' => 'Available', 'color' => '#28a745']);
} catch (PDOException $e) {
    error_log('PC Status Error: ' . $e->getMessage());
    echo json_encode(['status' => 'Error', 'color' => '#6c757d']);
}
