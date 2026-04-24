<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// --- HANDLE ACCEPT/DECLINE LOGIC ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $res_id = $_POST['reservation_id'] ?? '';
    $action = $_POST['action'] ?? '';

    if (!empty($res_id)) {
        try {
            $pdo->beginTransaction();

            if ($action === 'accept') {
                // Get student ID to deduct session
                $stmt = $pdo->prepare("SELECT student_id FROM reservations WHERE id = ?");
                $stmt->execute([$res_id]);
                $res = $stmt->fetch();

                if ($res) {
                    $stmt = $pdo->prepare("UPDATE reservations SET status = 'confirmed' WHERE id = ?");
                    $stmt->execute([$res_id]);

                    // Deduct session
                    $stmt = $pdo->prepare("UPDATE students SET remaining_session = remaining_session - 1 WHERE id = ?");
                    $stmt->execute([$res['student_id']]);

                    $_SESSION['success'] = 'Reservation accepted & session deducted.';
                }
            } elseif ($action === 'decline') {
                $stmt = $pdo->prepare("UPDATE reservations SET status = 'declined' WHERE id = ?");
                $stmt->execute([$res_id]);
                $_SESSION['success'] = 'Reservation declined.';
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error processing request.';
        }
        header('Location: reservation.php');
        exit;
    }
}

// --- FETCH DATA ---
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
            <h2>Reservation Management</h2>
        </div>
        <div class="link-ref">
            <a href="dashboard_admin.php">
                <p>Home</p>
            </a>
            <a href="student.php">
                <p>Students</p>
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
                        <td><?= htmlspecialchars($row['lab']) ?> / <?= htmlspecialchars($row['pc_number']) ?></td>
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