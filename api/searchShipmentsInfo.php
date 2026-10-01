<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
requireApiLogin(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Method Not Allowed']);
    exit();
}
header('Content-Type: application/json; charset=utf-8');

include '../api/getInfoById.php';

    $request = json_decode(file_get_contents('php://input'), true);

    // Validar que el parámetro existe
    if (!isset($request['search_param']) || empty($request['search_param'])) {
        http_response_code(400);
        echo json_encode(['error' => 'El parámetro de búsqueda es obligatorio']);
        exit;
    }

    $search_param = trim((string) $request['search_param']);
    if (mb_strlen($search_param) > 100) {
        http_response_code(400);
        echo json_encode(['error' => 'El criterio de búsqueda es demasiado largo']);
        exit;
    }

    try {
        // Búsqueda con LIKE (puede devolver múltiples resultados)
        $shipment_stmt = $conn->prepare("SELECT `origen`, `hbl`, `ci`, `status`, `manifest`, `route_id` 
                                        FROM `shipments` 
                                        WHERE `hbl` LIKE ?");
        $search_pattern = "%" . $search_param . "%";
        $shipment_stmt->bind_param("s", $search_pattern);
        $shipment_stmt->execute();
        $shipment_result = $shipment_stmt->get_result();
        
        // Obtener todos los envíos encontrados
        $shipments = [];
        while ($row = $shipment_result->fetch_assoc()) {
            $shipments[] = $row;
        }

        // Si no se encontraron envíos
        if (empty($shipments)) {
            http_response_code(404);
            echo json_encode(['error' => 'No se encontraron envíos con ese criterio']);
            exit;
        }

        // Obtener datos de todos los clientes relacionados
        // Extraer todos los CI únicos de los envíos
        $cis = array_unique(array_column($shipments, 'ci'));
        
        // Preparar consulta para obtener todos los clientes de una vez
        if (!empty($cis)) {
            $placeholders = implode(',', array_fill(0, count($cis), '?'));
            $client_stmt = $conn->prepare("SELECT `ci`, `name` FROM `clients` WHERE `ci` IN ($placeholders)");
            
            // Bind dinámico de parámetros
            $types = str_repeat('s', count($cis));
            $client_stmt->bind_param($types, ...$cis);
            $client_stmt->execute();
            $client_result = $client_stmt->get_result();
            
            // Crear array de clientes indexado por CI
            $clients_by_ci = [];
            while ($row = $client_result->fetch_assoc()) {
                $clients_by_ci[$row['ci']] = $row['name'];
            }
            $client_stmt->close();
        }

        // Construir respuesta combinando envíos con sus clientes
        $response = [];
        foreach ($shipments as $shipment) {
            // Combinar datos del envío con el nombre del cliente (si existe)
            $combined = $shipment;
            $combined['name'] = $clients_by_ci[$shipment['ci']] ?? 'Cliente no encontrado';
            $combined['origen'] = getInfoById($shipment['origen'], 'origen')['name'];
            $combined['status'] = modStatus($shipment['status']);
            $response[] = $combined;
        }

        // Devolver respuesta
        echo json_encode($response);

        $shipment_stmt->close();

    } catch (Exception $e) {
        error_log("Error en searchShipmentsInfo.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al obtener la información de los envíos']);
    }

    $conn->close();

    function modStatus(string $status) {
        if ($status == 'draft') {
            return 'Borrador';
        } else if ($status == 'delivering') {
            return 'Entregando';
        } else if ($status == 'finished') {
            return 'Terminado';
        } else if ($status == 'warehouse') {
            return 'Almacen';
        } else if ($status == 'detained') {
            return 'Detenido';
        }
    }
?>