<?php
session_start();
require 'database.php';

// --- 1. SECURITY CHECK ---
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'admin') {
    // If a student tries to access admin dashboard, send them to student dashboard
    header("Location: dashboard_student.php");
    exit;
}

// --- 2. STATISTICS QUERIES ---
// A. Count Students (excluding admins)
$stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'student'");
$totalStudents = $stmt->fetchColumn();

// B. Count Currently Sit-in (Active status)
$stmt = $pdo->query("SELECT COUNT(*) FROM sitin_records WHERE status = 'active'");
$currentlySitIn = $stmt->fetchColumn();

// C. Count Total Sit-in Records (All time)
$stmt = $pdo->query("SELECT COUNT(*) FROM sitin_records");
$totalSitIn = $stmt->fetchColumn();

// --- 3. ANALYTICS FOR SESSIONS BY PURPOSE (CHART DATA) ---
$stmt = $pdo->query("SELECT purpose, COUNT(*) as count FROM sitin_records GROUP BY purpose ORDER BY count DESC");
$purposeData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare JSON for Chart.js
$chartLabels = [];
$chartCounts = [];

if (empty($purposeData)) {
    // Fallback if database is empty to prevent Chart.js errors
    $chartLabels = json_encode(['No Data Yet']);
    $chartCounts = json_encode([1]);
} else {
    foreach ($purposeData as $row) {
        $chartLabels[] = $row['purpose'];
        $chartCounts[] = $row['count'];
    }
    $chartLabels = json_encode($chartLabels);
    $chartCounts = json_encode($chartCounts);
}

// --- 4. LAB USAGE STATS (BAR CHART) ---
$stmt = $pdo->query("SELECT lab, COUNT(*) as count FROM sitin_records GROUP BY lab");
$labData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$labLabels = json_encode(array_keys($labData));
$labCounts = json_encode(array_values($labData));

// --- 5. FETCH ANNOUNCEMENTS ---
$stmt = $pdo->query("SELECT a.*, s.fname, s.lname
    FROM announcements a
    JOIN students s ON a.admin_id = s.id
    ORDER BY a.created_at DESC
    LIMIT 5");
$announcements = $stmt->fetchAll();

// --- 6. MESSAGES ---
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Dashboard specific styles */
        .lab-stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }

        .lab-card {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dee2e6;
        }

        .lab-card h5 {
            margin: 0;
            color: #495057;
            font-size: 0.9rem;
        }

        .lab-card .count {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
            margin: 5px 0;
        }
    </style>
</head>

