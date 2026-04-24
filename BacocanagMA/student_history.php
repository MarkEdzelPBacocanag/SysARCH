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

    // Get Toast Messages
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
            /* History Page Styles */
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

            /* Toast */
            .toast-container {
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 3000;
            }

            .toast {
                padding: 12px 20px;
                border-radius: 8px;
                color: white;
                margin-bottom: 10px;
                animation: slideIn 0.3s ease-out;
            }

            .toast.success {
                background-color: #28a745;
            }

            .toast.error {
                background-color: #dc3545;
            }

            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }

                to {
                    transform: translateX(0);
                    opacity: 1;
                }
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
                <a href="dashboard_student.php">
                    <p>Home</p>
                </a>
                <a href="edit_profile.php">
                    <p>Edit Profile</p>
                </a>
                <a href="student_history.php">
                    <p>History</p>
                </a>
                <button class="logout-button" onclick="window.location.href='logout.php';">Log out</button>
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
                                    <th>Pc</th>
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
                                        <td><?= htmlspecialchars($row['pc_number']) ?></td>
                                        <td>
                                            <?php if ($row['status'] === 'active'): ?>
                                                <span class="status-badge status-active">Active</span>
                                            <?php else: ?>
                                                <span class="status-badge status-completed">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn-feedback"
                                                onclick="openFeedbackModal(<?= $row['id'] ?>)">
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
                    <textarea name="message" placeholder="Write your feedback, report an issue, or suggestion here..." required></textarea>
                    <div class="modal-actions">
                        <button type="button" class="btn-feedback" style="background:#6c757d;" onclick="closeFeedbackModal()">Cancel</button>
                        <button type="submit" class="btn-feedback" style="background:#28a745;">Submit Feedback</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openFeedbackModal(recordId) {
                document.getElementById('feedbackModal').classList.add('open');
                // Store the ID in a hidden input so we know which record this feedback is for
                document.getElementById('feedbackRecordId').value = recordId;
            }

            function closeFeedbackModal() {
                document.getElementById('feedbackModal').classList.remove('open');
            }
            // Close modal if clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('feedbackModal');
                if (event.target == modal) {
                    closeFeedbackModal();
                }
            }
        </script>

    </body>

    </html>