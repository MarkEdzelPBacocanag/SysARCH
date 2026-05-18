<?php
session_start();
require 'database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location:index.php');
    exit;
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$current_rank = null;
$my_breakdown = null;
$stmt = $pdo->query("SELECT s.id,s.fname,s.lname,s.mname,s.course,s.course_level,COALESCE(s.reward_points,0) as reward_points,COALESCE(s.total_rewards_earned,0) as total_rewards_earned,COALESCE(s.tasks_completed,0) as tasks_completed,COALESCE(SUM(TIMESTAMPDIFF(HOUR,r.date_created,CASE WHEN r.status='completed' THEN r.date_ended WHEN r.status='active' THEN NOW() ELSE NULL END)),0) as total_hours,ROUND(COALESCE(s.total_rewards_earned,0)*0.60,2) as totalreward,ROUND(COALESCE(SUM(TIMESTAMPDIFF(HOUR,r.date_created,CASE WHEN r.status='completed' THEN r.date_ended WHEN r.status='active' THEN NOW() ELSE NULL END)),0)*0.20,2) as totalhours,ROUND(COALESCE(s.tasks_completed,0)*0.20,2) as taskcompleted,ROUND((COALESCE(s.total_rewards_earned,0)*0.60)+(COALESCE(SUM(TIMESTAMPDIFF(HOUR,r.date_created,CASE WHEN r.status='completed' THEN r.date_ended WHEN r.status='active' THEN NOW() ELSE NULL END)),0)*0.20)+(COALESCE(s.tasks_completed,0)*0.20),2) as leaderboard_score FROM students s LEFT JOIN sitin_records r ON s.id=r.student_id WHERE s.role='student' GROUP BY s.id ORDER BY leaderboard_score DESC,s.fname ASC LIMIT 50");
$leaderboard = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT s.id,COALESCE(s.total_rewards_earned,0) as total_rewards_earned,COALESCE(s.tasks_completed,0) as tasks_completed,COALESCE(SUM(TIMESTAMPDIFF(HOUR,r.date_created,CASE WHEN r.status='completed' THEN r.date_ended ELSE NOW() END)),0) as my_hours,ROUND((COALESCE(s.total_rewards_earned,0)*0.60)+(COALESCE(SUM(TIMESTAMPDIFF(HOUR,r.date_created,CASE WHEN r.status='completed' THEN r.date_ended ELSE NOW() END)),0)*0.20)+(COALESCE(s.tasks_completed,0)*0.20),2) as my_score FROM students s LEFT JOIN sitin_records r ON s.id=r.student_id WHERE s.id=? AND s.role='student' GROUP BY s.id");
$stmt->execute([$_SESSION['user_id']]);
$my_data = $stmt->fetch();
if ($my_data) {
    $stmt = $pdo->query("SELECT COUNT(*)+1 as rank FROM (SELECT s.id,ROUND((COALESCE(s.total_rewards_earned,0)*0.60)+(COALESCE(SUM(TIMESTAMPDIFF(HOUR,r.date_created,CASE WHEN r.status='completed' THEN r.date_ended ELSE NOW() END)),0)*0.20)+(COALESCE(s.tasks_completed,0)*0.20),2) as score FROM students s LEFT JOIN sitin_records r ON s.id=r.student_id WHERE s.role='student' GROUP BY s.id) as ranked WHERE score>{$my_data['my_score']}");
    $current_rank = $stmt->fetchColumn();
    $my_breakdown = ['reward' => round($my_data['total_rewards_earned'] * 0.60, 2), 'hours' => round($my_data['my_hours'] * 0.20, 2), 'tasks' => round($my_data['tasks_completed'] * 0.20, 2), 'total' => $my_data['my_score']];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Student Leaderboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .leaderboard-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem
        }

        .leaderboard-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
            overflow: hidden
        }

        .leaderboard-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            padding: 25px;
            text-align: center
        }

        .leaderboard-header h2 {
            margin: 0 0 10px;
            font-size: 1.8rem
        }

        .leaderboard-header p {
            margin: 0;
            opacity: .9;
            font-size: .95rem
        }

        .leaderboard-body {
            padding: 20px
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse
        }

        .leaderboard-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: .85rem
        }

        .leaderboard-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: .9rem
        }

        .leaderboard-table tr:hover {
            background: #f8f9fa
        }

        .leaderboard-table tr.current-user {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            border-left: 4px solid #667eea
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: 700;
            font-size: .85rem
        }

        .rank-1 {
            background: #FFD700;
            color: #333
        }

        .rank-2 {
            background: #C0C0C0;
            color: #333
        }

        .rank-3 {
            background: #CD7F32;
            color: #fff
        }

        .rank-other {
            background: #6c757d;
            color: #fff
        }

        .score-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: .9rem
        }

        .breakdown {
            display: flex;
            gap: 8px;
            font-size: .8rem;
            color: #666;
            flex-wrap: wrap;
            margin-top: 4px
        }

        .breakdown span {
            background: #f1f3f4;
            padding: 2px 8px;
            border-radius: 10px
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999
        }

        .formula-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
            font-size: .9rem
        }

        .formula-box code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace
        }

        .my-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center
        }

        .my-breakdown .score {
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea
        }

        .my-breakdown .components {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
            font-size: .9rem
        }
    </style>
