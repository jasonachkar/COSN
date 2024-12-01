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

// Handle like action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_post_id'])) {
    $postId = $_POST['like_post_id'];
    $liked = isset($_POST['liked']) && $_POST['liked'] == 1;

    try {
        if ($liked) {
            // Check if the user already liked the post
            $checkLikeQuery = "SELECT id FROM likes WHERE post_id = ? AND member_id = ?";
            $checkLikeStmt = $conn->prepare($checkLikeQuery);
            $checkLikeStmt->bind_param("ii", $postId, $userId);
            $checkLikeStmt->execute();
            $checkLikeResult = $checkLikeStmt->get_result();

            if ($checkLikeResult->num_rows === 0) {
                // If not already liked, add a like
                $likeQuery = "INSERT INTO likes (post_id, member_id, created_at) VALUES (?, ?, NOW())";
                $likeStmt = $conn->prepare($likeQuery);
                $likeStmt->bind_param("ii", $postId, $userId);
                $likeStmt->execute();
            }
        } else {
            // Unlike the post
            $unlikeQuery = "DELETE FROM likes WHERE post_id = ? AND member_id = ?";
            $unlikeStmt = $conn->prepare($unlikeQuery);
            $unlikeStmt->bind_param("ii", $postId, $userId);
            $unlikeStmt->execute();
        }

        // Return a JSON response
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Log the error and return an error response
        error_log("Error in like/unlike action: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'An error occurred while processing your request.']);
    }
    exit();
}

// Handle comment action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    $postId = $_POST['post_id'];
    $content = $_POST['comment_content'];

    $commentQuery = "INSERT INTO comments (post_id, member_id, content) VALUES (?, ?, ?)";
    $commentStmt = $conn->prepare($commentQuery);
    if ($commentStmt) {
        $commentStmt->bind_param("iis", $postId, $userId, $content);
        if ($commentStmt->execute()) {
            header("Location: home.php?success=Comment added successfully#post-" . $postId);
            exit();
        } else {
            echo "Error adding comment: " . $commentStmt->error;
        }
        $commentStmt->close();
    } else {
        echo "Error preparing comment query: " . $conn->error;
    }
}

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
$groups = $stmt->get_result();

if (!$groups) {
    echo "Error fetching groups: " . $stmt->error;
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
            mkdir($imageDir, 0777, true);
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

// Fetch posts from the groups the user is part of, including like count, user like status, and comments
$groupPostsQuery = "
    SELECT 
        posts.id AS post_id, 
        posts.title, 
        posts.content, 
        posts.image_path, 
        posts.created_at, 
        groups.name AS group_name,
        (SELECT COUNT(*) FROM likes WHERE likes.post_id = posts.id) AS like_count,
        EXISTS (SELECT 1 FROM likes WHERE likes.post_id = posts.id AND likes.id = ?) AS liked_by_user
    FROM posts
    JOIN `groups` ON posts.group_id = groups.id
    JOIN group_members ON groups.id = group_members.group_id
    WHERE group_members.member_id = ?
    ORDER BY posts.created_at DESC";
$groupPostsStmt = $conn->prepare($groupPostsQuery);
$groupPostsStmt->bind_param("ii", $userId, $userId);
$groupPostsStmt->execute();
$groupPosts = $groupPostsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .like-button.liked {
            color: red;
        }
        .post-container {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 15px;
        }
        .comment-section {
            margin-top: 10px;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
        .comment {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="previous-page.php" class="back-button">← Back</a>
        <div class="welcome-container">
            <span class="welcome-message">COSN - Welcome, <?php echo htmlspecialchars($username); ?>!</span>
            <a href="logout.php" class="logout-button">Logout</a>
        </div>
    </div>

    <div class="content-container">
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
                            <?php while ($group = $groups->fetch_assoc()): ?>
                                <option value="<?php echo $group['id']; ?>">
                                    <?php echo htmlspecialchars($group['name']); ?>
                                </option>
                            <?php endwhile; ?>
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
            <div class="post-container">
                <?php while ($post = $groupPosts->fetch_assoc()): ?>
                    <div class="post-item">
                        <div class="post-content">
                            <h4><?php echo htmlspecialchars($post['group_name']); ?></h4>
                            <p><?php echo htmlspecialchars($post['content']); ?></p>
                            <?php if (!empty($post['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($post['image_path']); ?>" alt="Post Image" class="post-image">
                            <?php endif; ?>
                        </div>
                        <div class="post-actions">
                            <span class="like-count">Likes: <?php echo $post['like_count']; ?></span>
                            <button 
                                class="like-button <?php echo $post['liked_by_user'] ? 'liked' : ''; ?>" 
                                data-post-id="<?php echo $post['post_id']; ?>">
                                ♥
                            </button>
                        </div>
                        <div class="comment-section">
                            <h5>Comments:</h5>
                            <?php
                            $commentsQuery = "SELECT c.content, m.username FROM comments c JOIN members m ON c.id = m.id WHERE c.post_id = ? ORDER BY c.created_at DESC";
                            $commentsStmt = $conn->prepare($commentsQuery);
                            if ($commentsStmt) {
                                $commentsStmt->bind_param("i", $post['post_id']);
                                $commentsStmt->execute();
                                $comments = $commentsStmt->get_result();
                                if ($comments && $comments->num_rows > 0) {
                                    while ($comment = $comments->fetch_assoc()):
                                    ?>
                                        <div class="comment">
                                            <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                                            <?php echo htmlspecialchars($comment['content']); ?>
                                        </div>
                                    <?php 
                                    endwhile;
                                } else {
                                    echo "<p>No comments yet.</p>";
                                }
                                $commentsStmt->close();
                            } else {
                                echo "<p>Error: Unable to fetch comments.</p>";
                            }
                            ?>
                            <form action="home.php" method="POST" class="comment-form">
                                <input type="hidden" name="action" value="add_comment">
                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                <textarea name="comment_content" required placeholder="Add a comment..."></textarea>
                                <button type="submit">Comment</button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Groups Section -->
        <div class="groups-section">
            <h3>Your Groups</h3>
            <?php 
            $groups->data_seek(0); // Reset the groups result pointer
            while ($group = $groups->fetch_assoc()) : 
            ?>
                <p>
                    <strong><?php echo htmlspecialchars($group['name']); ?></strong>: <?php echo htmlspecialchars($group['description']); ?>
                    <form method="post" action="withdraw-group.php">
                        <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                        <button type="submit">Withdraw</button>
                    </form>
                </p>
            <?php endwhile; ?>
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
            <a href="gift_exchange.php" class="action-button">Gift Exchange</a><br/>
            <a href="admin.php" class="action-button">Account Settings</a><br/>
            <h3>Notifications</h3><br/>
            <p>No new notifications.</p><br/>
        </div>
    </div>

    <script>
    document.querySelectorAll('.like-button').forEach(button => {
        button.addEventListener('click', () => {
            const postId = button.getAttribute('data-post-id');
            const liked = button.classList.contains('liked') ? 0 : 1;

            fetch('home.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `like_post_id=${postId}&liked=${liked}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    button.classList.toggle('liked');
                    const likeCount = button.previousElementSibling;
                    const currentLikes = parseInt(likeCount.textContent.split(': ')[1]);
                    likeCount.textContent = `Likes: ${liked ? currentLikes + 1 : currentLikes - 1}`;
                } else {
                    console.error('Failed to like/unlike post:', data.error);
                }
            })
            .catch(err => console.error('Error:', err));
        });
    });
    </script>
</body>
</html>

