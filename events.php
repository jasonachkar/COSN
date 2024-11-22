<?php
session_start();
include 'database.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$backUrl = isset($_SESSION['username']) ? 'home.php' : 'index.php';

// Handle event creation form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_event'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];

    $stmt = $conn->prepare("INSERT INTO events (group_id, creator_id, title, description, location, event_date, event_time) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $_POST['group_id'], $userId, $title, $description, $location, $event_date, $event_time);
    $stmt->execute();
    $stmt->close();
}

// Handle voting
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote'])) {
    $eventId = $_POST['event_id'];
    $voteDate = $_POST['vote_date'];
    $voteTime = $_POST['vote_time'];
    $votePlace = $_POST['vote_place'];

    $voteQuery = "INSERT INTO event_votes (event_id, member_id, vote_date, vote_time, vote_place) VALUES (?, ?, ?, ?, ?)";
    $voteStmt = $conn->prepare($voteQuery);
    $voteStmt->bind_param("iisss", $eventId, $userId, $voteDate, $voteTime, $votePlace);

    if ($voteStmt->execute()) {
        echo "Vote submitted successfully!";
    } else {
        echo "Error: " . $voteStmt->error;
    }
}

// Fetch upcoming events
$events = $conn->query("SELECT e.*, m.username as creator FROM events e JOIN members m ON e.creator_id = m.id WHERE e.event_date >= CURDATE() ORDER BY e.event_date ASC");

// Fetch voting results
function getVotingResults($conn, $eventId) {
    $votesQuery = "
        SELECT vote_date, vote_time, vote_place, COUNT(*) AS vote_count
        FROM event_votes
        WHERE event_id = ?
        GROUP BY vote_date, vote_time, vote_place
        ORDER BY vote_count DESC";
    $stmt = $conn->prepare($votesQuery);
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    return $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Events - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="header">
        <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">&larr; Back</button>
        <h1>Events</h1>
    </div>

    <div class="container">
        <!-- Event Creation Form -->
        <div class="event-form">
            <h2>Create an Event</h2>
            <form method="POST" action="events.php">
                <input type="hidden" name="group_id" value="1"> <!-- Assuming group ID is 1 for now -->

                <label for="title">Event Title:</label>
                <input type="text" name="title" id="title" required>

                <label for="description">Description:</label>
                <textarea name="description" id="description"></textarea>

                <label for="location">Location:</label>
                <input type="text" name="location" id="location" required>

                <div class="datetime-fields">
                    <label for="event_date">Date:</label>
                    <input type="date" name="event_date" id="event_date" required>

                    <label for="event_time">Time:</label>
                    <input type="time" name="event_time" id="event_time" required>
                </div>

                <button type="submit" name="create_event" class="create-event-button">Create Event</button>
            </form>
        </div>

        <!-- Upcoming Events on the Right Side -->
        <div class="events-list">
            <h2>Upcoming Events</h2>
            <?php if ($events->num_rows > 0): ?>
                <ul>
                    <?php while ($event = $events->fetch_assoc()): ?>
                        <li>
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p><strong>Creator:</strong> <?php echo htmlspecialchars($event['creator']); ?></p>
                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                            <p><strong>Date:</strong> <?php echo htmlspecialchars($event['event_date']); ?></p>
                            <p><strong>Time:</strong> <?php echo htmlspecialchars($event['event_time']); ?></p>

                            <!-- Voting Form -->
                            <form method="POST" action="events.php">
                                <input type="hidden" name="vote" value="1">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <label for="vote_date">Date:</label>
                                <input type="date" name="vote_date" required>
                                <label for="vote_time">Time:</label>
                                <input type="time" name="vote_time" required>
                                <label for="vote_place">Place:</label>
                                <input type="text" name="vote_place" required>
                                <button type="submit">Submit Vote</button>
                            </form>

                            <!-- Voting Results -->
                            <h4>Voting Results:</h4>
                            <ul>
                                <?php
                                $results = getVotingResults($conn, $event['id']);
                                while ($result = $results->fetch_assoc()):
                                ?>
                                    <li>
                                        <?php echo htmlspecialchars($result['vote_date']) . ' at ' . htmlspecialchars($result['vote_time']) . ' - ' . htmlspecialchars($result['vote_place']) . ' (' . $result['vote_count'] . ' votes)'; ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No upcoming events. Create one to get started!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
