<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>
<html>
    <head>
        <meta charset="utf-8" />
        <link type="text/css" rel="stylesheet" href="../resources/css/bootstrap.min.css">
        <link type="text/css" rel="stylesheet" href="../resources/css/custom.css">
    </head>
<body>
<?php
if (isset($_GET['id']))
    exportRoute($_GET['id']);

function exportRoute($id) {

    include('../db/db_connect.php');

    try {
        $stmt = $conn->query("SELECT * FROM `delivery` WHERE `id` = $id");
        $route_data = $stmt->fetch_assoc();

        $name = $route_data['name'];
        $status = $route_data['status'];
        $driver = $route_data['driver'];
        $vehicule = $route_data['vehicule'];
        $shipments = $route_data['shipments'];

        $licence_stmt = $conn->query("SELECT * FROM `drivers` WHERE `name` = '$driver'");
        $licence_array = $licence_stmt->fetch_assoc();
        $licence = $licence_array['ci'];
    } catch (Exception $e) {
        echo $e;
    }

    $date = date("d/m/Y");

?>
    <div class="p-4 d-flex flex-column align-items-left">
        <img src="../resources/img/logo.png" class="rounded float-left" height="60" width="159">
    </div>
    <div class="d-flex flex-column align-items-left">
        <div class="">
            <div class="row" id="header">
                <div class="row" id="subheader1">
                    <div class="col">    
                        <label class="col-sm-2 col-form-label font-weight-bold"><b>ID</b></label>
                        <label class="col-sm-2 col-form-label"><?php echo $id ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Chofer</b></label>
                        <label class="font-weight-bold"><?php echo $driver ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Fecha</b></label> 
                        <label class="col-sm-2 col-form-label font-weight-bold"><?php echo $date ?></label>
                    </div>
                </div>
                <div class="row" id="subheader2">
                    <div class="col">
                        <label class="col-sm-2 col-form-label font-weight-bold"><b>Estado</b></label>    
                        <label class="col-sm-2 col-form-label font-weight-bold"><?php echo $status ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Licencia</b></label> 
                        <label class="col-sm-3 col-form-label"><?php echo $licence ?></label>
                    </div>
                    <div class="col">
                        <label class="col-sm-3 col-form-label font-weight-bold"><b>Matricula</b></label>
                        <label class="col-sm-3 col-form-label font-weight-bold"><?php echo $vehicule ?></label> 
                    </div>
                </div>
                <div class="row justify-content-center" id="subheader3">
                    <label class="col-sm-2 col-form-label"><b><?php echo $name ?></b></label>
                </div>
            </div>    
        </div>
    </div>
    <div class="d-flex flex-column align-items-center">
        <div class="form-control">
            <table class="" id="table">
                <thead>
                    <tr class="header">
                        <th scope="col">Nombre</th>
                        <th scope="col">CI</th>
                        <th scope="col">Telefono</th>
                        <th scope="col">Bultos</th>
                        <th scope="col">Arancel</th>
                        <th scope="col">Firma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $full_clients_stmt = $conn->query("SELECT * FROM `clients` ORDER BY `name`");
                        $clients_stmt = $conn->query("SELECT `shipments` FROM `delivery` WHERE `id` = $id;");
                        $clients_array = $clients_stmt->fetch_assoc();
                        $clients_ci = explode(", ", $clients_array['shipments']);
                            
                        while ($row = $full_clients_stmt->fetch_assoc()) {   
                            $ci = $row['ci'];
                            $tariff = 0;
                            if(in_array($ci, $clients_ci)) {
                                $pkts_result = $conn->query("SELECT COUNT(`ci`) as count FROM `shipments` WHERE `ci` = $ci AND `status` != 'finished'");
                                $pkts_row = $pkts_result->fetch_assoc();
                                $pkts = $pkts_row['count'];

                                $tariff_result = $conn->query("SELECT `tariff` FROM `shipments` WHERE `ci` = $ci AND `status` != 'finished'");
                                while($tariff_row = $tariff_result->fetch_assoc()){
                                    $tariff += $tariff_row['tariff'];
                                }
                    ?>
                                <tr>  
                                    <td style="font-size: 15px"><?php echo $row['name'] ?></td>
                                    <td style="font-size: 15px"><?php echo $row['ci'] ?></td>
                                    <td style="font-size: 15px"><?php echo $row['phone'] ?></td>
                                    <td style="font-size: 15px"><?php echo $pkts ?></td>
                                    <td style="font-size: 15px"><?php echo $tariff ?></td>
                                    <td style="font-size: 15px" rowspan="2">
                                        <input type="text" class="form-control" placeholder="FIRMA DEL CLIENTE">
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" style="font-size: 15px"><b>Direccion: </b><?php echo $row['address']?></td>
                                </tr>
                    <?php
                            } else {
                                continue;
                            }
                        }
                    ?>
                </tbody>
            </table>
        </div>
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