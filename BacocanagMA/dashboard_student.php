<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (($_SESSION['role'] ?? '') !== 'student') {
    // if admin tries to access student dashboard
    header("Location: dashboard_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container-nav">
        <div style="padding-left: 3rem;">
            <h2>Student Dashboard</h2>
        </div>
        <div class="link-ref">
            <div><strong><?= htmlspecialchars($_SESSION['user_name']) ?></strong></div>
            <button class="logout-button" type="button" onclick="window.location.href='logout.php';">Log out</button>
        </div>
    </div>

    <div style="padding:2rem;">
        <h3>Welcome, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h3>
        <p>This is the student dashboard.</p>
    </div>
</body>

</html>