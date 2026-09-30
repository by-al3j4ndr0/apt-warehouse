<?php 
    session_start();
    include './db_connect.php';

    $shipment_hbl = $_POST['shipment_hbl'];
    $shipment_owner_ci = $_POST['shipment_owner_ci'];
    $shipment_origen = $_POST['shipment_origen'];
    $shipment_weight = $_POST['shipment_weight'];
    $shipment_tariff = $_POST['shipment_tariff'];
    $shipment_manifest = $_POST['shipment_manifest'];
    $shipment_status = $_POST['shipment_status'];
    $shipment_route_id = $_POST['shipment_route_id'];
    $shipment_description = $_POST['shipment_description'];

    try {
        $updateShipment_stmt = $conn->prepare("UPDATE `shipments` SET `ci` = ?, `origen` = ?, `weight` = ?, `tariff` = ?, `manifest` = ?, `status` = ?, `route_id` = ?, `description` = ? WHERE `hbl` = ?");
        $updateShipment_stmt->bind_param("sssssssss", $shipment_owner_ci, $shipment_origen, $shipment_weight, $shipment_tariff, $shipment_manifest, $shipment_status, $shipment_route_id, $shipment_description, $shipment_hbl);
        $updateShipment_stmt->execute();

        header("Location: ../search/shipmentsDetails.php?hbl=" . $shipment_hbl);
        exit();
    } catch (Exception $e) {
        error_log("Error en updateShipment.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el envio']);
        header("Location: ../search/shipmentsDetails.php?hbl=" . $shipment_hbl);
        exit();
    }
    
    $conn->close();

?>