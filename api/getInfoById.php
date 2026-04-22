
<?php

function getInfoById(int $id, string $type) {
    include 'db_connect.php';
    
    global $orig2en_stmt, $drivers_stmt, $vehicule_stmt;
    
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

?>