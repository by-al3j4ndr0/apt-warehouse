<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
} else if (isset($_GET['ci'])) {
    clientDetails($_GET['ci']);
    getClientShipments($_GET['ci']);
}

function clientDetails($ci) {

    include '../api/db_connect.php';

    global $client_name, $client_ci, $client_phone, $client_address, $client_city, $client_state;

    try {

        $client_info_stmt = $conn->prepare("SELECT * FROM `clients` WHERE `ci` = ?");
        $client_info_stmt->bind_param("s", $ci);
        $client_info_stmt->execute();

        $client_info_result = $client_info_stmt->get_result();
        $client_info_data = $client_info_result->fetch_assoc();

        $client_info_stmt->close();

        $client_name = $client_info_data['name'];
        $client_ci = $client_info_data['ci'];
        $client_phone = $client_info_data['phone'];
        $client_address = $client_info_data['address'];
        $client_city = $client_info_data['city'];
        $client_state = $client_info_data['state'];

    } catch (Exception $e) {
         // Rollback on error
        $conn->rollback();
        
        // Log error (in production, use proper logging)
        error_log("Clients details getting error: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Failed to create delivery: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

function getClientShipments($ci) {

    include '../api/db_connect.php';

    global $shipment_info_result;

    try {

        $shipment_info_stmt = $conn->prepare("SELECT `hbl`, `route_id`, `status` FROM `shipments` WHERE `ci` = ?");
        $shipment_info_stmt->bind_param("s", $ci);
        $shipment_info_stmt->execute();

        $shipment_info_result = $shipment_info_stmt->get_result();

        $shipment_info_stmt->close();

    } catch (Exception $e) {
         // Rollback on error
        $conn->rollback();
        
        // Log error (in production, use proper logging)
        error_log("Clients details getting error: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Failed to create delivery: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <meta charset="UTF-8">
    <meta name="viewport"
  content="width=device-width, initial-scale=1.0">
    <title>Detalles</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div class="row">
        <div class="col container p-5 align-items-left">
            <div class="row">
                <h2><label class=""><?php echo $client_name ?></label></h2>
            </div>
            <div class="row">
                <div class="col">
                    <h5><label class="">CI: <?php echo $client_ci ?></label></h5>
                </div>
                <div class="col">
                    <h5><label class="">Telefono: <?php echo $client_phone ?></label></h5>
                </div>
            </div>
            <div class ="row flex">
                <h5><label class="">Direccion: <?php echo $client_address . ", " . $client_city . ", " . $client_state ?></label></h5>
            </div>
            <div class="row">
                <a class="btn btn-dark" href="./edit_client.php?ci=<?php echo $ci ?>">Editar Cliente</a>
            </div>
        </div>
        <div class="col container p-5 align-items-left">
            <div class="row align-items-center">
                <h2><label class="">ENVIOS</label></h2>
            </div>
            <div class="row">
                <table id="table">
                <?php

                    while($shipment_info_data = $shipment_info_result->fetch_assoc()) {
                        $shipment_hbl = $shipment_info_data['hbl'];
                        $shipment_route_id = $shipment_info_data['route_id'];
                        $shipment_status = $shipment_info_data['status'];

                ?>
                        <tbody>
                            <tr>  
                                <td><b>HBL: </b><?php echo $shipment_hbl ?></td>
                                <td rowspan="2">
                                    <b>Estado: </b><?php echo $shipment_status ?>
                                </td>
                            </tr>
                            <tr class="shipments">
                                <td><b>Ruta: </b><?php echo $shipment_route_id ?></td>
                            </tr>
                        </tbody>
                <?php 

                    }

                ?>
                </table>
            </div>
        </div>
    </div>
</body>
