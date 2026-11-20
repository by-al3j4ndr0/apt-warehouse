<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
requireCsrf();
header('Content-Type: application/json; charset=utf-8');

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
    echo json_encode(['error' => 'Lote inválido o demasiado grande']);
    exit;
}

require_once __DIR__ . '/../db_connect.php';
$username = (string) ($_SESSION['username'] ?? 'unknown');
$allowedClientResults = ['FOUND', 'NOT_FOUND', 'DUPLICATE'];
$results = [];
$saved = 0;
$entered = 0;
$duplicates = 0;
$notFound = 0;

try {
    $conn->begin_transaction();

    $existingEvent = $conn->prepare('SELECT result FROM scanner_events WHERE event_id = ? LIMIT 1');
    $shipmentStmt = $conn->prepare('SELECT hbl, status, ci, manifest FROM shipments WHERE hbl = ? LIMIT 1 FOR UPDATE');
    $updateShipment = $conn->prepare("UPDATE shipments SET status = 'warehouse' WHERE hbl = ? AND status = 'at_customs'");
    $insertEvent = $conn->prepare('INSERT INTO scanner_events
        (event_id, manifest_name, shipment_code, result, recipient, municipality, province, scanned_at, username)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');

    foreach ($records as $record) {
        if (!is_array($record)) {
            throw new RuntimeException('Registro de escaneo inválido');
        }

        $eventId = (string) ($record['id'] ?? '');
        $manifest = trim((string) ($record['manifest'] ?? 'Entrada de almacén'));
        $code = trim((string) ($record['code'] ?? ''));
        $clientResult = (string) ($record['status'] ?? 'FOUND');\n        $source = (string) ($record['source'] ?? 'csv');
        $recipient = trim((string) ($record['recipient'] ?? ''));
        $municipality = trim((string) ($record['municipality'] ?? ''));
        $province = trim((string) ($record['province'] ?? ''));
        $timestamp = (string) ($record['timestamp'] ?? '');

        if (!preg_match('/^[A-Za-z0-9-]{1,80}$/', $eventId)
            || $manifest === '' || mb_strlen($manifest) > 255
            || $code === '' || mb_strlen($code) > 180
            || !in_array($clientResult, $allowedClientResults, true)\n            || !in_array($source, ['csv', 'system'], true)
            || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,3})?Z$/', $timestamp)) {
            throw new RuntimeException('Datos de escaneo no válidos');
        }

        // An event already accepted is idempotent: never apply its state transition twice.
        $existingEvent->bind_param('s', $eventId);
        $existingEvent->execute();
        $existing = $existingEvent->get_result()->fetch_assoc();
        if ($existing) {
            $results[$eventId] = $existing['result'];
            if ($existing['result'] === 'FOUND') $entered++;
            elseif ($existing['result'] === 'DUPLICATE') $duplicates++;
            else $notFound++;
            continue;
        }

        $shipmentStmt->bind_param('s', $code);
        $shipmentStmt->execute();
        $shipment = $shipmentStmt->get_result()->fetch_assoc();

        // The client-side CSV is only a convenience. The database is authoritative.
        if (!$shipment) {
            $actualResult = 'NOT_FOUND';
        } elseif ($source === 'system' && (string) $shipment['manifest'] !== $manifest) {
            // A scanner session may only register codes belonging to its selected manifest.
            $actualResult = 'NOT_FOUND';
        } elseif ($shipment['status'] === 'at_customs') {
            $updateShipment->bind_param('s', $code);
            $updateShipment->execute();
            if ($updateShipment->affected_rows !== 1) {
                // A concurrent scan may have changed the status; re-read under lock.
                $shipmentStmt->bind_param('s', $code);
                $shipmentStmt->execute();
                $current = $shipmentStmt->get_result()->fetch_assoc();
                $actualResult = ($current && $current['status'] === 'warehouse') ? 'DUPLICATE' : 'NOT_FOUND';
            } else {
                $actualResult = 'FOUND';
            }
        } elseif ($shipment['status'] === 'warehouse') {
            $actualResult = 'DUPLICATE';
        } else {
            // Existing shipment, but it is not currently awaiting warehouse entry.
            $actualResult = 'NOT_FOUND';
        }

        $scannedAt = (new DateTimeImmutable($timestamp))->format('Y-m-d H:i:s');
        $insertEvent->bind_param(
            'sssssssss',
            $eventId, $manifest, $code, $actualResult,
            $recipient, $municipality, $province, $scannedAt, $username
        );
        $insertEvent->execute();

        $results[$eventId] = $actualResult;
        $saved++;
        if ($actualResult === 'FOUND') $entered++;
        elseif ($actualResult === 'DUPLICATE') $duplicates++;
        else $notFound++;
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
        'not_found' => $notFound,
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    $conn->rollback();
    error_log('Scanner sync error: ' . $error->getMessage());
    http_response_code(400);
    echo json_encode(['error' => 'No se pudieron procesar los escaneos. Verifique los códigos y vuelva a sincronizar.']);
}
?>