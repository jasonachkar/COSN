<?php
session_start();
include 'database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_id']) && isset($_POST['content'])) {
    $postId = $_POST['post_id'];
    $content = $_POST['content'];
    $userId = $_SESSION['user_id'];

    $commentQuery = "INSERT INTO comments (post_id, member_id, content) VALUES (?, ?, ?)";
    $commentStmt = $conn->prepare($commentQuery);
    $commentStmt->bind_param("iis", $postId, $userId, $content);

    if ($commentStmt->execute()) {
        $username = $_SESSION['username'];
        echo json_encode(['success' => true, 'username' => $username, 'content' => $content]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}