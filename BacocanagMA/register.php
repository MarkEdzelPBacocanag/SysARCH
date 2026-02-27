<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">    
    <title>Login</title>
</head>
<body>
    <div class = "container-nav" name="navigation bar" style="height: 3.8rem;">
            <div style="margin-left: 3rem; border: 0px solid black; border-radius: 0.5rem; background-color: #ff0000;">
                <a style="text-decoration: none;" href="index.php"><p style="margin: 0.5rem; font-weight: bold;">Back</p></a>
            </div>  
            </div>
    </div>
    <div class = "container-logo-login" style="min-height: 84vh;">
        <img src="ccs.png">
        <div class="registerform">
            <h3>Register</h3>
            <label for="id" >ID number: </label> 
            <input type="text" id="id-number" name="id" placeholder="ID number">
            <div class="name">
                </div>
                <div style="display: flex; justify-content: space-between;gap: 0.5rem;">
                <label for="fname">First name: </label>
                <label for="lname">Last name: </label>
                <label style="margin-right: 6rem;" for="mname">Middle name: </label>
                </div>
                <div style="display: flex; justify-content: space-between;gap: 0.5rem;">
                 <input type="text" id="fname" name="fname" placeholder="First name">
                <input type="text" id="lname" name="lname" placeholder="Last name">
                <input type="text" id="mname" name="mname" placeholder="Middle name">
            </div>
            <label for="course">Course level: </label>
            <select class="course-select" name="course" id="course">
                <option value="" disabled selected>Select course level</option>
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
            </select>
            <div class="pass" style="display: inline-flex; flex-direction: row; gap: 0.5rem;">
                <div>
                    <label for="password">Password: </label>
                    <input type="password" id="password" name="password" placeholder="Password">
                </div>
                <div>
                    <label for="confirm-password">Confirm password: </label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Confirm password">
                </div>
            </div>
            <label for="email">Email: </label>
            <input type="email" id="email" name="email" placeholder="Email">
            <label for="course">Course: </label>
            <select class="course-select" name="course" id="course">
                <option value="" disabled selected>Select course</option>
                <option value="BSCS">BSCS</option>
                <option value="BSIT">BSIT</option>
            </select>
            <label for="address">Address: </label>
            <input type="text" id="address" name="address" placeholder="Address">
            <button style="margin-top: 1rem; background-color: #5DA8DE; color: white; border: none; padding: 0.5rem; border-radius: 0.5rem;" type="submit">Register</button>
        </div>
    </div>
</body>
<footer>
    <p>© 2026 College of Computer Studies</p>
</footer>
</html>
