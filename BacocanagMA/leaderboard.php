<?php
session_start();
require 'database.php';

// Allow both admin and student to view
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Fetch leaderboard data with CORRECT formula
// Formula: Score = (reward_points × 0.60) + (total_hours × 0.20) + (tasks_completed × 0.20)
$stmt = $pdo->query("
    SELECT 
        s.id,
        s.fname,
        s.lname,
        s.mname,
        s.course,
        s.course_level,
        s.reward_points,
        s.tasks_completed,
        -- Calculate total hours from ALL sit-in records (active + completed)
        COALESCE(SUM(
            TIMESTAMPDIFF(HOUR, r.date_created, 
                CASE 
                    WHEN r.status = 'completed' THEN r.date_created 
                    ELSE NOW() 
                END
            )
        ), 0) as total_hours,
        
        -- ✅ EXACT FORMULA COMPONENTS (for display)
        ROUND(s.total_rewards_earned * 0.60, 2) as totalreward,
        ROUND(COALESCE(SUM(
            TIMESTAMPDIFF(HOUR, r.date_created, 
                CASE 
                    WHEN r.status = 'completed' THEN r.date_created 
                    ELSE NOW() 
                END
            )
        ), 0) * 0.20, 2) as totalhours,
        ROUND(s.tasks_completed * 0.20, 2) as taskcompleted,
        
        -- ✅ FINAL SCORE: Sum of all three components
        ROUND(
            (s.total_rewards_earned * 0.60) + 
            (COALESCE(SUM(
                TIMESTAMPDIFF(HOUR, r.date_created, 
                    CASE 
                        WHEN r.status = 'completed' THEN r.date_created 
                        ELSE NOW() 
                    END
                )
            ), 0) * 0.20) + 
            (s.tasks_completed * 0.20), 
            2
        ) as leaderboard_score
        
    FROM students s
    LEFT JOIN sitin_records r ON s.id = r.student_id
    WHERE s.role = 'student'
    GROUP BY s.id
    ORDER BY leaderboard_score DESC, s.fname ASC
    LIMIT 50
");
$leaderboard = $stmt->fetchAll();

// Get current user's rank for highlighting
$current_rank = null;
$my_breakdown = null;
if (($_SESSION['role'] ?? '') === 'student') {
    $stmt = $pdo->prepare("
        SELECT 
            s.id,
            s.reward_points,
            s.tasks_completed,
            COALESCE(SUM(
                TIMESTAMPDIFF(HOUR, r.date_created, 
                    CASE 
                        WHEN r.status = 'completed' THEN r.date_created 
                        ELSE NOW() 
                    END
                )
            ), 0) as my_hours,
            ROUND(
                (s.reward_points * 0.60) + 
                (COALESCE(SUM(
                    TIMESTAMPDIFF(HOUR, r.date_created, 
                        CASE 
                            WHEN r.status = 'completed' THEN r.date_created 
                            ELSE NOW() 
                        END
                    )
                ), 0) * 0.20) + 
                (s.tasks_completed * 0.20), 
                2
            ) as my_score
        FROM students s
        LEFT JOIN sitin_records r ON s.id = r.student_id
        WHERE s.id = ? AND s.role = 'student'
        GROUP BY s.id
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $my_data = $stmt->fetch();

    if ($my_data) {
        // Calculate rank
        $stmt = $pdo->query("
            SELECT COUNT(*) + 1 as rank FROM (
                SELECT 
                    s.id,
                    ROUND(
                        (s.reward_points * 0.60) + 
                        (COALESCE(SUM(
                            TIMESTAMPDIFF(HOUR, r.date_created, 
                                CASE 
                                    WHEN r.status = 'completed' THEN r.date_created 
                                    ELSE NOW() 
                                END
                            )
                        ), 0) * 0.20) + 
                        (s.tasks_completed * 0.20), 
                        2
                    ) as score
                FROM students s
                LEFT JOIN sitin_records r ON s.id = r.student_id
                WHERE s.role = 'student'
                GROUP BY s.id
            ) as ranked
            WHERE score > {$my_data['my_score']}
        ");
        $current_rank = $stmt->fetchColumn();

        // Store breakdown for display
        $my_breakdown = [
            'reward' => round($my_data['reward_points'] * 0.60, 2),
            'hours' => round($my_data['my_hours'] * 0.20, 2),
            'tasks' => round($my_data['tasks_completed'] * 0.20, 2),
            'total' => $my_data['my_score']
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🏆 Student Leaderboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .leaderboard-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .leaderboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .leaderboard-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .leaderboard-header h2 {
            margin: 0 0 10px 0;
            font-size: 1.8rem;
        }

        .leaderboard-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        .leaderboard-body {
            padding: 20px;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
            font-size: 0.85rem;
        }

        .leaderboard-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }

        .leaderboard-table tr:hover {
            background: #f8f9fa;
        }

        .leaderboard-table tr.current-user {
            background: linear-gradient(135deg, #667eea20 0%, #764ba220 100%);
            border-left: 4px solid #667eea;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .rank-1 {
            background: #FFD700;
            color: #333;
        }

        .rank-2 {
            background: #C0C0C0;
            color: #333;
        }

        .rank-3 {
            background: #CD7F32;
            color: white;
        }

        .rank-other {
            background: #6c757d;
            color: white;
        }

        .score-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .breakdown {
            display: flex;
            gap: 8px;
            font-size: 0.8rem;
            color: #666;
            flex-wrap: wrap;
            margin-top: 4px;
        }

        .breakdown span {
            background: #f1f3f4;
            padding: 2px 8px;
            border-radius: 10px;
        }

        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .formula-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 12px 15px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
            font-size: 0.9rem;
        }

        .formula-box code {
            background: #fff;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }

        .my-breakdown {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }

        .my-breakdown .score {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .my-breakdown .components {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 10px;
            font-size: 0.9rem;
        }
    </style>
</head>

<body>
    <!-- NAVIGATION -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>🏆 Student Dashboard</h2>
        </div>
        <div class="link-ref">
            <a href="dashboard_student.php">
                <p>Home</p>
            </a>
            <a href="leaderboard.php">
                <p>🏆 Leaderboard</p>
            </a>
            <a href="edit_profile.php">
                <p>Edit Profile</p>
            </a>
            <a href="student_history.php">
                <p>History</p>
            </a>
            <button class="btn btn-primary" data-modal-open="reservationModal">🖥️ Reserve a PC</button>
            <button class="logout-button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="leaderboard-container">
        <div class="leaderboard-card">
            <div class="leaderboard-header">
                <h2>🏅 Top Students</h2>
                <p>Ranked by performance and engagement</p>
            </div>
            <div class="leaderboard-body">

                <!-- ✅ Formula Explanation (EXACT as requested) -->
                <div class="formula-box">
                    <strong>📊 Scoring Formula:</strong><br>
                    <code>totalreward = points × 0.60</code><br>
                    <code>totalhours = hours × 0.20</code><br>
                    <code>taskcompleted = tasks × 0.20</code><br>
                    <strong>Score = totalreward + totalhours + taskcompleted</strong>
                </div>

                <?php if (count($leaderboard) > 0): ?>
                    <table class="leaderboard-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">Rank</th>
                                <th>Student</th>
                                <th style="width: 80px;">Reward<br><small>(×0.60)</small></th>
                                <th style="width: 80px;">Hours<br><small>(×0.20)</small></th>
                                <th style="width: 80px;">Tasks<br><small>(×0.20)</small></th>
                                <th style="width: 70px;">Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaderboard as $index => $row):
                                $rank = $index + 1;
                                $is_current = ($_SESSION['role'] === 'student' && $row['id'] === $_SESSION['user_id']);
                                $full_name = trim($row['fname'] . ' ' . ($row['mname'] ? substr($row['mname'], 0, 1) . '. ' : '') . $row['lname']);
                            ?>
                                <tr class="<?= $is_current ? 'current-user' : '' ?>">
                                    <td>
                                        <span class="rank-badge rank-<?= $rank <= 3 ? $rank : 'other' ?>">
                                            <?= $rank ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($full_name) ?></strong><br>
                                        <small style="color:#666;"><?= htmlspecialchars($row['course']) ?> <?= htmlspecialchars($row['course_level']) ?>Y</small>
                                        <?php if ($is_current): ?>
                                            <span style="color: #667eea; font-size: 0.75rem;">(You)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color:#d4af37;"><?= number_format($row['totalreward'], 2) ?></strong>
                                        <div class="breakdown"><span>🏅 <?= $row['reward_points'] ?> pts</span></div>
                                    </td>
                                    <td>
                                        <strong style="color:#17a2b8;"><?= number_format($row['totalhours'], 2) ?></strong>
                                        <div class="breakdown"><span>⏱️ <?= number_format($row['total_hours'], 1) ?>h</span></div>
                                    </td>
                                    <td>
                                        <strong style="color:#28a745;"><?= number_format($row['taskcompleted'], 2) ?></strong>
                                        <div class="breakdown"><span>✅ <?= $row['tasks_completed'] ?></span></div>
                                    </td>
                                    <td><span class="score-badge"><?= number_format($row['leaderboard_score'], 2) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">
                        <p>📭 No student data available yet.</p>
                        <p style="font-size: 0.9rem; margin-top: 10px;">Start using the lab to appear on the leaderboard!</p>
                    </div>
                <?php endif; ?>

                <!-- ✅ Current User Breakdown (if student) -->
                <?php if (($_SESSION['role'] ?? '') === 'student' && $my_breakdown): ?>
                    <div class="my-breakdown">
                        <div class="score">Your Score: <?= number_format($my_breakdown['total'], 2) ?></div>
                        <div class="components">
                            <span>🏅 Reward: <?= number_format($my_breakdown['reward'], 2) ?></span>
                            <span>⏱️ Hours: <?= number_format($my_breakdown['hours'], 2) ?></span>
                            <span>✅ Tasks: <?= number_format($my_breakdown['tasks'], 2) ?></span>
                        </div>
                        <?php if ($current_rank): ?>
                            <div style="margin-top: 10px; font-size: 0.9rem;">
                                Current Rank:
                                <span class="rank-badge rank-<?= $current_rank <= 3 ? $current_rank : 'other' ?>">
                                    #<?= $current_rank ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- RESERVATION MODAL (Synced) -->
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

                <!-- LIVE STATUS BADGE -->
                <div class="field-group">
                    <label>PC Status:</label>
                    <div id="pcStatusBadge" style="padding: 8px; border-radius: 5px; background: #e9ecef; text-align: center; font-weight: bold; color: #555;">
                        Select Lab & PC to check status
                    </div>
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
        // Modal & Auto-fill Logic
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
            if (modalId === 'reservationModal') {
                const remInput = document.getElementById('resRemaining');
                if (remInput) {
                    fetch(`get_student.php?id=<?= $_SESSION['user_id'] ?>`)
                        .then(res => res.json())
                        .then(data => {
                            remInput.value = data.success ? data.remaining_session : 0;
                        })
                        .catch(() => remInput.value = 'Error');
                }
                checkPCStatus(); // Check immediately on open
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

        // ✅ UPDATED: Date-Aware PC Status Checker
        const resLab = document.getElementById('resLab');
        const resPC = document.getElementById('resPC');
        const resDate = document.getElementById('resDate'); // Get date input
        const pcStatusBadge = document.getElementById('pcStatusBadge');
        const submitBtn = document.getElementById('submitReservation');

        async function checkPCStatus() {
            const lab = resLab.value;
            const pc = resPC.value;
            const date = resDate.value || new Date().toISOString().split('T')[0]; // Fallback to today

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
                // ✅ Pass selected date to backend
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

        // Listen to Lab, PC, AND Date changes
        resLab.addEventListener('change', checkPCStatus);
        resPC.addEventListener('change', checkPCStatus);
        resDate.addEventListener('change', checkPCStatus);
    </script>
</body>

</html>