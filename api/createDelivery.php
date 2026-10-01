<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
requireLogin(true);
requireCsrf();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize variables
    $errors = [];
    $requiredFields = ['name', 'driver', 'vehicule', 'origen', 'clients'];
    
    // Validate required fields
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "The field '$field' is required.";
        }
    }

    if (!is_array($_POST['clients'] ?? null) || count($_POST['clients']) === 0) {
        $errors[] = "At least one client must be selected.";
    }

    if (!empty($errors)) {
        $_SESSION['form_errors'] = $errors;
        header("Location: ../delivery/deliveries.php");
        exit();
    }

    // Sanitize and prepare data
    $name = trim((string)$_POST['name']);
    $driver = filter_var($_POST['driver'], FILTER_VALIDATE_INT);
    $vehicule = filter_var($_POST['vehicule'], FILTER_VALIDATE_INT);
    $origen = filter_var($_POST['origen'], FILTER_VALIDATE_INT);
    $status = trim((string)($_POST['status'] ?? 'draft'));
    $clients = array_values(array_unique(array_filter(array_map('trim', $_POST['clients']), static fn($ci) => $ci !== '')));

    if ($name === '' || mb_strlen($name) > 255 || $driver === false || $vehicule === false || $origen === false
        || $driver < 1 || $vehicule < 1 || $origen < 1
        || !in_array($status, ['draft', 'delivering'], true) || count($clients) === 0) {
        $_SESSION['error_message'] = 'Datos de ruta inválidos.';
        header('Location: ../delivery/deliveries.php');
        exit();
    }

    $clients_string = implode(', ', $clients);

    try {
        // Start transaction
        $conn->begin_transaction();

        // Validate referenced entities before creating the route.
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

        // Every selected client must exist and have at least one warehouse shipment at this origin.
        $clientStmt = $conn->prepare("SELECT 1 FROM `clients` WHERE `ci` = ? LIMIT 1");
        $shipmentCheck = $conn->prepare("SELECT COUNT(*) AS total FROM `shipments` WHERE `ci` = ? AND `status` = 'warehouse' AND `origen` = ?");
        foreach ($clients as $client_id) {
            $clientStmt->bind_param('s', $client_id);
            $clientStmt->execute();
            if (!$clientStmt->get_result()->fetch_row()) {
                throw new Exception("Cliente $client_id no encontrado.");
            }
            $shipmentCheck->bind_param('si', $client_id, $origen);
            $shipmentCheck->execute();
            if ((int)$shipmentCheck->get_result()->fetch_assoc()['total'] === 0) {
                throw new Exception("El cliente $client_id no tiene envíos en almacén para este origen.");
            }
        }
        $clientStmt->close();
        $shipmentCheck->close();

        // Insert delivery record using prepared statement
        $stmt = $conn->prepare("INSERT INTO `delivery` (
                                `name`, 
                                `driver`, 
                                `vehicule`, 
                                `status`, 
                                `shipments`,
                                `origen`
                            ) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siissi", $name, $driver, $vehicule, $status, $clients_string, $origen);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();

        // Prepare update statement for shipments
        $updateStmt = $conn->prepare("UPDATE `shipments` 
                                      SET `status` = ?, `route_id` = ? 
                                      WHERE `ci` = ? AND `status` = 'warehouse' AND `origen` = ?");
        $updateStmt->bind_param("sisi", $status, $id, $client_id, $origen);

        // Process each client
        foreach ($clients as $client_id) {
            $updateStmt->execute();
            
            // Check if any rows were affected
            if ($updateStmt->affected_rows === 0) {
                throw new Exception("Shipment $client_id not found or not in warehouse status");
            }
        }
        $updateStmt->close();

        // Get counts and totals in a single query
        $summaryQuery = $conn->prepare("
            SELECT 
                COUNT(`ci`) as count,
                COALESCE(SUM(`tariff`), 0) as tariff_total
            FROM `shipments` 
            WHERE `route_id` = ?
        ");
        $summaryQuery->bind_param("i", $id);
        $summaryQuery->execute();
        $result = $summaryQuery->get_result();
        $summary = $result->fetch_assoc();
        $summaryQuery->close();

        // Update delivery with totals
        $updateDelivery = $conn->prepare("
            UPDATE `delivery` 
            SET `total_shipments` = ?, `total_tariff` = ? 
            WHERE `id` = ?
        ");
        $updateDelivery->bind_param("idi", $summary['count'], $summary['tariff_total'], $id);
        $updateDelivery->execute();
        $updateDelivery->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Delivery route created successfully!";
        header("Location: ../delivery/deliveries.php");
        exit();

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        
        // Log error (in production, use proper logging)
        error_log("Delivery creation error: " . $e->getMessage());
        
        $_SESSION['error_message'] = 'No se pudo crear la ruta. Inténtelo nuevamente.';
        header("Location: ../delivery/deliveries.php");
        exit();
    }
}
?>