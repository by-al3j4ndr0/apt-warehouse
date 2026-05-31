<?php
include '../api/db_connect.php';

// Configuración
$targetDir = "../uploads/";
$allowedTypes = ['text/csv', 'text/plain'];
$maxFileSize = 1 * 1024 * 1024; // 1MB

// Configurar respuesta como JSON
header('Content-Type: application/json');

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
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function procesarCSV($archivo) {
    include '../api/db_connect.php';    

    $stats = ['shipments' => 0, 'clients' => 0, 'total' => 0];
    
    if (($handle = fopen($archivo, 'r')) !== false) {
        // Leer encabezados
        $headers = fgetcsv($handle);
        
        $conn->begin_transaction();
        
        try {
            // Preparar sentencias
            $stmt_shipment = $conn->prepare("INSERT INTO shipments (hbl, origen, ci, weight, description, tariff, status) VALUES (?, ?, ?, ?, ?, ?, 'warehouse')");
            $stmt_check_client = $conn->prepare("SELECT ci FROM clients WHERE ci = ?");
            $stmt_client = $conn->prepare("INSERT INTO clients (ci, name, phone, address, city, state) VALUES (?, ?, ?, ?, ?, ?)");
            
            while (($row = fgetcsv($handle)) !== false) {
                $data = array_combine($headers, $row);
                
                // Insertar shipment
                $stmt_shipment->bind_param(
                    "sssdss",
                    $data['hbl'],
                    $data['origen'],
                    $data['ci'],
                    $data['weight'],
                    $data['description'],
                    $data['tariff']
                );
                $stmt_shipment->execute();
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
                    $stmt_client->execute();
                    $stats['clients']++;
                }
            }
            
            $stats['total'] = $stats['shipments'];
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            throw new Exception("Error procesando CSV: " . $e->getMessage());
        }
        
        fclose($handle);
    }
    
    return $stats;
}
?>