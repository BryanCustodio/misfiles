<?php
// db_connect.php

$servername = "localhost";
$username = "root";
$password = ""; // Replace with your actual password if set
$database = "file_system"; // Replace with your actual database name

// Create a new MySQLi connection
$conn = new mysqli($servername, $username, $password, $database);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
