<?php
session_start();
include 'database.php';

echo "<h2>All Groups</h2>";
$query = "SELECT * FROM groups";
$result = $conn->query($query);

while ($group = $result->fetch_assoc()) {
    echo "<div class='group'>";
    echo "<h3>" . $group['name'] . "</h3>";
    echo "<p>" . $group['description'] . "</p>";
    echo "<a href='join_group.php?group_id=" . $group['id'] . "'>Join Group</a>";
    echo "</div>";
}
?>
