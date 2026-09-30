<?php 

function shipmentsDetails(string $hbl) {
    include '../api/db_connect.php';

    global $shipment_hbl, $shipment_owner_name, $shipment_owner_ci, $shipment_origen, $shipment_weight, $shipment_description, $shipment_tariff, $shipment_manifest, $shipment_status_raw, $shipment_route_id;
    
    try {
        $shipment_info_stmt = $conn->prepare("SELECT * FROM `shipments` WHERE `hbl` = ?");
        $shipment_info_stmt->bind_param("s", $hbl);
        $shipment_info_stmt->execute();
        
        $shipment_info_result = $shipment_info_stmt->get_result();
        
        if ($shipment_info_data = $shipment_info_result->fetch_assoc()) {
            // Escape all output data
            $shipment_hbl = htmlspecialchars($shipment_info_data['hbl'], ENT_QUOTES, 'UTF-8');
            $shipment_owner_ci = htmlspecialchars($shipment_info_data['ci'], ENT_QUOTES, 'UTF-8');
            $shipment_origen = htmlspecialchars($shipment_info_data['origen'], ENT_QUOTES, 'UTF-8');
            $shipment_weight = htmlspecialchars($shipment_info_data['weight'], ENT_QUOTES, 'UTF-8');
            $shipment_description = htmlspecialchars($shipment_info_data['description'], ENT_QUOTES, 'UTF-8');
            $shipment_tariff = htmlspecialchars($shipment_info_data['tariff'], ENT_QUOTES, 'UTF-8');
            $shipment_manifest = htmlspecialchars($shipment_info_data['manifest'], ENT_QUOTES, 'UTF-8');
            $shipment_status_raw = htmlspecialchars($shipment_info_data['status'], ENT_QUOTES, 'UTF-8');
            $shipment_route_id = htmlspecialchars($shipment_info_data['route_id'], ENT_QUOTES, 'UTF-8');
        } else {
            throw new Exception("Shipment not found");
        }

        $shipment_owner_stmt = $conn->prepare("SELECT `name` FROM `clients` WHERE `ci` = ?");
        $shipment_owner_stmt->bind_param("s", $shipment_owner_ci);
        $shipment_owner_stmt->execute();
        
        $shipment_owner_result = $shipment_owner_stmt->get_result();
        $shipment_owner_data = $shipment_owner_result->fetch_assoc();

        $shipment_owner_name = $shipment_owner_data['name'];
        
        $shipment_info_stmt->close();
        $shipment_owner_stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        // Log error internally
        error_log("Shipment details error: " . $e->getMessage());
        
        // Generic user message
        $_SESSION['error_message'] = "Unable to load shipment details. Please try again later.";
        header("Location: ../search/shipments.php");
        exit();
    }
}

?>