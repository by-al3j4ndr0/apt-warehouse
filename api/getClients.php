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

    $request = json_decode(file_get_contents('php://input'), true);

    if (!isset($request['origen_id']) || empty($request['origen_id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Origen no especificado']);
        exit();
    }

    $warehouse_status = "warehouse";
    $origen_id = intval($request['origen_id']);
    $status = $request['selected_status'] ?? '';
    $delivery_id = intval($request['delivery_id'] ?? 0);

    // Conectar a la base de datos
    include 'db_connect.php';

    try {
        header('Content-Type: application/json');

    if($status == 'delivering') {
        $warehouse_status = 'delivering';
    }
        
        if($delivery_id == 0) {
            // Query simple para delivery_id == 0
            $sql = "
                SELECT 
                    c.*,
                    COUNT(s.hbl) as count,
                    0 as checked
                FROM clients c
                INNER JOIN shipments s ON c.ci = s.ci
                WHERE s.origen = ? AND s.status = ?
                GROUP BY c.ci
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $origen_id, $warehouse_status);
            
        } else {
            // Query con checked para delivery_id > 0
            $sql = "
                SELECT 
                    c.*,
                    COUNT(s.hbl) as count,
                    MAX(CASE WHEN s.route_id = ? THEN 1 ELSE 0 END) as checked
                FROM clients c
                INNER JOIN shipments s ON c.ci = s.ci
                WHERE (s.origen = ? AND s.route_id = ?) 
                OR (s.origen = ? AND s.status = ?)
                GROUP BY c.ci
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $delivery_id, $origen_id, $delivery_id, $origen_id, $warehouse_status);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $clients = [];
        while($row = $result->fetch_assoc()) {
            $row['checked'] = ($delivery_id == 0) ? false : (bool)$row['checked'];
            $clients[] = $row;
        }
        
        echo json_encode($clients);
        
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error en getClients.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener los clientes']);
    }

    $conn->close();

?>