<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
requireLogin(true);
requireCsrf();

if ($_SERVER["REQUEST_METHOD"] !== "POST") { http_response_code(405); exit('Method Not Allowed'); }

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Enable error logging
    error_log("=== Delivery Update Started ===");
    error_log("POST data: " . print_r($_POST, true));
    
    // Initialize variables and validate input
    $errors = [];
    $requiredFields = ['deliveryId', 'name', 'driver', 'vehicule', 'origen'];
    
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || (is_array($_POST[$field]) && count($_POST[$field]) === 0) || (!is_array($_POST[$field]) && trim($_POST[$field]) === '')) {
            $errors[] = "The field '$field' is required.";
            error_log("Missing field: $field");
        }
    }

    if (!is_array($_POST['clients'] ?? null) || count($_POST['clients']) === 0) {
        $errors[] = "At least one client must be selected.";
        error_log("No clients selected");
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        error_log("Validation errors: " . print_r($errors, true));
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Sanitize and prepare data - TREAT AS INTEGERS
    $id = filter_var($_POST['deliveryId'], FILTER_VALIDATE_INT);
    $name = trim((string)$_POST['name']);
    $driver = filter_var($_POST['driver'], FILTER_VALIDATE_INT);
    $vehicule = filter_var($_POST['vehicule'], FILTER_VALIDATE_INT);
    $origen = filter_var($_POST['origen'], FILTER_VALIDATE_INT);
    $status = trim((string)($_POST['status'] ?? 'delivering'));

    $clients_after = array_values(array_unique(array_filter(array_map('trim', $_POST['clients']), static fn($ci) => $ci !== '')));

    if ($id === false || $driver === false || $vehicule === false || $origen === false
        || $id < 1 || $driver < 1 || $vehicule < 1 || $origen < 1 || $name === '' || mb_strlen($name) > 255
        || count($clients_after) === 0) {
        $_SESSION['error_message'] = 'Datos de ruta inválidos.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/deliveries.php'));
        exit();
    }

    $allowedStatuses = ['draft', 'delivering', 'finished'];
    if (!in_array($status, $allowedStatuses, true)) {
        $_SESSION['error_message'] = 'Estado de ruta inválido.';
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../delivery/deliveries.php'));
        exit();
    }

    try {
        // Lock and validate the route inside the transaction to prevent concurrent edits.
        $conn->begin_transaction();

    $routeCheck = $conn->prepare("SELECT status, origen FROM delivery WHERE id = ? FOR UPDATE");
    $routeCheck->bind_param('i', $id);
    $routeCheck->execute();
    $currentRoute = $routeCheck->get_result()->fetch_assoc();
    $routeCheck->close();

    if (!$currentRoute) {
        throw new Exception('Ruta no encontrada.');
    }

    $validTransitions = [
        'draft' => ['draft', 'delivering'],
        'delivering' => ['delivering', 'finished'],
        'finished' => ['finished']
    ];

    if (!in_array($status, $validTransitions[$currentRoute['status']] ?? [], true)) {
        throw new Exception('Transición de estado de ruta no permitida.');
    }

    $assignedCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM shipments WHERE route_id = ?");
    $assignedCountStmt->bind_param('i', $id);
    $assignedCountStmt->execute();
    $assignedCount = (int) $assignedCountStmt->get_result()->fetch_assoc()['total'];
    $assignedCountStmt->close();

    if ($assignedCount > 0 && (int)$currentRoute['origen'] !== $origen) {
        throw new Exception('No se puede cambiar el origen de una ruta que ya tiene envíos.');
    }

    if ($currentRoute['status'] === 'finished' && $status === 'finished') {
        $status = 'finished';
    }
    
    // Validate referenced entities before changing route membership.
    foreach ([
        ['table' => 'drivers', 'id' => $driver, 'label' => 'Conductor'],
        ['table' => 'vehicules', 'id' => $vehicule, 'label' => 'Vehículo'],
        ['table' => 'origen', 'id' => $origen, 'label' => 'Origen'],
    ] as $entity) {
        $entityStmt = $conn->prepare("SELECT 1 FROM `{$entity['table']}` WHERE `id` = ? LIMIT 1");
        $entityStmt->bind_param('i', $entity['id']);
        $entityStmt->execute();
        if (!$entityStmt->get_result()->fetch_row()) {
            $entityStmt->close();
            throw new Exception($entity['label'] . ' no encontrado.');
        }
        $entityStmt->close();
    }

    $clients_string = implode(', ', $clients_after);
    
    error_log("Processing delivery ID: $id (int)");
    error_log("Driver ID: $driver (int)");
    error_log("Vehicule ID: $vehicule (int)");
    error_log("Origen ID: $origen (int)");
    error_log("Selected clients: " . print_r($clients_after, true));

    error_log("Transaction started");

        // Get previous clients list from delivery
        $clients_stmt = $conn->prepare("SELECT `shipments` FROM `delivery` WHERE `id` = ?");
        if (!$clients_stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $clients_stmt->bind_param("i", $id);
        $clients_stmt->execute();
        $result = $clients_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Delivery route not found for ID: $id");
        }
        
        $row = $result->fetch_assoc();
        $clients_before = !empty($row['shipments']) ? explode(", ", $row['shipments']) : [];
        $clients_before = array_values(array_unique(array_filter($clients_before, static fn($ci) => $ci !== '')));
        $clients_stmt->close();
        
        error_log("Previous clients: " . print_r($clients_before, true));

        // Validate every selected client and require at least one shipment that can belong to this route.
        $clientStmt = $conn->prepare("SELECT 1 FROM `clients` WHERE `ci` = ? LIMIT 1");
        $shipmentCheck = $conn->prepare("SELECT COUNT(*) AS total FROM `shipments` WHERE `ci` = ? AND `origen` = ? AND (`status` = 'warehouse' OR `route_id` = ?)");
        foreach ($clients_after as $client_id) {
            $clientStmt->bind_param('s', $client_id);
            $clientStmt->execute();
            if (!$clientStmt->get_result()->fetch_row()) {
                throw new Exception("Cliente $client_id no encontrado.");
            }
            $shipmentCheck->bind_param('sii', $client_id, $origen, $id);
            $shipmentCheck->execute();
            if ((int)$shipmentCheck->get_result()->fetch_assoc()['total'] === 0) {
                throw new Exception("El cliente $client_id no tiene envíos disponibles para esta ruta.");
            }
        }
        $clientStmt->close();
        $shipmentCheck->close();

        if ($currentRoute['status'] === 'finished'
            && (array_diff($clients_before, $clients_after) !== [] || array_diff($clients_after, $clients_before) !== [])) {
            throw new Exception('Una ruta finalizada no puede cambiar sus envíos asignados.');
        }

        // Determine changes
        $clients_to_remove = array_diff($clients_before, $clients_after);
        $clients_to_add = array_diff($clients_after, $clients_before);
        
        error_log("Clients to remove: " . print_r($clients_to_remove, true));
        error_log("Clients to add: " . print_r($clients_to_add, true));

        // IMPORTANT: First, remove all shipments from this route for clients that were unchecked
        if (!empty($clients_to_remove)) {
            $placeholders = implode(',', array_fill(0, count($clients_to_remove), '?'));
            $types = str_repeat('s', count($clients_to_remove));
            
            $stmt = $conn->prepare("UPDATE `shipments` 
                                  SET `status` = 'warehouse', `route_id` = 0 
                                  WHERE `ci` IN ($placeholders) AND `route_id` = ?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed for remove: " . $conn->error);
            }
            
            $params = array_merge($clients_to_remove, [$id]);
            $stmt->bind_param($types . 'i', ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed for remove: " . $stmt->error);
            }
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            error_log("Removed $affected shipments from route for " . count($clients_to_remove) . " clients");
        }

        // Second, add all warehouse shipments for newly checked clients to this route
        if (!empty($clients_to_add)) {
            $placeholders = implode(',', array_fill(0, count($clients_to_add), '?'));
            $types = str_repeat('s', count($clients_to_add));
            
            $stmt = $conn->prepare("UPDATE `shipments` 
                                  SET `status` = ?, `route_id` = ? 
                                  WHERE `ci` IN ($placeholders) 
                                  AND `status` = 'warehouse' 
                                  AND `origen` = ?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed for add: " . $conn->error);
            }
            
            $params = array_merge([$status, $id], $clients_to_add, [$origen]);
            $stmt->bind_param('si' . $types . 'i', ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed for add: " . $stmt->error);
            }
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            error_log("Added $affected shipments to route for " . count($clients_to_add) . " clients");
        }

        // For clients that remain checked, ensure all their warehouse shipments are assigned to this route
        $clients_to_keep = array_intersect($clients_before, $clients_after);
        if (!empty($clients_to_keep)) {
            $placeholders = implode(',', array_fill(0, count($clients_to_keep), '?'));
            $types = str_repeat('s', count($clients_to_keep));
            
            $stmt = $conn->prepare("UPDATE `shipments` 
                                  SET `status` = ? 
                                  WHERE `ci` IN ($placeholders) 
                                  AND `route_id` = ?
                                  AND `origen` = ?");
            
            if (!$stmt) {
                throw new Exception("Prepare failed for keep: " . $conn->error);
            }
            
            $params = array_merge([$status], $clients_to_keep, [$id, $origen]);
            $stmt->bind_param('s' . $types . 'ii', ...$params);
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed for keep: " . $stmt->error);
            }
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            error_log("Updated $affected shipments for " . count($clients_to_keep) . " remaining clients");
        }

        // Update delivery record with integer values
        $update_stmt = $conn->prepare("UPDATE `delivery` SET 
                                    `name` = ?,
                                    `driver` = ?,
                                    `vehicule` = ?,
                                    `shipments` = ?,
                                    `status` = ?,
                                    `origen` = ?
                                    WHERE `id` = ?");
        
        if (!$update_stmt) {
            throw new Exception("Prepare failed for update: " . $conn->error);
        }
        
        // Bind parameters: driver (int), vehicule (int), shipments (string), status (string), origen (int), id (int)
        $update_stmt->bind_param("siissii", $name, $driver, $vehicule, $clients_string, $status, $origen, $id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Execute failed for update: " . $update_stmt->error);
        }
        
        $update_stmt->close();
        error_log("Delivery record updated");

        // Get updated counts and totals for this delivery route
        // Count DISTINCT hbl shipments and SUM tariff
        $summaryQuery = $conn->prepare("
            SELECT 
                COUNT(DISTINCT s.hbl) as count,
                COALESCE(SUM(s.tariff), 0) as tariff_total
            FROM shipments s
            WHERE s.route_id = ?
        ");
        
        if (!$summaryQuery) {
            throw new Exception("Prepare failed for summary: " . $conn->error);
        }
        
        $summaryQuery->bind_param("i", $id);
        
        if (!$summaryQuery->execute()) {
            throw new Exception("Execute failed for summary: " . $summaryQuery->error);
        }
        
        $result = $summaryQuery->get_result();
        $summary = $result->fetch_assoc();
        $summaryQuery->close();
        
        $total_shipments = intval($summary['count']);
        $total_tariff = floatval($summary['tariff_total']);
        
        error_log("Summary - Count: $total_shipments, Total Tariff: $total_tariff");

        // Update delivery totals
        $updateDelivery = $conn->prepare("
            UPDATE `delivery` 
            SET `total_shipments` = ?, `total_tariff` = ? 
            WHERE `id` = ?
        ");
        
        if (!$updateDelivery) {
            throw new Exception("Prepare failed for totals: " . $conn->error);
        }
        
        $updateDelivery->bind_param("idi", $total_shipments, $total_tariff, $id);
        
        if (!$updateDelivery->execute()) {
            throw new Exception("Execute failed for totals: " . $updateDelivery->error);
        }
        
        $updateDelivery->close();
        error_log("Delivery totals updated successfully");

        // Verify the update was successful
        $verify_stmt = $conn->prepare("SELECT total_shipments, total_tariff FROM delivery WHERE id = ?");
        $verify_stmt->bind_param("i", $id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $verify_data = $verify_result->fetch_assoc();
        $verify_stmt->close();
        
        error_log("Verification - Total Shipments: {$verify_data['total_shipments']}, Total Tariff: {$verify_data['total_tariff']}");

        // Commit transaction
        $conn->commit();
        error_log("Transaction committed successfully");

        $_SESSION['success_message'] = "Delivery route updated successfully! {$total_shipments} shipments with total tariff of {$total_tariff}";
        
        // Redirect to deliveries page
        header("Location: ../delivery/deliveries.php");
        exit();

    } catch (Exception $e) {
        // Rollback on error
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
            error_log("Transaction rolled back");
        }
        
        $error_message = "Delivery update error: " . $e->getMessage();
        error_log($error_message);
        
        $_SESSION['error_message'] = "Failed to update delivery: " . $e->getMessage();
        
        // Redirect back to the form with error
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
} else {
    // Not a POST request
    error_log("Not a POST request: " . $_SERVER["REQUEST_METHOD"]);
    header("Location: ../delivery/deliveries.php");
    exit();
}
?>