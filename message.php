<?php
session_start();
include 'database.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['username'])) {
    $recipient = $_POST['recipient'];
    $message = $_POST['message'];
    $sender = $_SESSION['username'];

    // Retrieve sender and recipient IDs
    $senderIdQuery = "SELECT id FROM members WHERE username = ?";
    $stmt = $conn->prepare($senderIdQuery);
    $stmt->bind_param("s", $sender);
    $stmt->execute();
    $senderId = $stmt->get_result()->fetch_assoc()['id'];

    $recipientIdQuery = "SELECT id FROM members WHERE username = ?";
    $stmt = $conn->prepare($recipientIdQuery);
    $stmt->bind_param("s", $recipient);
    $stmt->execute();
    $recipientId = $stmt->get_result()->fetch_assoc()['id'];

    // Insert message into database
    $insertQuery = "INSERT INTO messages (sender_id, recipient_id, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iis", $senderId, $recipientId, $message);

    if ($stmt->execute()) {
        echo "Message sent successfully.";
        header("Location: messages.php");
        exit();
    } else {
        echo "Error sending message.";
    }
}

// Close the connection
$conn->close();
