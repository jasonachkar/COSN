<?php
session_start();
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['username'])) {
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_SESSION['username'];
    $groupId = $_POST['group_id']; // assuming posts are linked to a group

    $query = "INSERT INTO posts (title, content, author, group_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sssi", $title, $content, $author, $groupId);

    if ($stmt->execute()) {
        echo "Post created successfully.";
        header("Location: group.php?id=" . $groupId); // Redirect to group page
    } else {
        echo "Error creating post.";
    }
}
?>
