<?php
    include '../api/db_connect.php';

    $request = json_decode(file_get_contents('php://input'), true);

    // Validar que el parámetro existe
    if (!isset($request['search_param']) || empty($request['search_param'])) {
        http_response_code(400);
        echo json_encode(['error' => 'El parámetro de búsqueda es obligatorio']);
        exit;
    }

    $search_param = $request['search_param'];

    try {
        // Búsqueda con LIKE (puede devolver múltiples resultados)
        $client_stmt = $conn->prepare("SELECT `name`, `ci`, `city`, `state`
                                        FROM `clients` 
                                        WHERE `name` LIKE ? OR `ci` LIKE ?");
        $search_pattern = "%" . $search_param . "%";
        $client_stmt->bind_param("ss", $search_pattern, $search_pattern);
        $client_stmt->execute();
        $client_result = $client_stmt->get_result();
        
        // Obtener todos los envíos encontrados
        $response = [];
        while ($row = $client_result->fetch_assoc()) {
            $response[] = $row;
        }

        // Si no se encontraron envíos
        if (empty($response)) {
            http_response_code(404);
            echo json_encode(['error' => 'No se encontraron clientes con ese criterio']);
            exit;
        }

        // Devolver respuesta
        echo json_encode($response);

        $client_stmt->close();

    } catch (Exception $e) {
        error_log("Error en searchClientInfo.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener la información de los clientes']);
    }

    $conn->close();

?>