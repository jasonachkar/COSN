<?php
include 'database.php'; // Ensure this file contains the correct database connection

if ($conn->connect_error) {
    echo (json_encode(["status" => "failed", "message" => "Connection failed: " . $conn->connect_error]));
} else {
    echo json_encode(["status" => "success", "message" => "Database connection successful!"]);
}
$conn->close();
