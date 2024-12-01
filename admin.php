<?php
session_start();
include 'database.php';



// Function to get all members
function getAllMembers($conn) {
    $query = "SELECT id, username, email, role FROM members";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all groups
function getAllGroups($conn) {
    $query = "SELECT id, name, description FROM `groups`";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all pending join requests
function getPendingJoinRequests($conn) {
    $query = "SELECT gr.id, g.name AS group_name, m.username, gr.status
              FROM group_requests gr
              JOIN `groups` g ON gr.group_id = g.id
              JOIN members m ON gr.id = m.id
              WHERE gr.status = 'pending'";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_member':
                $username = $_POST['username'];
                $email = $_POST['email'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $role = $_POST['role'];
                $stmt = $conn->prepare("INSERT INTO members (username, email, password, role) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $email, $password, $role);
                $stmt->execute();
                break;
            case 'edit_member':
                $id = $_POST['id'];
                $username = $_POST['username'];
                $email = $_POST['email'];
                $role = $_POST['role'];
                $stmt = $conn->prepare("UPDATE members SET username = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $email, $role, $id);
                $stmt->execute();
                break;
            case 'delete_member':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
            case 'create_group':
                $name = $_POST['name'];
                $description = $_POST['description'];
                $stmt = $conn->prepare("INSERT INTO `groups` (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
                $stmt->execute();
                break;
            case 'edit_group':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $description = $_POST['description'];
                $stmt = $conn->prepare("UPDATE `groups` SET name = ?, description = ? WHERE id = ?");
                $stmt->bind_param("ssi", $name, $description, $id);
                $stmt->execute();
                break;
            case 'delete_group':
                $id = $_POST['id'];
                $stmt = $conn->prepare("DELETE FROM `groups` WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                break;
            case 'handle_request':
                $id = $_POST['id'];
                $status = $_POST['status'];
                $stmt = $conn->prepare("UPDATE group_requests SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $status, $id);
                $stmt->execute();
                if ($status === 'approved') {
                    $request = $conn->prepare("SELECT group_id, member_id FROM group_requests WHERE id = ?");
                    $request->bind_param("i", $id);
                    $request->execute();
                    $result = $request->get_result();
                    $row = $result->fetch_assoc();
                    $stmt = $conn->prepare("INSERT INTO group_members (group_id, member_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $row['group_id'], $row['member_id']);
                    $stmt->execute();
                }
                break;
        }
    }
}

$members = getAllMembers($conn);
$groups = getAllGroups($conn);
$joinRequests = getPendingJoinRequests($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .admin-section {
            margin-bottom: 2rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<div class="header">
        <a href="home.php" class="back-button">← Back</a>
        </div>
    <div class="welcome-container">
        <h1>Admin Panel</h1>
        
        <div class="admin-section">
            <h2>Manage Members</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($members as $member): ?>
                <tr>
                    <td><?php echo htmlspecialchars($member['id']); ?></td>
                    <td><?php echo htmlspecialchars($member['username']); ?></td>
                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                    <td><?php echo htmlspecialchars($member['role']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="edit_member">
                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                            <input type="text" name="username" value="<?php echo htmlspecialchars($member['username']); ?>" required>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($member['email']); ?>" required>
                            <select name="role">
                                <option value="user" <?php echo $member['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo $member['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                            <button type="submit">Edit</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete_member">
                            <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this member?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <h3>Create New Member</h3>
            <form method="post">
                <input type="hidden" name="action" value="create_member">
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <select name="role">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit">Create Member</button>
            </form>
        </div>
        
        <div class="admin-section">
            <h2>Manage Groups</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($groups as $group): ?>
                <tr>
                    <td><?php echo htmlspecialchars($group['id']); ?></td>
                    <td><?php echo htmlspecialchars($group['name']); ?></td>
                    <td><?php echo htmlspecialchars($group['description']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="edit_group">
                            <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
                            <input type="text" name="name" value="<?php echo htmlspecialchars($group['name']); ?>" required>
                            <input type="text" name="description" value="<?php echo htmlspecialchars($group['description']); ?>" required>
                            <button type="submit">Edit</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete_group">
                            <input type="hidden" name="id" value="<?php echo $group['id']; ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this group?');">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <h3>Create New Group</h3>
            <form method="post">
                <input type="hidden" name="action" value="create_group">
                <input type="text" name="name" placeholder="Group Name" required>
                <input type="text" name="description" placeholder="Group Description" required>
                <button type="submit">Create Group</button>
            </form>
        </div>
        
        <div class="admin-section">
            <h2>Pending Join Requests</h2>
            <table>
                <tr>
                    <th>Request ID</th>
                    <th>Group Name</th>
                    <th>Username</th>
                    <th>Actions</th>
                </tr>
                <?php foreach ($joinRequests as $request): ?>
                <tr>
                    <td><?php echo htmlspecialchars($request['id']); ?></td>
                    <td><?php echo htmlspecialchars($request['group_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['username']); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="handle_request">
                            <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="status" value="approved">
                            <button type="submit">Approve</button>
                        </form>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="handle_request">
                            <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                            <input type="hidden" name="status" value="denied">
                            <button type="submit">Deny</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>

