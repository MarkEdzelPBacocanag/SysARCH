<?php
session_start();
require 'database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header('Location:index.php');
    exit;
}
$student_id = $_SESSION['user_id'];
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
$stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE student_id=? AND is_read=0");
$stmt->execute([$student_id]);
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE student_id=? ORDER BY created_at DESC");
$stmt->execute([$student_id]);
$notifications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notif-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem
        }

        .notif-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
            overflow: hidden
        }

        .notif-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        .notif-list {
            padding: 0;
            margin: 0;
            list-style: none
        }

        .notif-item {
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: flex-start
        }

        .notif-item:last-child {
            border-bottom: none
        }

        .notif-item.unread {
            background: #f8f9fa;
            border-left: 4px solid #007bff
        }

        .notif-content h4 {
            margin: 0 0 5px;
            font-size: 1rem
        }

        .notif-content p {
            margin: 0;
            color: #666;
            font-size: .9rem
        }

        .notif-time {
            font-size: .8rem;
            color: #999;
            white-space: nowrap;
            margin-left: 15px
        }

        .empty-notif {
            text-align: center;
            padding: 40px;
            color: #999
        }

        .badge-type {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: .7rem;
            font-weight: 700;
            margin-right: 8px
        }

        .badge-reward {
            background: #fff3cd;
            color: #856404
        }

        .badge-feedback {
            background: #d4edda;
            color: #155724
        }

        .badge-reservation {
            background: #d1ecf1;
            color: #0c5460
        }
    </style>
</head>

<body data-user-id="<?= $_SESSION['user_id'] ?>">

    <div class="toast-container"><?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?><?php if ($error): ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?></div>

    <div class="container-nav">
        <div style="padding-left:3rem;">
            <h2>Notifications</h2>
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
            <button class="btn btn-primary" data-modal-open="reservationModal">🖥️ Reserve a PC</button>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>
    <div class="notif-container">
        <div class="notif-card">
            <div class="notif-header">
                <h3>🔔 My Notifications</h3><span style="font-size:.9rem;"><?= count($notifications) ?> total</span>
            </div>
            <ul class="notif-list"><?php if (count($notifications) > 0): ?><?php foreach ($notifications as $notif): ?><li class="notif-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                    <div class="notif-content">
                        <h4><span class="badge-type <?= $notif['type'] === 'reward' ? 'badge-reward' : ($notif['type'] === 'feedback' ? 'badge-feedback' : 'badge-reservation') ?>"><?= $notif['type'] === 'reward' ? '🎁 Reward' : ($notif['type'] === 'feedback' ? '📝 Feedback' : '🖥️ Reservation') ?></span><?= htmlspecialchars($notif['title']) ?></h4>
                        <p><?= htmlspecialchars($notif['message']) ?></p>
                    </div><span class="notif-time"><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></span>
                </li><?php endforeach; ?><?php else: ?><li class="empty-notif">📭 No notifications yet.</li><?php endif; ?></ul>
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
    </script>

    <script src="assets/js/reservation.js"></script>
</body>

</html>