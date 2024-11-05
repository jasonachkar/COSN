<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['username'])) {
    $groupName = $_POST['group_name'];
    $description = $_POST['description'];
    $owner = $_SESSION['username'];

    // Insert new group
    $query = "INSERT INTO groups (name, description, owner) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $groupName, $description, $owner);

    if ($stmt->execute()) {
        echo "Group created successfully.";
        header("Location: groups.php"); // Redirect to groups page
    } else {
        echo "Error creating group.";
    }
}
?>
