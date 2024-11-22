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
$backUrl = 'home.php';

// Handle Report Query
$reportType = isset($_POST['report_type']) ? $_POST['report_type'] : 'members';
$filterColumn = isset($_POST['filter_column']) ? $_POST['filter_column'] : null;
$filterValue = isset($_POST['filter_value']) ? $_POST['filter_value'] : null;

$results = null;

if ($reportType && $filterColumn && $filterValue) {
    if ($reportType === 'members') {
        $query = "SELECT id, username, age, profession, region FROM members WHERE $filterColumn = ?";
    } elseif ($reportType === 'groups') {
        $query = "SELECT id, name, category, region FROM groups WHERE $filterColumn = ?";
    } else {
        $query = null;
    }

    if ($query) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $filterValue);
        $stmt->execute();
        $results = $stmt->get_result();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">&larr; Back</button>
    <h1>Reports</h1>

    <form method="POST" action="report.php" class="report-form">
        <label for="report_type">Report Type:</label>
        <select name="report_type" id="report_type" required>
            <option value="members" <?php echo $reportType === 'members' ? 'selected' : ''; ?>>Members</option>
            <option value="groups" <?php echo $reportType === 'groups' ? 'selected' : ''; ?>>Groups</option>
        </select>

        <label for="filter_column">Filter By:</label>
        <select name="filter_column" id="filter_column" required>
            <?php if ($reportType === 'members'): ?>
                <option value="interest" <?php echo $filterColumn === 'interest' ? 'selected' : ''; ?>>Interest</option>
                <option value="age" <?php echo $filterColumn === 'age' ? 'selected' : ''; ?>>Age</option>
                <option value="profession" <?php echo $filterColumn === 'profession' ? 'selected' : ''; ?>>Profession</option>
                <option value="region" <?php echo $filterColumn === 'region' ? 'selected' : ''; ?>>Region</option>
            <?php elseif ($reportType === 'groups'): ?>
                <option value="category" <?php echo $filterColumn === 'category' ? 'selected' : ''; ?>>Category</option>
                <option value="region" <?php echo $filterColumn === 'region' ? 'selected' : ''; ?>>Region</option>
            <?php endif; ?>
        </select>

        <label for="filter_value">Filter Value:</label>
        <input type="text" name="filter_value" id="filter_value" value="<?php echo htmlspecialchars($filterValue ?? ''); ?>" required>

        <button type="submit">Generate Report</button>
    </form>

    <?php if ($results): ?>
        <h2>Report Results</h2>
        <table>
            <thead>
                <tr>
                    <?php if ($reportType === 'members'): ?>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Age</th>
                        <th>Profession</th>
                        <th>Region</th>
                    <?php elseif ($reportType === 'groups'): ?>
                        <th>ID</th>
                        <th>Group Name</th>
                        <th>Category</th>
                        <th>Region</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <?php foreach ($row as $value): ?>
                            <td><?php echo htmlspecialchars($value); ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
        <p>No results found for the given filter.</p>
    <?php endif; ?>
</body>
</html>
