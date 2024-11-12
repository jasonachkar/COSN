<?php
session_start();
include 'database.php'; // Include the database connection

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

// Get the group ID from URL
$groupId = $_GET['id'];
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

// Retrieve group details and posts
$groupQuery = "SELECT * FROM `groups` WHERE id = ?";
$postQuery = "SELECT * FROM posts WHERE group_id = ? ORDER BY created_at DESC";

$groupStmt = $conn->prepare($groupQuery);
$groupStmt->bind_param("i", $groupId);
$groupStmt->execute();
$group = $groupStmt->get_result()->fetch_assoc();

$postStmt = $conn->prepare($postQuery);
$postStmt->bind_param("i", $groupId);
$postStmt->execute();
$posts = $postStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($group['name']); ?> - Group</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body>
<button onclick="window.location.href='<?php echo $backUrl; ?>';" 
        style="margin: 10px; padding: 5px 10px; font-size: 14px; cursor: pointer;">
    &larr; Back
</button>


    <h2><?php echo htmlspecialchars($group['name']); ?></h2>
    <p><?php echo htmlspecialchars($group['description']); ?></p>

    <h3>Posts</h3>
    <div class="posts">
        <?php while ($post = $posts->fetch_assoc()): ?>
            <div class="post">
                <h4><?php echo htmlspecialchars($post['title']); ?></h4>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <span>Posted on: <?php echo $post['created_at']; ?></span>
            </div>
        <?php endwhile; ?>
    </div>
</body>

</html>

<?php
// Close the connection
$conn->close();
?>