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
$userRole = $_SESSION['role'] ?? 'junior';
$backUrl = 'home.php';

// Handle creating a new group
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $groupName = $_POST['name'];
    $description = $_POST['description'];

    $createGroupQuery = "INSERT INTO `groups` (name, description, created_by) VALUES (?, ?, ?)";
    $createGroupStmt = $conn->prepare($createGroupQuery);
    $createGroupStmt->bind_param("ssi", $groupName, $description, $userId);

    if ($createGroupStmt->execute()) {
        header("Location: groups.php?success=Group created successfully.");
        exit();
    } else {
        echo "Error creating group: " . $createGroupStmt->error;
    }
}

// Handle join group requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join') {
    $groupId = $_POST['group_id'];

    $joinRequestQuery = "INSERT INTO group_requests (group_id, member_id, status) VALUES (?, ?, 'pending')";
    $joinRequestStmt = $conn->prepare($joinRequestQuery);
    $joinRequestStmt->bind_param("ii", $groupId, $userId);

    if ($joinRequestStmt->execute()) {
        header("Location: groups.php?success=Join request sent successfully.");
        exit();
    } else {
        echo "Error: " . $joinRequestStmt->error;
    }
}

// Handle Accept/Deny Group Join Requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $requestId = $_POST['request_id'];

    if ($action === 'approve') {
        // Approve the join request
        $approveQuery = "UPDATE group_requests SET status = 'approved' WHERE id = ?";
        $approveStmt = $conn->prepare($approveQuery);
        $approveStmt->bind_param("i", $requestId);

        // Add the member to the group_members table
        $addMemberQuery = "
            INSERT INTO group_members (group_id, member_id, username, joined_at)
            SELECT group_id, member_id, (SELECT username FROM members WHERE id = member_id), NOW()
            FROM group_requests WHERE id = ?";
        $addMemberStmt = $conn->prepare($addMemberQuery);
        $addMemberStmt->bind_param("i", $requestId);

        if ($approveStmt->execute() && $addMemberStmt->execute()) {
            header("Location: groups.php?success=Member approved successfully.");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    } elseif ($action === 'deny') {
        // Deny the join request
        $denyQuery = "UPDATE group_requests SET status = 'denied' WHERE id = ?";
        $denyStmt = $conn->prepare($denyQuery);
        $denyStmt->bind_param("i", $requestId);

        if ($denyStmt->execute()) {
            header("Location: groups.php?success=Member denied successfully.");
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Fetch active groups the user is part of
$activeGroupsQuery = "
    SELECT g.id, g.name, g.description
    FROM `groups` g
    JOIN group_members gm ON g.id = gm.group_id
    WHERE gm.member_id = ?";
$activeGroupsStmt = $conn->prepare($activeGroupsQuery);
$activeGroupsStmt->bind_param("i", $userId);
$activeGroupsStmt->execute();
$activeGroups = $activeGroupsStmt->get_result();

// Fetch pending group join requests
$pendingGroupQuery = "
    SELECT g.id AS group_id, g.name, gr.id AS request_id 
    FROM `groups` g 
    JOIN group_requests gr ON g.id = gr.group_id 
    WHERE gr.member_id = ? AND gr.status = 'pending'";
$pendingGroupStmt = $conn->prepare($pendingGroupQuery);
$pendingGroupStmt->bind_param("i", $userId);
$pendingGroupStmt->execute();
$pendingGroups = $pendingGroupStmt->get_result();

// Fetch suggested groups for the user to join
$suggestedGroupsQuery = "
    SELECT g.id, g.name, g.description
    FROM `groups` g
    WHERE g.id NOT IN (
        SELECT group_id FROM group_members WHERE member_id = ?
        UNION
        SELECT group_id FROM group_requests WHERE member_id = ?
    )";
$suggestedGroupsStmt = $conn->prepare($suggestedGroupsQuery);
$suggestedGroupsStmt->bind_param("ii", $userId, $userId);
$suggestedGroupsStmt->execute();
$suggestedGroups = $suggestedGroupsStmt->get_result();

// Fetch groups created by the user or for which the user is an admin
$adminGroupsQuery = "
    SELECT g.id, g.name, g.description
    FROM `groups` g
    WHERE g.created_by = ? OR ? = 'admin'";
$adminGroupsStmt = $conn->prepare($adminGroupsQuery);
$adminGroupsStmt->bind_param("is", $userId, $userRole);
$adminGroupsStmt->execute();
$adminGroups = $adminGroupsStmt->get_result();

$activeMembersQuery = "
    SELECT gm.username, gm.status
    FROM group_members gm
    WHERE gm.group_id = ? AND gm.status = 'active'";
$activeMembersStmt = $conn->prepare($activeMembersQuery);
$activeMembersStmt->bind_param("i", $groupId);
$activeMembersStmt->execute();
$activeMembers = $activeMembersStmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Groups Management - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="header">
        <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">&larr; Back</button>
        <h1>Groups</h1>
    </div>

    <div class="container">
        <!-- Section: Create Group -->
        <div class="section">
            <h2>Create a Group</h2>
            <form action="groups.php" method="POST">
                <input type="hidden" name="action" value="create">
                <label for="name">Group Name:</label>
                <input type="text" name="name" id="name" required>
                <label for="description">Description:</label>
                <textarea name="description" id="description" required></textarea>
                <button type="submit">Create Group</button>
            </form>
        </div>

        <?php if ($adminGroups->num_rows > 0): ?>
    <div class="section">
        <h2>Groups You Have Created</h2>
        <ul>
            <?php while ($group = $adminGroups->fetch_assoc()): ?>
            <li>
                <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                <p><?php echo htmlspecialchars($group['description']); ?></p>
                <details>
                    <summary>View Join Requests</summary>
                    <?php
                    $requestsQuery = "
                        SELECT gr.id AS request_id, m.username
                        FROM group_requests gr
                        JOIN members m ON gr.member_id = m.id
                        WHERE gr.group_id = ? AND gr.status = 'pending'";
                    $requestsStmt = $conn->prepare($requestsQuery);
                    $requestsStmt->bind_param("i", $group['id']);
                    $requestsStmt->execute();
                    $requests = $requestsStmt->get_result();

                    if ($requests->num_rows > 0):
                    ?>
                    <ul>
                        <?php while ($request = $requests->fetch_assoc()): ?>
                        <li>
                            <?php echo htmlspecialchars($request['username']); ?>
                            <form method="POST" action="groups.php" style="display:inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                <button type="submit">Accept</button>
                            </form>
                            <form method="POST" action="groups.php" style="display:inline;">
                                <input type="hidden" name="action" value="deny">
                                <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                <button type="submit">Deny</button>
                            </form>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                    <?php else: ?>
                    <p>No pending join requests.</p>
                    <?php endif; ?>
                </details>
            </li>
            <?php endwhile; ?>
        </ul>
    </div>
<?php else: ?>
    <p>You have not created any groups yet.</p>
<?php endif; ?>

        <!-- Section: Active Groups -->
        <div class="section">
            <h2>Groups You're Part Of</h2>
            <?php if ($activeGroups->num_rows > 0): ?>
                <ul>
                    <?php while ($group = $activeGroups->fetch_assoc()): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                            <p><?php echo htmlspecialchars($group['description']); ?></p>
                            <form method="POST" action="groups.php" style="display:inline;">
                                <input type="hidden" name="action" value="withdraw">
                                <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                <button type="submit">Withdraw</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>You are not part of any groups yet.</p>
            <?php endif; ?>
        </div>

        <!-- Section: Suggested Groups -->
        <div class="section">
            <h2>Suggested Groups</h2>
            <?php if ($suggestedGroups->num_rows > 0): ?>
                <ul>
                    <?php while ($group = $suggestedGroups->fetch_assoc()): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($group['name']); ?></strong>
                            <p><?php echo htmlspecialchars($group['description']); ?></p>
                            <form method="POST" action="groups.php">
                                <input type="hidden" name="action" value="join">
                                <input type="hidden" name="group_id" value="<?php echo $group['id']; ?>">
                                <button type="submit">Join</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No group suggestions available at the moment.</p>
            <?php endif; ?>
        </div>

        <!-- Section: Pending Group Join Requests -->
        <div class="section">
            <h2>Your Pending Group Join Requests</h2>
            <?php if ($pendingGroups->num_rows > 0): ?>
                <ul>
                    <?php while ($pending = $pendingGroups->fetch_assoc()): ?>
                        <li><?php echo htmlspecialchars($pending['name']); ?> - Request Pending</li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No pending group join requests.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
