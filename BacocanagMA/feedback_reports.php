<?php
session_start();
require 'database.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// Handle Status Update
if (isset($_POST['update_status']) && isset($_POST['feedback_id'])) {
    $fid = $_POST['feedback_id'];
    $newStatus = $_POST['status'] === 'resolved' ? 'pending' : 'resolved'; // Toggle logic
    try {
        $stmt = $pdo->prepare("UPDATE feedbacks SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $fid]);
    } catch (PDOException $e) {
        // Handle error
    }
    // Refresh page to avoid resubmission
    header('Location: feedback_reports.php');
    exit;
}

// Fetch Feedback
$stmt = $pdo->query("SELECT f.*, s.fname, s.lname FROM feedbacks f JOIN students s ON f.student_id = s.id ORDER BY f.created_at DESC");
$feedbacks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Feedback Reports</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;"><h2>Feedback Reports</h2></div>
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
            <div><a href="feedback_reports.php"><p>Feedback Reports</p></a></div>
            <div><a href="reservations.php"><p>Reservations</p></a></div>
            <button class="logout-button" type="button" onclick="window.location.href='index.php';">Log out</button>
        </div>
    </div>

    <div class="page-container">
        <h1 class="page-title">Student Feedback</h1>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student Name</th>
                    <th>Message</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (count($feedbacks) > 0): ?>
                <?php foreach ($feedbacks as $fb): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($fb['created_at'])) ?></td>
                        <td><?= htmlspecialchars($fb['fname'] . ' ' . $fb['lname']) ?></td>
                        <td><?= htmlspecialchars($fb['message']) ?></td>
                        <td>
                            <span class="<?= $fb['status'] === 'resolved' ? 'alert-success' : 'alert-error' ?>" style="padding: 5px 10px; border-radius: 5px;">
                                <?= ucfirst($fb['status']) ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="feedback_id" value="<?= $fb['id'] ?>">
                                <input type="hidden" name="update_status" value="1">
                                <input type="hidden" name="status" value="<?= $fb['status'] ?>">
                                <button type="submit" class="btn btn-secondary" style="padding: 5px 10px;">
                                    <?= $fb['status'] === 'pending' ? 'Mark Resolved' : 'Re-open' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-data">No feedback found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
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