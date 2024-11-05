<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['username'])) {
    $recipient = $_POST['recipient'];
    $message = $_POST['message'];
    $sender = $_SESSION['username'];

    $query = "INSERT INTO messages (sender, recipient, message) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $sender, $recipient, $message);

    if ($stmt->execute()) {
        echo "Message sent successfully.";
        header("Location: messages.php"); // Redirect to messages page
    } else {
        echo "Error sending message.";
    }
}
?>
