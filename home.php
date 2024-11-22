<?php
session_start();
include 'database.php'; // Include database connection

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

// Fetch groups the user belongs to
$query = "
    SELECT g.id, g.name, g.description 
    FROM `groups` g 
    JOIN group_members gm ON g.id = gm.group_id 
    WHERE gm.member_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$groupsResult = $stmt->get_result();
$groupIds = [];
// Fetch groups the user belongs to
$query = "
    SELECT g.id, g.name, g.description 
    FROM `groups` g 
    JOIN group_members gm ON g.id = gm.group_id 
    WHERE gm.member_id = ?
";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $groups = $stmt->get_result();

    if (!$groups) {
        echo "Error fetching groups: " . $stmt->error;
        exit();
    }
} else {
    echo "Error preparing statement: " . $conn->error;
    exit();
}

// Use the $groups variable safely
if ($groups->num_rows > 0) {
    
    while ($group = $groups->fetch_assoc()) {
        
    }

} else {

}

while ($group = $groups->fetch_assoc()) {
    $groupIds[] = $group['id'];
}

// If no groups, show a message
if (empty($groupIds)) {
    $posts = null; // No posts to show
} else {
    // Fetch best and latest posts
    $groupIdsPlaceholder = implode(',', array_fill(0, count($groupIds), '?'));
    $postQuery = "
        SELECT 
            p.id AS post_id, 
            p.content, 
            p.media_url, 
            p.created_at, 
            m.username, 
            g.name AS group_name,
            (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id) AS likes_count,
            (SELECT COUNT(*) FROM comments c WHERE c.post_id = p.id) AS comments_count
        FROM posts p
        JOIN members m ON p.member_id = m.id
        JOIN groups g ON p.group_id = g.id
        WHERE p.group_id IN ($groupIdsPlaceholder)
        ORDER BY likes_count DESC, comments_count DESC, p.created_at DESC
        LIMIT 20";
    $postStmt = $conn->prepare($postQuery);
    $postStmt->bind_param(str_repeat("i", count($groupIds)), ...$groupIds);
    $postStmt->execute();
    $posts = $postStmt->get_result();
}

// Handle like action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post_id'])) {
    $postId = $_POST['like_post_id'];

    // Check if the user already liked the post
    $likeCheckQuery = "SELECT id FROM likes WHERE post_id = ? AND member_id = ?";
    $likeCheckStmt = $conn->prepare($likeCheckQuery);
    $likeCheckStmt->bind_param("ii", $postId, $userId);
    $likeCheckStmt->execute();
    $likeCheckResult = $likeCheckStmt->get_result();

    if ($likeCheckResult->num_rows === 0) {
        $likeQuery = "INSERT INTO likes (post_id, member_id) VALUES (?, ?)";
        $likeStmt = $conn->prepare($likeQuery);
        $likeStmt->bind_param("ii", $postId, $userId);
        $likeStmt->execute();
    }

    header("Location: home.php");
    exit();
}
// Handle post creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_post') {
    $groupId = $_POST['group_id'];
    $content = $_POST['content'];
    $imagePath = null;

      // Handle image upload
      if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageDir = 'uploads/';
        if (!is_dir($imageDir)) {
            mkdir($imageDir, 0777, true); // Create the uploads directory if it doesn't exist
        }

        $imagePath = $imageDir . basename($_FILES['image']['name']);
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
            die("Error moving uploaded file. Check permissions on 'uploads/' directory.");
        }
    }

    $postQuery = "INSERT INTO posts (group_id, author_id, content, image_path) VALUES (?, ?, ?, ?)";
    $postStmt = $conn->prepare($postQuery);
    $postStmt->bind_param("iiss", $groupId, $userId, $content, $imagePath);

    if ($postStmt->execute()) {
        header("Location: home.php?success=Post created successfully.");
        exit();
    } else {
        echo "Error creating post: " . $postStmt->error;
    }
}

