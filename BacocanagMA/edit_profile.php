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
                if (
                    $student['profile_picture'] &&
                    $student['profile_picture'] !== 'default_profile.png' &&
                    file_exists($uploadDir . $student['profile_picture'])
                ) {
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
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
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