<?php

function getInfoById(int $id, string $type) {
    include 'db_connect.php';
    
    global $origen_stmt, $drivers_stmt, $vehicule_stmt;
    
    try {
        if($type === 'origen') {
            $origen_stmt = $conn->prepare("SELECT * FROM `origen` WHERE `id` = ?");
            $origen_stmt->bind_param("i", $id);
            $origen_stmt->execute();
            $origen_result = $origen_stmt->get_result();
            $origen = $origen_result->fetch_assoc();
            return $origen;
        } else if($type === 'driver') {
            $driver_stmt = $conn->prepare("SELECT * FROM `drivers` WHERE `id` = ?");
            $driver_stmt->bind_param("i", $id);
            $driver_stmt->execute();
            $driver_result = $driver_stmt->get_result();
            $driver = $driver_result->fetch_assoc();
            return $driver;
        } else if($type === 'vehicule') {
            $vehicule_stmt = $conn->prepare("SELECT * FROM `vehicules` WHERE `id` = ?");
            $vehicule_stmt->bind_param("i", $id);
            $vehicule_stmt->execute();
            $vehicule_result = $vehicule_stmt->get_result();
            $vehicule = $vehicule_result->fetch_assoc();
            return $vehicule;
        }
        
        if (!$origen_stmt || !$drivers_stmt || !$vehicule_stmt) {
            throw new Exception("Error al cargar los datos");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        error_log("Error en get_global_info: " . $e->getMessage());
    }
}

?>