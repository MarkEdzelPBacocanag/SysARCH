<?php
session_start();
require 'database.php'; // Connect to database

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'admin') {
    // if student tries to access admin dashboard
    header("Location: dashboard.php");
    exit;
}

// 1. Get Student Count
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $studentCount = $stmt->fetchColumn();
} catch (PDOException $e) {
    $studentCount = 0;
}

// Placeholder counts for sit-ins (You can replace these with SQL queries later)
$currentSitin = 0;
$totalSitin = 15;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="container-nav" name="navigation bar">
        <div style="padding-left: 3rem;">
            <h2>College of Computer Studies Admin</h2>
        </div>
        <div class="link-ref">
            <div name="home">
                <a href="#">
                    <p>Home</p>
                </a>
            </div>
            <div name="Search">
                <a href="#" data-modal-open="searchModal">
                    <p>Search</p>
                </a>
            </div>
            <div name="Students">
                <a href="#">
                    <p>Students</p>
                </a>
            </div>
            <div class="dropdown" style="margin: 0px; padding: 0px;">
                <span>Sit-in ▾</span>
                <ul class="dropdown-content">
                    <li><a href="#" data-modal-open="sitinModal">+ Sit in</a></li>
                    <li><a href="#">View Sit-in Records</a></li>
                    <li><a href="#">Sit-in Reports</a></li>
                </ul>
            </div>
            <div><a href="">Feedback Reports</a></div>
            <div><a href="">Reservations</a></div>
            <button class="logout-button" type="button" onclick="window.location.href='index.php';">Log out</button>
        </div>
    </div>

    <!-- MAIN DASHBOARD CONTENT -->
    <div class="dashboard-content">

        <!-- LEFT PANEL: STATISTICS -->
        <div class="panel stats-panel">
            <div class="panel-header">
                <span>Statistics</span>
            </div>
            <div class="panel-body">
                <div class="stat-text">
                    <p><strong>Students Registered:</strong> <?= $studentCount; ?></p>
                    <p><strong>Currently Sit-in:</strong> <?= $currentSitin; ?></p>
                    <p><strong>Total Sit-in:</strong> <?= $totalSitin; ?></p>
                </div>

                <div class="chart-container" style="margin-top: 5rem;">
                    <!-- Canvas for Chart.js -->
                    <canvas id="sitinChart"></canvas>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: ANNOUNCEMENTS -->
        <div class="panel announcement-panel">
            <div class="panel-header">
                <span>Announcement</span>
            </div>
            <div class="panel-body">
                <form action="#" method="POST" class="announcement-form">
                    <label for="announce-body">New Announcement</label>
                    <textarea id="announce-body" rows="4" placeholder="Type your announcement here..."></textarea>
                    <button type="button" class="post-btn">Submit</button>
                </form>

                <hr class="divider">

                <h3>Posted Announcement</h3>

                <!-- Static Example of a Post (Fetch from DB later) -->
                <div class="announcement-item">
                    <h4>CCS Admin | 2026-Feb-11</h4>
                    <p>Welcome to the new Sit-in Monitoring System.</p>
                </div>

                <div class="announcement-item">
                    <h4>CCS Admin | 2024-May-08</h4>
                    <p>Important Announcement: We are excited to announce the launch of our new website! 🚀 Explore our latest products and services now!</p>
                </div>
            </div>
        </div>

    </div>

    <!-- JAVASCRIPT FOR PIE CHART -->
    <script>
        const ctx = document.getElementById('sitinChart').getContext('2d');
        const sitinChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['C#', 'C', 'Java', 'ASP.Net', 'Php'],
                datasets: [{
                    label: 'Sit-in Languages',
                    data: [15, 10, 25, 5, 10], // Replace with dynamic PHP data later
                    backgroundColor: [
                        '#36A2EB', // Blue
                        '#FF6384', // Red/Pink
                        '#FFCE56', // Yellow
                        '#FF9F40', // Orange
                        '#4BC0C0' // Teal
                    ],
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
        // Modal open/close logic (from previous answer)
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
            // Reset form when closing
            const form = modal.querySelector('form');
            if (form) form.reset();
            // Clear readonly fields
            const nameField = document.getElementById('sitinName');
            const remField = document.getElementById('sitinRemaining');
            if (nameField) nameField.value = '';
            if (remField) remField.value = '';
        }

        document.addEventListener('click', function(e) {
            const openBtn = e.target.closest('[data-modal-open]');
            if (openBtn) {
                e.preventDefault();
                openModal(openBtn.getAttribute('data-modal-open'));
                return;
            }
            if (e.target.matches('[data-modal-close]')) {
                const modal = e.target.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
                return;
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
            }
        });

        // --- AUTO-FILL STUDENT DATA ---
        const idInput = document.getElementById('sitinId');
        const nameInput = document.getElementById('sitinName');
        const remainingInput = document.getElementById('sitinRemaining');

        if (idInput) {
            idInput.addEventListener('blur', async function() {
                const id = this.value.trim();
                if (!id) return;

                try {
                    // Fetch student data from backend
                    const response = await fetch(`get_student.php?id=${encodeURIComponent(id)}`);
                    const data = await response.json();

                    if (data.success) {
                        nameInput.value = data.name;
                        remainingInput.value = data.remaining_session;
                    } else {
                        nameInput.value = 'Student not found';
                        remainingInput.value = '';
                        // Optional: alert('Student ID not found');
                    }
                } catch (err) {
                    console.error('Error fetching student:', err);
                }
            });
        }

        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) return;
            modal.classList.add('open');
            modal.setAttribute('aria-hidden', 'false');
        }

        function closeModal(modal) {
            modal.classList.remove('open');
            modal.setAttribute('aria-hidden', 'true');
        }

        // Open modal using data-modal-open
        document.addEventListener('click', function(e) {
            const openBtn = e.target.closest('[data-modal-open]');
            if (openBtn) {
                e.preventDefault();
                openModal(openBtn.getAttribute('data-modal-open'));
                return;
            }

            // Close modal using close button
            if (e.target.matches('[data-modal-close]')) {
                const modal = e.target.closest('.modal');
                if (modal) closeModal(modal);
                return;
            }

            // Close when clicking outside dialog (overlay)
            if (e.target.classList.contains('modal')) {
                closeModal(e.target);
                return;
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal.open').forEach(m => closeModal(m));
            }
        });
    </script>