// Handle post like
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'like_post') {
    $postId = $_POST['post_id'];

    // Check if the user has already liked the post
    $likeCheckQuery = "SELECT * FROM likes WHERE post_id = ? AND user_id = ?";
    $likeCheckStmt = $conn->prepare($likeCheckQuery);
    $likeCheckStmt->bind_param("ii", $postId, $userId);
    $likeCheckStmt->execute();
    $likeResult = $likeCheckStmt->get_result();

    if ($likeResult->num_rows === 0) {
        // Add like
        $likeQuery = "INSERT INTO likes (post_id, user_id) VALUES (?, ?)";
        $likeStmt = $conn->prepare($likeQuery);
        $likeStmt->bind_param("ii", $postId, $userId);
        $likeStmt->execute();
    }
}

// Fetch posts from groups the user is part of
$postsQuery = "
    SELECT p.*, m.username, COUNT(l.id) AS likes_count
    FROM posts p
    JOIN members m ON p.author_id = m.id
    LEFT JOIN likes l ON p.id = l.post_id
    WHERE p.group_id IN (
        SELECT group_id FROM group_members WHERE member_id = ?
    )
    GROUP BY p.id
    ORDER BY p.created_at DESC";
$postsStmt = $conn->prepare($postsQuery);
$postsStmt->bind_param("i", $userId);
$postsStmt->execute();
$posts = $postsStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
<div class="header">
    <a href="previous-page.php" class="back-button">← Back</a>
    <div class="welcome-container">
        <span class="welcome-message">COSN - Welcome, <?php echo $username; ?>!</span>
        <a href="logout.php" class="logout-button">Logout</a>
    </div>
</div>

<div class="content-container">
   
    <!-- Latest Posts Section -->
    <div class="main-content">
        <h2>Your Latest and Best Posts</h2>
         <!-- Create Post Section -->
         <div class="section">
            <h2>Create a Post</h2>
            <form action="home.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create_post">
                <label for="group_id">Select Group:</label>
<select name="group_id" id="group_id" required>
    <option value="" disabled selected>Select a group</option>
    <?php if ($groups && $groups->num_rows > 0): ?>
        <?php foreach($groups as $group): ?>
            <option value="<?php echo $group['id']; ?>">
                <?php echo htmlspecialchars($group['name']); ?>
            </option>
        <?php endforeach; ?>
    <?php else: ?>
        <option value="" disabled>No groups available</option>
    <?php endif; ?>
</select>
                <label for="content">Content:</label>
                <textarea name="content" id="content" rows="4" required></textarea>
                <label for="image">Upload Image (optional):</label>
                <input type="file" name="image" id="image" accept="image/*">
                <button type="submit">Post</button>
            </form>
        </div>
        <div class="section">
            <h2>Posts in Your Groups</h2>
            <?php if ($posts && $posts->num_rows > 0): ?>
                <ul>
                    <?php while ($post = $posts->fetch_assoc()): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($post['username']); ?>:</strong>
                            <p><?php echo htmlspecialchars($post['content']); ?></p>
                            <?php if ($post['image_path']): ?>
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image" style="max-width: 100%; height: auto;">
                            <?php endif; ?>
                            <p>Likes: <?php echo $post['likes_count']; ?></p>
                            <form action="home.php" method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="like_post">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit">Like</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No posts available. Join groups and start posting!</p>
            <?php endif; ?>
        </div>
    </div>
 <!-- Groups Section -->
 <div class="groups-section">
        <h3>Your Groups</h3>
        <?php foreach ($groups as $group) : ?>
            <p>
                <strong><?php echo $group['name']; ?></strong>: <?php echo $group['description']; ?>
                <form method="post" action="withdraw-group.php">
                    <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                    <button type="submit">Withdraw</button>
                </form>
            </p>
        <?php endforeach; ?>
    </div>
    <!-- Actions Section -->
    <div class="sidebar actions">
        <h3>Actions</h3>
        <a href="messages.php" class="action-button">Messages</a>
        <br/>
        <a href="groups.php" class="action-button">Groups</a>
        <br/>
        <a href="events.php" class="action-button">Events</a>
        <br/>
        <a href="friends.php" class="action-button">Friends</a><br/>
        <a href="settings.php" class="action-button">Account Settings</a><br/>
        <h3>Notifications</h3><br/>
        <p>No new notifications.</p><br/>
    </div>
</div>
</body>
</html>
