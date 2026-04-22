<?php
    session_start();

    // Verificar autenticación
    if (!isset($_SESSION['username'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No autorizado']);
        exit();
    }

    // Configurar cabeceras
    header('Content-Type: application/json');

    $origen = json_decode(file_get_contents('php://input'), true);
    $status = 'warehouse';

    if (!isset($origen['origen_id']) || empty($origen['origen_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Origen no especificado']);
        exit();
    }

    $origen_id = intval($origen['origen_id']);

    // Conectar a la base de datos
    include 'db_connect.php';

    try {
        $shipments_stmt = $conn->prepare("SELECT * FROM `shipments` WHERE `origen` = ? AND `status` = ?");
        $shipments_stmt->bind_param("ss", $origen_id, $status);
        $shipments_stmt->execute();
        $shipments_result = $shipments_stmt->get_result();

        $count_stmt = $conn->prepare("SELECT COUNT(`ci`) as count FROM `shipments` WHERE `ci` = ? AND `status` = ?");

        $clients_stmt = $conn->prepare("SELECT * FROM `clients` WHERE `ci` = ?");

        $clients = [];

        while($shipments_data = $shipments_result->fetch_assoc()){
            $shipment_ci = $shipments_data['ci'];
            
            if(isset($clients[$shipment_ci])) {
                continue; 
            }
            
            $clients_stmt->bind_param("s", $shipment_ci);
            $clients_stmt->execute();
            $clients_result = $clients_stmt->get_result();
            $clients_data = $clients_result->fetch_assoc();

            $count_stmt->bind_param("ss", $shipment_ci, $status);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_data = $count_result->fetch_assoc();
            $clients_data['count'] = $count_data['count']; 

            $clients[$shipment_ci] = $clients_data;
        }

        $shipments_stmt->close();
        $clients_stmt->close();
        $count_stmt->close();

        print_r(json_encode($clients));
        
    } catch (Exception $e) {
        error_log("Error en getClients.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener los clientes']);
    }

    $conn->close();

?>