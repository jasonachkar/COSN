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
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

// Fetch conversations (friends with whom the user has exchanged messages)
$conversationsQuery = "
    SELECT DISTINCT m.id, m.username
    FROM members m
    JOIN messages msg ON (msg.sender_id = m.id OR msg.recipient_id = m.id)
    WHERE (msg.sender_id = ? OR msg.recipient_id = ?)
      AND m.id != ?";

$conversationsStmt = $conn->prepare($conversationsQuery);
$conversationsStmt->bind_param("iii", $userId, $userId, $userId);
$conversationsStmt->execute();
$conversations = $conversationsStmt->get_result();

// Fetch message suggestions (friends the user has never messaged)
$suggestionsQuery = "
    SELECT m.id, m.username 
    FROM members m 
    JOIN friends f ON (m.id = f.friend_id OR m.id = f.user_id) 
    WHERE (f.user_id = ? OR f.friend_id = ?) 
      AND f.status = 'accepted' 
      AND m.id != ? 
      AND m.id NOT IN (
          SELECT DISTINCT CASE 
              WHEN msg.sender_id = ? THEN msg.recipient_id 
              ELSE msg.sender_id END 
          FROM messages msg 
          WHERE msg.sender_id = ? OR msg.recipient_id = ?
      )";

$suggestionsStmt = $conn->prepare($suggestionsQuery);
$suggestionsStmt->bind_param("iiiiii", $userId, $userId, $userId, $userId, $userId, $userId);
$suggestionsStmt->execute();
$suggestions = $suggestionsStmt->get_result();

// Handle sending a new message
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message']) && isset($_POST['friend_id'])) {
    $message = $_POST['message'];
    $friendId = $_POST['friend_id'];

    // Updated query to use 'sent_at' instead of 'created_at'
    $sendMessageQuery = "INSERT INTO messages (sender_id, recipient_id, message, sent_at) VALUES (?, ?, ?, NOW())";
    $sendMessageStmt = $conn->prepare($sendMessageQuery);
    $sendMessageStmt->bind_param("iis", $userId, $friendId, $message);

    if ($sendMessageStmt->execute()) {
        echo "Message sent!";
    } else {
        echo "Error sending message: " . $sendMessageStmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">
        &larr; Back
    </button>
    <div class="messages-container">
        <h1>Messages</h1>

        <!-- Message Suggestions -->
        <div class="message-suggestions">
            <h3>Message Suggestions</h3>
            <?php if ($suggestions->num_rows > 0): ?>
                <ul>
                    <?php while ($suggestion = $suggestions->fetch_assoc()): ?>
                        <li>
                            <?php echo htmlspecialchars($suggestion['username']); ?>
                            <form method="POST" action="messages.php" style="display:inline;">
                                <input type="hidden" name="friend_id" value="<?php echo $suggestion['id']; ?>">
                                <input type="text" name="message" placeholder="Say Hi!" required>
                                <button type="submit">Send</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No new friends to suggest messaging.</p>
            <?php endif; ?>
        </div>

        <!-- Existing Conversations -->
        <div class="existing-conversations">
            <h3>Your Conversations</h3>
            <?php if ($conversations->num_rows > 0): ?>
                <ul>
                    <?php while ($conversation = $conversations->fetch_assoc()): ?>
                        <li>
                            <a href="chat.php?friend_id=<?php echo $conversation['id']; ?>">
                                <?php echo htmlspecialchars($conversation['username']); ?>
                            </a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No conversations yet. Start messaging a friend!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
