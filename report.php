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
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $reportType && $filterColumn && $filterValue) {
    $allowedColumns = [
        'members' => ['interest', 'age', 'profession', 'region'],
        'groups' => ['category', 'region']
    ];

    if (in_array($filterColumn, $allowedColumns[$reportType])) {
        if ($reportType === 'members') {
            $query = "SELECT id, username, age, profession, region FROM members WHERE $filterColumn = ?";
        } elseif ($reportType === 'groups') {
            $query = "SELECT id, name, category, region FROM `groups` WHERE $filterColumn = ?";
        }

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $filterValue);
        $stmt->execute();
        $results = $stmt->get_result();
    } else {
        $error = "Invalid filter column selected.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
    <script>
        function updateFilterOptions() {
            const reportType = document.getElementById('report_type').value;
            const filterColumn = document.getElementById('filter_column');
            filterColumn.innerHTML = '';

            const options = {
                'members': ['interest', 'age', 'profession', 'region'],
                'groups': ['category', 'region']
            };

            options[reportType].forEach(option => {
                const optionElement = document.createElement('option');
                optionElement.value = option;
                optionElement.textContent = option.charAt(0).toUpperCase() + option.slice(1);
                filterColumn.appendChild(optionElement);
            });
        }
    </script>
</head>
<body>
    <button class="back-button" onclick="window.location.href='<?php echo $backUrl; ?>';">&larr; Back</button>
    <h1>Reports</h1>

    <form method="POST" action="report.php" class="report-form">
        <label for="report_type">Report Type:</label>
        <select name="report_type" id="report_type" required onchange="updateFilterOptions()">
            <option value="members" <?php echo $reportType === 'members' ? 'selected' : ''; ?>>Members</option>
            <option value="groups" <?php echo $reportType === 'groups' ? 'selected' : ''; ?>>Groups</option>
        </select>

        <label for="filter_column">Filter By:</label>
        <select name="filter_column" id="filter_column" required></select>

        <label for="filter_value">Filter Value:</label>
        <input type="text" name="filter_value" id="filter_value" value="<?php echo htmlspecialchars($filterValue ?? ''); ?>" required>

        <button type="submit">Generate Report</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php elseif ($results): ?>
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

    <script>
        // Initialize filter options on page load
        updateFilterOptions();
    </script>
</body>
</html>