<?php
session_start();
require 'database.php';

// Check if student is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'student') {
    header("Location: dashboard_admin.php");
    exit;
}

// Get student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Get announcements (same as admin dashboard)
$announcements = [];
try {
    $stmt = $pdo->query("SELECT a.*, s.fname, s.lname 
                         FROM announcements a 
                         JOIN students s ON a.admin_id = s.id 
                         ORDER BY a.created_at DESC 
                         LIMIT 10");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    // Table might not exist yet
}

// Success/Error messages
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Get student's sit-in history
$stmt = $pdo->prepare("SELECT * FROM sitin_records 
                       WHERE student_id = ? 
                       ORDER BY date_created DESC");
$stmt->execute([$_SESSION['user_id']]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Student Dashboard Specific Styles */
        .student-dashboard {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            padding: 1.5rem;
            background-color: #f4f4f4;
            min-height: calc(100vh - 60px);
        }

        .student-panel {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
            flex: 1;
            min-width: 300px;
        }

        .student-panel.info-panel {
            flex: 0 0 320px;
        }

        .student-panel.rules-panel {
            flex: 1.2;
        }

        .panel-header {
            background-color: #007bff;
            color: white;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 1rem;
        }

        .panel-body {
            padding: 20px;
        }

        /* Profile Section */
        .profile-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
            margin-bottom: 15px;
            background-color: #e9ecef;
        }

        .profile-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .profile-id {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .info-list {
            width: 100%;
            text-align: left;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #555;
        }

        .info-value {
            color: #333;
        }

        .session-badge {
            background-color: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: bold;
        }

        .session-badge.low {
            background-color: #dc3545;
        }

        .session-badge.warning {
            background-color: #ffc107;
            color: #333;
        }

        .edit-profile-btn {
            margin-top: 15px;
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .edit-profile-btn:hover {
            background-color: #0056b3;
        }

        /* Announcements */
        .announcement-item {
            border-bottom: 1px solid #eee;
            padding: 12px 0;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-item h4 {
            margin: 0 0 5px 0;
            font-size: 0.85rem;
            color: #007bff;
        }

        .announcement-item p {
            margin: 0;
            color: #555;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .no-announcements {
            text-align: center;
            color: #999;
            padding: 20px;
        }

        /* Rules Section */
        .rules-content h3 {
            text-align: center;
            margin: 0 0 5px 0;
            color: #333;
        }

        .rules-content h4 {
            text-align: center;
            margin: 0 0 15px 0;
            color: #555;
            font-weight: normal;
        }

        .rules-content h5 {
            text-align: center;
            margin: 15px 0 10px 0;
            color: #007bff;
        }

        .rules-content p {
            text-align: center;
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 15px;
        }

        .rules-content ol {
            padding-left: 20px;
            margin: 0;
        }

        .rules-content ol li {
            padding: 8px 0;
            line-height: 1.5;
            color: #444;
            font-size: 0.9rem;
            border-bottom: 1px dashed #eee;
        }

        .rules-content ol li:last-child {
            border-bottom: none;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .student-dashboard {
                flex-direction: column;
            }
            .student-panel {
                flex: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- NAVIGATION BAR -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Student Dashboard</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_student.php">Home</a></div>
            <div><a href="notifications.php">Notifications</a></div>
            <div><a href="edit_profile.php">Edit Profile</a></div>
            <div><a href="student_history.php">History</a></div>
            <div><a href="student_reservations.php">Reservations</a></div>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <!-- TOAST NOTIFICATIONS -->
    <div class="toast-container">
        <?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
        <?php if ($error):   ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="student-dashboard">

        <!-- LEFT PANEL: STUDENT INFORMATION -->
        <div class="student-panel info-panel">
            <div class="panel-header">📋 Student Information</div>
            <div class="panel-body">
                <div class="profile-section">
                    <!-- Profile Picture -->
                    <?php 
                    $profilePic = $student['profile_picture'] ?? 'default_profile.png';
                    $profilePath = 'uploads/profiles/' . $profilePic;
                    if (!file_exists($profilePath) || empty($student['profile_picture'])) {
                        $profilePath = 'uploads/profiles/default_profile.png';
                    }
                    ?>
                    <img src="<?= htmlspecialchars($profilePath) ?>" 
                         alt="Profile Picture" 
                         class="profile-picture"
                         onerror="this.src='https://via.placeholder.com/120?text=No+Photo'">

                    <!-- Name -->
                    <div class="profile-name">
                        <?= htmlspecialchars($student['fname'] . ' ' . ($student['mname'] ? substr($student['mname'], 0, 1) . '. ' : '') . $student['lname']) ?>
                    </div>

                    <!-- ID Number -->
                    <div class="profile-id">
                        ID: <?= htmlspecialchars($student['id']) ?>
                    </div>

                    <!-- Info List -->
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label">Course:</span>
                            <span class="info-value"><?= htmlspecialchars($student['course']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Year Level:</span>
                            <span class="info-value"><?= htmlspecialchars($student['course_level']) ?> Year</span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Email:</span>
                            <span class="info-value" style="font-size: 0.85rem;"><?= htmlspecialchars($student['email']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Address:</span>
                            <span class="info-value" style="font-size: 0.85rem;"><?= htmlspecialchars($student['address']) ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Sessions Left:</span>
                            <?php 
                            $sessions = $student['remaining_session'] ?? 30;
                            $badgeClass = 'session-badge';
                            if ($sessions <= 5) $badgeClass .= ' low';
                            elseif ($sessions <= 10) $badgeClass .= ' warning';
                            ?>
                            <span class="<?= $badgeClass ?>"><?= htmlspecialchars($sessions) ?></span>
                        </div>
                    </div>

                    <!-- Edit Profile Button -->
                    <button class="edit-profile-btn" onclick="window.location.href='edit_profile.php'">
                        ✏️ Edit Profile
                    </button>
                </div>
            </div>
        </div>

        <!-- MIDDLE PANEL: ANNOUNCEMENTS -->
        <div class="student-panel">
            <div class="panel-header">📢 Announcements</div>
            <div class="panel-body">
                <?php if (count($announcements) > 0): ?>
                    <?php foreach ($announcements as $ann): ?>
                        <div class="announcement-item">
                            <h4>
                                <?= htmlspecialchars($ann['fname'] . ' ' . $ann['lname']) ?> 
                                | <?= date('Y-M-d', strtotime($ann['created_at'])) ?>
                            </h4>
                            <p><?= nl2br(htmlspecialchars($ann['content'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-announcements">
                        <p>📭 No announcements yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT PANEL: RULES AND REGULATIONS -->
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
</body>
</html>