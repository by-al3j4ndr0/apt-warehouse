<?php
include '../db/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Initialize variables and validate input
    $errors = [];
    $requiredFields = ['id', 'driver', 'vehicule', 'clients'];
    
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
    $id = (int)$_POST['id'];
    $driver = trim($_POST['driver']);
    $vehicule = trim($_POST['vehicule']);
    $status = 'delivering';
    $clients = implode(', ', array_map('trim', $_POST['clients']));
    $clients_after = array_map('strval', $_POST['clients']);

    try {
        // Start transaction
        $conn->begin_transaction();

        // Get previous clients list
        $clients_stmt = $conn->prepare("SELECT `shipments` FROM `delivery` WHERE `id` = ?");
        $clients_stmt->bind_param("i", $id);
        $clients_stmt->execute();
        $result = $clients_stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Delivery route not found");
        }
        
        $row = $result->fetch_assoc();
        $clients_before = !empty($row['shipments']) ? explode(", ", $row['shipments']) : [];
        $clients_before = array_map('strval', $clients_before);
        $clients_stmt->close();

        // Update delivery record
        $update_stmt = $conn->prepare("UPDATE `delivery` SET 
                                    `driver` = ?,
                                    `vehicule` = ?,
                                    `shipments` = ?
                                    WHERE `id` = ?");
        $update_stmt->bind_param("sssi", $driver, $vehicule, $clients, $id);
        $update_stmt->execute();
        $update_stmt->close();

        // Process removed clients (set back to warehouse)
        $removed_clients = array_diff($clients_before, $clients_after);
        if (!empty($removed_clients)) {
            $placeholders = implode(',', array_fill(0, count($removed_clients), '?'));
            $types = str_repeat('s', count($removed_clients));
            
            $stmt = $conn->prepare("UPDATE `shipments` 
                                  SET `status` = 'warehouse', `route_id` = 0 
                                  WHERE `ci` IN ($placeholders) AND `route_id` = ?");
            $stmt->bind_param($types . 'i', ...array_merge($removed_clients, [$id]));
            $stmt->execute();
            $stmt->close();
        }

        // Process new clients (set to delivering)
        $new_clients = array_diff($clients_after, $clients_before);
        if (!empty($new_clients)) {
            $placeholders = implode(',', array_fill(0, count($new_clients), '?'));
            $types = str_repeat('s', count($new_clients));
            
            $stmt = $conn->prepare("UPDATE `shipments` 
                                  SET `status` = ?, `route_id` = ? 
                                  WHERE `ci` IN ($placeholders) AND `status` = 'warehouse'");
            $stmt->bind_param('si' . $types, $status, $id, ...$new_clients);
            $stmt->execute();
            $stmt->close();
        }

        // Get updated counts and totals
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

        // Update delivery totals
        $updateDelivery = $conn->prepare("
            UPDATE `delivery` 
            SET `total_shipments` = ?, `total_tariff` = ? 
            WHERE `id` = ?
        ");
        $updateDelivery->bind_param("iii", $summary['count'], $summary['tariff_total'], $id);
        $updateDelivery->execute();
        $updateDelivery->close();

        // Commit transaction
        $conn->commit();

        $_SESSION['success_message'] = "Delivery route updated successfully!";
        header("Location: ../shipments/shipments.php");
        exit();

    } catch (Exception $e) {
        // Rollback on error
        if (isset($conn) && $conn instanceof mysqli) {
            $conn->rollback();
        }
        
        error_log("Delivery update error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to update delivery: " . $e->getMessage();
        echo $e;
        // header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>