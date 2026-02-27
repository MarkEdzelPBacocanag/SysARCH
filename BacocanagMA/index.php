<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">    
    <title>Login</title>
</head>
<body>
    <div class = "container-nav" name="navigation bar">
            <div style="padding-left: 3rem;">
                <h3>College of Computer Studies</h3>
            </div>
            <div class="link-ref">
<!--            
            <div name = "Home">
                <a href="#"><p>Home</p></a>
            </div>
-->
            <div name="Community">
                <a href="#"><p>Community</p></a>
            </div>
            <div name="about">
                <a href="#"><p>About</p></a>
            </div>
            <div name="login">
                <a href="#"><p>Login</p></a>
            </div>
            <div name = "Register">
                <a href= "register.php"><p>Register</p></a>
            </div>
            </div>
    </div>
    <div class = "container-logo-login">
        <img src="ccs.png">
        <div class="loginform">
            <label for="id" >ID number: </label>
            <input type="text" id="id-number" name="id" placeholder="Enter valid ID number">
            <lable for="password">Password: </lable>
            <input type="password" id="password" name="password" placeholder="Enter Password">
            <div style="display: inline-flex; justify-content: space-between; align-items: center; width: 100%;">
                <div style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" id="remember-me" name="remember-me">
                    <label for="remember-me">Remember me</label>
                </div>
                <a href="#">Forgot password?</a>
            </div>
            <div style="display: inline-flex; flex-direction: column; align-items: center;">
            <button>Login</button>
            <div style="display: inline-flex;">
                <p>Don't have an account?</p>
                <p>
                    <a href="register.php">Register</a>
                </p>
            </div>
            </div>
        </div>
    </div>
</body>
<footer>
    <p>© 2026 College of Computer Studies</p>
</footer>
</html>
