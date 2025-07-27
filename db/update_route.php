<?php
include '../db/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['id'];
    $driver = $_POST['driver'];
    $vehicule = $_POST['vehicule'];
    $status = 'delivering';
    
    if (($_POST['clients'])) {
        $clients = '';
        $num_clients = count($_POST['clients']);
        $current = 0;
        foreach ($_POST['clients'] as $key => $value) {
            if ($current != $num_clients-1)
                $clients .= $value.', ';
            else
                $clients .= $value.'.';
            $current++;
        }
    }

    try {
        $clients_stmt = $conn->query("SELECT `shipments` FROM `delivery` WHERE `id` = $id");
        $clients_array = $clients_stmt->fetch_assoc();
        $clients_before = substr($clients_array['shipments'], 0, -1);
        $clients_before = explode(", ", $clients_before);

        $stmt = $conn->prepare("UPDATE `delivery` SET 
                                                `driver` = ?,
                                                `vehicule` = ?,
                                                `shipments` = ?
                                                WHERE `id` = ?");
        $stmt->bind_param("ssss", $driver, $vehicule, $clients, $id);
        $stmt->execute();

        
        // Convert all clients to strings
        $clients_before = array_map('strval', $clients_before);
        $clients_after = array_map('strval', $_POST['clients']);
        $status = (string)$status;

        // Update clients that are in both lists
        $common_clients = array_intersect($clients_before, $clients_after);
        if (!empty($common_clients)) {
            $placeholders = implode(',', array_fill(0, count($common_clients), '?'));
            $types = str_repeat('s', count($common_clients));
            
            $stmt = $conn->prepare("UPDATE `shipments` SET `status` = ? WHERE `ci` IN ($placeholders) AND `status` != 'finished'");
            $stmt->bind_param("s" . $types, $status, ...$common_clients);
            $stmt->execute();
        }

        // Set to warehouse for clients no longer in the list
        $removed_clients = array_diff($clients_before, $clients_after);
        if (!empty($removed_clients)) {
            $placeholders = implode(',', array_fill(0, count($removed_clients), '?'));
            $types = str_repeat('s', count($removed_clients));
            
            $stmt = $conn->prepare("UPDATE `shipments` SET `status` = 'warehouse' WHERE `ci` IN ($placeholders) AND `status` != 'finished'");
            $stmt->bind_param($types, ...$removed_clients);
            $stmt->execute();
        }

        // Add new clients (if needed)
        $new_clients = array_diff($clients_after, $clients_before);
        if (!empty($new_clients)) {
            $placeholders = implode(',', array_fill(0, count($new_clients), '?'));
            $types = str_repeat('s', count($new_clients));
            
            $stmt = $conn->prepare("UPDATE `shipments` SET `status` = ? WHERE `ci` IN ($placeholders) AND `status` != 'finished'");
            $stmt->bind_param("s" . $types, $status, ...$new_clients);
            $stmt->execute();
}
    } catch (Exception $e) {
        echo $e;
    }
    
    header("Location: ../shipments.php");
}
?>