</body>
<!-- A) SEARCH MODAL -->
<div class="modal" id="searchModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Search Student</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>

        <form class="modal-body" method="GET" action="search_results.php">
            <label for="searchQuery">Search by ID or Name</label>
            <input type="text" id="searchQuery" name="q" placeholder="Enter ID number or Student Name" required autofocus>

            <div class="modal-actions">
                <button type="button" class="back-button" data-modal-close>Cancel</button>
                <button type="submit" class="submit-button">Search</button>
            </div>
        </form>
    </div>
</div>

<!-- B) SIT-IN MODAL (Start Sit-in Form) -->
<div class="modal" id="sitinModal" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-header">
            <h3>Sit In Form</h3>
            <button type="button" class="modal-close" data-modal-close>&times;</button>
        </div>

        <form class="modal-body" method="POST" action="start_sitin.php" id="sitinForm">

            <!-- ID Number -->
            <div class="field-group">
                <div style="display: inline-flex; flex-direction: row; align-items: center; justify-content: space-around;">
                    <div style="display: inline-flex; flex-direction: column; gap: 1.7rem;">
                        <label for="sitinId">ID Number:</label>
                        <label for="sitinName">Student Name:</label>
                        <label for="sitinPurpose">Purpose:</label>
                        <label for="sitinLab">Lab:</label>
                        <label for="sitinRemaining">Remaining Session:</label>
                    </div>
                    <div style="display: inline-flex; flex-direction: column;">
                        <input type="text" id="sitinId" name="student_id" placeholder="Enter ID" required>
                        <input type="text" id="sitinName" name="student_name" placeholder="Auto-filled from ID" readonly style="background-color:#f0f0f0;">
                        <select id="sitinPurpose" name="purpose" class="course-select" required>
                            <option value="" disabled selected>Select Programming Language</option>
                            <option value="C Programming">C Programming</option>
                            <option value="C#">C#</option>
                            <option value="Java">Java</option>
                            <option value="ASP.Net">ASP.Net</option>
                            <option value="Php">Php</option>
                            <option value="Python">Python</option>
                        </select>
                        <input type="text" id="sitinLab" name="lab" placeholder="e.g. 524" required>
                        <input type="number" id="sitinRemaining" name="remaining_session" placeholder="Auto-filled" readonly
                            style="background-color:#f0f0f0;">
                    </div>
                </div>
            </div>
            <div class="modal-actions" style="margin-top:1rem;">
                <button type="button" class="back-button" data-modal-close>Close</button>
                <button type="submit" class="submit-button" style="background-color:#007bff;">Sit In</button>
            </div>
        </form>
    </div>
</div>

</html>