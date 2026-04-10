<?php
session_start();
require 'database.php'; // Connect to database

$base = '/' . basename(dirname($_SERVER['SCRIPT_NAME'])) . '/';

// 1. Count Students
$stmt = $pdo->query("SELECT COUNT(*) FROM students WHERE role = 'student'");
$totalStudents = $stmt->fetchColumn();

// 2. Count Currently Sit-in (Active status)
$stmt = $pdo->query("SELECT COUNT(*) FROM sitin_records WHERE status = 'active'");
$currentlySitIn = $stmt->fetchColumn();

// 3. Count Total Sit-in Records (All time)
$stmt = $pdo->query("SELECT COUNT(*) FROM sitin_records");
$totalSitIn = $stmt->fetchColumn();

// 4. Fetch Statistics for Pie Chart (Group by Purpose)
$stmt = $pdo->query("SELECT purpose, COUNT(*) as count FROM sitin_records GROUP BY purpose");
$chartData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Returns ['C#' => 5, 'Java' => 3, etc.]

// 5. Fetch Announcements (JOIN with students table to get admin name)
$stmt = $pdo->query("SELECT a.*, s.fname, s.lname 
                     FROM announcements a 
                     JOIN students s ON a.admin_id = s.id 
                     ORDER BY a.created_at DESC 
                     LIMIT 5");
$announcements = $stmt->fetchAll();

// Success/Error Messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Prepare data for JS
$labels = array_keys($chartData);
$counts = array_values($chartData);

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    // if student tries to access admin dashboard
    header("Location: dashboard.php");
    exit;
}

try {
    $stmt = $pdo->query("SELECT purpose, COUNT(*) as count 
                         FROM sitin_records 
                         GROUP BY purpose 
                         ORDER BY count DESC");
    $purposeData = $stmt->fetchAll();
} catch (PDOException $e) {
    $purposeData = [];
}

// FIX: Ensure we always have valid JSON arrays
if (count($purposeData) > 0) {
    $chartLabels = json_encode(array_column($purposeData, 'purpose'));
    $chartCounts = json_encode(array_column($purposeData, 'count'));
    $hasData = 'true';
} else {
    // Default empty state
    $chartLabels = json_encode(['No Data', 'C#', 'Java', 'C', 'Php']);
    $chartCounts = json_encode([1, 0, 0, 0, 0]); // 1 prevents Chart.js error
    $hasData = 'false';
}

// 1. Get Student Count
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $studentCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    $studentCount = 0;
}

