<?php

function getDeliveryInfo(int $delivery_id) {

    include '../api/db_connect.php';

    $delivery_stmt = $conn->prepare("SELECT * FROM `delivery` WHERE `id` = ?");
    $delivery_stmt->bind_param("i", $delivery_id);
    $delivery_stmt->execute();
    $delivery_result = $delivery_stmt->get_result();
    $delivery_data = $delivery_result->fetch_assoc();

    return $delivery_data;

}

function getGlobalInfo() {
    include '../api/db_connect.php';
    
    global $origen_stmt, $drivers_stmt, $vehicule_stmt;
    
    try {
        $origen_stmt = $conn->query("SELECT * FROM `origen`");
        $drivers_stmt = $conn->query("SELECT * FROM `drivers`");
        $vehicule_stmt = $conn->query("SELECT * FROM `vehicules`");
        
        if (!$origen_stmt || !$drivers_stmt || !$vehicule_stmt) {
            throw new Exception("Error al cargar los datos");
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        error_log("Error en get_global_info: " . $e->getMessage());
    }
}

function getSingleFieldInfo(int $fieldId, string $fieldType) {
    include '../api/db_connect.php';

    $origen_stmt = $conn->prepare("SELECT * FROM `origen` WHERE `id` = ?");
    $drivers_stmt = $conn->prepare("SELECT * FROM `drivers` WHERE `id` = ?");
    $vehicule_stmt = $conn->prepare("SELECT * FROM `vehicules` WHERE `id` = ?");

    try {
        if ($fieldType == 'origen') {
            $origen_stmt->bind_param('i', $fieldId);
            $origen_stmt->execute();
            $origen_result = $origen_stmt->get_result();
            return $origen_result;
        } else if ($fieldType == 'drivers') {
            $drivers_stmt->bind_param('i', $fieldId);
            $drivers_stmt->execute();
            $drivers_result = $drivers_stmt->get_result();
            return $drivers_result;
        } else if ($fieldType == 'vehicules') {
            $vehicule_stmt->bind_param('i', $fieldId);
            $vehicule_stmt->execute();
            $vehicule_result = $vehicule_stmt->get_result();
            return $vehicule_result;
        } else {
            throw new Exception("Tipo desconocido");
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        error_log("Error en getSingleFieldInfo: " . $e->getMessage());
    }
}

?>