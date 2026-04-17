<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Register</title>
</head>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Select all error bubbles
        const bubbles = document.querySelectorAll(".error-bubble");
        if (bubbles.length > 0) {
            // Wait 2–5 seconds, then fade them out
            setTimeout(() => {
                bubbles.forEach(bubble => bubble.classList.add("fade-out"));
            }, 2000); // adjust: 2000 = 2s, 5000 = 5s
        }
    });
</script>

<body>
    <form method="post" action="register_process.php">
        <div class="container-nav" name="navigation bar" style="height: 3rem;">
            <div style="padding-left: 3rem;">
                <h2>College of Computer Studies</h2>
            </div>
            <div class="link-ref">
                <!--            
            <div name = "Home">
                <a href="#"><p>Home</p></a>
            </div>
-->
                <div name="Community">
                    <a href="#">
                        <p>Community</p>
                    </a>
                </div>
                <div name="about">
                    <a href="#">
                        <p>About</p>
                    </a>
                </div>
                <div name="login">
                    <a href="index.php">
                        <p>Login</p>
                    </a>
                </div>
                <div name="Register">
                    <a href="register.php">
                        <p>Register</p>
                    </a>
                </div>
            </div>
        </div>
        <div class="container-logo-login" style="min-height: 82.9vh;">
            <img src="ccs.png">
            <div class="registerform">
                <div style="display: flex; justify-content: center;">
                    <h3>Register</h3>
                </div>
                <div class="field-group">
                    <label for="id">ID number: </label>
                    <input for="id" type="text" id="id-number" name="id" placeholder="ID number" value="<?= htmlspecialchars($old['id'] ?? '') ?>">
                    <?php if (!empty($errors['id'])): ?>
                        <span class="error-bubble"><?= htmlspecialchars($errors['id']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="name">
                </div>
                <div style="display: flex; justify-content: space-between;gap: 0.5rem;">
                    <div class="field-group" style="flex:1;">
                        <label for="fname">First name: </label>
                        <input type="text" id="fname" name="fname" placeholder="First name" value="<?= htmlspecialchars($old['fname'] ?? '') ?>">
                        <?php if (!empty($errors['fname'])): ?>
                            <span class="error-bubble"><?= htmlspecialchars($errors['fname']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="field-group" style="flex:1;">
                        <label for="lname">Last name: </label>
                        <input type="text" id="lname" name="lname" placeholder="Last name" value="<?= htmlspecialchars($old['lname'] ?? '') ?>">
                        <?php if (!empty($errors['lname'])): ?>
                            <span class="error-bubble"><?= htmlspecialchars($errors['lname']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="field-group" style="flex:1;">
                        <label for="mname">Middle name: <small style="color: #7e7676b6;">(Optional)</small> </label>
                        <input type="text" id="mname" name="mname" placeholder="Middle name" value="<?= htmlspecialchars($old['mname'] ?? '') ?>">
                    </div>
                </div>
                <div class="field-group">
                    <label for="course">Course: </label>
                    <select class="course-select" name="course" id="course">
                        <option value="" disabled <?= empty($old['course']) ? 'selected' : '' ?>>Select course</option>
                        <option value="BSCS" <?= ($old['course'] ?? '') === 'BSCS' ? 'selected' : '' ?>>BSCS</option>
                        <option value="BSIT" <?= ($old['course'] ?? '') === 'BSIT' ? 'selected' : '' ?>>BSIT</option>
                    </select>
                    <?php if (!empty($errors['course'])): ?>
                        <span class="error-bubble"><?= htmlspecialchars($errors['course']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="field-group">
                    <label for="course-level">Course level: </label>
                    <select class="course-select" name="course_level" id="course-level">
                        <option value="" disabled <?= empty($old['course_level']) ? 'selected' : '' ?>>Select course level</option>
                        <option value="1" <?= ($old['course_level'] ?? '') === '1' ? 'selected' : '' ?>>1st</option>
                        <option value="2" <?= ($old['course_level'] ?? '') === '2' ? 'selected' : '' ?>>2nd</option>
                        <option value="3" <?= ($old['course_level'] ?? '') === '3' ? 'selected' : '' ?>>3rd</option>
                        <option value="4" <?= ($old['course_level'] ?? '') === '4' ? 'selected' : '' ?>>4th</option>
                    </select>
                    <?php if (!empty($errors['course_level'])): ?>
                        <span class="error-bubble"><?= htmlspecialchars($errors['course_level']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="pass" style="display: inline-flex; flex-direction: column; gap: 0.5rem;">
                    <div style="display: flex; justify-content: space-between;">
                        <div class="field-group" style="flex:1;">
                            <label for="password">Password: </label>
                            <input style="width: 15rem; margin-right: 0.8rem;" for="password" type="password" id="password" name="password" placeholder="Password">
                            <?php if (!empty($errors['password'])): ?>
                                <span class="error-bubble"><?= htmlspecialchars($errors['password']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="field-group" style="flex:1;">
                            <label for="confirm-password">Confirm password: </label>
                            <input style="width: 18rem;" for="confirm-password" type="password" id="confirm-password" name="confirm-password" placeholder="Confirm password">
                            <?php if (!empty($errors['confirm-password'])): ?>
                                <span class="error-bubble"><?= htmlspecialchars($errors['confirm-password']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="field-group">
                    <label for="email">Email: </label>
                    <input type="email" id="email" name="email" placeholder="Email" value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error-bubble"><?= htmlspecialchars($errors['email']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="field-group">
                    <label for="address">Address: </label>
                    <input type="text" id="address" name="address" placeholder="Address" value="<?= htmlspecialchars($old['address'] ?? '') ?>">
                    <?php if (!empty($errors['address'])): ?>
                        <span class="error-bubble"><?= htmlspecialchars($errors['address']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="display: inline-flex; flex-direction: row; gap: 5px;">
                    <div>
                        <button class="back-button" type="button" style="background-color: #dc3545; color: #ffffff;" onclick="window.location.href='index.php';">Back</button>
                    </div>
                    <div>
                        <button class="submit-button" type="submit">Register</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</body>
<footer>
    <p>© 2026 College of Computer Studies</p>
</footer>

</html>