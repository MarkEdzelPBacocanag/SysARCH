<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
$old    = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Register</title>
</head>

<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>College of Computer Studies</h2>
        </div>
        <div class="link-ref">
            <div><a href="#">
                    <p>Community</p>
                </a></div>
            <div><a href="#">
                    <p>About</p>
                </a></div>
            <div><a href="index.php">
                    <p>Login</p>
                </a></div>
        </div>
    </div>

    <div class="container-logo-login">
        <img src="ccs.png" alt="CCS Logo">
        <div class="registerform">
            <h3 style="text-align: center; margin-bottom: 1rem;">Create Account</h3>
            <form method="POST" action="register_process.php">
                <!-- ID -->
                <div class="field-group">
                    <label for="id">ID Number:</label>
                    <input type="text" id="id" name="id" value="<?= htmlspecialchars($old['id'] ?? '') ?>" required>
                    <?php if (!empty($errors['id'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['id']) ?></span><?php endif; ?>
                </div>

                <!-- Names -->
                <div class="form-row">
                    <div class="field-group">
                        <label for="fname">First Name:</label>
                        <input type="text" id="fname" name="fname" value="<?= htmlspecialchars($old['fname'] ?? '') ?>" required>
                        <?php if (!empty($errors['fname'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['fname']) ?></span><?php endif; ?>
                    </div>
                    <div class="field-group">
                        <label for="lname">Last Name:</label>
                        <input type="text" id="lname" name="lname" value="<?= htmlspecialchars($old['lname'] ?? '') ?>" required>
                        <?php if (!empty($errors['lname'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['lname']) ?></span><?php endif; ?>
                    </div>
                </div>
                <div class="field-group">
                    <label for="mname">Middle Name: <small>(Optional)</small></label>
                    <input type="text" id="mname" name="mname" value="<?= htmlspecialchars($old['mname'] ?? '') ?>">
                </div>

                <!-- Course & Level -->
                <div class="form-row">
                    <div class="field-group">
                        <label for="course">Course:</label>
                        <select id="course" name="course" class="course-select" required>
                            <option value="" disabled <?= empty($old['course']) ? 'selected' : '' ?>>Select Course</option>
                            <option value="BSCS" <?= ($old['course'] ?? '') === 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                            <option value="BSIT" <?= ($old['course'] ?? '') === 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                        </select>
                        <?php if (!empty($errors['course'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['course']) ?></span><?php endif; ?>
                    </div>
                    <div class="field-group">
                        <label for="course_level">Year Level:</label>
                        <select id="course_level" name="course_level" class="course-select" required>
                            <option value="" disabled <?= empty($old['course_level']) ? 'selected' : '' ?>>Select Level</option>
                            <option value="1" <?= ($old['course_level'] ?? '') === '1' ? 'selected' : '' ?>>1st Year</option>
                            <option value="2" <?= ($old['course_level'] ?? '') === '2' ? 'selected' : '' ?>>2nd Year</option>
                            <option value="3" <?= ($old['course_level'] ?? '') === '3' ? 'selected' : '' ?>>3rd Year</option>
                            <option value="4" <?= ($old['course_level'] ?? '') === '4' ? 'selected' : '' ?>>4th Year</option>
                        </select>
                        <?php if (!empty($errors['course_level'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['course_level']) ?></span><?php endif; ?>
                    </div>
                </div>

                <!-- Password -->
                <div class="form-row">
                    <div class="field-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                        <?php if (!empty($errors['password'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['password']) ?></span><?php endif; ?>
                    </div>
                    <div class="field-group">
                        <label for="confirm-password">Confirm Password:</label>
                        <input type="password" id="confirm-password" name="confirm-password" required>
                        <?php if (!empty($errors['confirm-password'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['confirm-password']) ?></span><?php endif; ?>
                    </div>
                </div>

                <!-- Email & Address -->
                <div class="field-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                    <?php if (!empty($errors['email'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['email']) ?></span><?php endif; ?>
                </div>
                <div class="field-group">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($old['address'] ?? '') ?>" required>
                    <?php if (!empty($errors['address'])): ?><span class="error-bubble"><?= htmlspecialchars($errors['address']) ?></span><?php endif; ?>
                </div>

                <!-- Buttons -->
                <div style="display: flex; gap: 10px; margin-top: 1rem;">
                    <button type="button" class="back-button" onclick="window.location.href='index.php'" style="flex: 1;">Back</button>
                    <button type="submit" class="submit-button" style="flex: 1;">Register</button>
                </div>
            </form>
        </div>
    </div>
    <footer>
        <p>© 2026 College of Computer Studies</p>
    </footer>
</body>

</html>