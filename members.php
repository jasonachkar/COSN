<?php
session_start();
include 'database.php'; // Include database connection

// Handle Create Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $profilePicture = $_FILES['profile_picture']['name'] ? $_FILES['profile_picture']['name'] : null;

    // File upload handling
    if ($profilePicture) {
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile);
    }

    $query = "INSERT INTO members (username, email, password, profile_picture) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $username, $email, $password, $profilePicture);

    if ($stmt->execute()) {
        header("Location: members.php?success=Member created successfully");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Handle Delete Member
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = "DELETE FROM members WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: members.php?success=Member deleted successfully");
    exit();
}

// Handle Edit Member
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $profilePicture = $_FILES['profile_picture']['name'] ? $_FILES['profile_picture']['name'] : null;

    // File upload handling
    if ($profilePicture) {
        $targetDir = "uploads/";
        $targetFile = $targetDir . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFile);
    }

    $query = $profilePicture
        ? "UPDATE members SET username = ?, email = ?, profile_picture = ? WHERE id = ?"
        : "UPDATE members SET username = ?, email = ? WHERE id = ?";
    $stmt = $conn->prepare($query);

    if ($profilePicture) {
        $stmt->bind_param("sssi", $username, $email, $profilePicture, $id);
    } else {
        $stmt->bind_param("ssi", $username, $email, $id);
    }

    if ($stmt->execute()) {
        header("Location: members.php?success=Member updated successfully");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}

// Fetch All Members
$query = "SELECT * FROM members";
$result = $conn->query($query);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Members Management - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <h1>Manage Members</h1>
    
    <!-- Success Message -->
    <?php if (isset($_GET['success'])): ?>
        <p style="color: green;"><?php echo htmlspecialchars($_GET['success']); ?></p>
    <?php endif; ?>

    <!-- Create Member Form -->
    <h2>Create Member</h2>
    <form action="members.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">
        <label for="username">Username:</label>
        <input type="text" name="username" required><br>
        <label for="email">Email:</label>
        <input type="email" name="email" required><br>
        <label for="password">Password:</label>
        <input type="password" name="password" required><br>
        <label for="profile_picture">Profile Picture:</label>
        <input type="file" name="profile_picture"><br>
        <button type="submit">Create Member</button>
    </form>

    <!-- Display Members -->
    <h2>Existing Members</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Profile Picture</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($member = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($member['id']); ?></td>
                    <td><?php echo htmlspecialchars($member['username']); ?></td>
                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                    <td>
                        <?php if ($member['profile_picture']): ?>
                            <img src="uploads/<?php echo htmlspecialchars($member['profile_picture']); ?>" alt="Profile Picture" width="50">
                        <?php else: ?>
                            No Picture
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Edit Member Form -->
                        <form action="members.php" method="POST" enctype="multipart/form-data" style="display:inline;">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                            <input type="text" name="username" value="<?php echo htmlspecialchars($member['username']); ?>" required>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                            <input type="file" name="profile_picture">
                            <button type="submit">Edit</button>
                        </form>
                        <!-- Delete Member -->
                        <a href="members.php?delete=<?php echo $member['id']; ?>" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>
