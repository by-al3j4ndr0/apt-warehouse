<?php
require_once __DIR__ . '/../auth.php';
requireApiLogin(true);
require_once __DIR__ . '/../db_connect.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $uuid = trim((string)($_GET['uuid'] ?? ''));
    if ($uuid !== '') {
        $stmt = $conn->prepare('SELECT t.*, (SELECT COUNT(*) FROM warehouse_transfer_items i WHERE i.transfer_id=t.id AND i.received_at IS NOT NULL) AS received_count FROM warehouse_transfers t WHERE t.transfer_uuid = ? LIMIT 1');
        $stmt->bind_param('s', $uuid); $stmt->execute(); $transfer = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$transfer) { http_response_code(404); echo json_encode(['error' => 'Transferencia no encontrada.']); exit; }
        $items = $conn->prepare('SELECT i.shipment_hbl AS code, i.manifest_name AS manifest, i.received_at, i.received_by, s.status FROM warehouse_transfer_items i LEFT JOIN shipments s ON s.hbl=i.shipment_hbl WHERE i.transfer_id=? ORDER BY i.shipment_hbl');
        $id=(int)$transfer['id']; $items->bind_param('i',$id); $items->execute(); $rows=$items->get_result()->fetch_all(MYSQLI_ASSOC); $items->close();
        echo json_encode(['ok'=>true,'transfer'=>$transfer,'shipments'=>$rows],JSON_UNESCAPED_UNICODE); exit;
    }
    $search = trim((string)($_GET['search'] ?? ''));
    $stmt = $conn->prepare('SELECT t.*, (SELECT COUNT(*) FROM warehouse_transfer_items i WHERE i.transfer_id=t.id AND i.received_at IS NOT NULL) AS received_count FROM warehouse_transfers t WHERE (? = "" OR t.transfer_uuid LIKE CONCAT("%", ?, "%") OR t.destination_name LIKE CONCAT("%", ?, "%") OR t.username LIKE CONCAT("%", ?, "%") OR t.driver LIKE CONCAT("%", ?, "%") OR t.vehicle_plate LIKE CONCAT("%", ?, "%")) ORDER BY t.transfer_date DESC LIMIT 300');
    $stmt->bind_param('ssssss',$search,$search,$search,$search,$search,$search); $stmt->execute(); $rows=$stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    echo json_encode(['ok'=>true,'transfers'=>$rows],JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Warehouse transfer list error: '.$error->getMessage());
    http_response_code(500); echo json_encode(['error'=>'No se pudo consultar el historial de transferencias.']);
}
