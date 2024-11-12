<?php
session_start();
include 'database.php'; // Include database connection

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];

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

    $userQuery = "SELECT id FROM members WHERE username = ?";
    $userStmt = $conn->prepare($userQuery);
    $userStmt->bind_param("s", $friendUsername);
    $userStmt->execute();
    $userResult = $userStmt->get_result();

    if ($userResult->num_rows > 0) {
        $friendId = $userResult->fetch_assoc()['id'];

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

// Accept and Decline Friend Requests
if (isset($_GET['accept']) && is_numeric($_GET['accept'])) {
    $requestId = $_GET['accept'];
    $acceptQuery = "UPDATE friends SET status = 'accepted' WHERE id = ?";
    $acceptStmt = $conn->prepare($acceptQuery);
    $acceptStmt->bind_param("i", $requestId);
    $acceptStmt->execute();
    header("Location: friends.php");
    exit();
}

if (isset($_GET['decline']) && is_numeric($_GET['decline'])) {
    $requestId = $_GET['decline'];
    $declineQuery = "UPDATE friends SET status = 'declined' WHERE id = ?";
    $declineStmt = $conn->prepare($declineQuery);
    $declineStmt->bind_param("i", $requestId);
    $declineStmt->execute();
    header("Location: friends.php");
    exit();
}

// Friend Suggestions
$suggestionsQuery = "
    SELECT username 
    FROM members 
    WHERE id != ? 
      AND id NOT IN (
          SELECT friend_id FROM friends WHERE user_id = ? 
          UNION 
          SELECT user_id FROM friends WHERE friend_id = ?
          UNION
          SELECT friend_id FROM friends WHERE user_id = ? AND status = 'pending'
          UNION 
          SELECT user_id FROM friends WHERE friend_id = ? AND status = 'pending'
      )
    ORDER BY RAND() LIMIT 5";
$suggestionsStmt = $conn->prepare($suggestionsQuery);
$suggestionsStmt->bind_param("iiiii", $userId, $userId, $userId, $userId, $userId);
$suggestionsStmt->execute();
$suggestions = $suggestionsStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Friends - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
<button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">
    &larr; Back
</button>
<div class="friends-page">
    <!-- Add Friend Form -->
    <div class="add-friend">
        <h2>Add a Friend</h2>
        <form method="POST" action="friends.php">
            <input type="text" name="friend_username" placeholder="Enter friend's username" required>
            <button type="submit">Send Request</button>
        </form>
    </div>

    <!-- Friends Section Container -->
    <div class="friends-section">
        <!-- Your Friends -->
        <div class="friend-list">
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

        <!-- Friend Suggestions -->
        <div class="friend-suggestions">
            <h3>Friend Suggestions</h3>
            <?php if ($suggestions->num_rows > 0): ?>
                <ul>
                    <?php while ($suggested = $suggestions->fetch_assoc()): ?>
                        <li>
                            <?php echo htmlspecialchars($suggested['username']); ?>
                            <form method="POST" action="friends.php" style="display:inline;">
                                <input type="hidden" name="friend_username" value="<?php echo htmlspecialchars($suggested['username']); ?>">
                                <button type="submit">Add Friend</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No suggestions available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
