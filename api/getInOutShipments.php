<?php
require_once __DIR__ . '/auth.php';
requireApiLogin(true);
require_once __DIR__ . '/db_connect.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $stmt = $conn->prepare("SELECT s.hbl, s.manifest, s.origen, c.name AS recipient,
                                   c.city AS municipality, c.state AS province
                            FROM shipments s
                            LEFT JOIN clients c ON c.ci = s.ci
                            WHERE s.status = 'at_customs'
                            ORDER BY s.manifest, s.hbl");
    $stmt->execute();
    $result = $stmt->get_result();
    $shipments = [];
    while ($row = $result->fetch_assoc()) {
        $shipments[] = [
            'code' => (string) $row['hbl'],
            'manifest' => (string) $row['manifest'],
            'recipient' => (string) ($row['recipient'] ?? ''),
            'municipality' => (string) ($row['municipality'] ?? ''),
            'province' => (string) ($row['province'] ?? '')
        ];
    }
    $stmt->close();
    echo json_encode(['ok' => true, 'total' => count($shipments), 'shipments' => $shipments], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('In/out shipments list error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo consultar la mercancía pendiente de entrada.']);
}
?>