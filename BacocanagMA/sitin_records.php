<?php
session_start();
require 'database.php';

// Check Admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// Search/Filter logic
$search = $_GET['search'] ?? '';
$sql = "SELECT r.id, r.student_id, r.purpose, r.lab, r.date_created, r.status, s.fname, s.lname 
        FROM sitin_records r 
        JOIN students s ON r.student_id = s.id 
        WHERE s.id LIKE ? OR s.fname LIKE ? OR s.lname LIKE ?
        ORDER BY r.date_created DESC";

$stmt = $pdo->prepare($sql);
$param = "%$search%";
$stmt->execute([$param, $param, $param]);
$records = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sit-in Records</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;"><h2>Sit-in Records</h2></div>
        <div class="link-ref">
            <div><a href="dashboard_admin.php">Back to Dashboard</a></div>
        </div>
    </div>

    <div class="page-container" style="padding: 2rem;">
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
                    <th>Lab</th>
                    <th>Date/Time In</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($records as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['student_id']) ?></td>
                    <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                    <td><?= htmlspecialchars($row['lab']) ?></td>
                    <td><?= date('M d, Y h:i A', strtotime($row['date_created'])) ?></td>
                    <td>
                        <?php if ($row['status'] == 'active'): ?>
                            <span style="color: green; font-weight: bold;">In Session</span>
                        <?php else: ?>
                            <span style="color: gray;">Completed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['status'] == 'active'): ?>
                            <a href="end_sitin.php?id=<?= $row['id'] ?>" class="btn btn-danger" onclick="return confirm('End this session?');">End Session</a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>