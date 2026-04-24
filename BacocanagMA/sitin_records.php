<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$search = $_GET['search'] ?? '';
$sql = "SELECT r.id, r.student_id, r.purpose, r.lab, r.pc_number, r.date_created, r.date_ended, r.status, s.fname, s.lname
        FROM sitin_records r
        JOIN students s ON r.student_id = s.id
        WHERE s.id LIKE ? OR s.fname LIKE ? OR s.lname LIKE ? OR r.purpose LIKE ?
        ORDER BY
        CASE r.status
            WHEN 'active' THEN 1
            WHEN 'pending' THEN 2
            ELSE 3
        END,
        r.date_created DESC";

$stmt = $pdo->prepare($sql);
$param = "%$search%";
$stmt->execute([$param, $param, $param, $param]);
$records = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sit-in Sessions</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Sit-in Sessions</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_admin.php">
                    <p>Back to Dashboard</p>
                </a></div>
            <div><a href="reservation.php">
                    <p>Reservations</p>
                </a></div>
        </div>
    </div>

    <div class="page-container" style="padding: 2rem;">
        <h1 class="page-title">Manage Sessions</h1>

        <!-- TOAST NOTIFICATIONS -->
        <div class="toast-container">
            <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        </div>

        <div class="table-controls">
            <form method="GET">
                <input type="text" name="search" placeholder="Search ID or Name" value="<?= htmlspecialchars($search) ?>" style="padding: 0.5rem; width: 250px;">
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab & PC</th>
                    <th>Start Time</th>
                    <th>End Time</th> <!-- ✅ NEW COLUMN -->
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($records) > 0): ?>
                    <?php foreach ($records as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                            <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                            <td><?= htmlspecialchars($row['purpose']) ?></td>
                            <td><?= htmlspecialchars($row['lab']) ?> / <?= htmlspecialchars($row['pc_number'] ?? 'N/A') ?></td>
                            <td><?= $row['date_created'] ? date('M d, Y h:i A', strtotime($row['date_created'])) : '-' ?></td>

                            <!-- ✅ END TIME LOGIC -->
                            <td>
                                <?php if ($row['status'] === 'completed' && $row['date_ended']): ?>
                                    <?= date('M d, Y h:i A', strtotime($row['date_ended'])) ?>
                                <?php elseif ($row['status'] === 'active'): ?>
                                    <span style="color: #ffc107; font-style: italic;">Ongoing...</span>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($row['status'] === 'active'): ?>
                                    <span style="color: green; font-weight: bold;">Active</span>
                                <?php elseif ($row['status'] === 'pending'): ?>
                                    <span style="color: #ffc107; font-weight: bold;">Pending Start</span>
                                <?php else: ?>
                                    <span style="color: gray;">Completed</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <a href="activate_sitin.php?id=<?= $row['id'] ?>" class="btn btn-primary" style="padding: 4px 8px; font-size: 0.8rem;">▶ Start</a>
                                <?php elseif ($row['status'] === 'active'): ?>
                                    <a href="end_sitin.php?id=<?= $row['id'] ?>" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8rem;" onclick="return confirm('End this session?');">⏹ End</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- ✅ Updated colspan to 8 for the new column -->
                    <tr>
                        <td colspan="8" class="no-data">No records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>