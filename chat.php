<?php
session_start();
include 'database.php';

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$backUrl = 'messages.php';

// Check if `friend_id` is set in the URL
if (!isset($_GET['friend_id']) || !is_numeric($_GET['friend_id'])) {
    echo "Friend ID is not set.";
    exit();
}

$friendId = $_GET['friend_id'];

// Fetch friend's username
$friendQuery = "SELECT username FROM members WHERE id = ?";
$friendStmt = $conn->prepare($friendQuery);
$friendStmt->bind_param("i", $friendId);
$friendStmt->execute();
$friendResult = $friendStmt->get_result();
if ($friendResult->num_rows == 0) {
    echo "Friend not found.";
    exit();
}
$friendUsername = $friendResult->fetch_assoc()['username'];

// Fetch conversation history
$messagesQuery = "
    SELECT sender_id, recipient_id, message, sent_at 
    FROM messages 
    WHERE (sender_id = ? AND recipient_id = ?) 
       OR (sender_id = ? AND recipient_id = ?)
    ORDER BY sent_at ASC";
$messagesStmt = $conn->prepare($messagesQuery);
$messagesStmt->bind_param("iiii", $userId, $friendId, $friendId, $userId);
$messagesStmt->execute();
$messages = $messagesStmt->get_result();

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];

    $sendMessageQuery = "INSERT INTO messages (sender_id, recipient_id, message, sent_at) VALUES (?, ?, ?, NOW())";
    $sendMessageStmt = $conn->prepare($sendMessageQuery);
    $sendMessageStmt->bind_param("iis", $userId, $friendId, $message);

    if ($sendMessageStmt->execute()) {
        header("Location: chat.php?friend_id=$friendId"); // Refresh to show the new message
        exit();
    } else {
        echo "Error sending message: " . $sendMessageStmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat with <?php echo htmlspecialchars($friendUsername); ?> - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">
        &larr; Back to Messages
    </button>

    <div class="chat-container">
        <h2>Chat with <?php echo htmlspecialchars($friendUsername); ?></h2>

        <div class="chat-history">
            <?php if ($messages->num_rows > 0): ?>
                <?php while ($msg = $messages->fetch_assoc()): ?>
                    <div class="message <?php echo $msg['sender_id'] == $userId ? 'sent' : 'received'; ?>">
                        <p><?php echo htmlspecialchars($msg['message']); ?></p>
                        <span class="timestamp"><?php echo date('Y-m-d H:i', strtotime($msg['sent_at'])); ?></span>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No messages yet. Start the conversation!</p>
            <?php endif; ?>
        </div>

        <!-- Message Form -->
        <form method="POST" class="message-form">
            <textarea name="message" placeholder="Type your message here..." required></textarea>
            <button type="submit">Send</button>
        </form>
    </div>
</body>
</html>