// Placeholder counts for sit-ins (You can replace these with SQL queries later)
$currentSitin = $currentlySitIn; // Replace with actual query
$totalSitin = $totalSitIn; // Replace with actual query
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container-nav" name="navigation bar">
        <div style="padding-left: 3rem;">
            <h2>College of Computer Studies Admin</h2>
        </div>
        <div class="link-ref">
            <div name="home">
                <a href="dashboard_admin.php">
                    <p>Home</p>
                </a>
            </div>
            <div name="Search">
                <a href="search_results.php">
                    <p>Search</p>
                </a>
            </div>
            <div name="Students">
                <a href="student.php">
                    <p>Students</p>
                </a>
            </div>
            <div class="dropdown" style="margin: 0px; padding: 0px;">
                <span>Sit-in ▾</span>
                <ul class="dropdown-content">
                    <!-- Keep Start Sit-in as a modal if you like, or make it a page -->
                    <li><a data-modal-open="sitinModal">Add Sit-in</a></li>

                    <!-- Link to the new PHP files -->
                    <li><a href="sitin_records.php">View Sit-in Records</a></li>
                    <li><a href="sitin_reports.php">Sit-in Reports</a></li>
                </ul>
            </div>
            <div><a href="feedback_reports.php">
                    <p>Feedback Reports</p>
                </a></div>
            <div><a href="reservations.php">
                    <p>Reservations</p>
                </a></div>
            <button class="logout-button" type="button" onclick="window.location.href='index.php';">Log out</button>
        </div>
    </div>

    <!-- TOAST NOTIFICATIONS -->
    <div class="toast-container">
        <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    </div>
    <!-- MAIN DASHBOARD CONTENT -->
    <div class="dashboard-content">

        <!-- LEFT PANEL: STATISTICS -->
        <div class="panel stats-panel">
            <div class="panel-header">
                <span>Statistics</span>
            </div>
            <div class="panel-body">
                <div class="stat-text">
                    <p><strong>Students Registered:</strong> <?= $studentCount; ?></p>
                    <p><strong>Currently Sit-in:</strong> <?= $currentSitin; ?></p>
                    <p><strong>Total Sit-in:</strong> <?= $totalSitin; ?></p>
                </div>

                <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
                    <!-- Canvas for Chart.js -->
                    <canvas id="sitinChart"></canvas>
                    <?php if (count($purposeData) === 0): ?>
                        <p style="text-align: center; color: #666; margin-top: 10px;">
                            No sit-in data yet. Start a sit-in to see statistics.
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: ANNOUNCEMENTS -->
        <div class="panel announcement-panel">
            <div class="panel-header">
                <span>Announcement</span>
            </div>
            <div style="padding: 1rem;">
                <!-- Static Example of a Post (Fetch from DB later) -->
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
                                    <span class="announcement-date">
                                        <?php echo date('M d, Y h:i A', strtotime($announcement['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="announcement-content">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </div>
                                <!-- Delete Button (Optional) -->
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

    </div>

    <!-- ==================== MODALS ==================== -->

    <!-- ADD STUDENT MODAL -->
    <div class="modal" id="addStudentModal" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h3>Add New Student</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form class="modal-body" method="POST" action="add_student.php">
                <div class="form-row">
                    <div class="field-group">
                        <label for="addId">ID Number:</label>
                        <input type="text" id="addId" name="id" required>
                    </div>
                    <div class="field-group">
                        <label for="addEmail">Email:</label>
                        <input type="email" id="addEmail" name="email" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field-group">
                        <label for="addFname">First Name:</label>
                        <input type="text" id="addFname" name="fname" required>
                    </div>
                    <div class="field-group">
                        <label for="addLname">Last Name:</label>
                        <input type="text" id="addLname" name="lname" required>
                    </div>
                    <div class="field-group">
                        <label for="addMname">Middle Name:</label>
                        <input type="text" id="addMname" name="mname">
                    </div>
                </div>

                <div class="form-row">
                    <div class="field-group">
                        <label for="addCourse">Course:</label>
                        <select id="addCourse" name="course" class="course-select" required>
                            <option value="" disabled selected>Select course</option>
                            <option value="BSCS">BSCS</option>
                            <option value="BSIT">BSIT</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="addLevel">Year Level:</label>
                        <select id="addLevel" name="course_level" class="course-select" required>
                            <option value="" disabled selected>Select level</option>
                            <option value="1">1st</option>
                            <option value="2">2nd</option>
                            <option value="3">3rd</option>
                            <option value="4">4th</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field-group">
                        <label for="addPassword">Password:</label>
                        <input type="password" id="addPassword" name="password" required>
                    </div>
                    <div class="field-group">
                        <label for="addAddress">Address:</label>
                        <input type="text" id="addAddress" name="address" required>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT STUDENT MODAL -->
    <div class="modal" id="editStudentModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Edit Student</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form class="modal-body" method="POST" action="edit_student.php">
                <input type="hidden" id="editId" name="id">

                <div class="form-row">
                    <div class="field-group">
                        <label for="editFname">First Name:</label>
                        <input type="text" id="editFname" name="fname" required>
                    </div>
                    <div class="field-group">
                        <label for="editLname">Last Name:</label>
                        <input type="text" id="editLname" name="lname" required>
                    </div>
                </div>

                <div class="field-group">
                    <label for="editMname">Middle Name:</label>
                    <input type="text" id="editMname" name="mname">
                </div>

                <div class="form-row">
                    <div class="field-group">
                        <label for="editCourse">Course:</label>
                        <select id="editCourse" name="course" class="course-select" required>
                            <option value="BSCS">BSCS</option>
                            <option value="BSIT">BSIT</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="editLevel">Year Level:</label>
                        <select id="editLevel" name="course_level" class="course-select" required>
                            <option value="1">1st</option>
                            <option value="2">2nd</option>
                            <option value="3">3rd</option>
                            <option value="4">4th</option>
                        </select>
                    </div>
                </div>

                <div class="field-group">
                    <label for="editRemaining">Remaining Sessions:</label>
                    <input type="number" id="editRemaining" name="remaining_session" min="0" max="30">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SEARCH MODAL (reused) -->
    <div class="modal" id="searchModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Search Student</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>

            <!-- ACTION POINTS TO students.php which already exists -->
            <form class="modal-body" method="GET" action="search_results.php">
                <div class="field-group">
                    <label for="searchQueryModal">Search by ID or Name:</label>
                    <input type="text"
                        id="searchQueryModal"
                        name="search"
                        placeholder="Enter ID number or Student Name"
                        required
                        autofocus>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SIT-IN MODAL (reused) -->
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
                <div class="field-group">
                    <label for="sitinLab">Lab:</label>
                    <input type="text" id="sitinLab" name="lab" placeholder="e.g. 524" required>
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
        // Event listeners for modals
        document.addEventListener('click', function(e) {
            // Open modal
            const openBtn = e.target.closest('[data-modal-open]');
            if (openBtn) {
                e.preventDefault();
                const modalId = openBtn.getAttribute('data-modal-open');
                openModal(modalId);

                // If it's the edit button, populate the form
                if (modalId === 'editStudentModal' && openBtn.dataset.id) {
                    document.getElementById('editId').value = openBtn.dataset.id;
                    document.getElementById('editFname').value = openBtn.dataset.fname;
                    document.getElementById('editLname').value = openBtn.dataset.lname;
                    document.getElementById('editMname').value = openBtn.dataset.mname || '';
                    document.getElementById('editCourse').value = openBtn.dataset.course;
                    document.getElementById('editLevel').value = openBtn.dataset.level;
                    document.getElementById('editRemaining').value = openBtn.dataset.remaining;
                }
                return;
            }

            // Close modal
            if (e.target.matches('[data-modal-close]')) {
                closeModal(e.target.closest('.modal'));
                return;
            }

            // Click outside closes modal
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
            }
        });

        // Confirm delete
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete student ID: ' + id + '?')) {
                window.location.href = 'delete_student.php?id=' + encodeURIComponent(id);
            }
        }

        // Confirm reset all sessions
        function confirmResetSessions() {
            if (confirm('Are you sure you want to reset ALL students\' sessions to 30?')) {
                window.location.href = 'reset_sessions.php';
            }
        }
        // JAVASCRIPT FOR PIE CHART

        // Pass PHP data to JavaScript
        document.addEventListener('DOMContentLoaded', function() {

            // Check if Chart.js is loaded
            if (typeof Chart === 'undefined') {
                console.error('Chart.js not loaded! Check your internet connection.');
                document.querySelector('.chart-container').innerHTML =
                    '<p style="color:red; text-align:center;">Chart library failed to load</p>';
                return;
            }

            const ctx = document.getElementById('sitinChart');
            if (!ctx) {
                console.error('Canvas element #sitinChart not found!');
                return;
            }

            // Get the 2D context
            const context = ctx.getContext('2d');

            // Destroy existing chart if any (prevents duplicates on AJAX reloads)
            if (window.myPieChart) {
                window.myPieChart.destroy();
            }

            // Create the chart
            window.myPieChart = new Chart(context, {
                type: 'pie',
                data: {
                    labels: <?= $chartLabels ?>,
                    datasets: [{
                        data: <?= $chartCounts ?>,
                        backgroundColor: [
                            '#36A2EB', // Blue
                            '#FF6384', // Red
                            '#FFCE56', // Yellow
                            '#4BC0C0', // Teal
                            '#FF9F40' // Orange
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
                                padding: 10,
                                font: {
                                    size: 11
                                }
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

            console.log('Chart loaded successfully with data:', <?= $chartCounts ?>);
        });
        // Modal open/close logic (from previous answer)
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
            if (e.target.matches('[data-modal-close]')) {
                const modal = e.target.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
                return;
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
            }
        });

        // --- AUTO-FILL STUDENT DATA ---
        // --- AUTO-FILL STUDENT DATA & CHECK ACTIVE STATUS ---
        const idInput = document.getElementById('sitinId');
        const nameInput = document.getElementById('sitinName');
        const remainingInput = document.getElementById('sitinRemaining');
        const sitinForm = document.getElementById('sitinForm'); // Reference the form
        const submitBtn = sitinForm ? sitinForm.querySelector('button[type="submit"]') : null;

        if (idInput) {
            idInput.addEventListener('blur', async function() {
                const id = this.value.trim();
                if (!id) return;

                try {
                    // Fetch student data from backend
                    const response = await fetch(`get_student.php?id=${encodeURIComponent(id)}`);
                    const data = await response.json();

                    if (data.success) {
                        // Check if student has active session
                        if (data.has_active_session) {
                            nameInput.value = data.name + ' (⚠️ Currently Active)';
                            remainingInput.value = data.remaining_session;

                            // Disable submit button to prevent duplicate
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.title = "Student is already in a session";
                                submitBtn.style.opacity = "0.6";
                                submitBtn.style.cursor = "not-allowed";
                            }
                        } else {
                            nameInput.value = data.name;
                            remainingInput.value = data.remaining_session;

                            // Enable submit button
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                submitBtn.title = "";
                                submitBtn.style.opacity = "1";
                                submitBtn.style.cursor = "pointer";
                            }
                        }
                    } else {
                        nameInput.value = 'Student not found';
                        remainingInput.value = '';
                        if (submitBtn) submitBtn.disabled = true; // Disable if student not found
                    }
                } catch (err) {
                    console.error('Error fetching student:', err);
                }
            });
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }

        // Open modal using data-modal-open
        document.addEventListener('click', function(e) {
            const openBtn = e.target.closest('[data-modal-open]');
            if (openBtn) {
                e.preventDefault();
                openModal(openBtn.getAttribute('data-modal-open'));
                return;
            }

            // Close modal using close button
            if (e.target.matches('[data-modal-close]')) {
                const modal = e.target.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }

            // Close when clicking outside dialog (overlay)
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
                return;
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
            }
        });
    </script>
</body>

</html>