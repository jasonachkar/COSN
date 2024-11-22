<?php
session_start();
include 'database.php'; // Include database connection

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$groupId = $_GET['group_id']; // Group ID passed in URL

// Check if the user is a member of the group
$membershipQuery = "SELECT * FROM group_members WHERE group_id = ? AND member_id = ?";
$membershipStmt = $conn->prepare($membershipQuery);
$membershipStmt->bind_param("ii", $groupId, $userId);
$membershipStmt->execute();
$membershipResult = $membershipStmt->get_result();

if ($membershipResult->num_rows === 0) {
    echo "Access denied. You are not a member of this group.";
    exit();
}

// Fetch group details
$groupQuery = "SELECT name, description FROM groups WHERE id = ?";
$groupStmt = $conn->prepare($groupQuery);
$groupStmt->bind_param("i", $groupId);
$groupStmt->execute();
$group = $groupStmt->get_result()->fetch_assoc();

// Handle new post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = $_POST['content'];
    $mediaUrl = isset($_FILES['media']['name']) ? 'uploads/' . basename($_FILES['media']['name']) : null;

    if ($mediaUrl && move_uploaded_file($_FILES['media']['tmp_name'], $mediaUrl)) {
        // Save post with media
        $postQuery = "INSERT INTO posts (group_id, member_id, content, media_url) VALUES (?, ?, ?, ?)";
        $postStmt = $conn->prepare($postQuery);
        $postStmt->bind_param("iiss", $groupId, $userId, $content, $mediaUrl);
    } else {
        // Save post without media
        $postQuery = "INSERT INTO posts (group_id, member_id, content) VALUES (?, ?, ?)";
        $postStmt = $conn->prepare($postQuery);
        $postStmt->bind_param("iis", $groupId, $userId, $content);
    }

    if ($postStmt->execute()) {
        header("Location: group_posts.php?group_id=$groupId");
        exit();
    } else {
        echo "Error posting: " . $postStmt->error;
    }
}

// Fetch all posts in the group
$postsQuery = "
    SELECT p.id, p.content, p.media_url, p.created_at, m.username 
    FROM posts p 
    JOIN members m ON p.member_id = m.id 
    WHERE p.group_id = ? 
    ORDER BY p.created_at DESC";
$postsStmt = $conn->prepare($postsQuery);
$postsStmt->bind_param("i", $groupId);
$postsStmt->execute();
$posts = $postsStmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($group['name']); ?> - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <button class="back-button" onclick="window.location.href='home.php';">&larr; Back</button>
    <h1><?php echo htmlspecialchars($group['name']); ?></h1>
    <p><?php echo htmlspecialchars($group['description']); ?></p>

    <!-- Create a Post -->
    <h2>Create a Post</h2>
    <form action="group_posts.php?group_id=<?php echo $groupId; ?>" method="POST" enctype="multipart/form-data">
        <textarea name="content" placeholder="Write something..." required></textarea><br>
        <label for="media">Attach an image/video:</label>
        <input type="file" name="media" accept="image/*,video/*"><br>
        <button type="submit">Post</button>
    </form>

    <!-- Display Posts -->
    <h2>Posts</h2>
    <?php while ($post = $posts->fetch_assoc()): ?>
        <div class="post">
            <p><strong><?php echo htmlspecialchars($post['username']); ?>:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
            <?php if ($post['media_url']): ?>
                <p><img src="<?php echo htmlspecialchars($post['media_url']); ?>" alt="Media" style="max-width:100%;"></p>
            <?php endif; ?>
            <p><small>Posted on <?php echo $post['created_at']; ?></small></p>
        </div>
    <?php endwhile; ?>
</body>
</html>
