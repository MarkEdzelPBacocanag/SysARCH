<?php
session_start();
require 'database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

$q = $_GET['q'] ?? '';
$results = [];

if ($q) {
    // Search by ID, First Name, or Last Name (exclude admins)
    $search = "%$q%";
    $stmt = $pdo->prepare("SELECT id, fname, lname, mname, course, course_level, remaining_session 
                           FROM students 
                           WHERE role = 'student' AND (id LIKE ? OR fname LIKE ? OR lname LIKE ?)");
    $stmt->execute([$search, $search, $search]);
    $results = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .search-box {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-box input {
            flex: 1;
            padding: 12px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .result-count {
            margin-bottom: 1rem;
            color: #666;
        }
    </style>
</head>

<body>

    <!-- NAVIGATION BAR -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Search Students</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_admin.php">
                    <p>Home</p>
                </a></div>
            <div><a href="search_results.php">
                    <p>Search</p>
                </a></div>
            <div><a href="student.php">
                    <p>Students</p>
                </a></div>
            <div class="dropdown" style="margin: 0px; padding: 0px;">
                <span>Sit-in ▾</span>
                <ul class="dropdown-content">
                    <li><a data-modal-open="sitinModal">Add Sit-in</a></li>
                    <li><a href="sitin_records.php">View Sit-in Records</a></li>
                    <li><a href="sitin_reports.php">Sit-in Reports</a></li>
                </ul>
            </div>
            <div>
                <a href="feedback_reports.php">
                    <p>Feedback Reports</p>
                </a>
            </div>
            <div>
                <a href="reservations.php">
                    <p>Reservations</p>
                </a>
            </div>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="search-container">

        <!-- Search Form -->
        <form method="GET" class="search-box">
            <input type="text" name="q" placeholder="Enter Student ID, First Name, or Last Name..." value="<?= htmlspecialchars($q) ?>" required autofocus>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($q): ?>
                <a href="search_results.php" class="btn btn-secondary" style="text-decoration:none; display:flex; align-items:center; height: 40px;">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Results -->
        <?php if ($q): ?>
            <div class="result-count">
                Found <strong><?= count($results) ?></strong> result(s) for "<?= htmlspecialchars($q) ?>"
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Remaining</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($results) > 0): ?>
                        <?php foreach ($results as $row): ?>
                            <?php
                            $name = trim($row['fname'] . ' ' . ($row['mname'] ? substr($row['mname'], 0, 1) . '. ' : '') . $row['lname']);
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($name) ?></td>
                                <td><?= htmlspecialchars($row['course']) ?></td>
                                <td><?= htmlspecialchars($row['course_level']) ?></td>
                                <td><?= htmlspecialchars($row['remaining_session']) ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="window.location.href='student.php?search=<?= urlencode($row['id']) ?>'">Manage</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No students found matching your search.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else: ?>
            <!-- Initial State -->
            <div style="text-align: center; color: #666; margin-top: 3rem;">
                <p>🔍 Please enter a student's name or ID to begin searching.</p>
            </div>
        <?php endif; ?>
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
        <script>
            // Modal functions
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
            const idInput = document.getElementById('sitinId');
            const nameInput = document.getElementById('sitinName');
            const remainingInput = document.getElementById('sitinRemaining');

            if (idInput) {
                idInput.addEventListener('blur', async function() {
                    const id = this.value.trim();
                    if (!id) return;

                    try {
                        // Fetch student data from backend
                        const response = await fetch(`get_student.php?id=${encodeURIComponent(id)}`);
                        const data = await response.json();

                        if (data.success) {
                            nameInput.value = data.name;
                            remainingInput.value = data.remaining_session;
                        } else {
                            nameInput.value = 'Student not found';
                            remainingInput.value = '';
                            // Optional: alert('Student ID not found');
                        }
                    } catch (err) {
                        console.error('Error fetching student:', err);
                    }
                });
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