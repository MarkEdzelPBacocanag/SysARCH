<?php
session_start();
require 'database.php';

// Check if student is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'student') {
    header("Location: dashboard_admin.php");
    exit;
}

// Get student's sit-in history
$stmt = $pdo->prepare("SELECT * FROM sitin_records 
                       WHERE student_id = ? 
                       ORDER BY date_created DESC");
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sit-in History</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .history-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .history-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
        }

        .card-header h2 {
            margin: 0;
        }

        .card-body {
            padding: 20px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .status-completed {
            background: #e2e3e5;
            color: #6c757d;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .no-history {
            text-align: center;
            padding: 40px;
            color: #999;
        }
    </style>
</head>
<body>
    <!-- NAVIGATION BAR -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>My History</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_student.php">Home</a></div>
            <div><a href="notifications.php">Notifications</a></div>
            <div><a href="edit_profile.php">Edit Profile</a></div>
            <div><a href="student_history.php">History</a></div>
            <div><a href="student_reservations.php">Reservations</a></div>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="history-container">
        <div class="history-card">
            <div class="card-header">
                <h2>📜 My Sit-in History</h2>
            </div>
            <div class="card-body">
                <?php if (count($history) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date & Time</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['date_created'])) ?></td>
                                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                                    <td><?= htmlspecialchars($row['lab']) ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-completed">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-history">
                        <p>📭 You have no sit-in history yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>