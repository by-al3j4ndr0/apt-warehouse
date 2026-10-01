<?php
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'apt_admin';
$password = getenv('DB_PASSWORD') ?: 'DCMU7323**';
$dbname = getenv('DB_NAME') ?: 'apt_warehouse';

if ($username === false || $password === false) {
    error_log('Database credentials are not configured.');
    http_response_code(500);
    exit('Database configuration error.');
}

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    error_log('Database connection failed.');
    http_response_code(500);
    exit('Database connection error.');
}

$conn->set_charset('utf8mb4');
?>