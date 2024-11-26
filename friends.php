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
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

// Fetch friend list
$friendQuery = "
    SELECT m.id, m.username 
    FROM members m 
    JOIN friends f ON (m.id = f.friend_id AND f.user_id = ?) OR (m.id = f.user_id AND f.friend_id = ?) 
    WHERE f.status = 'accepted'";
$friendStmt = $conn->prepare($friendQuery);
$friendStmt->bind_param("ii", $userId, $userId);
$friendStmt->execute();
$friends = $friendStmt->get_result();

// Fetch pending friend requests
$pendingQuery = "
    SELECT m.username, f.id AS friend_request_id, f.user_id 
    FROM members m 
    JOIN friends f ON m.id = f.user_id 
    WHERE f.friend_id = ? AND f.status = 'pending'";
$pendingStmt = $conn->prepare($pendingQuery);
$pendingStmt->bind_param("i", $userId);
$pendingStmt->execute();
$pendingRequests = $pendingStmt->get_result();

// Fetch sent friend requests
$sentRequestsQuery = "
    SELECT m.username 
    FROM members m 
    JOIN friends f ON m.id = f.friend_id 
    WHERE f.user_id = ? AND f.status = 'pending'";
$sentRequestsStmt = $conn->prepare($sentRequestsQuery);
$sentRequestsStmt->bind_param("i", $userId);
$sentRequestsStmt->execute();
$sentRequests = $sentRequestsStmt->get_result();

// Add friend request handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['friend_username'])) {
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
            header("Location: friends.php?success=Friend request sent!");
            exit();
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

// Block a member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'block_member') {
    $blockedMemberId = $_POST['blocked_member_id'];

    $blockQuery = "INSERT INTO blocked_members (member_id, blocked_member_id) VALUES (?, ?)";
    $blockStmt = $conn->prepare($blockQuery);
    $blockStmt->bind_param("ii", $userId, $blockedMemberId);

    if ($blockStmt->execute()) {
        header("Location: friends.php?success=Member blocked successfully");
        exit();
    } else {
        echo "Error: " . $blockStmt->error;
    }
}

// Fetch blocked members
$blockedQuery = "
    SELECT m.username 
    FROM members m 
    JOIN blocked_members bm ON m.id = bm.blocked_member_id 
    WHERE bm.member_id = ?";
$blockedStmt = $conn->prepare($blockedQuery);
$blockedStmt->bind_param("i", $userId);
$blockedStmt->execute();
$blockedMembers = $blockedStmt->get_result();
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
    <!-- Add Friend -->
    <div class="add-friend">
        <h2>Add a Friend</h2>
        <form method="POST" action="friends.php">
            <input type="text" name="friend_username" placeholder="Enter friend's username" required>
            <button type="submit">Send Request</button>
        </form>
    </div>

    <!-- Friend List -->
    <div class="friend-list">
        <h2>Your Friends</h2>
        <?php if ($friends->num_rows > 0): ?>
            <ul>
                <?php while ($friend = $friends->fetch_assoc()): ?>
                    <li>
                        <?php echo htmlspecialchars($friend['username']); ?>
                        <form method="POST" action="friends.php" style="display:inline;">
                            <input type="hidden" name="action" value="block_member">
                            <input type="hidden" name="blocked_member_id" value="<?php echo $friend['id']; ?>">
                            <button type="submit">Block</button>
                        </form>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No friends yet.</p>
        <?php endif; ?>
    </div>

    <!-- Pending Requests -->
    <div class="pending-requests">
        <h2>Pending Friend Requests</h2>
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
            <p>No pending requests.</p>
        <?php endif; ?>
    </div>

    <!-- Sent Requests -->
    <div class="sent-requests">
        <h2>Sent Friend Requests</h2>
        <?php if ($sentRequests->num_rows > 0): ?>
            <ul>
                <?php while ($sentRequest = $sentRequests->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($sentRequest['username']); ?> - Waiting for response</li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No sent requests.</p>
        <?php endif; ?>
    </div>

    <!-- Blocked Members -->
    <div class="blocked-members">
        <h2>Blocked Members</h2>
        <?php if ($blockedMembers->num_rows > 0): ?>
            <ul>
                <?php while ($blocked = $blockedMembers->fetch_assoc()): ?>
                    <li><?php echo htmlspecialchars($blocked['username']); ?></li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <p>No blocked members.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
