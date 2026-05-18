<?php
session_start();
require 'database.php';
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'student') {
    header("Location: index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();
if (!$student) {
    session_destroy();
    header("Location: index.php");
    exit;
}

$announcements = [];
try {
    $stmt = $pdo->query("SELECT a.*, s.fname, s.lname FROM announcements a JOIN students s ON a.admin_id = s.id ORDER BY a.created_at DESC LIMIT 10");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
}

$stmt = $pdo->prepare("SELECT id, lab, pc_number, purpose, date_created, end_time, extension_count FROM sitin_records WHERE student_id = ? AND status = 'active' ORDER BY date_created DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$activeSession = $stmt->fetch();

$stmt = $pdo->prepare("SELECT id, lab, pc_number, purpose, date_created FROM sitin_records WHERE student_id = ? AND status = 'pending' ORDER BY date_created DESC");
$stmt->execute([$_SESSION['user_id']]);
$pending_sessions = $stmt->fetchAll();

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$yearLevel = intval($student['course_level']);
$yearLabel = match ($yearLevel) {
    1 => '1st year',
    2 => '2nd year',
    3 => '3rd year',
    4 => '4th year',
    default => ($yearLevel > 0 ? $yearLevel . 'th year' : 'N/A')
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .student-dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 1.5rem;
            background: #f4f4f4;
            min-height: calc(100vh - 60px)
        }

        .student-panel {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .1);
            overflow: hidden;
            flex: 1;
            min-width: 300px
        }

        .student-panel.info-panel {
            flex: 0 0 320px
        }

        .student-panel.rules-panel {
            flex: 1.2
        }

        .panel-header {
            background: #007bff;
            color: #fff;
            padding: 12px 15px;
            font-weight: 700;
            font-size: 1rem
        }

        .panel-body {
            padding: 20px
        }

        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
            margin-bottom: 15px;
            background: #e9ecef
        }

        .profile-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px
        }

        .profile-id {
            color: #666;
            font-size: .9rem;
            margin-bottom: 15px
        }

        .info-list {
            width: 100%;
            text-align: left
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee
        }

        .info-item:last-child {
            border-bottom: none
        }

        .info-label {
            font-weight: 600;
            color: #555
        }

        .info-value {
            color: #333
        }

        .session-badge {
            background: #28a745;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700
        }

        .session-badge.low {
            background: #dc3545
        }

        .session-badge.warning {
            background: #ffc107;
            color: #333
        }

        .edit-profile-btn {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 700;
            transition: background .3s
        }

        .edit-profile-btn:hover {
            background: #0056b3
        }

        .announcement-item {
            border-bottom: 1px solid #eee;
            padding: 12px 0
        }

        .announcement-item:last-child {
            border-bottom: none
        }

        .announcement-item h4 {
            margin: 0 0 5px;
            font-size: .85rem;
            color: #007bff
        }

        .announcement-item p {
            margin: 0;
            color: #555;
            font-size: .95rem;
            line-height: 1.5
        }

        .no-announcements {
            text-align: center;
            color: #999;
            padding: 20px
        }

        .rules-content h3 {
            text-align: center;
            margin: 0 0 5px;
            color: #333
        }

        .rules-content h4 {
            text-align: center;
            margin: 0 0 15px;
            color: #555;
            font-weight: 400
        }

        .rules-content h5 {
            text-align: center;
            margin: 15px 0 10px;
            color: #007bff
        }

        .rules-content p {
            text-align: center;
            font-size: .9rem;
            color: #666;
            margin-bottom: 15px
        }

        .rules-content ol {
            padding-left: 20px;
            margin: 0
        }

        .rules-content ol li {
            padding: 8px 0;
            line-height: 1.5;
            color: #444;
            font-size: .9rem;
            border-bottom: 1px dashed #eee
        }

        .rules-content ol li:last-child {
            border-bottom: none
        }

        @media(max-width:992px) {
            .student-dashboard {
                flex-direction: column
            }

            .student-panel {
                flex: none;
                width: 100%
            }
        }
    </style>
</head>

