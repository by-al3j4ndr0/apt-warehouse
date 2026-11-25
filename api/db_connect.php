<?php
$servername = getenv('DB_HOST');
$username = getenv('DB_USER');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');

$requiredConfig = [
    'DB_HOST' => $servername,
    'DB_NAME' => $dbname,
    'DB_USER' => $username,
    'DB_PASSWORD' => $password,
];

foreach ($requiredConfig as $key => $value) {
    if ($value === false || trim((string) $value) === '') {
        error_log('Required database environment variable is missing: ' . $key);
        http_response_code(500);
        exit('Database configuration error.');
    }
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('Database connection error.');
}
?>