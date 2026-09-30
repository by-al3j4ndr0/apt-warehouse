<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';

requireLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
requireCsrf();

$hbl = trim((string)($_POST['shipment_hbl'] ?? ''));
$ci = trim((string)($_POST['shipment_owner_ci'] ?? ''));
$origen = filter_input(INPUT_POST, 'shipment_origen', FILTER_VALIDATE_INT);
$weight = trim((string)($_POST['shipment_weight'] ?? ''));
$tariff = trim((string)($_POST['shipment_tariff'] ?? ''));
$manifest = trim((string)($_POST['shipment_manifest'] ?? ''));
$status = trim((string)($_POST['shipment_status'] ?? ''));
$routeId = filter_input(INPUT_POST, 'shipment_route_id', FILTER_VALIDATE_INT);
$description = trim((string)($_POST['shipment_description'] ?? ''));

$allowedStatuses = ['warehouse', 'draft', 'delivering', 'finished', 'detained'];

if ($hbl === '' || !preg_match('/^[A-Za-z0-9-]+$/', $hbl)
    || $ci === '' || $origen === false || $origen === null
    || $weight === '' || $tariff === '' || $manifest === ''
    || !in_array($status, $allowedStatuses, true)
    || $routeId === false || $routeId === null) {
    http_response_code(400);
    exit('Invalid shipment data');
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("SELECT ci, origen, status, route_id FROM shipments WHERE hbl = ? FOR UPDATE");
    $stmt->bind_param('s', $hbl);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$current) {
        throw new Exception('Shipment not found');
    }

    $transitions = [
        'warehouse' => ['warehouse', 'draft', 'delivering', 'detained'],
        'draft' => ['draft', 'warehouse', 'delivering', 'detained'],
        'delivering' => ['delivering', 'finished', 'detained'],
        'finished' => ['finished'],
        'detained' => ['detained', 'warehouse', 'draft', 'delivering'],
    ];

    if (!in_array($status, $transitions[$current['status']] ?? [], true)) {
        throw new Exception('Invalid shipment status transition');
    }

    if ($status === 'warehouse' && $routeId !== 0) {
        throw new Exception('Warehouse shipments cannot have a route');
    }

    if ($status !== 'warehouse' && $routeId === 0) {
        throw new Exception('Non-warehouse shipments must have a route');
    }

    if ($routeId > 0) {
        $routeStmt = $conn->prepare("SELECT origen, status FROM delivery WHERE id = ? FOR SHARE");
        $routeStmt->bind_param('i', $routeId);
        $routeStmt->execute();
        $route = $routeStmt->get_result()->fetch_assoc();
        $routeStmt->close();

        if (!$route || (int)$route['origen'] !== (int)$origen) {
            throw new Exception('Route does not belong to the shipment origin');
        }

        if ($status === 'finished' && $route['status'] !== 'finished') {
            throw new Exception('Shipment cannot be finished before its route is finished');
        }

        if ($status === 'delivering' && !in_array($route['status'], ['delivering', 'finished'], true)) {
            throw new Exception('Shipment cannot be delivering on a draft route');
        }
    }

    $clientStmt = $conn->prepare("SELECT 1 FROM clients WHERE ci = ? LIMIT 1");
    $clientStmt->bind_param('s', $ci);
    $clientStmt->execute();
    if (!$clientStmt->get_result()->fetch_row()) {
        throw new Exception('Client not found');
    }
    $clientStmt->close();

    $update = $conn->prepare(
        "UPDATE shipments
         SET ci = ?, origen = ?, weight = ?, tariff = ?, manifest = ?, status = ?, route_id = ?, description = ?
         WHERE hbl = ?"
    );
    $update->bind_param(
        'sissssiss',
        $ci, $origen, $weight, $tariff, $manifest, $status, $routeId, $description, $hbl
    );
    $update->execute();
    $update->close();

    $conn->commit();
    header('Location: ../search/shipmentsDetails.php?hbl=' . urlencode($hbl));
    exit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Error updating shipment: ' . $e->getMessage());
    $_SESSION['error_message'] = 'No se pudo actualizar el envío.';
    header('Location: ../search/shipmentsDetails.php?hbl=' . urlencode($hbl));
    exit();
}
?>