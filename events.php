<?php
session_start();
include 'database.php';

// Ensure user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$username = $_SESSION['username'];
$userId = $_SESSION['user_id'];
$backUrl = 'home.php';

// Function to get user's groups
function getUserGroups($conn, $userId) {
    $query = "SELECT g.id, g.name FROM `groups` g
              JOIN group_members gm ON g.id = gm.group_id
              WHERE gm.member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Handle event creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $groupId = $_POST['group_id'];
    $dates = $_POST['dates'];
    $times = $_POST['times'];
    $locations = $_POST['locations'];

    $createEventQuery = "INSERT INTO events (title, description, group_id, creator_id) VALUES (?, ?, ?, ?)";
    $createEventStmt = $conn->prepare($createEventQuery);
    $createEventStmt->bind_param("ssii", $title, $description, $groupId, $userId);

    if ($createEventStmt->execute()) {
        $eventId = $createEventStmt->insert_id;

        // Insert event options
        $insertOptionQuery = "INSERT INTO event_options (event_id, date, time, location) VALUES (?, ?, ?, ?)";
        $insertOptionStmt = $conn->prepare($insertOptionQuery);

        for ($i = 0; $i < count($dates); $i++) {
            $insertOptionStmt->bind_param("isss", $eventId, $dates[$i], $times[$i], $locations[$i]);
            $insertOptionStmt->execute();
        }

        header("Location: events.php?success=Event created successfully.");
        exit();
    } else {
        $error = "Error creating event: " . $createEventStmt->error;
    }
}


//Handle Voting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'vote') {
    $optionId = $_POST['option_id'];
    $eventId = $_POST['event_id'];

    // Check if user has already voted
    $checkVoteQuery = "SELECT id FROM event_votes WHERE event_id = ? AND user_id = ?";
    $checkVoteStmt = $conn->prepare($checkVoteQuery);
    $checkVoteStmt->bind_param("ii", $eventId, $userId);
    $checkVoteStmt->execute();
    $existingVote = $checkVoteStmt->get_result()->fetch_assoc();

    if ($existingVote) {
        // Update existing vote
        $updateVoteQuery = "UPDATE event_votes SET option_id = ? WHERE id = ?";
        $updateVoteStmt = $conn->prepare($updateVoteQuery);
        $updateVoteStmt->bind_param("ii", $optionId, $existingVote['id']);
        $updateVoteStmt->execute();
    } else {
        // Insert new vote
        $insertVoteQuery = "INSERT INTO event_votes (event_id, user_id, option_id) VALUES (?, ?, ?)";
        $insertVoteStmt = $conn->prepare($insertVoteQuery);
        $insertVoteStmt->bind_param("iii", $eventId, $userId, $optionId);
        $insertVoteStmt->execute();
    }

    header("Location: events.php?success=Vote recorded successfully.");
    exit();
}

// Fetch upcoming events for the user's groups
$upcomingEventsQuery = "SELECT e.id, e.title, e.description, e.creator_id, g.name AS group_name, m.username AS creator_name
                        FROM events e
                        JOIN `groups` g ON e.group_id = g.id
                        JOIN members m ON e.creator_id = m.id
                        JOIN group_members gm ON g.id = gm.group_id
                        WHERE gm.member_id = ?
                        ORDER BY e.creator_id ASC";
$upcomingEventsStmt = $conn->prepare($upcomingEventsQuery);
$upcomingEventsStmt->bind_param("i", $userId);
$upcomingEventsStmt->execute();
$upcomingEvents = $upcomingEventsStmt->get_result();

// Get user's groups for the dropdown
$userGroups = getUserGroups($conn, $userId);
?>

<!DOCTYPE html>
<html lang="en" class="events">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="header">
        <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">&larr; Back</button>
        <h1>Events</h1>
    </div>

    <div class="container">
        <!-- Create Event Section -->
        <div class="section">
            <h2>Create an Event</h2>
            <form action="events.php" method="POST">
                <input type="hidden" name="action" value="create_event">
                <label for="title">Event Title:</label>
                <input type="text" id="title" name="title" required>

                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>

                <label for="group_id">Select Group:</label>
                <select id="group_id" name="group_id" required>
                    <?php foreach ($userGroups as $group): ?>
                        <option value="<?php echo $group['id']; ?>"><?php echo htmlspecialchars($group['name']); ?></option>
                    <?php endforeach; ?>
                </select>

                <div id="event-options">
                    <div class="event-option">
                        <label for="dates[]">Date:</label>
                        <input type="date" name="dates[]" required>
                        <label for="times[]">Time:</label>
                        <input type="time" name="times[]" required>
                        <label for="locations[]">Location:</label>
                        <input type="text" name="locations[]" required>
                    </div>
                </div>
                <button type="button" onclick="addEventOption()">Add Another Option</button>
                <button type="submit">Create Event</button>
            </form>
        </div>

        <!-- Upcoming Events Section -->
        <div class="section">
            <h2>Upcoming Events</h2>
            <?php if ($upcomingEvents->num_rows > 0): ?>
                <ul>
                    <?php while ($event = $upcomingEvents->fetch_assoc()): ?>
                        <li>
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <p>Group: <?php echo htmlspecialchars($event['group_name']); ?></p>
                            <p>Created by: <?php echo htmlspecialchars($event['creator_name']); ?></p>
                            <p><?php echo htmlspecialchars($event['description']); ?></p>
                            <h4>Options:</h4>
                            <form action="events.php" method="POST">
                                <input type="hidden" name="action" value="vote">
                                <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                <?php
                                $optionsQuery = "SELECT eo.id, eo.date, eo.time, eo.location, COUNT(ev.id) as votes
                                                 FROM event_options eo
                                                 LEFT JOIN event_votes ev ON eo.id = ev.option_id
                                                 WHERE eo.event_id = ?
                                                 GROUP BY eo.id";
                                $optionsStmt = $conn->prepare($optionsQuery);
                                $optionsStmt->bind_param("i", $event['id']);
                                $optionsStmt->execute();
                                $options = $optionsStmt->get_result();
                                while ($option = $options->fetch_assoc()):
                                ?>
                                    <div>
                                        <input type="radio" id="option-<?php echo $option['id']; ?>" name="option_id" value="<?php echo $option['id']; ?>" required>
                                        <label for="option-<?php echo $option['id']; ?>">
                                            <?php echo htmlspecialchars($option['date']); ?> at 
                                            <?php echo htmlspecialchars($option['time']); ?>, 
                                            <?php echo htmlspecialchars($option['location']); ?>
                                            (Votes: <?php echo $option['votes']; ?>)
                                        </label>
                                    </div>
                                <?php endwhile; ?>
                                <button type="submit">Vote</button>
                            </form>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p>No upcoming events.</p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function addEventOption() {
            const container = document.getElementById('event-options');
            const newOption = document.createElement('div');
            newOption.className = 'event-option';
            newOption.innerHTML = `
                <label for="dates[]">Date:</label>
                <input type="date" name="dates[]" required>
                <label for="times[]">Time:</label>
                <input type="time" name="times[]" required>
                <label for="locations[]">Location:</label>
                <input type="text" name="locations[]" required>
            `;
            container.appendChild(newOption);
        }
    </script>
</body>
</html>