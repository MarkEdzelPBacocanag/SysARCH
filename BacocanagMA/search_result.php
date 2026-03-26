<?php
session_start();
require 'database.php';

$q = $_GET['q'] ?? '';

if (!$q) {
    header('Location: dashboard_admin.php');
    exit;
}

// Search by ID or Name (first name or last name)
$search = "%$q%";
$stmt = $pdo->prepare("SELECT id, fname, lname, course, course_level, remaining_session 
                       FROM students 
                       WHERE id LIKE ? OR fname LIKE ? OR lname LIKE ?");
$stmt->execute([$search, $search, $search]);
$results = $stmt->fetchAll();

// Display results (you can format this as a table or cards)
?>
<!DOCTYPE html>
<html>

<head>
    <title>Search Results</title>
    <link rel="stylesheet" href="style.css">
</head>

<body style="padding:2rem;">
    <h2>Search Results for "<?= htmlspecialchars($q) ?>"</h2>
    <a href="dashboard_admin.php">← Back to Dashboard</a>

    <table border="1" cellpadding="10" style="margin-top:1rem; width:100%; border-collapse:collapse;">
        <tr style="background:#53a6f4; color:white;">
            <th>ID</th>
            <th>Name</th>
            <th>Course</th>
            <th>Year</th>
            <th>Remaining</th>
            <th>Action</th>
        </tr>
        <?php if (count($results) > 0): ?>
            <?php foreach ($results as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['id']) ?></td>
                    <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                    <td><?= htmlspecialchars($row['course']) ?></td>
                    <td><?= htmlspecialchars($row['course_level']) ?></td>
                    <td><?= htmlspecialchars($row['remaining_session']) ?></td>
                    <td>
                        <button onclick="window.opener.postMessage({studentId:'<?= $row['id'] ?>', action:'startSitin'}, '*'); window.close();">
                            Select for Sit-in
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="6" style="text-align:center;">No students found</td>
            </tr>
        <?php endif; ?>
    </table>
</body>

</html>