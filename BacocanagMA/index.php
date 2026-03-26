<?php
session_start();
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? [];
unset($_SESSION['errors'], $_SESSION['old']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Login</title>
</head>

<body>
    <div class="container-nav" name="navigation bar">
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
            <div name="Register">
                <a href="register.php">
                    <p>Register</p>
                </a>
            </div>
        </div>
    </div>
    <div class="container-logo-login">
        <img src="ccs.png">
        <div class="loginform">
            <form method="post" action="login_process.php">
                <div class="field-group">
                    <label for="id">ID number: </label>
                    <input type="text" id="id-number" name="id" placeholder="Enter valid ID number" value="<?= htmlspecialchars($old['id'] ?? '') ?>">
                    <?php if (!empty($errors['id'])): ?>
                        <span class="error-bubble"><?= htmlspecialchars($errors['id']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="field-group">
                    <lable for="password">Password: </lable>
                    <input type="password" id="password" name="password" placeholder="Enter Password">
                    <?php if (!empty($errors['password'])): ?>
                        <span class="error-bubble"><?= htmlspecialchars($errors['password']) ?></span>
                    <?php endif; ?>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" id="remember-me" name="remember-me">
                        <label for="remember-me">Remember me</label>
                    </div>
                    <a href="#">Forgot password?</a>
                </div>
                <div style="display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: center;">
                        <button type="submit">Login</button>
                    </div>
                    <div style="display: inline-flex; justify-content: space-between; margin-top: .5rem;">
                        <div>
                            <p>Don't have an account?</p>
                        </div>
                        <div>
                            <p>
                                <a href="register.php">Register</a>
                            </p>
                        </div>
                    </div>
            </form>
        </div>
    </div>
    </div>
</body>
<footer>
    <p>© 2026 College of Computer Studies</p>
</footer>

</html>