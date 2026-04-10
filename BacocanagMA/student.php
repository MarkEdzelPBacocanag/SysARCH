<?php

session_start();
require 'database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle success/error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Pagination settings
$entries_per_page = isset($_GET['entries']) ? (int)$_GET['entries'] : 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $entries_per_page;

// Search
$search = $_GET['search'] ?? '';
$search_param = "%$search%";

// Count total students (exclude admins)
if ($search) {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE role = 'student' AND (id LIKE ? OR fname LIKE ? OR lname LIKE ? OR email LIKE ?)");
    $count_stmt->execute([$search_param, $search_param, $search_param, $search_param]);
} else {
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE role = 'student'");
    $count_stmt->execute();
}
$total_students = $count_stmt->fetchColumn();
$total_pages = ceil($total_students / $entries_per_page);

// Fetch students (exclude admins)
if ($search) {
    $stmt = $pdo->prepare("SELECT id, fname, lname, mname, course, course_level, remaining_session
                           FROM students
                           WHERE role = 'student' AND (id LIKE ? OR fname LIKE ? OR lname LIKE ? OR email LIKE ?)
                           ORDER BY id ASC
                           LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(2, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(3, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(4, $search_param, PDO::PARAM_STR);
    $stmt->bindValue(5, $entries_per_page, PDO::PARAM_INT);
    $stmt->bindValue(6, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare("SELECT id, fname, lname, mname, course, course_level, remaining_session
                           FROM students
                           WHERE role = 'student'
                           ORDER BY id ASC
                           LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $entries_per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}
$students = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Information</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- NAVIGATION BAR -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>College of Computer Studies Admin</h2>
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
            <div><a href="feedback_reports.php">
                    <p>Feedback Reports</p>
                </a></div>
            <div><a href="reservations.php">
                    <p>Reservations</p>
                </a></div>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="page-container">
        <h1 class="page-title">Students Information</h1>

        <!-- Success/Error Messages -->
        <!-- TOAST NOTIFICATIONS -->
        <div class="toast-container">
            <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        </div>

        <!-- ACTION BUTTONS -->
        <div class="table-actions">
            <div class="left-actions">
                <button class="btn btn-primary" data-modal-open="addStudentModal">Add Students</button>
                <button class="btn btn-danger" onclick="confirmResetSessions()">Reset All Session</button>
            </div>
        </div>

        <!-- ENTRIES & SEARCH -->
        <div class="table-controls">
            <div class="entries-control">
                <form method="GET" action="student.php" id="entriesForm">
                    <select name="entries" onchange="document.getElementById('entriesForm').submit();">
                        <option value="10" <?= $entries_per_page == 10 ? 'selected' : '' ?>>10</option>
                        <option value="25" <?= $entries_per_page == 25 ? 'selected' : '' ?>>25</option>
                        <option value="50" <?= $entries_per_page == 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $entries_per_page == 100 ? 'selected' : '' ?>>100</option>
                    </select>
                    <label>entries per page</label>
                    <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                </form>
            </div>

            <div class="search-control">
                <form method="GET" action="student.php">
                    <label for="searchInput">Search:</label>
                    <input type="text" id="searchInput" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="ID, Name, Email...">
                    <input type="hidden" name="entries" value="<?= $entries_per_page ?>">
                </form>
            </div>
        </div>

        <!-- STUDENTS TABLE -->
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID Number ↕</th>
                    <th>Name ↕</th>
                    <th>Year Level ↕</th>
                    <th>Course ↕</th>
                    <th>Remaining Session ↕</th>
                    <th>Actions ↕</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($students) > 0): ?>
                    <?php foreach ($students as $row): ?>
                        <?php
                        $full_name = trim($row['fname'] . ' ' . ($row['mname'] ? substr($row['mname'], 0, 1) . '. ' : '') . $row['lname']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['id']) ?></td>
                            <td><?= htmlspecialchars($full_name) ?></td>
                            <td><?= htmlspecialchars($row['course_level']) ?></td>
                            <td><?= htmlspecialchars($row['course']) ?></td>
                            <td><?= htmlspecialchars($row['remaining_session'] ?? 30) ?></td>
                            <td class="action-buttons">
                                <button class="btn btn-edit"
                                    data-modal-open="editStudentModal"
                                    data-id="<?= htmlspecialchars($row['id']) ?>"
                                    data-fname="<?= htmlspecialchars($row['fname']) ?>"
                                    data-lname="<?= htmlspecialchars($row['lname']) ?>"
                                    data-mname="<?= htmlspecialchars($row['mname']) ?>"
                                    data-course="<?= htmlspecialchars($row['course']) ?>"
                                    data-level="<?= htmlspecialchars($row['course_level']) ?>"
                                    data-remaining="<?= htmlspecialchars($row['remaining_session'] ?? 30) ?>">
                                    Edit
                                </button>
                                <button class="btn btn-delete" onclick="confirmDelete('<?= htmlspecialchars($row['id']) ?>')">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-data">No students found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- PAGINATION -->
        <div class="pagination-container">
            <span>Showing <?= $offset + 1 ?> to <?= min($offset + $entries_per_page, $total_students) ?> of <?= $total_students ?> entries</span>

            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=1&entries=<?= $entries_per_page ?>&search=<?= urlencode($search) ?>">«</a>
                    <a href="?page=<?= $current_page - 1 ?>&entries=<?= $entries_per_page ?>&search=<?= urlencode($search) ?>">‹</a>
                <?php endif; ?>

                <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                    <a href="?page=<?= $i ?>&entries=<?= $entries_per_page ?>&search=<?= urlencode($search) ?>"
                        class="<?= $i == $current_page ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>

                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>&entries=<?= $entries_per_page ?>&search=<?= urlencode($search) ?>">›</a>
                    <a href="?page=<?= $total_pages ?>&entries=<?= $entries_per_page ?>&search=<?= urlencode($search) ?>">»</a>
                <?php endif; ?>
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
        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('open');
                modal.setAttribute('aria-hidden', 'false');
            }
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }

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

        // ESC key closes modals
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
            }
        });

        // Auto-fill student data for sit-in
        const sitinIdInput = document.getElementById('sitinId');
        if (sitinIdInput) {
            sitinIdInput.addEventListener('blur', async function() {
                const id = this.value.trim();
                if (!id) return;
                try {
                    const res = await fetch(`get_student.php?id=${encodeURIComponent(id)}`);
                    const data = await res.json();
                    if (data.success) {
                        document.getElementById('sitinName').value = data.name;
                        document.getElementById('sitinRemaining').value = data.remaining_session;
                    } else {
                        document.getElementById('sitinName').value = 'Not found';
                        document.getElementById('sitinRemaining').value = '';
                    }
                } catch (err) {
                    console.error(err);
                }
            });
        }

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
    </script>
</body>

</html>