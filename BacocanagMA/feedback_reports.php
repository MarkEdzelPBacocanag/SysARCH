<?php
session_start();
require 'database.php';

// 1. Admin Security Check
if (($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: index.php');
    exit;
}

// 2. Handle Status Update (Mark Resolved/Re-open)
if (isset($_POST['update_status']) && isset($_POST['feedback_id'])) {
    $fid = $_POST['feedback_id'];
    $newStatus = $_POST['status'] === 'resolved' ? 'pending' : 'resolved'; // Toggle logic
    try {
        $stmt = $pdo->prepare("UPDATE feedbacks SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $fid]);
        $_SESSION['success'] = 'Feedback status updated.';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error updating status.';
    }
    header('Location: feedback_reports.php');
    exit;
}

// 3. Fetch Feedback (✅ UPDATED: Joins with sitin_records to get Lab & PC)
$stmt = $pdo->query("
    SELECT f.*, s.fname, s.lname, r.lab, r.pc_number 
    FROM feedbacks f 
    JOIN students s ON f.student_id = s.id 
    LEFT JOIN sitin_records r ON f.record_id = r.id 
    ORDER BY f.created_at DESC
");
$feedbacks = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Feedback Reports</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- NAVIGATION BAR -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Student Feedback Reports</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_admin.php">
                    <p>Home</p>
                </a></div>
            <div><a href="student.php">
                    <p>Students</p>
                </a></div>
            <div class="dropdown">
                <span>Sit-in ▾</span>
                <ul class="dropdown-content">
                    <li><a data-modal-open="sitinModal">Add Sit-in</a></li>
                    <li><a href="sitin_records.php">View Sit-in Records</a></li>
                    <li><a href="sitin_reports.php">Sit-in Reports</a></li>
                </ul>
            </div>
            <div><a href="feedback_reports.php">
                    <p>Feedback</p>
                </a></div>
            <div><a href="reservation.php">
                    <p>Reservations</p>
                </a></div>
            <button class="logout-button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- TOAST NOTIFICATIONS -->
    <div class="toast-container">
        <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    </div>

    <!-- MAIN CONTENT -->
    <div class="page-container">
        <h1 class="page-title">Student Feedback</h1>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Student Name</th>
                    <th>Lab</th> <!-- ✅ NEW COLUMN -->
                    <th>PC</th> <!-- ✅ NEW COLUMN -->
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
                            <!-- ✅ DISPLAY LAB & PC -->
                            <td><?= htmlspecialchars($fb['lab'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($fb['pc_number'] ?? 'N/A') ?></td>
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
                        <td colspan="7" class="no-data">No feedback found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- SIT-IN MODAL (For Admin Convenience) -->
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
        document.addEventListener('click', function(e) {
            const openBtn = e.target.closest('[data-modal-open]');
            if (openBtn) {
                e.preventDefault();
                const modalId = openBtn.getAttribute('data-modal-open');
                openModal(modalId);
                return;
            }
            if (e.target.matches('[data-modal-close]')) {
                closeModal(e.target.closest('.modal'));
                return;
            }
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
            }
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
            }
        });
        // Auto-fill student data
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
    </script>
</body>

</html>