<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}
?>
<?php
if (isset($_GET['id']))
    exportRoute($_GET['id']);

function exportRoute($delivery_id) {

    include '../api/db_connect.php';
    include '../api/getInfoById.php';

    try {
        $delivery_stmt = $conn->prepare("SELECT * FROM `delivery` WHERE `id` = ?");
        $delivery_stmt->bind_param("i", $delivery_id);
        $delivery_stmt->execute();
        $delivery_result = $delivery_stmt->get_result();
        $delivery_data = $delivery_result->fetch_assoc();

        $name = $delivery_data['name'];
        $status = $delivery_data['status'];
        $driver_id = $delivery_data['driver'];
        $vehicule_id = $delivery_data['vehicule'];
        $origen_id = $delivery_data['origen'];
        $date = date("d/m/Y");

        if($status == 'draft') {
            $status_showed = "Borrador";
        } else if($status == 'delivering') {
            $status_showed = "Entregando";
        } else if ($status == 'finished') {
            $status_showed = "Terminada";
        }
    } catch (Exception $e) {
        echo $e;
    }

    
?>
<html>
    <head>
        <meta charset="utf-8" />
        <title><?php echo $name ?></title>
        <link type="text/css" rel="stylesheet" href="../resources/css/bootstrap.min.css">
        <link type="text/css" rel="stylesheet" href="../resources/css/custom.css">
    </head>
<body>
    <div class="p-4 d-flex flex-column align-items-left">
        <div class="row">
            <div class="col">
                <img src="../resources/img/logo.png" class="rounded float-left" height="60" width="159">
            </div>
            <div class="col">
                <h3><?php echo getInfoById($origen_id, 'origen')['name'] ?></h3>
            </div>
        </div>
    </div>
    <div class="d-flex flex-column align-items-left">
        <div class="">
            <div class="row" id="header">
                <div class="row" id="subheader1">
                    <div class="col">    
                        <label class="col-sm-2 col-form-label font-weight-bold"><b>ID</b></label>
                        <label class="col-sm-2 col-form-label"><?php echo $delivery_id ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Chofer</b></label>
                        <label class="font-weight-bold"><?php echo getInfoById($driver_id, 'driver')['name'] ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Fecha</b></label> 
                        <label class="col-sm-2 col-form-label font-weight-bold"><?php echo $date ?></label>
                    </div>
                </div>
                <div class="row" id="subheader2">
                    <div class="col">
                        <label class="col-sm-2 col-form-label font-weight-bold"><b>Estado</b></label>    
                        <label class="col-sm-2 col-form-label font-weight-bold"><?php echo $status_showed ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Licencia</b></label> 
                        <label class="col-sm-3 col-form-label"><?php echo getInfoById($driver_id, 'driver')['ci'] ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Matricula</b></label>
                        <label class="col-sm-3 col-form-label font-weight-bold"><?php echo getInfoById($vehicule_id, 'vehicule')['matriculate'] ?></label> 
                    </div>
                </div>
            </div>    
        </div>
    </div>
    <div class="p-4 d-flex flex-column align-items-center" id="subheader3">
        <h4><b><?php echo $name ?></b></h4>
    </div>
    <div class="d-flex flex-column align-items-center">
            <table id="table">
                <thead>
                    <tr class="header">
                        <th scope="col">Nombre</th>
                        <th scope="col">CI</th>
                        <th scope="col">Telefono</th>
                        <th scope="col">Bultos</th>
                        <th scope="col">Arancel</th>
                        <th class="col-md-3" scope="col">Firma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $clients_stmt = $conn->prepare("SELECT * FROM `clients` WHERE `ci` = ?");
                        $count_stmt = $conn->prepare("SELECT COUNT(`ci`) as count FROM `shipments` WHERE `ci` = ? AND `route_id` = ?");
                        $tariff_stmt = $conn->prepare("SELECT COALESCE(SUM(`tariff`), 0) as tariff FROM `shipments` WHERE `ci` = ? AND `route_id` = ?");
                        $shipment_ci = explode(", ", $delivery_data['shipments']);
                        $clients_data = [];
                            
                        foreach ($shipment_ci as $ci => $value) {   
                            $clients_stmt->bind_param("s", $shipment_ci[$ci]);
                            $clients_stmt->execute();
                            $clients_result = $clients_stmt->get_result();
                            $clients_data = $clients_result->fetch_assoc();

                            $count_stmt->bind_param("ss", $shipment_ci[$ci], $delivery_id);
                            $count_stmt->execute();
                            $count_result = $count_stmt->get_result();
                            $count_data = $count_result->fetch_assoc();
                            $clients_data['count'] = $count_data['count'];
                            
                            $tariff_stmt->bind_param("ss", $shipment_ci[$ci], $delivery_id);
                            $tariff_stmt->execute();
                            $tariff_result = $tariff_stmt->get_result();
                            $tariff = $tariff_result->fetch_assoc();

                    ?>
                            <tr>  
                                <td style="font-size: 15px"><?php echo $clients_data['name'] ?></td>
                                <td style="font-size: 15px"><?php echo $clients_data['ci'] ?></td>
                                <td style="font-size: 15px"><?php echo $clients_data['phone'] ?></td>
                                <td style="font-size: 15px"><?php echo $clients_data['count'] ?></td>
                                <td style="font-size: 15px"><?php echo "$" . $tariff['tariff'] ?></td>
                                <td style="font-size: 15px" rowspan="2">
                                    <input type="text" class="form-control" placeholder="">
                                </td>
                            </tr>
                            <tr class="address">
                                <td colspan="6" style="font-size: 15px"><b>Direccion: </b>
                                <?php echo $clients_data['address'] . " " . $clients_data['city']?></td>
                            </tr>
                    <?php

                        }

                    ?>

                    <tr class="total-footer">
                        <td colspan="2"></td>
                        <td class="align-items-right">Total:</td>
                        <td><?php echo $delivery_data['total_shipments'] ?></td>
                        <td><?php echo "$" . $delivery_data['total_tariff'] ?></td>
                    </tr>
                </tbody>
            </table>
    </div>
    <div class="p-5 d-flex flex-column align-items-left">
        <div class="p-3 row">
            <div class="col">
                <h5>Emite: <?php echo $_SESSION['first_name'] . " " . $_SESSION['last_name'] ?></h5>
            </div>
            <div class="col">
                
            </div>
            <div class="col">
                <h5>Entrega: </h5>
            </div>
        </div>
    </div>
    <?php

}

    ?>
</body>
</html>