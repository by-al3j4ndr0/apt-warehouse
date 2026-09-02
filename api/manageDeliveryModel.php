<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Funciones
function newDelivery() {

    include '../api/getDeliveryInfo.php';

    global $formActionHref;
    global $deliveryId, $deliveryName, $origenId, $driverId, $vehiculeId;
    global $page_tittle, $draft_state, $finished_state, $origen_state, $input_delivering_state;

    getGlobalInfo();
    $page_tittle = "Nueva Ruta";
    $formActionHref = "../api/createDelivery.php";
    $deliveryId = "0";
    $draft_state = "checked";
    $finished_state = "disabled";
    $deliveryName = "";
    $origenId = 0;
    $driverId = 0;
    $vehiculeId = 0;
    $origen_state = "";
    $input_delivering_state = '';

}

function updateDelivery(int $delivery_id) {

    include '../api/getDeliveryInfo.php';
    include '../api/getInfoById.php';

    global $page_tittle;
    global $formActionHref;
    global $draft_state, $finished_state, $origen_state, $delivering_state, $input_delivering_state;
    global $deliveryId, $deliveryName, $origenId, $driverId, $vehiculeId;
    global $origen_stmt, $drivers_stmt, $vehicule_stmt;

    $page_tittle = "Modificar Ruta";
    $formActionHref = "../api/updateDelivery.php";
    $delivery_data = getDeliveryInfo($delivery_id);
    $deliveryId = $delivery_id;  

    if($delivery_data['status'] == 'draft') {
        getGlobalInfo();
        $draft_state = "checked";
        $finished_state = "disabled";
        $origen_state = 'readonly="true"';
        $input_delivering_state = '';
        $deliveryName = $delivery_data['name'];
        $origenId = $delivery_data['origen'];
        $driverId = $delivery_data['driver'];
        $vehiculeId = $delivery_data['vehicule'];
    } else if($delivery_data['status'] == 'delivering') {
        $origen_stmt = getSingleFieldInfo($delivery_data['origen'], 'origen');
        $drivers_stmt = getSingleFieldInfo($delivery_data['driver'], 'drivers');
        $vehicule_stmt = getSingleFieldInfo($delivery_data['vehicule'], 'vehicules');
        $draft_state = "disabled";
        $delivering_state = "checked";
        $origen_state = 'readonly="true"';
        $input_delivering_state = 'readonly="true"';
        $deliveryName = $delivery_data['name'];
        $origenId = $delivery_data['origen'];
        $driverId = $delivery_data['driver'];
        $vehiculeId = $delivery_data['vehicule'];
    }

}
?>
