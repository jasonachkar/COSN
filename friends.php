<?php
session_start();
include 'database.php'; // Include database connection

// Ensure user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id']; // Assuming user_id is stored in session upon login

// Fetch friend list
$friendQuery = "
    SELECT m.username 
    FROM members m 
    JOIN friends f ON m.id = f.friend_id 
    WHERE f.user_id = ? AND f.status = 'accepted'";
$friendStmt = $conn->prepare($friendQuery);
$friendStmt->bind_param("i", $userId);
$friendStmt->execute();
$friends = $friendStmt->get_result();

// Fetch pending friend requests
$pendingQuery = "
    SELECT m.username, f.id AS friend_request_id 
    FROM members m 
    JOIN friends f ON m.id = f.user_id 
    WHERE f.friend_id = ? AND f.status = 'pending'";
$pendingStmt = $conn->prepare($pendingQuery);
$pendingStmt->bind_param("i", $userId);
$pendingStmt->execute();
$pendingRequests = $pendingStmt->get_result();

// Add friend request handler
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['friend_username'])) {
    $friendUsername = $_POST['friend_username'];

    // Find friend's ID by username
    $userQuery = "SELECT id FROM members WHERE username = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("s", $friendUsername);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows > 0) {
        $friendId = $userResult->fetch_assoc()['id'];

        // Insert friend request
        $requestQuery = "INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'pending')";
        $requestStmt = $conn->prepare($requestQuery);
        $requestStmt->bind_param("ii", $userId, $friendId);

        if ($requestStmt->execute()) {
            echo "Friend request sent to $friendUsername!";
        } else {
            echo "Error sending friend request.";
        }
    } else {
        echo "User not found.";
    }
}

// Accept friend request
if (isset($_GET['accept']) && is_numeric($_GET['accept'])) {
    $requestId = $_GET['accept'];
    $acceptQuery = "UPDATE friends SET status = 'accepted' WHERE id = ?";
    $acceptStmt = $conn->prepare($acceptQuery);
    $acceptStmt->bind_param("i", $requestId);
    $acceptStmt->execute();
    header("Location: friends.php"); // Refresh the page
    exit();
}

// Decline friend request
if (isset($_GET['decline']) && is_numeric($_GET['decline'])) {
    $requestId = $_GET['decline'];
    $declineQuery = "UPDATE friends SET status = 'declined' WHERE id = ?";
    $declineStmt = $conn->prepare($declineQuery);
    $declineStmt->bind_param("i", $requestId);
    $declineStmt->execute();
    header("Location: friends.php"); // Refresh the page
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Friends - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="container">
        <h1>Friends</h1>

        <!-- Add Friend Form -->
        <div class="add-friend">
            <h3>Add a Friend</h3>
            <form method="POST" action="friends.php">
                <input type="text" name="friend_username" placeholder="Enter friend's username" required>
                <button type="submit">Send Request</button>
            </form>
        </div>

        <!-- Friend List -->
        <div class="friends-list">
            <h3>Your Friends</h3>
            <?php if ($friends->num_rows > 0): ?>
                <ul>
                    <?php while ($friend = $friends->fetch_assoc()): ?>
                        <li><?php echo htmlspecialchars($friend['username']); ?></li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>You have no friends yet.</p>
            <?php endif; ?>
        </div>

        <!-- Pending Friend Requests -->
        <div class="pending-requests">
            <h3>Pending Friend Requests</h3>
            <?php if ($pendingRequests->num_rows > 0): ?>
                <ul>
                    <?php while ($request = $pendingRequests->fetch_assoc()): ?>
                        <li>
                            <?php echo htmlspecialchars($request['username']); ?>
                            <a href="friends.php?accept=<?php echo $request['friend_request_id']; ?>">Accept</a>
                            <a href="friends.php?decline=<?php echo $request['friend_request_id']; ?>">Decline</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No pending friend requests.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
