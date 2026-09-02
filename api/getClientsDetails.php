<?php 

function clientDetails(string $ci) {
    include '../api/db_connect.php';

    global $client_name, $client_ci, $client_phone, $client_address, $client_city, $client_state;
    
    try {
        $client_info_stmt = $conn->prepare("SELECT * FROM `clients` WHERE `ci` = ?");
        $client_info_stmt->bind_param("s", $ci);
        $client_info_stmt->execute();
        
        $client_info_result = $client_info_stmt->get_result();
        
        if ($client_info_data = $client_info_result->fetch_assoc()) {
            // Escape all output data
            $client_name = htmlspecialchars($client_info_data['name'], ENT_QUOTES, 'UTF-8');
            $client_ci = htmlspecialchars($client_info_data['ci'], ENT_QUOTES, 'UTF-8');
            $client_phone = htmlspecialchars($client_info_data['phone'], ENT_QUOTES, 'UTF-8');
            $client_address = htmlspecialchars($client_info_data['address'], ENT_QUOTES, 'UTF-8');
            $client_city = htmlspecialchars($client_info_data['city'], ENT_QUOTES, 'UTF-8');
            $client_state = htmlspecialchars($client_info_data['state'], ENT_QUOTES, 'UTF-8');
        } else {
            throw new Exception("Client not found");
        }
        
        $client_info_stmt->close();
        $conn->close();
        
    } catch (Exception $e) {
        // Log error internally
        error_log("Client details error: " . $e->getMessage());
        
        // Generic user message
        $_SESSION['error_message'] = "Unable to load client details. Please try again later.";
        header("Location: ../clients.php");
        exit();
    }
}

function getClientShipments(string $ci) {
    include '../api/db_connect.php';

    global $shipment_info_result;
    
    try {
        $shipment_info_stmt = $conn->prepare("SELECT `hbl`, `route_id`, `status` FROM `shipments` WHERE `ci` = ?");
        $shipment_info_stmt->bind_param("s", $ci);
        $shipment_info_stmt->execute();
        
        $shipment_info_result = $shipment_info_stmt->get_result();
        $shipment_info_stmt->close();

        $conn->close();
        
    } catch (Exception $e) {
        // Log error internally
        error_log("Shipments query error: " . $e->getMessage());
        
        // Generic user message
        $_SESSION['error_message'] = "Unable to load shipments. Please try again later.";
        header("Location: ../clients.php");
        exit();
    }
}

function editClient(string $ci) {
    include '../api/db_connect.php';

    global $client_name, $client_ci, $client_phone, $client_address, $client_city, $client_state;
}

?>