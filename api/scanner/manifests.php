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

try {
    $stmt = $conn->prepare("
        SELECT manifest,
               SUM(CASE WHEN status = 'at_customs' THEN 1 ELSE 0 END) AS pending,
               SUM(CASE WHEN status = 'warehouse' THEN 1 ELSE 0 END) AS entered
        FROM shipments
        WHERE status IN ('at_customs', 'warehouse')
          AND manifest IS NOT NULL
          AND manifest <> ''
        GROUP BY manifest
        ORDER BY manifest DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'manifest' => (string) $row['manifest'],
            'pending' => (int) $row['pending'],
            'entered' => (int) $row['entered']
        ];
    }
    $stmt->close();
    echo json_encode(['ok' => true, 'manifests' => $items], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Scanner manifests list error: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron consultar los manifiestos disponibles.']);
}
?>