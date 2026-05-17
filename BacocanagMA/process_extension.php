<?php
session_start();
require 'database.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$record_id = $input['record_id'] ?? 0;
$minutes = intval($input['minutes'] ?? 0);

if (!$record_id || $minutes <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Get current session details
    $stmt = $pdo->prepare("SELECT id, student_id, end_time, extension_count, lab, pc_number, DATE(date_created) as session_date FROM sitin_records WHERE id = ? AND student_id = ? AND status = 'active' FOR UPDATE");
    $stmt->execute([$record_id, $_SESSION['user_id']]);
    $session = $stmt->fetch();

    if (!$session) throw new Exception("Session not found.");
    if ($session['extension_count'] >= 5) throw new Exception("Maximum extensions (5) reached.");

    // 2. Calculate New End Time
    $currentEnd = $session['end_time']; // e.g., 14:00:00
    $newEnd = date('H:i:s', strtotime($currentEnd . " +{$minutes} minutes"));

    // 3. CHECK CONFLICT: Is this PC reserved by someone else at the new time?
    // We check if there is an active session OR a confirmed reservation overlapping the new end time
    $stmt = $pdo->prepare("
        SELECT id FROM sitin_records 
        WHERE lab = ? AND pc_number = ? AND status = 'active' AND id != ?
        UNION
        SELECT id FROM reservations 
        WHERE lab = ? AND pc_number = ? AND DATE(reservation_datetime) = ? 
        AND status IN ('pending', 'confirmed')
        AND (
            start_time < ? AND end_time > ?
        )
    ");
    $stmt->execute([
        $session['lab'],
        $session['pc_number'],
        $record_id, // Active sessions
        $session['lab'],
        $session['pc_number'],
        $session['session_date'], // Reservations
        $newEnd,
        $currentEnd // Time overlap logic: NewEnd < ExistingEnd AND NewEnd > ExistingStart
    ]);

    $conflict = $stmt->fetch();

    if ($conflict) {
        // ❌ CONFLICT: Requires Admin Approval
        throw new Exception("Time slot is reserved by another student. Extension requires Admin Approval.");
    }

    // 4. ✅ AUTO-APPROVE: No conflict found
    $stmt = $pdo->prepare("UPDATE sitin_records SET end_time = ?, extension_count = extension_count + 1 WHERE id = ?");
    $stmt->execute([$newEnd, $record_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "Extended by {$minutes} mins. New end time: {$newEnd}"]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
