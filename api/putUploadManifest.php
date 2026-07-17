<?php
include '../api/db_connect.php';

// Configuración
$targetDir = "../uploads/";
$allowedTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel'];
$maxFileSize = 1 * 1024 * 1024; // 1MB

// Configurar respuesta como JSON y charset UTF-8
header('Content-Type: application/json; charset=utf-8');

// Configurar para manejar caracteres especiales
setlocale(LC_ALL, 'es_ES.UTF-8');
mb_internal_encoding('UTF-8');

try {
    // Verificar si se recibió archivo
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo'])) {
        throw new Exception('No se recibió ningún archivo');
    }
    
    $file = $_FILES['archivo'];
    
    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Extensión de archivo no permitida'
        ];
        $errorMsg = isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : 'Error desconocido';
        throw new Exception($errorMsg);
    }
    
    // Validar tipo de archivo
    $fileType = mime_content_type($file['tmp_name']);
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten archivos Excel o CSV');
    }
    
    // Validar tamaño
    if ($file['size'] > $maxFileSize) {
        throw new Exception('El archivo es demasiado grande. Máximo 10MB');
    }
    
    // Crear directorio si no existe
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . uniqid() . '.' . $extension;
    $targetFile = $targetDir . $fileName;
    
    // Mover archivo subido
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        throw new Exception('Error al guardar el archivo en el servidor');
    }
    
    // Procesar según extensión
    $stats = [];
    if ($extension == 'csv') {
        $stats = procesarCSV($targetFile);
    }
    
    // Eliminar archivo temporal después de procesar (opcional)
    unlink($targetFile);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'message' => "✅ Archivo procesado correctamente\n\n📊 Resumen:\n• Shipments insertados: {$stats['shipments']}\n• Clientes nuevos: {$stats['clients']}\n• Total registros: {$stats['total']}",
        'stats' => $stats
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function detectarCodificacionCSV($archivo) {
    $handle = fopen($archivo, 'r');
    $linea = fgets($handle);
    fclose($handle);
    
    // Detectar codificación
    $codificacion = mb_detect_encoding($linea, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    // Si es UTF-8 con BOM, remover BOM
    if (substr($linea, 0, 3) == "\xEF\xBB\xBF") {
        return 'UTF-8-BOM';
    }
    
    return $codificacion ?: 'ISO-8859-1';
}

function convertirUTF8($texto) {
    if (empty($texto)) return '';
    
    $codificacion = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
    
    if ($codificacion !== 'UTF-8') {
        $texto = mb_convert_encoding($texto, 'UTF-8', $codificacion);
    }
    
    // Limpiar caracteres no deseados pero mantener los del español
    $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    return $texto;
}

function procesarCSV($archivo) {
    include '../api/db_connect.php';    
    // Configurar la conexión a la base de datos para UTF-8
    $conn->set_charset("utf8mb4");

    $stats = ['shipments' => 0, 'clients' => 0, 'total' => 0];
    
    // Detectar codificación del archivo
    $codificacion = detectarCodificacionCSV($archivo);
    
    if (($handle = fopen($archivo, 'r')) !== false) {
        // Leer encabezados y convertir a UTF-8
        $headers = fgetcsv($handle);
        
        // Si el archivo tiene BOM, removerlo del primer encabezado
        if ($codificacion === 'UTF-8-BOM' && !empty($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }
        
        // Convertir encabezados a UTF-8
        foreach ($headers as &$header) {
            $header = convertirUTF8(trim($header));
        }
        
        $conn->begin_transaction();
        
        try {
            // Preparar sentencias con charset UTF-8
            $stmt_shipment = $conn->prepare("INSERT INTO `shipments` (`hbl`, `origen`, `ci`, `weight`, `description`, `tariff`, `status`) VALUES (?, ?, ?, ?, ?, ?, 'warehouse')");
            $stmt_check_client = $conn->prepare("SELECT `ci` FROM `clients` WHERE `ci` = ?");
            $stmt_client = $conn->prepare("INSERT INTO `clients` (`ci`, `name`, `phone`, `address`, `city`, `state`) VALUES (?, ?, ?, ?, ?, ?)");
            
            while (($row = fgetcsv($handle)) !== false) {
                // Convertir cada campo de la fila a UTF-8
                foreach ($row as &$campo) {
                    $campo = convertirUTF8(trim($campo));
                }
                
                // Verificar que la cantidad de columnas coincida
                if (count($headers) !== count($row)) {
                    throw new Exception("Error: El número de columnas no coincide con los encabezados");
                }
                
                $data = array_combine($headers, $row);
                
                // Validar campos requeridos
                $camposRequeridos = ['hbl', 'origen', 'ci', 'weight', 'description', 'tariff', 'name', 'phone', 'address', 'city', 'state'];
                foreach ($camposRequeridos as $campo) {
                    if (!isset($data[$campo]) || empty($data[$campo])) {
                        throw new Exception("Error: Campo '$campo' faltante o vacío en el registro " . ($stats['total'] + 1));
                    }
                }
                
                // Insertar shipment
                $stmt_shipment->bind_param(
                    "sssdsd",
                    $data['hbl'],
                    $data['origen'],
                    $data['ci'],
                    $data['weight'],
                    $data['description'],
                    $data['tariff']
                );
                
                if (!$stmt_shipment->execute()) {
                    throw new Exception("Error al insertar shipment: " . $stmt_shipment->error);
                }
                $stats['shipments']++;
                
                // Verificar si cliente existe
                $stmt_check_client->bind_param("s", $data['ci']);
                $stmt_check_client->execute();
                $result = $stmt_check_client->get_result();
                
                if ($result->num_rows == 0) {
                    // Insertar nuevo cliente
                    $stmt_client->bind_param(
                        "ssssss",
                        $data['ci'],
                        $data['name'],
                        $data['phone'],
                        $data['address'],
                        $data['city'],
                        $data['state']
                    );
                    
                    if (!$stmt_client->execute()) {
                        throw new Exception("Error al insertar cliente: " . $stmt_client->error);
                    }
                    $stats['clients']++;
                }
                
                $stats['total'] = $stats['shipments'];
            }
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            fclose($handle);
            throw new Exception("Error procesando CSV: " . $e->getMessage());
        }
        
        fclose($handle);
    }
    
    return $stats;
}
?>