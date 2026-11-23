<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
requireCsrf();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$records = $payload['records'] ?? null;
if (!is_array($records) || count($records) < 1 || count($records) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'El lote debe contener entre 1 y 500 códigos.']);
    exit;
}

require_once __DIR__ . '/../db_connect.php';
$username = (string) ($_SESSION['username'] ?? 'unknown');
$results = [];
$entered = 0;
$duplicates = 0;
$invalidStatus = 0;
$notFound = 0;
$saved = 0;

try {
    $conn->begin_transaction();
    $existingEvent = $conn->prepare('SELECT result, manifest_name FROM scanner_events WHERE event_id = ? LIMIT 1');
    $shipmentStmt = $conn->prepare("SELECT s.hbl, s.status, s.manifest, c.name AS recipient,
        c.city AS municipality, c.state AS province
        FROM shipments s LEFT JOIN clients c ON c.ci = s.ci
        WHERE s.hbl = ? LIMIT 1 FOR UPDATE");
    $updateShipment = $conn->prepare("UPDATE shipments SET status = 'warehouse' WHERE hbl = ? AND status = 'at_customs'");
    $insertEvent = $conn->prepare('INSERT INTO scanner_events
        (event_id, manifest_name, shipment_code, result, recipient, municipality, province, scanned_at, username)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

    foreach ($records as $record) {
        if (!is_array($record)) {
            throw new RuntimeException('Registro inválido.');
        }
        $eventId = (string) ($record['id'] ?? '');
        $code = trim((string) ($record['code'] ?? ''));
        if (!preg_match('/^[A-Za-z0-9-]{1,80}$/', $eventId)
            || $code === '' || mb_strlen($code) > 180) {
            throw new RuntimeException('Cada registro debe contener un identificador y un código HBL válido.');
        }

        $existingEvent->bind_param('s', $eventId);
        $existingEvent->execute();
        $prior = $existingEvent->get_result()->fetch_assoc();
        if ($prior) {
            $actualResult = (string) $prior['result'];
            $manifest = (string) $prior['manifest_name'];
            $shipmentStatus = $actualResult === 'FOUND' ? 'warehouse' : '';
            $savedResult = false;
        } else {
            $shipmentStmt->bind_param('s', $code);
            $shipmentStmt->execute();
            $shipment = $shipmentStmt->get_result()->fetch_assoc();

            if (!$shipment) {
                $manifest = 'SIN_MANIFIESTO';
                $recipient = $municipality = $province = '';
                $shipmentStatus = 'NOT_FOUND';
                $actualResult = 'NOT_FOUND';
            } else {
                $manifest = (string) $shipment['manifest'];
                $recipient = (string) ($shipment['recipient'] ?? '');
                $municipality = (string) ($shipment['municipality'] ?? '');
                $province = (string) ($shipment['province'] ?? '');
                $shipmentStatus = (string) $shipment['status'];

                if ($shipmentStatus === 'at_customs') {
                    $updateShipment->bind_param('s', $code);
                    $updateShipment->execute();
                    if ($updateShipment->affected_rows === 1) {
                        $actualResult = 'FOUND';
                        $shipmentStatus = 'warehouse';
                    } else {
                        $shipmentStmt->bind_param('s', $code);
                        $shipmentStmt->execute();
                        $current = $shipmentStmt->get_result()->fetch_assoc();
                        $shipmentStatus = (string) ($current['status'] ?? 'unknown');
                        $actualResult = $shipmentStatus === 'warehouse' ? 'DUPLICATE' : 'INVALID_STATUS';
                    }
                } elseif ($shipmentStatus === 'warehouse') {
                    $actualResult = 'DUPLICATE';
                } else {
                    $actualResult = 'INVALID_STATUS';
                }
            }

            $scannedAt = gmdate('Y-m-d H:i:s');
            $insertEvent->bind_param(
                'sssssssss',
                $eventId, $manifest, $code, $actualResult,
                $recipient, $municipality, $province, $scannedAt, $username
            );
            $insertEvent->execute();
            $saved++;
        }

        if ($actualResult === 'FOUND') {
            if (!$prior) $entered++;
        } elseif ($actualResult === 'DUPLICATE') {
            $duplicates++;
        } elseif ($actualResult === 'INVALID_STATUS') {
            $invalidStatus++;
        } else {
            $notFound++;
        }

        $results[$eventId] = [
            'code' => $code,
            'manifest' => $manifest,
            'result' => $actualResult,
            'shipment_status' => $shipmentStatus
        ];
    }

    $existingEvent->close();
    $shipmentStmt->close();
    $updateShipment->close();
    $insertEvent->close();
    $conn->commit();

    echo json_encode([
        'ok' => true,
        'saved' => $saved,
        'entered' => $entered,
        'duplicates' => $duplicates,
        'invalid_status' => $invalidStatus,
        'not_found' => $notFound,
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Bulk scanner sync error: ' . $error->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'No se pudo procesar el lote. No se aplicaron sus cambios. Verifique los códigos y vuelva a intentarlo.']);
}
?>