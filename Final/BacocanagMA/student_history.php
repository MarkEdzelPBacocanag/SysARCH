<?php
session_start();
require 'database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location:index.php");
    exit;
}
$stmt = $pdo->prepare("SELECT id,lab,pc_number,purpose,date_created,end_time,extension_count FROM sitin_records WHERE student_id=? AND status='active' ORDER BY date_created DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$activeSession = $stmt->fetch();
$stmt = $pdo->prepare("SELECT id,lab,pc_number,purpose,date_created FROM sitin_records WHERE student_id=? AND status='pending' ORDER BY date_created DESC");
$stmt->execute([$_SESSION['user_id']]);
$pending_sessions = $stmt->fetchAll();
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
$stmt = $pdo->prepare("SELECT * FROM sitin_records WHERE student_id=? ORDER BY date_created DESC");
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>My History</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .history-container {
            max-width: 1100px;
            margin: 2rem auto;
            padding: 0 1rem
        }

        .history-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
            overflow: hidden
        }

        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            padding: 20px
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.4rem
        }

        .card-body {
            padding: 20px
        }

        .history-table {
            width: 100%;
            border-collapse: collapse
        }

        .history-table th,
        .history-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee
        }

        .history-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333
        }

        .history-table tr:hover {
            background: #f8f9fa
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: .8rem;
            font-weight: 700
        }

        .status-pending {
            background: #fff3cd;
            color: #856404
        }

        .status-active {
            background: #d4edda;
            color: #155724
        }

        .status-completed {
            background: #e2e3e5;
            color: #6c757d
        }

        .btn-feedback {
            background: #17a2b8;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: .85rem
        }

        .btn-feedback:hover {
            background: #138496
        }

        .btn-feedback:disabled {
            background: #6c757d;
            cursor: not-allowed;
            opacity: .6
        }

        .no-history {
            text-align: center;
            padding: 40px;
            color: #999
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 2000;
            align-items: center;
            justify-content: center
        }

        .modal.open {
            display: flex
        }

        .modal-dialog {
            background: #fff;
            width: 100%;
            max-width: 500px;
            border-radius: 8px;
            overflow: hidden
        }

        .modal-header {
            background: #007bff;
            color: #fff;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .modal-close {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 1.5rem;
            cursor: pointer
        }

        .modal-body {
            padding: 20px
        }

        .modal-body textarea {
            width: 100%;
            height: 120px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: vertical;
            box-sizing: border-box
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 15px
        }
    </style>
</head>

