<?php
session_start();
require 'database.php';

// Check if student is logged in
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit;
}

// Get student's sit-in history
$stmt = $pdo->prepare("SELECT * FROM sitin_records WHERE student_id = ? ORDER BY date_created DESC");
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll();

// Toast Messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My History</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .history-container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .history-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.4rem;
        }

        .card-body {
            padding: 20px;
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .history-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #e2e3e5;
            color: #6c757d;
        }

        .btn-feedback {
            background-color: #17a2b8;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-feedback:hover {
            background-color: #138496;
        }

        .btn-feedback:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .no-history {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.open {
            display: flex;
        }

        .modal-dialog {
            background: #fff;
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            background-color: #007bff;
            color: #fff;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.1rem;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-body textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: vertical;
            box-sizing: border-box;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px;
        }
    </style>
</head>

<body>
    <!-- NAVIGATION BAR -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>My History</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_student.php">Home</a></div>
            <div><a href="leaderboard.php">Leaderboard</a></div>
            <div><a href="edit_profile.php">Edit Profile</a></div>
            <div><a href="student_history.php">History</a></div>
            <!-- ✅ NOTIFICATION BELL -->
            <?php
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE student_id = ? AND is_read = 0");
            $stmt->execute([$_SESSION['user_id']]);
            $unread_count = $stmt->fetchColumn();
            ?>
            <a href="notifications.php">
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span style="position:absolute; top:-8px; right:-10px; background:#dc3545; color:white; border-radius:50%; padding:2px 6px; font-size:0.7rem; font-weight:bold;">
                        <?= $unread_count > 9 ? '9+' : $unread_count ?>
                    </span>
                <?php endif; ?>
            </a>
            <button class="btn btn-primary" data-modal-open="reservationModal">🖥️ Reserve a PC</button>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- TOAST NOTIFICATIONS -->
    <div class="toast-container">
        <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    </div>

    <!-- MAIN CONTENT -->
    <div class="history-container">
        <div class="history-card">
            <div class="card-header">
                <h2>📜 My Sit-in History</h2>
            </div>
            <div class="card-body">
                <?php if (count($history) > 0): ?>
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date & Time</th>
                                <th>Purpose</th>
                                <th>Lab</th>
                                <th>PC</th>
                                <th>Status</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['date_created'])) ?></td>
                                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                                    <td><?= htmlspecialchars($row['lab']) ?></td>
                                    <td><?= htmlspecialchars($row['pc_number'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'pending'): ?>
                                            <span class="status-badge status-pending">Pending Start</span>
                                        <?php elseif ($row['status'] === 'active'): ?>
                                            <span class="status-badge status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-badge status-completed">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn-feedback"
                                            onclick="openFeedbackModal(<?= $row['id'] ?>)"
                                            <?= ($row['status'] === 'pending') ? 'disabled title="Feedback available after session starts"' : '' ?>>
                                            ✍️ Send Feedback
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-history">
                        <p>📭 You have no sit-in history yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- FEEDBACK MODAL -->
    <div class="modal" id="feedbackModal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>📩 Submit Feedback</h3>
                <button type="button" class="modal-close" onclick="closeFeedbackModal()">&times;</button>
            </div>
            <form class="modal-body" method="POST" action="student_feedback.php">
                <input type="hidden" name="record_id" id="feedbackRecordId" value="">
                <textarea name="message" placeholder="Write your feedback, report an issue, or suggestion here..." required></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-feedback" style="background:#6c757d;" onclick="closeFeedbackModal()">Cancel</button>
                    <button type="submit" class="btn-feedback" style="background:#28a745;">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <!-- RESERVATION MODAL -->
    <div class="modal" id="reservationModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>🖥️ Reserve a Computer</h3>
                <button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <form class="modal-body" method="POST" action="add_reservation.php" id="reservationForm">
                <div class="form-row">
                    <div class="field-group">
                        <label for="resLab">Laboratory:</label>
                        <select id="resLab" name="lab" class="course-select" required>
                            <option value="" disabled selected>Select Lab</option>
                            <option value="Lab 543">Lab 543</option>
                            <option value="Lab 544">Lab 544</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="resPC">PC Number:</label>
                        <select id="resPC" name="pc_number" class="course-select" required>
                            <option value="" disabled selected>Select PC</option>
                            <?php for ($i = 1; $i <= 50; $i++): ?>
                                <option value="PC-<?= $i < 10 ? '0' : '' ?><?= $i ?>">PC-<?= $i < 10 ? '0' : '' ?><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="field-group">
                    <label>PC Status:</label>
                    <div id="pcStatusBadge" style="padding: 8px; border-radius: 5px; background: #e9ecef; text-align: center; font-weight: bold; color: #555;">Select Lab & PC to check status</div>
                </div>
                <div class="field-group">
                    <label for="resPurpose">Purpose:</label>
                    <select id="resPurpose" name="purpose" class="course-select" required>
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
                        <label for="resDate">Date:</label>
                        <input type="date" id="resDate" name="reservation_date" class="course-select" required>
                    </div>
                    <div class="field-group">
                        <label for="resTime">Time:</label>
                        <input type="time" id="resTime" name="reservation_time" class="course-select" required>
                    </div>
                </div>
                <div class="field-group">
                    <label>Remaining Sessions:</label>
                    <input type="number" id="resRemaining" readonly style="background:#f0f0f0;">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" id="submitReservation" class="btn btn-primary" disabled>Submit Request</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            if (modalId === 'reservationModal') {
                const remInput = document.getElementById('resRemaining');
                if (remInput) {
                    fetch(`get_student.php?id=<?= $_SESSION['user_id'] ?>`).then(res => res.json()).then(data => {
                        remInput.value = data.success ? data.remaining_session : 0;
                    }).catch(() => remInput.value = 'Error');
                }
                checkPCStatus();
            }
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            const form = modal.querySelector('form');
            if (form) form.reset();
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
            if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
        });

        const resLab = document.getElementById('resLab');
        const resPC = document.getElementById('resPC');
        const resDate = document.getElementById('resDate');
        const pcStatusBadge = document.getElementById('pcStatusBadge');
        const submitBtn = document.getElementById('submitReservation');

        async function checkPCStatus() {
            const lab = resLab.value;
            const pc = resPC.value;
            const date = resDate.value || new Date().toISOString().split('T')[0];
            if (!lab || !pc) {
                pcStatusBadge.style.background = '#e9ecef';
                pcStatusBadge.textContent = 'Select Lab & PC to check status';
                pcStatusBadge.style.color = '#555';
                submitBtn.disabled = true;
                return;
            }
            pcStatusBadge.style.background = '#e9ecef';
            pcStatusBadge.textContent = 'Checking status...';
            try {
                const res = await fetch(`check_pc_status.php?lab=${encodeURIComponent(lab)}&pc=${encodeURIComponent(pc)}&date=${date}`);
                const data = await res.json();
                pcStatusBadge.textContent = ` ${data.status}`;
                pcStatusBadge.style.background = `${data.color}20`;
                pcStatusBadge.style.color = data.color;
                pcStatusBadge.style.border = `2px solid ${data.color}`;
                submitBtn.disabled = data.status !== 'Available';
            } catch (err) {
                pcStatusBadge.textContent = 'Error checking status';
                submitBtn.disabled = true;
            }
        }
        resLab.addEventListener('change', checkPCStatus);
        resPC.addEventListener('change', checkPCStatus);
        resDate.addEventListener('change', checkPCStatus);
    </script>
</body>

</html>