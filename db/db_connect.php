<?php
$servername = "localhost";
$username = "apt_admin";
$password = "DCMU7323**";
$dbname = "apt_warehouse";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>