<body>

    <!-- NAVIGATION BAR -->
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
            <div class="dropdown" style="margin: 0px; padding: 0px;">
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
            <!-- FIXED: Redirects to logout.php -->
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- TOAST NOTIFICATIONS -->
    <div class="toast-container">
        <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="dashboard-content">
        <!-- LEFT PANEL: STATISTICS & ANALYTICS -->
        <div class="panel stats-panel">
            <div class="panel-header">
                <span>📊 System Statistics</span>
            </div>
            <div class="panel-body">
                <!-- General Stats -->
                <div class="stat-text">
                    <p><strong>Total Students:</strong> <?= $totalStudents; ?></p>
                    <p><strong>Currently Sit-in:</strong> <?= $currentlySitIn; ?></p>
                    <p><strong>Total Sit-ins (All Time):</strong> <?= $totalSitIn; ?></p>
                </div>

                <!-- NEW: Lab Usage Summary -->
                <h4 style="margin: 20px 0 10px 0; border-bottom: 1px solid #eee; padding-bottom: 5px;">Lab Activity</h4>
                <div class="lab-stats-grid">
                    <div class="lab-card">
                        <h5>Active Sessions</h5>
                        <div class="count"><?= $currentlySitIn ?></div>
                    </div>
                    <div class="lab-card">
                        <h5>Total Sessions</h5>
                        <div class="count"><?= $totalSitIn ?></div>
                    </div>
                </div>

                <!-- Pie Chart: Purpose -->
                <h4 style="margin: 20px 0 10px 0; border-bottom: 1px solid #eee; padding-bottom: 5px;">Sessions by Purpose</h4>
                <div class="chart-container" style="position: relative; height: 250px; width: 100%;">
                    <canvas id="purposeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: ANNOUNCEMENTS -->
        <div class="panel announcement-panel">
            <div class="panel-header">
                <span>📢 Announcements</span>
            </div>
            <div style="padding: 1rem;">
                <div class="announcement-form">
                    <form method="post" action="add_announcement.php">
                        <textarea name="announcement" placeholder="Write a new announcement..." rows="3" required></textarea>
                        <button type="submit" class="submit-button">📤 Post Announcement</button>
                    </form>
                </div>

                <div class="posted-announcements">
                    <h5>Recent Announcements</h5>
                    <?php if (empty($announcements)): ?>
                        <p style="text-align: center; color: #666; padding: 2rem;">No announcements yet.</p>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-item">
                                <div class="announcement-header">
                                    <strong><?php echo htmlspecialchars($announcement['fname'] . ' ' . $announcement['lname']); ?></strong>
                                    <span class="announcement-date"><?php echo date('M d, Y h:i A', strtotime($announcement['created_at'])); ?></span>
                                </div>
                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </div>
                                <div style="margin-top: 8px;">
                                    <a href="delete_announcement.php?id=<?= $announcement['id'] ?>"
                                        style="color: #dc3545; font-size: 0.85rem; text-decoration: none;"
                                        onclick="return confirm('Delete this announcement?')">
                                        🗑️ Delete
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODALS ==================== -->
    <!-- SIT-IN MODAL -->
    <div class="modal" id="sitinModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Sit In Form</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form class="modal-body" method="POST" action="start_sitin.php" id="sitinForm">
                <div class="field-group">
                    <label for="sitinId">ID Number:</label>
                    <input type="text" id="sitinId" name="student_id" required>
                </div>
                <div class="field-group">
                    <label for="sitinName">Student Name:</label>
                    <input type="text" id="sitinName" name="student_name" readonly style="background:#f0f0f0;">
                </div>
                <div class="field-group">
                    <label for="sitinPurpose">Purpose:</label>
                    <select id="sitinPurpose" name="purpose" class="course-select" required>
                        <option value="" disabled selected>Select Purpose</option>
                        <option value="C Programming">C Programming</option>
                        <option value="C#">C#</option>
                        <option value="Java">Java</option>
                        <option value="ASP.Net">ASP.Net</option>
                        <option value="Php">Php</option>
                        <option value="Python">Python</option>
                    </select>
                </div>
                <div class="form-row">
                    <div class="field-group">
                        <label for="sitinLab">Laboratory:</label>
                        <select id="sitinLab" name="lab" class="course-select" required>
                            <option value="" disabled selected>Select Lab</option>
                            <option value="Lab 543">Lab 543</option>
                            <option value="Lab 544">Lab 544</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="sitinPC">PC Number:</label>
                        <select id="sitinPC" name="pc_number" class="course-select" required>
                            <option value="" disabled selected>Select PC</option>
                            <?php for ($i = 1; $i <= 50; $i++): ?>
                                <option value="PC-<?= $i < 10 ? '0' : '' ?><?= $i ?>">PC-<?= $i < 10 ? '0' : '' ?><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label for="sitinRemaining">Remaining Session:</label>
                    <input type="number" id="sitinRemaining" name="remaining_session" readonly style="background:#f0f0f0;">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Close</button>
                    <button type="submit" class="btn btn-primary">Sit In</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        // --- 1. CHART.JS SETUP ---
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('purposeChart');
            if (!ctx) return;

            // Destroy existing chart if any
            if (window.myPieChart) {
                window.myPieChart.destroy();
            }

            // Create the chart
            window.myPieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?= $chartLabels ?>,
                    datasets: [{
                        data: <?= $chartCounts ?>,
                        backgroundColor: [
                            '#36A2EB', '#FF6384', '#FFCE56', '#4BC0C0', '#FF9F40', '#9966FF', '#7BC225', '#C9CBCF'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    let value = context.parsed || 0;
                                    let total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    let percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        });

        // --- 2. MODAL LOGIC ---
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            // Reset form when closing
            const form = modal.querySelector('form');
            if (form) form.reset();
            // Clear readonly fields
            const nameField = document.getElementById('sitinName');
            const remField = document.getElementById('sitinRemaining');
            if (nameField) nameField.value = '';
            if (remField) remField.value = '';
        }

        document.addEventListener('click', function(e) {
            const openBtn = e.target.closest('[data-modal-open]');
            if (openBtn) {
                e.preventDefault();
                openModal(openBtn.getAttribute('data-modal-open'));
                return;
            }
            if (e.target.matches('[data-modal-close]') || e.target.classList.contains('modal')) {
                closeModal(e.target.closest('.modal'));
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
            }
        });

        // --- 3. AUTO-FILL STUDENT DATA ---
        const idInput = document.getElementById('sitinId');
        if (idInput) {
            idInput.addEventListener('blur', async function() {
                const id = this.value.trim();
                if (!id) return;
                try {
                    const response = await fetch(`get_student.php?id=${encodeURIComponent(id)}`);
                    const data = await response.json();
                    if (data.success) {
                        document.getElementById('sitinName').value = data.name;
                        document.getElementById('sitinRemaining').value = data.remaining_session;
                    } else {
                        document.getElementById('sitinName').value = 'Student not found';
                        document.getElementById('sitinRemaining').value = '';
                    }
                } catch (err) {
                    console.error('Error fetching student:', err);
                }
            });
        }
    </script>
</body>

</html>