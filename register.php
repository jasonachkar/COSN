<?php
ob_start(); // Start output buffering
session_start();
include 'database.php'; // Include the database connection
// Check if 'REQUEST_METHOD' exists in the server array and if it's 'POST'
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $email = $_POST['email'];

    // Check if username or email already exists
    $checkQuery = "SELECT * FROM members WHERE username = ? OR email = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();

    if ($checkResult->num_rows > 0) {
        echo "Username or email already exists. Please choose a different one.";
    } else {
        // Insert new user into the database
        $query = "INSERT INTO members (username, password, email) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);

        // Check if statement preparation was successful
        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        $stmt->bind_param("sss", $username, $password, $email);

        // Execute the statement and check for success
        if ($stmt->execute()) {
            // Set session variables for the new user
            $_SESSION['username'] = $username;

            // Redirect to the home page
            header("Location: home.php");
            exit();
        } else {
            echo "Error registering user: " . $stmt->error;
        }
    }
} else {
    echo "Invalid request method or 'REQUEST_METHOD' not set.";
}

ob_end_flush(); // End output buffering
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - COSN</title>
    <link rel="stylesheet" href="styles/styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Create Your COSN Account</h2>
        <form action="register.php" method="POST">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" required>
            
            <label for="password">Password</label>
            <input type="password" name="password" id="password" required>
            
            <label for="email">Email</label>
            <input type="email" name="email" id="email" required>
            
            <button type="submit" class="button">Register</button>
        </form>
        <p>Already have an account? <a href="login.html">Login here</a>.</p>
    </div>
</body>
</html>
