<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Enable error logging
    error_log("=== Delivery Update Started ===");
    error_log("POST data: " . print_r($_POST, true));
    
    // Initialize variables and validate input
    $errors = [];
    $requiredFields = ['deliveryId', 'driver', 'vehicule', 'origen'];
    
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
    $id = intval($_POST['deliveryId']);
    $driver = intval($_POST['driver']);  // Driver as integer (ID)
    $vehicule = intval($_POST['vehicule']);  // Vehicule as integer (ID)
    $origen = intval($_POST['origen']);  // Origen as integer
    $status = trim($_POST['status'] ?? 'delivering');
    
    // Get selected clients (those that are checked) - clients are strings (CI)
    $clients_after = array_map('trim', $_POST['clients']);
    $clients_after = array_values($clients_after);
    $clients_string = implode(', ', $clients_after);
    
    error_log("Processing delivery ID: $id (int)");
    error_log("Driver ID: $driver (int)");
    error_log("Vehicule ID: $vehicule (int)");
    error_log("Origen ID: $origen (int)");
    error_log("Selected clients: " . print_r($clients_after, true));

    try {
        // Start transaction
        $conn->begin_transaction();
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
        $clients_before = array_map('trim', $clients_before);
        $clients_before = array_values($clients_before);
        $clients_stmt->close();
        
        error_log("Previous clients: " . print_r($clients_before, true));

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
        $update_stmt->bind_param("iissii", $driver, $vehicule, $clients_string, $status, $origen, $id);
        
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