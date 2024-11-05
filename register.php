<?php
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $email = $_POST['email'];

    $query = "INSERT INTO members (username, password, email) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $password, $email);
    
    if ($stmt->execute()) {
        header("Location: login.html");
    } else {
        echo "<p>Error registering user</p>";
    }
}
?>
