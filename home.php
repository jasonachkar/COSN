<?php
session_start();
include 'database.php'; // Include the database connection

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit();
}

$username = $_SESSION['username'];

// Retrieve user's latest posts or group posts
$query = "SELECT * FROM posts WHERE author_id = (SELECT id FROM members WHERE username = ?) ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Home - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>

<body>
    <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>
    <h3>Your Latest Posts</h3>
    <div class="posts">
        <?php while ($post = $result->fetch_assoc()): ?>
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