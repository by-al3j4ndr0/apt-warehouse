<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
require_once __DIR__ . '/../db_connect.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$name = trim((string) ($_GET['manifest'] ?? ''));
if ($name === '' || mb_strlen($name) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'Debe indicar un manifiesto válido.']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT s.hbl, s.manifest, s.status, s.origen,
               c.name AS recipient, c.city AS municipality, c.state AS province
        FROM shipments s
        LEFT JOIN clients c ON c.ci = s.ci
        WHERE s.manifest = ?
          AND s.status IN ('at_customs', 'warehouse')
        ORDER BY s.hbl
    ");
    $stmt->bind_param('s', $name);
    $stmt->execute();
    $result = $stmt->get_result();
    $shipments = [];
    $pending = 0;
    $entered = 0;
    while ($row = $result->fetch_assoc()) {
        $status = (string) $row['status'];
        if ($status === 'at_customs') $pending++;
        if ($status === 'warehouse') $entered++;
        $shipments[] = [
            'code' => (string) $row['hbl'],
            'origin' => (int) ($row['origen'] ?? 0),
            'recipient' => (string) ($row['recipient'] ?? ''),
            'municipality' => (string) ($row['municipality'] ?? ''),
            'province' => (string) ($row['province'] ?? ''),
            'status' => $status
        ];
    }
    $stmt->close();

    echo json_encode([
        'ok' => true,
        'manifest' => $name,
        'total' => count($shipments),
        'pending' => $pending,
        'entered' => $entered,
        'shipments' => $shipments
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Scanner manifest detail error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron cargar los envíos del manifiesto seleccionado.']);
}
?>