<body data-user-id="<?= $_SESSION['user_id'] ?>">

    <div class="toast-container">
        <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    </div>

    <div class="container-nav">
        <div style="padding-left:3rem;">
            <h2>Student Dashboard</h2>
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

    <div class="student-dashboard">
        <div class="student-panel info-panel">
            <div class="student-panel" style="flex:1 1 100%;">
                <div class="panel-header">🖥️ Session</div>
                <div class="panel-body">
                    <?php if (count($pending_sessions) > 0): ?>
                        <div style="overflow-x:auto;">
                            <table class="data-table" style="width:100%;border-collapse:collapse;margin:0;">
                                <thead>
                                    <tr>
                                        <th>Lab</th>
                                        <th>PC</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody><?php foreach ($pending_sessions as $sess): ?><tr>
                                            <td><?= htmlspecialchars($sess['lab']) ?></td>
                                            <td><?= htmlspecialchars($sess['pc_number']) ?></td>
                                            <td>
                                                <form method="POST" action="start_student_session.php" style="display:inline;margin:0;"><input type="hidden" name="record_id" value="<?= $sess['id'] ?>"><button type="submit" class="btn btn-primary" style="padding:5px 12px;font-size:.85rem;white-space:nowrap;">▶ Start Session</button></form>
                                            </td>
                                        </tr><?php endforeach; ?></tbody>
                            </table>
                        </div>
                    <?php else: ?><div class="no-announcements" style="padding:20px;text-align:center;">
                            <p>📭 No approved reservations waiting to start.</p><small>Submit a reservation and wait for admin approval.</small>
                        </div><?php endif; ?>
                </div>
            </div>
            <div class="panel-header">📋 Student Information</div>
            <div class="panel-body">
                <div class="profile-section">
                    <?php $profilePic = $student['profile_picture'] ?? 'default_profile.png';
                    $profilePath = 'uploads/profiles/' . $profilePic;
                    if (!file_exists($profilePath) || empty($student['profile_picture'])) $profilePath = 'uploads/profiles/default_profile.png'; ?>
                    <img src="<?= htmlspecialchars($profilePath) ?>" alt="Profile Picture" class="profile-picture" onerror="this.src='https://via.placeholder.com/120?text=No+Photo'">
                    <div class="profile-name"><?= htmlspecialchars($student['fname'] . ' ' . ($student['mname'] ? substr($student['mname'], 0, 1) . '. ' : '') . $student['lname']) ?></div>
                    <div class="profile-id">ID: <?= htmlspecialchars($student['id']) ?></div>
                    <div class="info-list">
                        <div class="info-item"><span class="info-label">Course:</span><span class="info-value"><?= htmlspecialchars($student['course']) ?></span></div>
                        <div class="info-item"><span class="info-label">Year Level:</span><span class="info-value"><?= htmlspecialchars($yearLabel) ?></span></div>
                        <div class="info-item"><span class="info-label">Email:</span><span class="info-value" style="font-size:.85rem;"><?= htmlspecialchars($student['email']) ?></span></div>
                        <div class="info-item"><span class="info-label">Address:</span><span class="info-value" style="font-size:.85rem;"><?= htmlspecialchars($student['address']) ?></span></div>
                        <div class="info-item"><span class="info-label">Sessions Left:</span><?php $sessions = $student['remaining_session'] ?? 30;
                                                                                                $badgeClass = 'session-badge';
                                                                                                if ($sessions <= 5) $badgeClass .= ' low';
                                                                                                elseif ($sessions <= 10) $badgeClass .= ' warning'; ?><span class="<?= $badgeClass ?>"><?= htmlspecialchars($sessions) ?></span></div>
                        <div class="info-item"><span class="info-label">🏅 Reward Points:</span><?php $rPoints = $student['reward_points'] ?? 0;
                                                                                                $rProgress = $rPoints / 3;
                                                                                                $barColor = $rPoints >= 2 ? '#28a745' : ($rPoints >= 1 ? '#ffc107' : '#6c757d'); ?><span class="info-value" style="display:flex;align-items:center;gap:8px;"><?= $rPoints ?>/3<div style="width:60px;height:8px;background:#e9ecef;border-radius:4px;overflow:hidden;">
                                    <div style="width:<?= $rProgress * 100 ?>%;height:100%;background:<?= $barColor ?>;transition:width .3s;"></div>
                                </div></span></div>
                        <small style="color:#666;display:block;margin-top:5px;text-align:center;">Earn 3 points to get +1 free session!</small>
                    </div><button class="edit-profile-btn" onclick="window.location.href='edit_profile.php'">✏️ Edit Profile</button>
                </div>
            </div>
        </div>
        <div class="student-panel">
            <div class="panel-header">📢 Announcements</div>
            <div class="panel-body">
                <?php if (count($announcements) > 0): ?><?php foreach ($announcements as $ann): ?><div class="announcement-item">
                    <h4><?= htmlspecialchars($ann['fname'] . ' ' . $ann['lname']) ?> | <?= date('Y-M-d', strtotime($ann['created_at'])) ?></h4>
                    <p><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                </div><?php endforeach; ?><?php else: ?><div class="no-announcements">
                    <p>📭 No announcements yet.</p>
                </div><?php endif; ?>
            </div>
        </div>
        <div class="student-panel rules-panel">
            <div class="panel-header">📜 Rules and Regulations</div>
            <div class="panel-body">
                <div class="rules-content">
                    <h3>University of Cebu</h3>
                    <h4>COLLEGE OF INFORMATION & COMPUTER STUDIES</h4>
                    <h5>LABORATORY RULES AND REGULATIONS</h5>
                    <p>To maintain order and ensure a productive learning environment, please observe the following:</p>
                    <ol>
                        <li>Maintain silence, proper decorum, and discipline inside the laboratory. Mobile phones, walkmans and other personal pieces of equipment must be switched off.</li>
                        <li>Games are not allowed inside the lab. This includes computer-related games, card games and other games that may disturb the operation of the lab.</li>
                        <li>Surfing the Internet is allowed only with the permission of the instructor. Downloading and installing of software are strictly prohibited.</li>
                        <li>Food and drinks are not allowed inside the laboratory at all times.</li>
                        <li>Always keep the laboratory clean. Dispose of waste materials properly in the designated trash bins.</li>
                        <li>Report any malfunctioning equipment to the laboratory instructor immediately. Do not attempt to repair any equipment without proper authorization.</li>
                        <li>All students must wear their proper ID when entering the laboratory.</li>
                        <li>Always log off from your account before leaving the laboratory to prevent unauthorized access.</li>
                        <li>Respect the laboratory equipment and other property. Any damage caused by negligence will be subject to disciplinary action.</li>
                        <li>Unauthorized use of laboratory resources may result in suspension of lab privileges.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- ✅ SHARED MODAL INCLUDE -->
    <?php include 'includes/reservation_modal.php'; ?>

    <!-- SHARED MODAL JS & LOGIC -->
    <script>
        function openModal(modalId) {
            const m = document.getElementById(modalId);
            if (!m) return;
            m.classList.add('open');
            m.setAttribute('aria-hidden', 'false');
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            const f = modal.querySelector('form');
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