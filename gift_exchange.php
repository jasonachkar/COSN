<?php
session_start();
include 'database.php'; // Include database connection

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Handle Secret Santa initialization
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['init_secret_santa'])) {
    $groupId = $_POST['group_id'];

    // Fetch group members
    $membersQuery = "SELECT id FROM group_members WHERE group_id = ?";
    $stmt = $conn->prepare($membersQuery);
    $stmt->bind_param("i", $groupId);
    $stmt->execute();
    $result = $stmt->get_result();

    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = $row['id'];
    }

    // Randomly shuffle and assign recipients
    shuffle($members);
    $pairs = [];
    for ($i = 0; $i < count($members); $i++) {
        $pairs[] = [
            'giver' => $members[$i],
            'recipient' => $members[($i + 1) % count($members)]
        ];
    }

    // Insert pairs into the database
    $insertQuery = "INSERT INTO gift_exchange (group_id, member_id, recipient_id) VALUES (?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);

    foreach ($pairs as $pair) {
        $insertStmt->bind_param("iii", $groupId, $pair['giver'], $pair['recipient']);
        $insertStmt->execute();
    }

    header("Location: gift_exchange.php?success=Secret Santa initialized!");
    exit();
}

// Fetch Secret Santa assignments
$assignmentsQuery = "
    SELECT m1.username AS giver, m2.username AS recipient, ge.gift_suggestion
    FROM gift_exchange ge
    JOIN members m1 ON ge.member_id = m1.id
    JOIN members m2 ON ge.recipient_id = m2.id
    WHERE ge.group_id = ?";
$assignmentsStmt = $conn->prepare($assignmentsQuery);
$assignmentsStmt->bind_param("i", $_GET['group_id']);
$assignmentsStmt->execute();
$assignments = $assignmentsStmt->get_result();

// Add gift suggestion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_gift'])) {
    $giftSuggestion = $_POST['gift_suggestion'];
    $recipientId = $_POST['recipient_id'];

    $updateQuery = "UPDATE gift_exchange SET gift_suggestion = ? WHERE member_id = ? AND recipient_id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param("sii", $giftSuggestion, $userId, $recipientId);

    if ($updateStmt->execute()) {
        header("Location: gift_exchange.php?success=Gift suggestion added!");
        exit();
    } else {
        echo "Error: " . $updateStmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gift Exchange - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="header">
        <button onclick="window.location.href='groups.php';" class="back-button">&larr; Back</button>
        <h1>Secret Santa Gift Exchange</h1>
    </div>

    <div class="container">
        <!-- Initialize Secret Santa -->
        <?php if (isset($_GET['group_id'])): ?>
            <form method="POST" action="gift_exchange.php">
                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($_GET['group_id']); ?>">
                <button type="submit" name="init_secret_santa">Initialize Secret Santa</button>
            </form>
        <?php endif; ?>

        <!-- Display Assignments -->
        <div class="assignments">
            <h2>Your Assignments</h2>
            <?php if ($assignments->num_rows > 0): ?>
                <ul>
                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                        <li>
                            <strong><?php echo htmlspecialchars($assignment['giver']); ?></strong> ➡️ <strong><?php echo htmlspecialchars($assignment['recipient']); ?></strong>
                            <?php if ($assignment['giver'] == $username): ?>
                                <form method="POST" action="gift_exchange.php" style="margin-top: 10px;">
                                    <input type="hidden" name="recipient_id" value="<?php echo htmlspecialchars($assignment['recipient']); ?>">
                                    <label for="gift_suggestion">Gift Suggestion:</label>
                                    <input type="text" name="gift_suggestion" value="<?php echo htmlspecialchars($assignment['gift_suggestion']); ?>" required>
                                    <button type="submit" name="add_gift">Submit</button>
                                </form>
                            <?php endif; ?>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No assignments yet. Please initialize Secret Santa!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
