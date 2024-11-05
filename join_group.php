<?php
session_start();
include 'database.php';

if (isset($_GET['group_id']) && isset($_SESSION['username'])) {
    $groupId = $_GET['group_id'];
    $username = $_SESSION['username'];

    $query = "INSERT INTO group_members (group_id, username) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $groupId, $username);

    if ($stmt->execute()) {
        echo "Joined group successfully.";
        header("Location: groups.php");
    } else {
        echo "Error joining group.";
    }
}
?>