</head>

<body data-user-id="<?= $_SESSION['user_id'] ?>">

    <div class="toast-container"><?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?><?php if ($error): ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?></div>

    <div class="container-nav">
        <div style="padding-left:3rem;">
            <h2>Leaderboard</h2>
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

    <div class="leaderboard-container">
        <div class="leaderboard-card">
            <div class="leaderboard-header">
                <h2>🏅 Top Students</h2>
                <p>Ranked by performance and engagement</p>
            </div>
            <div class="leaderboard-body">
                <div class="formula-box"><strong>📊 Scoring Formula:</strong><br><code>totalreward = points × 0.60</code><br><code>totalhours = hours × 0.20</code><br><code>taskcompleted = tasks × 0.20</code><br><strong>Score = totalreward + totalhours + taskcompleted</strong></div><?php if (count($leaderboard) > 0): ?><table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th style="width:50px;">Rank</th>
                                <th>Student</th>
                                <th style="width:80px;">Reward<br><small>(×0.60)</small></th>
                                <th style="width:80px;">Hours<br><small>(×0.20)</small></th>
                                <th style="width:80px;">Tasks<br><small>(×0.20)</small></th>
                                <th style="width:70px;">Score</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($leaderboard as $index => $row): $rank = $index + 1;
                                                                                                                                                                                                                                                                                                    $is_current = ($_SESSION['role'] === 'student' && $row['id'] === $_SESSION['user_id']);
                                                                                                                                                                                                                                                                                                    $full_name = trim($row['fname'] . ' ' . ($row['mname'] ? substr($row['mname'], 0, 1) . '. ' : '') . $row['lname']); ?><tr class="<?= $is_current ? 'current-user' : '' ?>">
                                    <td><span class="rank-badge rank-<?= $rank <= 3 ? $rank : 'other' ?>"><?= $rank ?></span></td>
                                    <td><strong><?= htmlspecialchars($full_name) ?></strong><br><small style="color:#666;"><?= htmlspecialchars($row['course']) ?> <?= htmlspecialchars($row['course_level']) ?>Y</small><?php if ($is_current): ?><span style="color:#667eea;font-size:.75rem;">(You)</span><?php endif; ?></td>
                                    <td><strong style="color:#d4af37;"><?= number_format($row['totalreward'], 2) ?></strong>
                                        <div class="breakdown"><span>🏅 <?= $row['reward_points'] ?> pts</span></div>
                                    </td>
                                    <td><strong style="color:#17a2b8;"><?= number_format($row['totalhours'], 2) ?></strong>
                                        <div class="breakdown"><span>⏱️ <?= number_format($row['total_hours'], 1) ?>h</span></div>
                                    </td>
                                    <td><strong style="color:#28a745;"><?= number_format($row['taskcompleted'], 2) ?></strong>
                                        <div class="breakdown"><span>✅ <?= $row['tasks_completed'] ?></span></div>
                                    </td>
                                    <td><span class="score-badge"><?= number_format($row['leaderboard_score'], 2) ?></span></td>
                                </tr><?php endforeach; ?></tbody>
                    </table><?php else: ?><div class="no-data">
                        <p>📭 No student data available yet.</p>
                    </div><?php endif; ?><?php if (($_SESSION['role'] ?? '') === 'student' && $my_breakdown): ?><div class="my-breakdown">
                        <div class="score">Your Score: <?= number_format($my_breakdown['total'], 2) ?></div>
                        <div class="components"><span>🏅 Reward: <?= number_format($my_breakdown['reward'], 2) ?></span><span>⏱️ Hours: <?= number_format($my_breakdown['hours'], 2) ?></span><span>✅ Tasks: <?= number_format($my_breakdown['tasks'], 2) ?></span></div><?php if ($current_rank): ?><div style="margin-top:10px;font-size:.9rem;">Current Rank: <span class="rank-badge rank-<?= $current_rank <= 3 ? $current_rank : 'other' ?>">#<?= $current_rank ?></span></div><?php endif; ?>
                    </div><?php endif; ?>
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
    </script>

    <script src="assets/js/reservation.js"></script>
</body>

</html>