<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
requireCsrf();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); header('Allow: POST'); echo json_encode(['error' => 'Método no permitido']); exit;
}
require_once __DIR__ . '/../db_connect.php';
$data = json_decode(file_get_contents('php://input'), true) ?: [];
$direction = (string)($data['direction'] ?? '');
$code = trim((string)($data['code'] ?? ''));
$username = (string)($_SESSION['username'] ?? 'unknown');
if ($code === '' || mb_strlen($code) > 180 || !in_array($direction, ['outbound', 'inbound'], true)) {
    http_response_code(400); echo json_encode(['error' => 'Datos de escaneo inválidos.']); exit;
}
try {
    $conn->begin_transaction();
    $shipmentStmt = $conn->prepare('SELECT hbl, status, manifest FROM shipments WHERE hbl = ? LIMIT 1 FOR UPDATE');
    $shipmentStmt->bind_param('s', $code); $shipmentStmt->execute();
    $shipment = $shipmentStmt->get_result()->fetch_assoc();
    if (!$shipment) throw new DomainException('NOT_FOUND');
    if ($direction === 'outbound') {
        $uuid = strtolower((string)($data['transfer_uuid'] ?? ''));
        $destinationId = (int)($data['destination_id'] ?? 0);
        $driver = trim((string)($data['driver'] ?? ''));
        $plate = trim((string)($data['vehicle_plate'] ?? ''));
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)
            || $destinationId < 1 || $driver === '' || mb_strlen($driver) > 180
            || $plate === '' || mb_strlen($plate) > 40) throw new DomainException('INVALID_TRANSFER');
        if ($shipment['status'] !== 'warehouse') throw new DomainException('INVALID_STATUS');
        $loc = $conn->prepare('SELECT name FROM warehouse_locations WHERE id = ? AND is_active = 1 AND name <> 'Almacén Arceo & Padrón'');
        $loc->bind_param('i', $destinationId); $loc->execute();
        $destination = $loc->get_result()->fetch_assoc(); $loc->close();
        if (!$destination) throw new DomainException('INVALID_DESTINATION');
        $find = $conn->prepare('SELECT id, destination_id, driver, vehicle_plate FROM warehouse_transfers WHERE transfer_uuid = ? FOR UPDATE');
        $find->bind_param('s', $uuid); $find->execute(); $transfer = $find->get_result()->fetch_assoc(); $find->close();
        if (!$transfer) {
            $origin = 'Almacén Arceo & Padrón'; $date = gmdate('Y-m-d H:i:s'); $total = 0;
            $ins = $conn->prepare('INSERT INTO warehouse_transfers (transfer_uuid, transfer_date, username, origin_name, destination_id, destination_name, driver, vehicle_plate, total_shipments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $ins->bind_param('ssssisssi', $uuid, $date, $username, $origin, $destinationId, $destination['name'], $driver, $plate, $total);
            $ins->execute(); $transferId = $conn->insert_id; $ins->close();
        } else {
            $transferId = (int)$transfer['id'];
            if ((int)$transfer['destination_id'] !== $destinationId || $transfer['driver'] !== $driver || $transfer['vehicle_plate'] !== $plate) throw new DomainException('TRANSFER_MISMATCH');
        }
        $item = $conn->prepare('INSERT INTO warehouse_transfer_items (transfer_id, shipment_hbl, manifest_name) VALUES (?, ?, ?)');
        $manifest = (string)($shipment['manifest'] ?? '');
        $item->bind_param('iss', $transferId, $code, $manifest); $item->execute(); $item->close();
        $upd = $conn->prepare("UPDATE shipments SET status = 'in_transfer' WHERE hbl = ? AND status = 'warehouse'");
        $upd->bind_param('s', $code); $upd->execute();
        if ($upd->affected_rows !== 1) throw new DomainException('INVALID_STATUS');
        $upd->close();
        $count = $conn->prepare('UPDATE warehouse_transfers SET total_shipments = total_shipments + 1 WHERE id = ?');
        $count->bind_param('i', $transferId); $count->execute(); $count->close();
        $conn->commit();
        echo json_encode(['ok' => true, 'result' => 'OUTBOUND_REGISTERED', 'transfer_uuid' => $uuid, 'code' => $code]); exit;
    }

    $uuid = strtolower((string)($data['transfer_uuid'] ?? ''));
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid)) throw new DomainException('INVALID_TRANSFER');
    $find = $conn->prepare('SELECT t.id, t.destination_name FROM warehouse_transfers t WHERE t.transfer_uuid = ? LIMIT 1 FOR UPDATE');
    $find->bind_param('s', $uuid); $find->execute(); $transfer = $find->get_result()->fetch_assoc(); $find->close();
    if (!$transfer) throw new DomainException('TRANSFER_NOT_FOUND');
    if ($shipment['status'] !== 'in_transfer') throw new DomainException('INVALID_STATUS');
    $item = $conn->prepare('SELECT id, received_at FROM warehouse_transfer_items WHERE transfer_id = ? AND shipment_hbl = ? FOR UPDATE');
    $transferId = (int)$transfer['id']; $item->bind_param('is', $transferId, $code); $item->execute();
    $itemRow = $item->get_result()->fetch_assoc(); $item->close();
    if (!$itemRow) throw new DomainException('NOT_IN_TRANSFER');
    if ($itemRow['received_at'] !== null) throw new DomainException('ALREADY_RECEIVED');
    $upd = $conn->prepare("UPDATE shipments SET status = 'warehouse' WHERE hbl = ? AND status = 'in_transfer'");
    $upd->bind_param('s', $code); $upd->execute();
    if ($upd->affected_rows !== 1) throw new DomainException('INVALID_STATUS');
    $upd->close();
    $receivedAt = gmdate('Y-m-d H:i:s');
    $mark = $conn->prepare('UPDATE warehouse_transfer_items SET received_at = ?, received_by = ? WHERE id = ?');
    $itemId = (int)$itemRow['id']; $mark->bind_param('ssi', $receivedAt, $username, $itemId); $mark->execute(); $mark->close();
    $conn->commit();
    echo json_encode(['ok' => true, 'result' => 'INBOUND_REGISTERED', 'code' => $code, 'destination' => $transfer['destination_name']]); exit;
} catch (DomainException $error) {
    $conn->rollback();
    $messages = [
        'NOT_FOUND' => 'El envío no existe.', 'INVALID_STATUS' => 'El envío no está en el estado requerido para esta operación.',
        'INVALID_TRANSFER' => 'La transferencia o sus datos no son válidos.', 'INVALID_DESTINATION' => 'El almacén de destino no está disponible.',
        'TRANSFER_MISMATCH' => 'Los datos no coinciden con la transferencia iniciada.', 'TRANSFER_NOT_FOUND' => 'No existe la transferencia indicada.',
        'NOT_IN_TRANSFER' => 'El envío no pertenece a esta transferencia.', 'ALREADY_RECEIVED' => 'Este envío ya fue recibido.'
    ];
    http_response_code(409); echo json_encode(['ok' => false, 'result' => $error->getMessage(), 'error' => $messages[$error->getMessage()] ?? 'Operación no válida.']);
} catch (Throwable $error) {
    $conn->rollback(); error_log('Warehouse transfer scan error: ' . $error->getMessage());
    http_response_code(500); echo json_encode(['error' => 'No se pudo registrar el movimiento.']);
}
