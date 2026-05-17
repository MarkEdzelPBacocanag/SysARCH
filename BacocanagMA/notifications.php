<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location: index.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Mark all as read when page is visited
$stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0");
$stmt->execute([$student_id]);

// Fetch notifications
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE student_id = ? ORDER BY created_at DESC");
$stmt->execute([$student_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notif-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .notif-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .notif-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notif-list {
            padding: 0;
            margin: 0;
            list-style: none;
        }

        .notif-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .notif-item:last-child {
            border-bottom: none;
        }

        .notif-item.unread {
            background-color: #f8f9fa;
            border-left: 4px solid #007bff;
        }

        .notif-content h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }

        .notif-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        .notif-time {
            font-size: 0.8rem;
            color: #999;
            white-space: nowrap;
            margin-left: 15px;
        }

        .empty-notif {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .badge-type {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-right: 8px;
        }

        .badge-reward {
            background: #fff3cd;
            color: #856404;
        }

        .badge-feedback {
            background: #d4edda;
            color: #155724;
        }

        .badge-reservation {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>

<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Notifications</h2>
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

    <div class="notif-container">
        <div class="notif-card">
            <div class="notif-header">
                <h3>🔔 My Notifications</h3>
                <span style="font-size: 0.9rem;"><?= count($notifications) ?> total</span>
            </div>
            <ul class="notif-list">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notif): ?>
                        <li class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                            <div class="notif-content">
                                <h4>
                                    <span class="badge-type <?= $notif['type'] === 'reward' ? 'badge-reward' : ($notif['type'] === 'feedback' ? 'badge-feedback' : 'badge-reservation') ?>">
                                        <?= $notif['type'] === 'reward' ? '🎁 Reward' : ($notif['type'] === 'feedback' ? '📝 Feedback' : '🖥️ Reservation') ?>
                                    </span>
                                    <?= htmlspecialchars($notif['title']) ?>
                                </h4>
                                <p><?= htmlspecialchars($notif['message']) ?></p>
                            </div>
                            <span class="notif-time"><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="empty-notif">📭 No notifications yet.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</body>
<!--Modal for PC Reservation -->
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
            pcStatusBadge.textContent = `🟢 ${data.status}`;
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

</html>