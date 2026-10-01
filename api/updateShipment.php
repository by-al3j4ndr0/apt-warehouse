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

if ($hbl === '' || !preg_match('/^[A-Za-z0-9-]+$/', $hbl) || mb_strlen($hbl) > 64
    || $ci === '' || $origen === false || $origen === null
    || $weight === '' || $tariff === '' || $manifest === ''
    || !in_array($status, $allowedStatuses, true)
    || $routeId === false || $routeId === null || $routeId < 0
    || mb_strlen($ci) > 64 || mb_strlen($manifest) > 255 || mb_strlen($description) > 1000) {
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

    if ($current['status'] === 'finished'
        && ($routeId !== (int)$current['route_id'] || (int)$origen !== (int)$current['origen'])) {
        throw new Exception('A finished shipment cannot change route or origin');
    }

    $originStmt = $conn->prepare("SELECT 1 FROM origen WHERE id = ? LIMIT 1");
    $originStmt->bind_param('i', $origen);
    $originStmt->execute();
    if (!$originStmt->get_result()->fetch_row()) {
        throw new Exception('Origin not found');
    }
    $originStmt->close();

    if ($routeId > 0) {
        $routeStmt = $conn->prepare("SELECT origen, status, shipments FROM delivery WHERE id = ? FOR UPDATE");
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

        if ($status === 'draft' && $route['status'] !== 'draft') {
            throw new Exception('Draft shipments must belong to draft routes');
        }

        if ($current['status'] === 'finished' && $routeId !== (int)$current['route_id']) {
            throw new Exception('A finished shipment cannot be moved to another route');
        }
    }

    $clientStmt = $conn->prepare("SELECT 1 FROM clients WHERE ci = ? LIMIT 1");
    $clientStmt->bind_param('s', $ci);
    $clientStmt->execute();
    if (!$clientStmt->get_result()->fetch_row()) {
        throw new Exception('Client not found');
    }
    $clientStmt->close();

    // If route or client membership changes, lock the old route and synchronize both route lists.
    $oldRouteId = (int)$current['route_id'];
    $routeIdsToLock = array_values(array_unique(array_filter([$oldRouteId, $routeId], static fn($value) => $value > 0)));
    sort($routeIdsToLock, SORT_NUMERIC);
    $lockedRoutes = [];
    foreach ($routeIdsToLock as $lockedRouteId) {
        $lockRoute = $conn->prepare("SELECT shipments FROM delivery WHERE id = ? FOR UPDATE");
        $lockRoute->bind_param('i', $lockedRouteId);
        $lockRoute->execute();
        $lockedRoute = $lockRoute->get_result()->fetch_assoc();
        $lockRoute->close();
        if (!$lockedRoute) {
            throw new Exception('Route not found');
        }
        $lockedRoutes[$lockedRouteId] = $lockedRoute['shipments'];
    }

    if ($oldRouteId !== $routeId || $current['ci'] !== $ci) {
        $oldClients = $oldRouteId > 0 ? array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($lockedRoutes[$oldRouteId] ?? ''))), static fn($v) => $v !== ''))) : [];
        $newClients = $routeId > 0 ? array_values(array_unique(array_filter(array_map('trim', explode(',', (string)($lockedRoutes[$routeId] ?? ''))), static fn($v) => $v !== ''))) : [];

        if ($oldRouteId > 0 && $current['ci'] !== $ci && !in_array($current['ci'], $newClients, true)) {
            $oldClients = array_values(array_filter($oldClients, static fn($v) => $v !== $current['ci']));
        }
        if ($oldRouteId > 0 && $oldRouteId !== $routeId && !in_array($current['ci'], $oldClients, true)) {
            $oldClients = array_values(array_filter($oldClients, static fn($v) => $v !== $current['ci']));
        }
        if ($routeId > 0 && !in_array($ci, $newClients, true)) {
            $newClients[] = $ci;
        }

        if ($oldRouteId > 0 && $oldRouteId !== $routeId) {
            $routeUpdate = $conn->prepare("UPDATE delivery SET shipments = ? WHERE id = ?");
            $oldClientsString = implode(', ', $oldClients);
            $routeUpdate->bind_param('si', $oldClientsString, $oldRouteId);
            if (!$routeUpdate->execute()) throw new Exception('Failed to update previous route membership');
            $routeUpdate->close();
        }
        if ($routeId > 0 && ($oldRouteId !== $routeId || $current['ci'] !== $ci)) {
            $routeUpdate = $conn->prepare("UPDATE delivery SET shipments = ? WHERE id = ?");
            $newClientsString = implode(', ', $newClients);
            $routeUpdate->bind_param('si', $newClientsString, $routeId);
            if (!$routeUpdate->execute()) throw new Exception('Failed to update new route membership');
            $routeUpdate->close();
        }
    }

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