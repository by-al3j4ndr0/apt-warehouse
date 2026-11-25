<?php
require_once __DIR__ . '/auth.php';
requireApiLogin(true);

    // Configurar cabeceras
    header('Content-Type: application/json');

    $request = json_decode(file_get_contents('php://input'), true);
    if (!is_array($request) || ($request['location'] ?? '') !== 'warehouse') {
        http_response_code(400);
        echo json_encode(['error' => 'Ubicación no válida']);
        exit;
    }

    $status_warehouse = "warehouse";
    $starus_draft = "draft";

    // Conectar a la base de datos
    include 'db_connect.php';

    try {
        // Búsqueda con LIKE (puede devolver múltiples resultados)
        $shipment_stmt = $conn->prepare("SELECT s.`origen`, s.`hbl`, s.`ci`, s.`manifest`, s.`route_id`, o.`name` AS `origen_name`
                                        FROM `shipments` s
                                        LEFT JOIN `origen` o ON o.`id` = s.`origen`
                                        WHERE s.`status` = ? OR s.`status` = ?");
        $shipment_stmt->bind_param("ss", $status_warehouse, $starus_draft);
        $shipment_stmt->execute();
        $shipment_result = $shipment_stmt->get_result();
        
        // Array de encabezados
        $header_data = [];
        
        // Obtener todos los envíos encontrados
        $shipments = [];
        while ($row = $shipment_result->fetch_assoc()) {
            $header_data['origen'][] = $row['origen_name'] ?? 'Unknown';
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
            $client = $clients_by_ci[$shipment['ci']] ?? null;
            $combined['name'] = $client['name'] ?? 'Cliente no encontrado';
            $combined['city'] = $client !== null ? strtoupper((string) ($client['city'] ?? '')) : 'Cliente no encontrado';
            $combined['state'] = $client !== null ? strtoupper((string) ($client['state'] ?? '')) : 'Cliente no encontrado';
            $combined['origen'] = $shipment['origen_name'] ?? 'Unknown';
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