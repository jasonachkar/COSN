<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}
include 'database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
    <p>Your profile details go here.</p>
    <a href="logout.php">Logout</a>
</body>
</html>
