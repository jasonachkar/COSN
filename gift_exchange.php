<?php
session_start();
include 'database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Function to get all groups the user is a member of
function getUserGroups($conn, $user_id) {
    $query = "SELECT g.id, g.name FROM `groups` g
              JOIN group_members gm ON g.id = gm.group_id
              WHERE gm.member_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get all gift exchanges for a group
function getGroupExchanges($conn, $group_id) {
    $query = "SELECT * FROM gift_exchanges WHERE group_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get participant details for an exchange
function getExchangeParticipants($conn, $exchange_id) {
    $query = "SELECT ge.id, m.username, ge.wishlist, ge.recipient_id 
              FROM gift_exchange_participants ge
              JOIN members m ON ge.user_id = m.id
              WHERE ge.exchange_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $exchange_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to safely handle null values in htmlspecialchars
function safeHtmlSpecialChars($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_exchange':
                $group_id = $_POST['group_id'];
                $name = $_POST['name'];
                $budget = $_POST['budget'];
                $deadline = $_POST['deadline'];
                
                $stmt = $conn->prepare("INSERT INTO gift_exchanges (group_id, name, budget, deadline) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isds", $group_id, $name, $budget, $deadline);
                if ($stmt->execute()) {
                    $exchange_id = $stmt->insert_id;
                    
                    // Add the creator as a participant
                    $stmt = $conn->prepare("INSERT INTO gift_exchange_participants (exchange_id, user_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $exchange_id, $user_id);
                    $stmt->execute();
                    header("Location: gift_exchange.php?success=1");
                    exit();
                }
                break;
                
            case 'join_exchange':
                $exchange_id = $_POST['exchange_id'];
                $stmt = $conn->prepare("INSERT INTO gift_exchange_participants (exchange_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $exchange_id, $user_id);
                if ($stmt->execute()) {
                    header("Location: gift_exchange.php?success=2");
                    exit();
                }
                break;
                
            case 'update_wishlist':
                $participant_id = $_POST['participant_id'];
                $wishlist = $_POST['wishlist'];
            
                // Debug statements
                echo "Updating wishlist for participant ID: $participant_id<br>";
                echo "New wishlist: " . htmlspecialchars($wishlist) . "<br>";
            
                $stmt = $conn->prepare("UPDATE gift_exchange_participants SET wishlist = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sii", $wishlist, $participant_id, $user_id);
            
                if ($stmt->execute()) {
                    echo "Wishlist updated successfully.<br>";
                    header("Location: gift_exchange.php?success=3");
                    exit();
                } else {
                    echo "Error updating wishlist: " . $stmt->error . "<br>";
                }
                break;
                
            case 'assign_recipients':
                $exchange_id = $_POST['exchange_id'];
                $participants = getExchangeParticipants($conn, $exchange_id);
                
                if (count($participants) >= 2) {
                    $participant_ids = array_column($participants, 'id');
                    shuffle($participant_ids);
                    $count = count($participant_ids);
                    
                    $conn->begin_transaction();
                    try {
                        for ($i = 0; $i < $count; $i++) {
                            $giver_id = $participant_ids[$i];
                            $recipient_id = $participant_ids[($i + 1) % $count];
                            $stmt = $conn->prepare("UPDATE gift_exchange_participants SET recipient_id = ? WHERE id = ?");
                            $stmt->bind_param("ii", $recipient_id, $giver_id);
                            $stmt->execute();
                        }
                        $conn->commit();
                        header("Location: gift_exchange.php?success=4");
                        exit();
                    } catch (Exception $e) {
                        $conn->rollback();
                        header("Location: gift_exchange.php?error=1");
                        exit();
                    }
                }
                break;
        }
    }
}

$user_groups = getUserGroups($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gift Exchange - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .exchange-section {
            margin-bottom: 2rem;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .exchange-list {
            list-style-type: none;
            padding: 0;
        }
        .exchange-item {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .wishlist-form {
            margin-top: 1rem;
        }
        .wishlist-form textarea {
            width: 100%;
            min-height: 100px;
            margin-bottom: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background-color: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
<div class="header">
        <a href="home.php" class="back-button">← Back</a>
        </div>
    <div class="welcome-container">
        <h1>Gift Exchange</h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <?php
                $success_messages = [
                    1 => 'Gift exchange created successfully!',
                    2 => 'You have joined the gift exchange!',
                    3 => 'Wishlist updated successfully!',
                    4 => 'Recipients assigned successfully!'
                ];
                echo safeHtmlSpecialChars($success_messages[$_GET['success']] ?? 'Operation completed successfully!');
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php
                $error_messages = [
                    1 => 'Error assigning recipients. Please try again.'
                ];
                echo safeHtmlSpecialChars($error_messages[$_GET['error']] ?? 'An error occurred. Please try again.');
                ?>
            </div>
        <?php endif; ?>
        
        <div class="exchange-section">
            <h2>Create New Gift Exchange</h2>
            <form method="post">
                <input type="hidden" name="action" value="create_exchange">
                <div class="form-group">
                    <label for="group_id">Select a group:</label>
                    <select name="group_id" id="group_id" required>
                        <option value="">Select a group</option>
                        <?php foreach ($user_groups as $group): ?>
                            <option value="<?php echo $group['id']; ?>">
                                <?php echo safeHtmlSpecialChars($group['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="name">Exchange Name:</label>
                    <input type="text" name="name" id="name" required>
                </div>
                <div class="form-group">
                    <label for="budget">Budget:</label>
                    <input type="number" name="budget" id="budget" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="deadline">Deadline:</label>
                    <input type="date" name="deadline" id="deadline" required>
                </div>
                <button type="submit" class="btn">Create Exchange</button>
            </form>
        </div>
        
        <div class="exchange-section">
            <h2>Your Gift Exchanges</h2>
            <?php foreach ($user_groups as $group): ?>
                <h3><?php echo safeHtmlSpecialChars($group['name']); ?></h3>
                <?php 
                $exchanges = getGroupExchanges($conn, $group['id']);
                if (empty($exchanges)):
                ?>
                    <p>No gift exchanges in this group.</p>
                <?php else: ?>
                    <ul class="exchange-list">
                        <?php foreach ($exchanges as $exchange): ?>
                            <li class="exchange-item">
                                <h4><?php echo safeHtmlSpecialChars($exchange['name']); ?></h4>
                                <p>Budget: $<?php echo number_format($exchange['budget'], 2); ?></p>
                                <p>Deadline: <?php echo safeHtmlSpecialChars($exchange['deadline']); ?></p>
                                <?php 
                                $participants = getExchangeParticipants($conn, $exchange['id']);
                                $is_participant = false;
                                $user_participant = null;
                                foreach ($participants as $participant) {
                                    if ($participant['username'] === $username) {
                                        $is_participant = true;
                                        $user_participant = $participant;
                                        break;
                                    }
                                }
                                if ($is_participant):
                                ?>
                                    <p>You are participating in this exchange.</p>
                                    <?php if ($user_participant['recipient_id']): ?>
                                        <?php
                                        $recipient = null;
                                        foreach ($participants as $p) {
                                            if ($p['id'] === $user_participant['recipient_id']) {
                                                $recipient = $p;
                                                break;
                                            }
                                        }
                                        if ($recipient):
                                        ?>
                                            <p>Your recipient: <?php echo safeHtmlSpecialChars($recipient['username']); ?></p>
                                            <p>Their wishlist: <?php echo safeHtmlSpecialChars($recipient['wishlist'] ?? 'No wishlist provided yet.'); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <form method="post" class="wishlist-form">
                                        <input type="hidden" name="action" value="update_wishlist">
                                        <input type="hidden" name="participant_id" value="<?php echo $user_participant['id']; ?>">
                                        <textarea name="wishlist" placeholder="Enter your wishlist here..."><?php echo safeHtmlSpecialChars($user_participant['wishlist'] ?? ''); ?></textarea>
                                        <button type="submit" class="btn">Update Wishlist</button>
                                    </form>
                                <?php else: ?>
                                    <form method="post">
                                        <input type="hidden" name="action" value="join_exchange">
                                        <input type="hidden" name="exchange_id" value="<?php echo $exchange['id']; ?>">
                                        <button type="submit" class="btn">Join Exchange</button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($is_participant && $user_participant['id'] === $participants[0]['id'] && !$user_participant['recipient_id']): ?>
                                    <form method="post" style="margin-top: 10px;">
                                        <input type="hidden" name="action" value="assign_recipients">
                                        <input type="hidden" name="exchange_id" value="<?php echo $exchange['id']; ?>">
                                        <button type="submit" class="btn">Assign Recipients</button>
                                    </form>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

