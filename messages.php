<?php
session_start();
include 'database.php';

if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    
    echo "<h2>Messages</h2>";

    // Fetch messages received
    $query = "SELECT * FROM messages WHERE recipient = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $receivedMessages = $stmt->get_result();

    echo "<h3>Received Messages</h3>";
    while ($msg = $receivedMessages->fetch_assoc()) {
        echo "<div class='message'>";
        echo "<p><strong>From:</strong> " . $msg['sender'] . "</p>";
        echo "<p>" . $msg['message'] . "</p>";
        echo "</div>";
    }

    // Fetch messages sent
    $query = "SELECT * FROM messages WHERE sender = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $sentMessages = $stmt->get_result();

    echo "<h3>Sent Messages</h3>";
    while ($msg = $sentMessages->fetch_assoc()) {
        echo "<div class='message'>";
        echo "<p><strong>To:</strong> " . $msg['recipient'] . "</p>";
        echo "<p>" . $msg['message'] . "</p>";
        echo "</div>";
    }
}
?>
