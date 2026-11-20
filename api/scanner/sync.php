<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
requireCsrf();
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Allow: POST');
    echo json_encode(['error' => 'Método no permitido']); exit;
}
$payload = json_decode(file_get_contents('php://input'), true);
$records = $payload['records'] ?? null;
if (!is_array($records) || count($records) > 500) {
    http_response_code(400); echo json_encode(['error' => 'Lote inválido o demasiado grande']); exit;
}
require_once __DIR__ . '/../db_connect.php';
$conn->begin_transaction();
try {
    $stmt = $conn->prepare('INSERT INTO scanner_events
      (event_id, manifest_name, shipment_code, result, recipient, municipality, province, scanned_at, username)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE event_id = event_id');
    $saved = 0; $allowed = ['FOUND', 'NOT_FOUND', 'DUPLICATE'];
    foreach ($records as $record) {
        if (!is_array($record)) throw new RuntimeException('Registro inválido');
        $eventId=(string)($record['id']??''); $manifest=trim((string)($record['manifest']??''));
        $code=trim((string)($record['code']??'')); $result=(string)($record['status']??'');
        $recipient=trim((string)($record['recipient']??'')); $municipality=trim((string)($record['municipality']??''));
        $province=trim((string)($record['province']??'')); $timestamp=(string)($record['timestamp']??'');
        if (!preg_match('/^[a-zA-Z0-9-]{1,80}$/',$eventId) || $manifest==='' || mb_strlen($manifest)>120 ||
            $code==='' || mb_strlen($code)>180 || !in_array($result,$allowed,true) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?Z$/',$timestamp)) {
            throw new RuntimeException('Datos de escaneo no válidos');
        }
        $scannedAt=(new DateTimeImmutable($timestamp))->format('Y-m-d H:i:s');
        $username=(string)($_SESSION['username']??'unknown');
        $stmt->bind_param('sssssssss',$eventId,$manifest,$code,$result,$recipient,$municipality,$province,$scannedAt,$username);
        $stmt->execute(); if($stmt->affected_rows===1)$saved++;
    }
    $stmt->close(); $conn->commit(); echo json_encode(['ok'=>true,'saved'=>$saved]);
} catch (Throwable $error) {
    $conn->rollback(); error_log('Scanner sync error: '.$error->getMessage());
    http_response_code(400); echo json_encode(['error'=>'No se pudieron guardar los registros enviados']);
}
?>