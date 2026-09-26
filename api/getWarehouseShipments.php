<?php

    // Configurar cabeceras
    header('Content-Type: application/json');

    $request = json_decode(file_get_contents('php://input'), true);

    $location = $request['location'];

    if($location == "warehouse"){
        $status_warehouse = "warehouse";
        $starus_draft = "draft";
    }

    // Conectar a la base de datos
    include 'db_connect.php';
    include 'getInfoById.php';

    try {
        // Búsqueda con LIKE (puede devolver múltiples resultados)
        $shipment_stmt = $conn->prepare("SELECT `origen`, `hbl`, `ci`, `manifest`, `route_id` 
                                        FROM `shipments` 
                                        WHERE `status` = ? OR `status` = ?");
        $shipment_stmt->bind_param("ss", $status_warehouse, $starus_draft);
        $shipment_stmt->execute();
        $shipment_result = $shipment_stmt->get_result();
        
        // Array de encabezados
        $header_data = [];
        
        // Obtener todos los envíos encontrados
        $shipments = [];
        $origen_cache = [];

        while ($row = $shipment_result->fetch_assoc()) {
            $oid = $row['origen'];
            if (!isset($origen_cache[$oid])) {
                $origen_cache[$oid] = getInfoById($oid, 'origen')['name'] ?? 'Unknown';
            }
            $header_data['origen'][] = $origen_cache[$oid];
            $shipments[] = $row;
        }

        if (empty($shipments)) {
            http_response_code(404);
            echo json_encode(['error' => 'No se encontraron envíos con ese criterio']);
            exit;
        }

        $cis = array_values(array_filter(array_unique(array_column($shipments, 'ci'))));

        $clients_by_ci = [];
        if (!empty($cis)) {
            $placeholders = implode(',', array_fill(0, count($cis), '?'));
            $client_stmt = $conn->prepare(
                "SELECT `ci`, `name`, `city`, `state` FROM `clients` WHERE `ci` IN ($placeholders)"
            );
            $client_stmt->bind_param(str_repeat('s', count($cis)), ...$cis);
            $client_stmt->execute();
            $client_result = $client_stmt->get_result();

            while ($row = $client_result->fetch_assoc()) {
                $clients_by_ci[$row['ci']] = [
                    'name'  => $row['name'],
                    'city'  => $row['city'],
                    'state' => $row['state'],
                ];
                $header_data['city'][]  = strtoupper($row['city']);
                $header_data['state'][] = strtoupper($row['state']);
            }
            $client_stmt->close();
        }

        $header_data_clr = [
            'origen'   => array_values(array_unique($header_data['origen'] ?? [])),
            'hbl'      => 'hbl',
            'name'     => 'name',
            'city'     => array_values(array_unique($header_data['city'] ?? [])),
            'state'    => array_values(array_unique($header_data['state'] ?? [])),
            'manifest' => 'manifest',
            'route_id' => 'route_id',
        ];
        $response['header_data'] = $header_data_clr;

        foreach ($shipments as $shipment) {
            // Combinar datos del envío con el nombre del cliente (si existe)
            $combined = $shipment;
            $combined['name'] = $clients_by_ci[$shipment['ci']]['name'] ?? 'Cliente no encontrado';
            $combined['city'] = strtoupper($clients_by_ci[$shipment['ci']]['city']) ?? 'Cliente no encontrado';
            $combined['state'] = strtoupper($clients_by_ci[$shipment['ci']]['state']) ?? 'Cliente no encontrado';
            $combined['origen'] = getInfoById($shipment['origen'], 'origen')['name'];
            $response['row_data'][] = $combined;
        }

        // Devolver respuesta
        echo json_encode($response);

        $shipment_stmt->close();

    } catch (Exception $e) {
        error_log("Error en searchShipmentsInfo.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener la información de los envíos']);
    }


?>