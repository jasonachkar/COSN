<?php
session_start();
include 'database.php'; // Include the database connection

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$userId = $_SESSION['user_id'];
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

// Retrieve friends with whom there is an existing conversation
$chatQuery = "
    SELECT DISTINCT m.id, m.username
    FROM members m
    JOIN messages msg ON (msg.sender_id = m.id AND msg.receiver_id = ?) 
                        OR (msg.receiver_id = m.id AND msg.sender_id = ?)";
$chatStmt = $conn->prepare($chatQuery);
$chatStmt->bind_param("ii", $userId, $userId);
$chatStmt->execute();
$chats = $chatStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chats - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';" 
            style="margin: 10px; padding: 5px 10px; font-size: 14px; cursor: pointer;">
        &larr; Back
    </button>
    
    <div class="chats-container">
        <h1>Chats</h1>
        <?php if ($chats->num_rows > 0): ?>
            <ul>
                <?php while ($chat = $chats->fetch_assoc()): ?>
                    <li>
                        <a href="messages.php?friend_id=<?php echo $chat['id']; ?>">
                            <?php echo htmlspecialchars($chat['username']); ?>
                        </a>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No active conversations yet. Start messaging your friends!</p>
        <?php endif; ?>
    </div>
</body>
</html>
