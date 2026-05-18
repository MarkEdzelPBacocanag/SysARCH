<?php
session_start();
require 'database.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
if (($_SESSION['role'] ?? '') !== 'student') {
    header("Location: dashboard_admin.php");
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

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $course = $_POST['course'] ?? '';
    $course_level = $_POST['course_level'] ?? '';
    $errors = [];

    if (empty($fname)) $errors[] = 'First name is required';
    if (empty($lname)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($course)) $errors[] = 'Course is required';
    if (empty($course_level)) $errors[] = 'Year level is required';

    $emailCheck = $pdo->prepare("SELECT id FROM students WHERE email = ? AND id != ?");
    $emailCheck->execute([$email, $_SESSION['user_id']]);
    if ($emailCheck->fetch()) $errors[] = 'Email is already used by another account';

    $profilePicture = $student['profile_picture'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['profile_picture'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) $errors[] = 'Invalid image type.';
        elseif ($file['size'] > 5 * 1024 * 1024) $errors[] = 'Image size must be less than 5MB';
        else {
            $uploadDir = 'uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newFilename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $newFilename)) {
                if ($student['profile_picture'] && $student['profile_picture'] !== 'default_profile.png' && file_exists($uploadDir . $student['profile_picture'])) {
                    unlink($uploadDir . $student['profile_picture']);
                }
                $profilePicture = $newFilename;
            } else $errors[] = 'Failed to upload image.';
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET fname=?, lname=?, mname=?, email=?, address=?, course=?, course_level=?, profile_picture=? WHERE id=?");
            $stmt->execute([$fname, $lname, $mname, $email, $address, $course, $course_level, $profilePicture, $_SESSION['user_id']]);
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
            display: flex;
            justify-content: center;
            padding: 0 1rem;
        }

        /* 🔒 SCOPED: Only affects elements inside .edit-profile-container */
        .edit-profile-container .edit-profile-card {
            background: #fff;
            border-radius: 10px;
            width: 40rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .edit-profile-container .card-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .edit-profile-container .card-header h2 {
            margin: 0;
        }

        .edit-profile-container .card-body {
            padding: 30px;
        }

        .edit-profile-container .profile-picture-section {
            text-align: center;
            margin-bottom: 25px;
        }

        .edit-profile-container .current-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #007bff;
            margin-bottom: 15px;
            background: #e9ecef;
        }

        .edit-profile-container .picture-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }

        .edit-profile-container .picture-upload input[type="file"] {
            display: none;
        }

        .edit-profile-container .upload-btn {
            background: #6c757d;
            color: #fff;
            padding: 8px 20px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .edit-profile-container .upload-btn:hover {
            background: #5a6268;
        }

        .edit-profile-container .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .edit-profile-container .field-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .edit-profile-container .field-group label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .edit-profile-container .field-group input,
        .edit-profile-container .field-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .edit-profile-container .field-group input[readonly] {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .edit-profile-container .btn-row {
            display: flex;
            gap: 10px;
            margin-top: 25px;
        }

        .edit-profile-container .btn {
            flex: 1;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
        }

        .edit-profile-container .btn-primary {
            background: #007bff;
            color: #fff;
        }

        .edit-profile-container .btn-secondary {
            background: #6c757d;
            color: #fff;
        }

        .edit-profile-container .filename-display {
            font-size: 0.85rem;
            color: #666;
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        @media (max-width: 600px) {
            .edit-profile-container .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>

<body>

    <div class="toast-container"><?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?><?php if ($error): ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?></div>

    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Edit Profile</h2>
        </div>
        <div class="link-ref">
            <div><a href="dashboard_student.php">Home</a></div>
            <div><a href="leaderboard.php">Leaderboard</a></div>
            <div><a href="edit_profile.php">Edit Profile</a></div>
            <div><a href="student_history.php">History</a></div>
            <?php $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE student_id = ? AND is_read = 0");
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

    <div class="edit-profile-container" style="margin-top: 2rem;">
        <div class="edit-profile-card">
            <div class="card-header">
                <h2>✏️ Edit Your Profile</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="edit_profile.php" enctype="multipart/form-data">
                    <div class="profile-picture-section">
                        <?php $profilePic = $student['profile_picture'] ?? 'default_profile.png';
                        $profilePath = 'uploads/profiles/' . $profilePic;
                        if (!file_exists($profilePath) || empty($student['profile_picture'])) $profilePath = 'uploads/profiles/default_profile.png'; ?>
                        <img src="<?= htmlspecialchars($profilePath) ?>" alt="Profile Picture" class="current-picture" id="previewImage" onerror="this.src='https://via.placeholder.com/150?text=No+Photo'">
                        <div class="picture-upload"><label for="profile_picture" class="upload-btn">📷 Change Photo</label><input type="file" id="profile_picture" name="profile_picture" accept="image/jpeg,image/png,image/gif,image/webp"><span class="filename-display" id="filenameDisplay">No file chosen</span><small>Max size: 5MB | JPG, PNG, GIF, WEBP</small></div>
                    </div>
                    <div class="field-group"><label>ID Number</label><input type="text" value="<?= htmlspecialchars($student['id']) ?>" readonly><small>ID number cannot be changed</small></div>
                    <div class="form-row">
                        <div class="field-group"><label>First Name *</label><input type="text" name="fname" value="<?= htmlspecialchars($student['fname']) ?>" required></div>
                        <div class="field-group"><label>Last Name *</label><input type="text" name="lname" value="<?= htmlspecialchars($student['lname']) ?>" required></div>
                    </div>
                    <div class="field-group"><label>Middle Name</label><input type="text" name="mname" value="<?= htmlspecialchars($student['mname'] ?? '') ?>" placeholder="Optional"></div>
                    <div class="form-row">
                        <div class="field-group"><label>Course *</label><select name="course" required>
                                <option value="BSCS" <?= $student['course'] === 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                                <option value="BSIT" <?= $student['course'] === 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                            </select></div>
                        <div class="field-group"><label>Year Level *</label><select name="course_level" required>
                                <option value="1" <?= $student['course_level'] == 1 ? 'selected' : '' ?>>1st Year</option>
                                <option value="2" <?= $student['course_level'] == 2 ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3" <?= $student['course_level'] == 3 ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4" <?= $student['course_level'] == 4 ? 'selected' : '' ?>>4th Year</option>
                            </select></div>
                    </div>
                    <div class="field-group"><label>Email Address *</label><input type="email" name="email" value="<?= htmlspecialchars($student['email']) ?>" required></div>
                    <div class="field-group"><label>Address *</label><input type="text" name="address" value="<?= htmlspecialchars($student['address']) ?>" required></div>
                    <div class="field-group"><label>Remaining Sessions</label><input type="text" value="<?= htmlspecialchars($student['remaining_session'] ?? 30) ?>" readonly><small>Sessions are managed by admin only</small></div>
                    <div class="btn-row"><button type="button" class="btn btn-secondary" onclick="window.location.href='dashboard_student.php'">Cancel</button><button type="submit" class="btn btn-primary">💾 Save Changes</button></div>
                </form>
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
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            const file = e.target.files[0],
                fd = document.getElementById('filenameDisplay'),
                pi = document.getElementById('previewImage');
            if (file) {
                fd.textContent = file.name;
                const reader = new FileReader();
                reader.onload = function(ev) {
                    pi.src = ev.target.result;
                };
                reader.readAsDataURL(file);
            } else {
                fd.textContent = 'No file chosen';
            }
        });
    </script>

    <script src="assets/js/reservation.js"></script>
</body>

</html>