<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="navbar">
        <h2>COSN - Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
        <a href="logout.php" class="button logout">Logout</a>
    </div>

    <div class="container">
        <div class="section">
            <h3>Actions</h3>
            <div class="button-container">
                <a href="message.php" class="button">Messages</a>
                <a href="groups.php" class="button">Groups</a>
                <a href="events.php" class="button">Events</a>
                <a href="friends.php" class="button">Friends</a>
                <a href="settings.php" class="button">Account Settings</a>
            </div>
        </div>

        <div class="section">
            <h3>Your Latest Posts</h3>
            <!-- Code to display the latest posts by the user -->
            <div class="posts">
                <p>No recent posts yet. Start connecting with your friends and groups!</p>
                <!-- Loop to display user posts -->
            </div>
        </div>

        <div class="section">
            <h3>Notifications</h3>
            <!-- Notifications area -->
            <div class="notifications">
                <p>No new notifications.</p>
                <!-- Notification loop -->
            </div>
        </div>
    </div>
</body>
</html>
