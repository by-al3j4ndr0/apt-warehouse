<?php
include 'db_connect.php';

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
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Sanitize and prepare data
    $name = trim($_POST['name']);
    $driver = trim($_POST['driver']);
    $vehicule = trim($_POST['vehicule']);
    $origen = trim($_POST['origen']);
    $status = trim($_POST['status']);
    $clients = implode(', ', array_map('trim', $_POST['clients']));

    try {
        // Start transaction
        $conn->begin_transaction();

        // Insert delivery record using prepared statement
        $stmt = $conn->prepare("INSERT INTO `delivery` (
                                `name`, 
                                `driver`, 
                                `vehicule`, 
                                `status`, 
                                `shipments`,
                                `origen`
                            ) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $driver, $vehicule, $status, $clients, $origen);
        $stmt->execute();
        $id = $conn->insert_id;
        $stmt->close();

        // Prepare update statement for shipments
        $updateStmt = $conn->prepare("UPDATE `shipments` 
                                      SET `status` = ?, `route_id` = ? 
                                      WHERE `ci` = ? AND `status` = 'warehouse'");
        $updateStmt->bind_param("sis", $status, $id, $client_id);

        // Process each client
        foreach ($_POST['clients'] as $client) {
            $client_id = trim($client);
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
        
        $_SESSION['error_message'] = "Failed to create delivery: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>