<body data-user-id="<?= $_SESSION['user_id'] ?>">

    <div class="toast-container"><?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?><?php if ($error): ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?></div>

    <div class="container-nav">
        <div style="padding-left:3rem;">
            <h2>My History</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_student.php">Home</a></div>
            <div><a href="leaderboard.php">Leaderboard</a></div>
            <div><a href="edit_profile.php">Edit Profile</a></div>
            <div><a href="student_history.php">History</a></div>
            <?php $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE student_id=? AND is_read=0");
            $stmt->execute([$_SESSION['user_id']]);
            $unread_count = $stmt->fetchColumn(); ?>
            <a href="notifications.php" class="nav-notification-wrapper">
                Notifications
                <?php if ($unread_count > 0): ?>
                    <span class="notification-badge"><?= $unread_count > 9 ? '9+' : $unread_count ?></span>
                <?php endif; ?>
            </a>
            <button class="btn btn-primary" data-modal-open="reservationModal">🖥️ Reserve a PC</button><button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <div class="history-container">
        <div class="history-card">
            <div class="card-header">
                <h2>📜 My Sit-in History</h2>
            </div>
            <div class="card-body"><?php if (count($history) > 0): ?><table class="history-table">
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
                        <tbody><?php foreach ($history as $i => $row): ?><tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['date_created'])) ?></td>
                                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                                    <td><?= htmlspecialchars($row['lab']) ?></td>
                                    <td><?= htmlspecialchars($row['pc_number'] ?? 'N/A') ?></td>
                                    <td><?php if ($row['status'] === 'pending'): ?><span class="status-badge status-pending">Pending Start</span><?php elseif ($row['status'] === 'active'): ?><span class="status-badge status-active">Active</span><?php else: ?><span class="status-badge status-completed">Completed</span><?php endif; ?></td>
                                    <td><button class="btn-feedback" onclick="openFeedbackModal(<?= $row['id'] ?>)" <?= ($row['status'] === 'pending') ? 'disabled title="Feedback available after session starts"' : '' ?>>✍️ Send Feedback</button></td>
                                </tr><?php endforeach; ?></tbody>
                    </table><?php else: ?><div class="no-history">
                        <p>📭 You have no sit-in history yet.</p>
                    </div><?php endif; ?></div>
        </div>
    </div>

    <div class="modal" id="feedbackModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>📩 Submit Feedback</h3><button type="button" class="modal-close" onclick="closeFeedbackModal()">&times;</button>
            </div>
            <form class="modal-body" method="POST" action="student_feedback.php"><input type="hidden" name="record_id" id="feedbackRecordId" value=""><textarea name="message" placeholder="Write your feedback..." required></textarea>
                <div class="modal-actions"><button type="button" class="btn-feedback" style="background:#6c757d;" onclick="closeFeedbackModal()">Cancel</button><button type="submit" class="btn-feedback" style="background:#28a745;">Submit Feedback</button></div>
            </form>
        </div>
    </div>

    <div class="modal" id="extensionModal" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-header" style="background:#ffc107;">
                <h3>⏰ Session Ending Soon!</h3><button type="button" class="modal-close" data-modal-close>&times;</button>
            </div>
            <div class="modal-body">
                <p>Your session ends at <strong id="extEndTimeDisplay"></strong>.</p>
                <p>You have used <span id="extCountDisplay"></span> extensions today. (Max 5)</p>
                <h4>Select Extension Time:</h4>
                <div id="extensionOptions">
                    <div class="extension-option" data-minutes="15">+15 Minutes</div>
                    <div class="extension-option" data-minutes="30">+30 Minutes</div>
                    <div class="extension-option" data-minutes="60">+1 Hour</div>
                </div>
                <div id="extensionStatus" style="margin-top:10px;font-size:.9rem;color:#666;"></div>
                <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeExtensionModal()">Close (End Session)</button><button type="button" id="btnRequestExtension" class="btn btn-primary" disabled>Request Extension</button></div>
            </div>
        </div>
    </div>

    <?php include 'includes/reservation_modal.php'; ?>
    <script>
        function openModal(mId) {
            const m = document.getElementById(mId);
            if (!m) return;
            m.classList.add('open');
            m.setAttribute('aria-hidden', 'false');
        }

        function closeModal(m) {
            m.classList.remove('open');
            m.setAttribute('aria-hidden', 'true');
            const f = m.querySelector('form');
            if (f) f.reset();
        }
        document.addEventListener('click', function(e) {
            const o = e.target.closest('[data-modal-open]');
            if (o) {
                e.preventDefault();
                openModal(o.getAttribute('data-modal-open'));
                return;
            }
            if (e.target.matches('[data-modal-close]') || e.target.classList.contains('modal')) closeModal(e.target.closest('.modal'));
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
        });

        function openFeedbackModal(id) {
            openModal('feedbackModal');
            document.getElementById('feedbackRecordId').value = id;
        }

        function closeFeedbackModal() {
            closeModal(document.getElementById('feedbackModal'));
        }
        <?php if ($activeSession): ?>
            const activeEndString = "<?= $activeSession['end_time'] ?>",
                activeRecordId = <?= $activeSession['id'] ?>,
                currentExtensions = <?= $activeSession['extension_count'] ?? 0 ?>;
            let extensionModalShown = false,
                endTimeDate = new Date();
            endTimeDate.setHours(...activeEndString.split(':'), 0, 0);
            setInterval(() => {
                const now = new Date(),
                    diffMs = endTimeDate - now,
                    diffMins = diffMs / 1000 / 60;
                if (diffMins <= 10 && diffMins > 0 && !extensionModalShown) {
                    extensionModalShown = true;
                    openExtensionModal();
                }
            }, 30000);

            function openExtensionModal() {
                const m = document.getElementById('extensionModal');
                m.classList.add('open');
                m.setAttribute('aria-hidden', 'false');
                document.getElementById('extEndTimeDisplay').textContent = endTimeDate.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit'
                });
                document.getElementById('extCountDisplay').textContent = currentExtensions;
                document.querySelectorAll('.extension-option').forEach(o => o.classList.remove('selected'));
                document.getElementById('btnRequestExtension').disabled = true;
                document.getElementById('extensionStatus').textContent = '';
                if (currentExtensions >= 5) {
                    document.getElementById('extensionOptions').style.opacity = '0.5';
                    document.getElementById('extensionOptions').style.pointerEvents = 'none';
                    document.getElementById('extensionStatus').textContent = 'Maximum extensions reached.';
                    document.getElementById('btnRequestExtension').style.display = 'none';
                }
            }

            function closeExtensionModal() {
                document.getElementById('extensionModal').classList.remove('open');
            }
            let selectedMinutes = 0;
            document.querySelectorAll('.extension-option').forEach(o => {
                o.addEventListener('click', function() {
                    document.querySelectorAll('.extension-option').forEach(x => x.classList.remove('selected'));
                    this.classList.add('selected');
                    selectedMinutes = parseInt(this.dataset.minutes);
                    document.getElementById('btnRequestExtension').disabled = false;
                });
            });
            document.getElementById('btnRequestExtension').addEventListener('click', async function() {
                if (!selectedMinutes) return;
                const btn = this;
                btn.textContent = 'Checking...';
                btn.disabled = true;
                try {
                    const res = await fetch('process_extension.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            record_id: activeRecordId,
                            minutes: selectedMinutes
                        })
                    });
                    const data = await res.json();
                    if (data.success) {
                        alert(`✅ ${data.message}`);
                        location.reload();
                    } else {
                        alert(`❌ ${data.message}`);
                    }
                } catch (err) {
                    alert('Error connecting to server.');
                }
                btn.textContent = 'Request Extension';
                btn.disabled = false;
            });
        <?php endif; ?>
    </script>
    <script src="assets/js/reservation.js"></script>
</body>

</html>