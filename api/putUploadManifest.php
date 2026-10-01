<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_connect.php';
requireLogin(true);
requireCsrf();

// Configuración
$targetDir = "../uploads/";
$allowedTypes = ['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/csv'];
$maxFileSize = 10 * 1024 * 1024; // 10MB (corregido)

// Configurar respuesta como JSON y charset UTF-8
header('Content-Type: application/json; charset=utf-8');

// Configurar para manejar caracteres especiales
setlocale(LC_ALL, 'es_ES.UTF-8');
mb_internal_encoding('UTF-8');

// Variables para logs
$errorLog = [];
$warningLog = [];

try {
    // Verificar si se recibió archivo
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['archivo'])) {
        throw new Exception('No se recibió ningún archivo', 400);
    }
    
    $file = $_FILES['archivo'];
    
    // Verificar errores de subida
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamaño máximo permitido por el servidor',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamaño máximo del formulario',
            UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
            UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
            UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal',
            UPLOAD_ERR_CANT_WRITE => 'Error al escribir el archivo en el disco',
            UPLOAD_ERR_EXTENSION => 'Extensión de archivo no permitida'
        ];
        $errorMsg = isset($uploadErrors[$file['error']]) ? $uploadErrors[$file['error']] : 'Error desconocido';
        throw new Exception($errorMsg, 400);
    }
    
    // Validate both MIME signature and extension. Never trust the client-supplied MIME type.
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['csv', 'txt'], true)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten archivos CSV o TXT.', 400);
    }

    $fileType = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($fileType === false || !in_array($fileType, $allowedTypes, true)) {
        throw new Exception('El contenido del archivo no corresponde a un CSV/TXT permitido.', 400);
    }

    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception('La carga del archivo no es válida.', 400);
    }
    
    // Validar tamaño
    if ($file['size'] > $maxFileSize) {
        throw new Exception('El archivo es demasiado grande. Máximo ' . ($maxFileSize / 1024 / 1024) . 'MB', 413);
    }
    
    // Create the storage directory with restrictive permissions.
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0750, true)) {
            throw new Exception('No se pudo crear el directorio de uploads');
        }
    }
    if (!is_writable($targetDir)) {
        throw new Exception('El directorio de uploads no es escribible.');
    }

    // Generate an opaque server-side filename; never reuse the client filename.

    $fileName = date('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
    $targetFile = $targetDir . $fileName;
    
    // Mover archivo subido
    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        throw new Exception('Error al guardar el archivo en el servidor');
    }
    
    // Procesar según extensión
    $stats = [];
    if ($extension == 'csv' || $extension == 'txt') {
        $stats = procesarCSV($targetFile, $errorLog, $warningLog);
    } else {
        throw new Exception('Formato de archivo no soportado: ' . $extension);
    }
    
    // Eliminar archivo temporal después de procesar
    if (file_exists($targetFile)) {
        unlink($targetFile);
    }
    
    // Construir mensaje de respuesta
    $message = "✅ Archivo procesado correctamente\n\n";
    $message .= "📊 Resumen:\n";
    $message .= "• Shipments insertados: {$stats['shipments']}\n";
    $message .= "• Clientes nuevos: {$stats['clients']}\n";
    $message .= "• Total registros: {$stats['total']}\n";
    
    if (!empty($warningLog)) {
        $message .= "\n⚠️ Advertencias: " . count($warningLog) . " registros con problemas";
    }
    
    if (!empty($errorLog)) {
        $message .= "\n❌ Errores: " . count($errorLog) . " registros no procesados";
    }
    
    // Estructura de respuesta más informativa
    echo json_encode([
        'success' => true,
        'message' => $message,
        'stats' => $stats,
        'details' => [
            'filename' => $file['name'],
            'processed_at' => date('Y-m-d H:i:s'),
            'errors' => $errorLog,
            'warnings' => $warningLog
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Return an HTTP error instead of reporting validation failures as HTTP 200.
    $statusCode = (int) $e->getCode();
    if (!in_array($statusCode, [400, 413, 500], true)) {
        $statusCode = 500;
    }
    http_response_code($statusCode);

    // Limpiar archivo temporal en caso de error
    if (isset($targetFile) && file_exists($targetFile)) {
        unlink($targetFile);
    }
    
    echo json_encode([
        'success' => false,
        'message' => '❌ Error: ' . $e->getMessage(),
        'details' => [
            'errors' => $errorLog,
            'warnings' => $warningLog
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function detectarCodificacionCSV($archivo) {
    if (!file_exists($archivo) || !is_readable($archivo)) {
        return 'UTF-8';
    }
    
    $handle = fopen($archivo, 'r');
    if ($handle === false) {
        return 'UTF-8';
    }
    
    $linea = fgets($handle);
    fclose($handle);
    
    if ($linea === false) {
        return 'UTF-8';
    }
    
    // Remover BOM primero
    if (substr($linea, 0, 3) == "\xEF\xBB\xBF") {
        return 'UTF-8-BOM';
    }
    
    // Array de codificaciones a probar (orden de prioridad)
    $codificaciones = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'ISO-8859-15', 'CP1252'];
    
    foreach ($codificaciones as $cod) {
        if (mb_check_encoding($linea, $cod)) {
            return $cod;
        }
    }
    
    return 'ISO-8859-1';
}

function convertirUTF8($texto) {
    if (empty($texto)) return '';
    
    // Si es null o no es string, devolver vacío
    if ($texto === null) return '';
    if (!is_string($texto)) return (string)$texto;
    
    // Intentar detectar codificación
    $codificacion = mb_detect_encoding($texto, ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'], true);
    
    if ($codificacion !== false && $codificacion !== 'UTF-8') {
        $texto = mb_convert_encoding($texto, 'UTF-8', $codificacion);
    }
    
    // Limpiar caracteres no deseados pero mantener los del español
    $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    
    // Eliminar caracteres de control (excepto saltos de línea)
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $texto);
    
    return trim($texto);
}

function procesarCSV($archivo, &$errorLog = [], &$warningLog = []) {
    include '../api/db_connect.php';
    
    // Configuración
    $conn->set_charset("utf8mb4");
    $conn->query("SET SESSION wait_timeout = 600");
    
    $stats = ['shipments' => 0, 'clients' => 0, 'total' => 0, 'duplicates' => 0];
    
    if (!file_exists($archivo)) {
        throw new Exception("Archivo no encontrado: $archivo");
    }
    
    if (($handle = fopen($archivo, 'r')) === false) {
        throw new Exception("No se pudo abrir el archivo: $archivo");
    }
    
    try {
        // Leer encabezados
        $headers = fgetcsv($handle);
        if ($headers === false) {
            throw new Exception("El archivo CSV está vacío o no tiene encabezados válidos");
        }
        
        // Limpiar encabezados
        $headers = array_map(function($header) {
            $header = trim($header);
            $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
            return convertirUTF8($header);
        }, $headers);
        
        // Mapeo de columnas (flexible)
        $columnMap = [
            'hbl' => ['hbl', 'hb/l', 'hawb', 'numero_guia', 'guia'],
            'origen' => ['origen', 'origin', 'origenes', 'pais_origen'],
            'ci' => ['ci', 'cedula', 'cedula_identidad', 'id', 'documento', 'identificacion'],
            'weight' => ['weight', 'peso', 'kg', 'kilos', 'peso_kg'],
            'description' => ['description', 'descripcion', 'desc', 'detalle', 'contenido'],
            'tariff' => ['tariff', 'tarifa', 'arancel', 'flete', 'costo', 'valor', 'precio'],
            'manifest' => ['manifest', 'manifiesto', 'guia', 'numero_guia', 'referencia'],
            'name' => ['name', 'nombre', 'cliente', 'razon_social', 'cliente_nombre'],
            'phone' => ['phone', 'telefono', 'celular', 'contacto', 'telefono_contacto'],
            'address' => ['address', 'direccion', 'domicilio', 'calle'],
            'city' => ['city', 'ciudad', 'localidad', 'municipio'],
            'state' => ['state', 'estado', 'provincia', 'region', 'departamento']
        ];
        
        // Encontrar índices de columnas
        $columnIndex = [];
        foreach ($columnMap as $field => $aliases) {
            $found = false;
            foreach ($headers as $index => $header) {
                if (in_array(strtolower(trim($header)), array_map('strtolower', $aliases))) {
                    $columnIndex[$field] = $index;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                throw new Exception("Columna requerida no encontrada: $field. Columnas disponibles: " . implode(', ', $headers));
            }
        }
        
        $conn->begin_transaction();
        
        // Preparar sentencias con mejor manejo
        $stmt_shipment = $conn->prepare("INSERT INTO `shipments` (`hbl`, `origen`, `ci`, `weight`, `description`, `tariff`, `manifest`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, 'warehouse')");
        if (!$stmt_shipment) {
            throw new Exception("Error preparando statement shipments: " . $conn->error);
        }
        
        $stmt_client = $conn->prepare("INSERT INTO `clients` (`ci`, `name`, `phone`, `address`, `city`, `state`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `name` = VALUES(name), `phone` = VALUES(phone), `address` = VALUES(address), `city` = VALUES(city), `state` = VALUES(state)");
        if (!$stmt_client) {
            throw new Exception("Error preparando statement clients: " . $conn->error);
        }
        $rowNumber = 1;
        $errorLog = [];
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Convertir a UTF-8
            $row = array_map('convertirUTF8', $row);
            
            // Extraer datos por índice
            $data = [];
            foreach ($columnIndex as $field => $index) {
                $data[$field] = isset($row[$index]) ? trim($row[$index]) : '';
            }
            
            // === VALIDACIÓN CORREGIDA ===
            $requiredFields = ['hbl', 'origen', 'ci', 'weight', 'description', 'tariff', 'manifest', 'name', 'phone', 'address', 'city', 'state'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if ($field === 'tariff') {
                    // Para tariff: permitir 0, solo verificar que exista
                    if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                        $missingFields[] = $field;
                    }
                } else {
                    // Para otros campos: no permitir vacío
                    if (!isset($data[$field]) || trim($data[$field]) === '') {
                        $missingFields[] = $field;
                    }
                }
            }
            
            if (!empty($missingFields)) {
                $errorLog[] = "Fila $rowNumber: Campos faltantes: " . implode(', ', $missingFields);
                continue;
            }

            // Validate identifiers and field sizes before touching the database.
            if (!preg_match('/^[A-Za-z0-9-]+$/', $data['hbl'])
                || mb_strlen($data['hbl']) > 64
                || !preg_match('/^[A-Za-z0-9-]+$/', $data['ci'])
                || mb_strlen($data['ci']) > 64
                || !ctype_digit($data['origen'])
                || (int) $data['origen'] < 1
                || mb_strlen($data['description']) > 1000
                || mb_strlen($data['manifest']) > 255
                || mb_strlen($data['name']) > 255
                || mb_strlen($data['phone']) > 64
                || mb_strlen($data['address']) > 255
                || mb_strlen($data['city']) > 128
                || mb_strlen($data['state']) > 128) {
                $errorLog[] = "Fila $rowNumber: Uno o más campos tienen formato o longitud inválidos.";
                continue;
            }

            // Validar peso (debe ser numérico y > 0)
            if (!is_numeric($data['weight']) || floatval($data['weight']) <= 0) {
                $errorLog[] = "Fila $rowNumber: Peso inválido: {$data['weight']}";
                continue;
            }
            
            // Keep each manifest row atomic: a failed client/shipment operation
            // must not leave the other record committed.
            $savepoint = 'row_' . $rowNumber;
            if (!$conn->query("SAVEPOINT \`$savepoint\`")) {
                throw new Exception("No se pudo preparar la transacción de la fila $rowNumber");
            }

            // Insertar/actualizar cliente first.
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
                $errorLog[] = "Fila $rowNumber: Error al insertar cliente: " . $stmt_client->error;
                $conn->query("ROLLBACK TO SAVEPOINT \`$savepoint\`");
                continue;
            }

            if ($conn->affected_rows > 0) {
                $stats['clients']++;
            }

            // Insertar shipment.
            $stmt_shipment->bind_param(
                "sssdsds",
                $data['hbl'],
                $data['origen'],
                $data['ci'],
                $data['weight'],
                $data['description'],
                $data['tariff'],
                $data['manifest']
            );

            if (!$stmt_shipment->execute()) {
                $errorLog[] = "Fila $rowNumber: Error al insertar shipment: " . $stmt_shipment->error;
                $conn->query("ROLLBACK TO SAVEPOINT \`$savepoint\`");
                continue;
            }

            $stats['shipments']++;
            $conn->query("RELEASE SAVEPOINT \`$savepoint\`");
            
            $stats['total'] = $stats['shipments'];
        }
        
        // Si hay errores, mostrar estadísticas
        if (!empty($errorLog)) {
            $totalRows = $rowNumber - 1;
            $errorCount = count($errorLog);
            $successCount = $stats['shipments'];
            
            if ($errorCount > $totalRows * 0.5 && $totalRows > 0) {
                throw new Exception("Demasiados errores ($errorCount de $totalRows registros). Últimos errores: " . 
                    implode(' | ', array_slice($errorLog, -3)));
            }
            
            // Save import errors outside the public uploads area.
            $logDir = __DIR__ . '/../logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0750, true);
            }
            $logFile = $logDir . '/import_errors_' . date('Y-m-d') . '.log';
            foreach ($errorLog as $error) {
                error_log("[" . date('Y-m-d H:i:s') . "] $error\n", 3, $logFile);
            }
        }
        
        $conn->commit();
        
    } catch (Exception $e) {
        $conn->rollback();
        fclose($handle);
        throw $e;
    }
    
    fclose($handle);
    return $stats;
}
?>