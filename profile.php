<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}
include 'database.php';
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
<button onclick="window.location.href='<?php echo $backUrl; ?>';" 
        style="margin: 10px; padding: 5px 10px; font-size: 14px; cursor: pointer;">
    &larr; Back
</button>


    <h2>Welcome, <?php echo $_SESSION['username']; ?></h2>
    <p>Your profile details go here.</p>
    <a href="logout.php">Logout</a>
</body>
</html>
