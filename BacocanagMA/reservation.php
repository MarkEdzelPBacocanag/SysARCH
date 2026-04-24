<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle Accept/Decline Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $res_id = $_POST['reservation_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!empty($res_id)) {
        try {
            $pdo->beginTransaction();

            if ($action === 'accept') {
                // 1. Get details to create Sit-in record
                $stmt = $pdo->prepare("SELECT student_id, pc_number, purpose, lab FROM reservations WHERE id = ?");
                $stmt->execute([$res_id]);
                $res = $stmt->fetch();

                if ($res) {
                    // 2. Check if PC is occupied (just in case)
                    $stmt = $pdo->prepare("SELECT id FROM sitin_records WHERE pc_number = ? AND status = 'active' LIMIT 1");
                    $stmt->execute([$res['pc_number']]);

                    if (!$stmt->fetch()) {
                        // 3. Update Reservation to Confirmed
                        $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
                        $stmt->execute([$res_id]);

                        // 4. Deduct Session
                        $stmt = $pdo->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id = ?");
                        $stmt->execute([$res['student_id']]);

                        // ✅ NEW: Create Sit-in Record (Status: Pending)
                        $stmt = $pdo->prepare("INSERT INTO sitin_records (student_id, purpose, lab, pc_number, date_created, status) VALUES (?, ?, ?, ?, NOW(), 'pending')");
                        $stmt->execute([$res['student_id'], $res['purpose'], $res['lab'], $res['pc_number']]);

                        $_SESSION['success'] = '✅ Reservation accepted & added to Sit-in Records.';
                    } else {
                        $_SESSION['error'] = '❌ Cannot accept: This PC is already occupied.';
                    }
                }
            } elseif ($action === 'decline') {
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'declined' WHERE id = ?");
                $stmt->execute([$res_id]);
                $_SESSION['success'] = 'Reservation declined.';
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error processing reservation.';
        }
        header('Location: reservation.php');
        exit;
    }
}

// Fetch Reservations
$stmt = $pdo->query("SELECT r.*, s.fname, s.lname, s.id as student_id 
                     FROM reservations r 
                     JOIN students s ON r.student_id = s.id 
                     ORDER BY r.created_at DESC");
$reservations = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Reservations</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>College of Computer Studies Admin</h2>
        </div>
        <div class="link-ref">
            <a href="dashboard_admin.php">
                <p>Home</p>
            </a>
            <a href="search_results.php">
                <p>Search</p>
            </a>
            <a href="student.php">
                <p>Students</p>
            </a>
            <div class="dropdown">
                <span>Sit-in ▾</span>
                <ul class="dropdown-content">
                    <li><a data-modal-open="sitinModal">Add Sit-in</a></li>
                    <li><a href="sitin_records.php">View Sit-in Records</a></li>
                    <li><a href="sitin_reports.php">Sit-in Reports</a></li>
                </ul>
            </div>
            <a href="feedback_reports.php">
                <p>Feedback</p>
            </a>
            <a href="reservation.php">
                <p>Reservations</p>
            </a>
            <button class="logout-button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <div class="page-container">
        <h1 class="page-title">Reservation Requests</h1>

        <div class="toast-container">
            <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Purpose</th>
                    <th>Lab & PC</th>
                    <th>Date & Time</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reservations as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?> <br><small>(<?= htmlspecialchars($row['student_id']) ?>)</small></td>
                        <td><?= htmlspecialchars($row['purpose']) ?></td>
                        <td><?= htmlspecialchars($row['lab']) ?> / <strong><?= htmlspecialchars($row['pc_number']) ?></strong></td>
                        <td><?= date('M d, Y h:i A', strtotime($row['reservation_datetime'])) ?></td>
                        <td>
                            <span class="<?= $row['status'] === 'confirmed' ? 'alert-success' : ($row['status'] === 'declined' ? 'alert-error' : '') ?>"
                                style="padding: 4px 8px; border-radius: 4px; font-weight: bold;">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($row['status'] === 'pending'): ?>
                                <form method="POST" style="display:inline-flex; gap:5px;">
                                    <input type="hidden" name="reservation_id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="action" value="accept" class="post-btn">Accept</button>
                                    <button type="submit" name="action" value="decline" class="btn btn-danger">Decline</button>
                                </form>
                            <?php else: ?>
                                <span style="color:#888;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>