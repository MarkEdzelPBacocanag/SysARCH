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
    <?php if ($success): ?>
        <div class="alert alert-success" style="margin: 1rem; padding: 10px; background: #d4edda; color: #155724; border-radius: 5px;">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" style="margin: 1rem; padding: 10px; background: #f8d7da; color: #721c24; border-radius: 5px;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

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
```

---

## 3. Create `edit_profile.php`

```php
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

// Get current student information
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $course = $_POST['course'] ?? '';
    $course_level = $_POST['course_level'] ?? '';

    $errors = [];

    // Validation
    if (empty($fname)) $errors[] = 'First name is required';
    if (empty($lname)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($course)) $errors[] = 'Course is required';
    if (empty($course_level)) $errors[] = 'Year level is required';

    // Check if email is already used by another student
    $emailCheck = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $_SESSION['user_id']]);
    if ($emailCheck->fetch()) {
        $errors[] = 'Email is already used by another account';
    }

    // Handle profile picture upload
    $profilePicture = $student['profile_picture']; // Keep existing
    
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowedTypes)) {
            $errors[] = 'Invalid image type. Allowed: JPG, PNG, GIF, WEBP';
        } elseif ($file['size'] > $maxSize) {
            $errors[] = 'Image size must be less than 5MB';
        } else {
            // Create uploads directory if not exists
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $newFilename;

            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Delete old profile picture (except default)
                if ($student['profile_picture'] && 
                    $student['profile_picture'] !== 'default_profile.png' &&
                    file_exists($uploadDir . $student['profile_picture'])) {
                    unlink($uploadDir . $student['profile_picture']);
                }
                $profilePicture = $newFilename;
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE students 
                                   SET fname = ?, lname = ?, mname = ?, email = ?, 
                                       address = ?, course = ?, course_level = ?, profile_picture = ?
                                   WHERE id = ?");
            $stmt->execute([$fname, $lname, $mname, $email, $address, $course, $course_level, $profilePicture, $_SESSION['user_id']]);

            // Update session name
            $_SESSION['user_name'] = $fname;

            $_SESSION['success'] = 'Profile updated successfully!';
            header('Location: dashboard_student.php');
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }

    $_SESSION['errors'] = $errors;
}

