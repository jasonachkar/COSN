<?php
session_start();
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
    <header class="header">
        <button onclick="window.location.href='<?php echo isset($_SESSION['username']) ? 'home.php' : 'index.php'; ?>';" class="back-button">&larr; Back</button>
        <div class="welcome-container">
            <span class="welcome-message">COSN - Welcome, <?php echo htmlspecialchars($username); ?>!</span>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </header>

    <div class="content-container">
        <div class="main-content">
            <h2>Your Latest Posts</h2>
            <div class="posts">
                <p>No recent posts yet. Start connecting with your friends and groups!</p>
            </div>
        </div>

        <aside class="sidebar">
            <h3>Actions</h3>
            <div class="actions">
                <a href="messages.php" class="action-button">Messages</a>
                <a href="groups.php" class="action-button">Groups</a>
                <a href="events.php" class="action-button">Events</a>
                <a href="friends.php" class="action-button">Friends</a>
                <a href="settings.php" class="action-button">Account Settings</a>
            </div>

            <h3>Notifications</h3>
            <div class="notifications">
                <p>No new notifications.</p>
            </div>
        </aside>
    </div>
</body>
</html>
