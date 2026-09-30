<?php
require_once __DIR__ . '/auth.php';
requireApiLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid route ID');
}

// Export implementation is not available yet. Do not expose database internals.
http_response_code(501);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Route export is not implemented']);
exit;
?>