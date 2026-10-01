<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';

requireLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
requireCsrf();

$originalClientCI = trim((string)($_POST['original_client_ci'] ?? ''));
$clientCI = trim((string)($_POST['client_ci'] ?? ''));
$clientName = trim((string)($_POST['client_name'] ?? ''));
$clientPhone = trim((string)($_POST['client_phone'] ?? ''));
$clientAddress = trim((string)($_POST['client_address'] ?? ''));
$clientCity = trim((string)($_POST['client_city'] ?? ''));
$clientState = trim((string)($_POST['client_state'] ?? ''));

if ($originalClientCI === '' || $clientCI === '' || $clientName === '') {
    http_response_code(400);
    exit('Datos de cliente inválidos');
}

if (mb_strlen($clientCI) > 64 || mb_strlen($clientName) > 255
    || mb_strlen($clientPhone) > 64 || mb_strlen($clientAddress) > 255
    || mb_strlen($clientCity) > 128 || mb_strlen($clientState) > 128) {
    http_response_code(400);
    exit('Datos de cliente demasiado largos');
}

try {
    $conn->begin_transaction();

    // Lock the original record so two edits cannot silently overwrite each other.
    $check = $conn->prepare("SELECT ci FROM clients WHERE ci = ? FOR UPDATE");
    $check->bind_param('s', $originalClientCI);
    $check->execute();
    if (!$check->get_result()->fetch_row()) {
        throw new Exception('Cliente no encontrado');
    }
    $check->close();

    if ($clientCI !== $originalClientCI) {
        $duplicate = $conn->prepare("SELECT 1 FROM clients WHERE ci = ? LIMIT 1");
        $duplicate->bind_param('s', $clientCI);
        $duplicate->execute();
        if ($duplicate->get_result()->fetch_row()) {
            throw new Exception('El nuevo CI ya pertenece a otro cliente');
        }
        $duplicate->close();
    }

    // Keep route membership consistent with the client CI as well as shipment ownership.
    if ($clientCI !== $originalClientCI) {
        $routeStmt = $conn->prepare(
            "SELECT id, shipments FROM delivery
             WHERE FIND_IN_SET(?, REPLACE(shipments, ' ', '')) > 0
             FOR UPDATE"
        );
        $routeStmt->bind_param('s', $originalClientCI);
        $routeStmt->execute();
        $routeResult = $routeStmt->get_result();
        $routes = [];
        while ($route = $routeResult->fetch_assoc()) {
            $routes[] = $route;
        }
        $routeStmt->close();

        $updateRoute = $conn->prepare("UPDATE delivery SET shipments = ? WHERE id = ?");
        foreach ($routes as $route) {
            $routeClients = array_values(array_unique(array_filter(
                array_map('trim', explode(',', (string)$route['shipments'])),
                static fn($value) => $value !== ''
            )));
            $routeClients = array_values(array_filter($routeClients, static fn($value) => $value !== $originalClientCI));
            if (!in_array($clientCI, $routeClients, true)) {
                $routeClients[] = $clientCI;
            }
            $routeShipments = implode(', ', $routeClients);
            $routeId = (int)$route['id'];
            $updateRoute->bind_param('si', $routeShipments, $routeId);
            if (!$updateRoute->execute()) {
                throw new Exception('No se pudo actualizar la pertenencia del cliente en una ruta');
            }
        }
        $updateRoute->close();

        // Update children first so restrictive foreign keys can remain valid.
        $updateShipments = $conn->prepare("UPDATE shipments SET ci = ? WHERE ci = ?");
        $updateShipments->bind_param('ss', $clientCI, $originalClientCI);
        if (!$updateShipments->execute()) {
            throw new Exception('No se pudo actualizar la propiedad de los envíos');
        }
        $updateShipments->close();
    }

    $updateClient = $conn->prepare(
        "UPDATE clients
         SET ci = ?, name = ?, phone = ?, address = ?, city = ?, state = ?
         WHERE ci = ?"
    );
    $updateClient->bind_param(
        'sssssss',
        $clientCI, $clientName, $clientPhone, $clientAddress, $clientCity, $clientState, $originalClientCI
    );
    if (!$updateClient->execute()) {
        throw new Exception('No se pudo actualizar el cliente');
    }
    $updateClient->close();
    $conn->commit();
    header('Location: ../search/details.php?ci=' . urlencode($clientCI));
    exit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('Error en updateClient.php: ' . $e->getMessage());
    $_SESSION['error_message'] = 'No se pudo actualizar el cliente.';
    header('Location: ../search/details.php?ci=' . urlencode($originalClientCI));
    exit();
}
?>