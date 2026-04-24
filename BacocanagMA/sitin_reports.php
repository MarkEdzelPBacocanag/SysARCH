<?php
session_start();
require 'database.php';

// Check Admin
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$lab_filter = $_GET['lab'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build Query
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Sit-in Reports</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .filter-form {
            display: flex;
            gap: 1rem;
            background: white;
            padding: 1rem;
            margin-bottom: 1rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
        }
    </style>
</head>

<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Sit-in Reports</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_admin.php">Back to Dashboard</a></div>
        </div>
    </div>

    <div class="page-container">
        <!-- FILTER FORM -->
        <form method="GET" class="filter-form" id="reportForm">
            <div class="filter-group">
                <label>Date From:</label>
                <input type="date" name="date_from" value="<?= $date_from ?>">
            </div>
            <div class="filter-group">
                <label>Date To:</label>
                <input type="date" name="date_to" value="<?= $date_to ?>">
            </div>
            <div class="filter-group">
                <label>Lab:</label>
                <input type="text" name="lab" placeholder="e.g. 524" value="<?= htmlspecialchars($lab_filter) ?>">
            </div>
            <button type="submit" class="btn btn-primary" style="margin-bottom: 5px;">Filter</button>
            <a href="sitin_reports.php" class="btn btn-secondary" style="margin-bottom: 5px; text-decoration:none;">Reset</a>
            <!-- reuse the same form inputs but submit to the export file -->
            <button onclick="exportCSV()" class="btn btn-success" style="background-color: #28a745; margin-bottom: 5px; padding: 5px;">📄 Export CSV</button>
        </form>

        <!-- RESULTS TABLE (Keep existing table logic) -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student ID</th>
                    <th>Name</th>
                    <th>Purpose</th>
                    <th>Lab</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($reports) > 0): ?>
                    <?php foreach ($reports as $row): ?>
                        <tr>
                            <td><?= date('Y-m-d h:i A', strtotime($row['date_created'])) ?></td>
                            <td><?= htmlspecialchars($row['student_id']) ?></td>
                            <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']) ?></td>
                            <td><?= htmlspecialchars($row['purpose']) ?></td>
                            <td><?= htmlspecialchars($row['lab']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;">No records found for this period.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function exportCSV() {
            // Get current filter values
            const dateFrom = document.querySelector('input[name="date_from"]').value;
            const dateTo = document.querySelector('input[name="date_to"]').value;
            const lab = document.querySelector('input[name="lab"]').value;

            // Construct URL for the export script
            let url = `export_sitin_csv.php?date_from=${encodeURIComponent(dateFrom)}&date_to=${encodeURIComponent(dateTo)}&lab=${encodeURIComponent(lab)}`;

            // Redirect to download file
            window.location.href = url;
        }
    </script>
</body>

</html>