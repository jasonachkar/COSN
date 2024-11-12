<?php
session_start();
include 'database.php'; // Database connection

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

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

// Fetch upcoming events
$events = $conn->query("SELECT e.*, m.username as creator FROM events e JOIN members m ON e.creator_id = m.id WHERE e.event_date >= CURDATE() ORDER BY e.event_date ASC");

// Handle voting on event dates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vote_event'])) {
    $eventId = $_POST['event_id'];
    $preferred_date = $_POST['preferred_date'];
    $preferred_time = $_POST['preferred_time'];

    $stmt = $conn->prepare("INSERT INTO votes (event_id, user_id, preferred_date, preferred_time) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE preferred_date=?, preferred_time=?");
    $stmt->bind_param("iissss", $eventId, $userId, $preferred_date, $preferred_time, $preferred_date, $preferred_time);
    $stmt->execute();
    $stmt->close();
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
    <div class="container">
        <h1>Events</h1>

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
                
                <label for="event_date">Date:</label>
                <input type="date" name="event_date" id="event_date" required>
                
                <label for="event_time">Time:</label>
                <input type="time" name="event_time" id="event_time" required>
                
                <button type="submit" name="create_event">Create Event</button>
            </form>
        </div>

        <!-- Upcoming Events -->
        <div class="events-list">
            <h2>Upcoming Events</h2>
            <?php if ($events->num_rows > 0): ?>
                <ul>
                    <?php while ($event = $events->fetch_assoc()): ?>
                        <li>
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p>Created by: <?php echo htmlspecialchars($event['creator']); ?></p>
                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                            <p>Location: <?php echo htmlspecialchars($event['location']); ?></p>
                            <p>Date: <?php echo htmlspecialchars($event['event_date']); ?></p>
                            <p>Time: <?php echo htmlspecialchars($event['event_time']); ?></p>
                            
                            <!-- Voting Form -->
                            <h4>Vote for Date & Time</h4>
                            <form method="POST" action="events.php">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <label for="preferred_date">Preferred Date:</label>
                                <input type="date" name="preferred_date" required>
                                
                                <label for="preferred_time">Preferred Time:</label>
                                <input type="time" name="preferred_time" required>
                                
                                <button type="submit" name="vote_event">Submit Vote</button>
                            </form>
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