// Get errors from session
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['errors']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-profile-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .edit-profile-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
            text-align: center;
        }

        .card-header h2 {
            margin: 0;
        }

        .card-body {
            padding: 30px;
        }

        .profile-picture-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .current-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
            margin-bottom: 15px;
            background-color: #e9ecef;
        }

        .picture-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .picture-upload input[type="file"] {
            display: none;
        }

        .upload-btn {
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .upload-btn:hover {
            background: #5a6268;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-row .field-group {
            flex: 1;
        }

        .field-group {
            margin-bottom: 15px;
        }

        .field-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .field-group input,
        .field-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .field-group input:focus,
        .field-group select:focus {
            outline: none;
            border-color: #007bff;
        }

        .field-group input[readonly] {
            background-color: #e9ecef;
            cursor: not-allowed;
        }

        .field-group small {
            color: #666;
            font-size: 0.8rem;
        }

        .error-list {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .error-list ul {
            margin: 0;
            padding-left: 20px;
        }

        .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .filename-display {
            font-size: 0.85rem;
            color: #666;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 600px) {
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <!-- NAVIGATION BAR -->
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Edit Profile</h2>
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

    <!-- MAIN CONTENT -->
    <div class="edit-profile-container">
        <div class="edit-profile-card">
            <div class="card-header">
                <h2>✏️ Edit Your Profile</h2>
            </div>
            <div class="card-body">

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="error-list">
                        <strong>Please fix the following errors:</strong>
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="edit_profile.php" enctype="multipart/form-data">

                    <!-- Profile Picture -->
                    <div class="profile-picture-section">
                        <?php 
                        $profilePic = $student['profile_picture'] ?? 'default_profile.png';
                        $profilePath = 'uploads/profiles/' . $profilePic;
                        if (!file_exists($profilePath) || empty($student['profile_picture'])) {
                            $profilePath = 'uploads/profiles/default_profile.png';
                        }
                        ?>
                        <img src="<?= htmlspecialchars($profilePath) ?>" 
                             alt="Profile Picture" 
                             class="current-picture"
                             id="previewImage"
                             onerror="this.src='https://via.placeholder.com/150?text=No+Photo'">

                        <div class="picture-upload">
                            <label for="profile_picture" class="upload-btn">📷 Change Photo</label>
                            <input type="file" 
                                   id="profile_picture" 
                                   name="profile_picture" 
                                   accept="image/jpeg,image/png,image/gif,image/webp">
                            <span class="filename-display" id="filenameDisplay">No file chosen</span>
                            <small>Max size: 5MB | JPG, PNG, GIF, WEBP</small>
                        </div>
                    </div>

                    <!-- ID Number (readonly) -->
                    <div class="field-group">
                        <label for="id">ID Number</label>
                        <input type="text" 
                               id="id" 
                               value="<?= htmlspecialchars($student['id']) ?>" 
                               readonly>
                        <small>ID number cannot be changed</small>
                    </div>

                    <!-- Name Fields -->
                    <div class="form-row">
                        <div class="field-group">
                            <label for="fname">First Name *</label>
                            <input type="text" 
                                   id="fname" 
                                   name="fname" 
                                   value="<?= htmlspecialchars($student['fname']) ?>" 
                                   required>
                        </div>
                        <div class="field-group">
                            <label for="lname">Last Name *</label>
                            <input type="text" 
                                   id="lname" 
                                   name="lname" 
                                   value="<?= htmlspecialchars($student['lname']) ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="mname">Middle Name</label>
                        <input type="text" 
                               id="mname" 
                               name="mname" 
                               value="<?= htmlspecialchars($student['mname'] ?? '') ?>"
                               placeholder="Optional">
                    </div>

                    <!-- Course & Year Level -->
                    <div class="form-row">
                        <div class="field-group">
                            <label for="course">Course *</label>
                            <select id="course" name="course" required>
                                <option value="BSCS" <?= $student['course'] === 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                                <option value="BSIT" <?= $student['course'] === 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="course_level">Year Level *</label>
                            <select id="course_level" name="course_level" required>
                                <option value="1" <?= $student['course_level'] == 1 ? 'selected' : '' ?>>1st Year</option>
                                <option value="2" <?= $student['course_level'] == 2 ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3" <?= $student['course_level'] == 3 ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4" <?= $student['course_level'] == 4 ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="field-group">
                        <label for="email">Email Address *</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?= htmlspecialchars($student['email']) ?>" 
                               required>
                    </div>

                    <!-- Address -->
                    <div class="field-group">
                        <label for="address">Address *</label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               value="<?= htmlspecialchars($student['address']) ?>" 
                               required>
                    </div>

                    <!-- Sessions (readonly) -->
                    <div class="field-group">
                        <label for="sessions">Remaining Sessions</label>
                        <input type="text" 
                               id="sessions" 
                               value="<?= htmlspecialchars($student['remaining_session'] ?? 30) ?>" 
                               readonly>
                        <small>Sessions are managed by admin only</small>
                    </div>

                    <!-- Buttons -->
                    <div class="btn-row">
                        <button type="button" 
                                class="btn btn-secondary" 
                                onclick="window.location.href='dashboard_student.php'">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            💾 Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Preview image before upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const filenameDisplay = document.getElementById('filenameDisplay');
            const previewImage = document.getElementById('previewImage');

            if (file) {
                filenameDisplay.textContent = file.name;

                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                filenameDisplay.textContent = 'No file chosen';
            }
        });
    </script>
</body>
</html>
```

---

## 4. Create Default Profile Picture Folder

Create a folder and add a default profile picture:

```
/uploads/profiles/default_profile.png
```

You can use any placeholder image or create one. If you don't have one, the code falls back to a placeholder from the web.

---

## 5. Create `student_history.php` (Sit-in History for Student)

```php
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
    <title>Sit-in History</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .history-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .history-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 20px;
        }

        .card-header h2 {
            margin: 0;
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

        .status-active {
            background: #d4edda;
            color: #155724;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .status-completed {
            background: #e2e3e5;
            color: #6c757d;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
        }

        .no-history {
            text-align: center;
            padding: 40px;
            color: #999;
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
            <div><a href="notifications.php">Notifications</a></div>
            <div><a href="edit_profile.php">Edit Profile</a></div>
            <div><a href="student_history.php">History</a></div>
            <div><a href="student_reservations.php">Reservations</a></div>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
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
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['date_created'])) ?></td>
                                    <td><?= htmlspecialchars($row['purpose']) ?></td>
                                    <td><?= htmlspecialchars($row['lab']) ?></td>
                                    <td>
                                        <?php if ($row['status'] === 'active'): ?>
                                            <span class="status-active">Active</span>
                                        <?php else: ?>
                                            <span class="status-completed">Completed</span>
                                        <?php endif; ?>
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
</body>
</html>
