<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
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
    <title>Rutas</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div class="p-5 flex-column align-items-center">
        <form action="../db/create_route.php" method="post">
            <div class="form-control">
                <div class="row p-2">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check" name="draft" id="draft" autocomplete="off" checked>
                        <label class="btn btn-outline-warning" for="draft">Borrador</label>

                        <input type="radio" class="btn-check" name="delivering" id="delivering" autocomplete="off">
                        <label class="btn btn-outline-primary" for="delivering">Entregando</label>

                        <input type="radio" class="btn-check" name="finished" id="finished" autocomplete="off">
                        <label class="btn btn-outline-success" for="finished">Terminada</label>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="formGroupExampleInput">Nombre</label>  
                        <input type="text" class="form-control" id="name" name="name" placeholder="(XXX-00) 00/00" autocomple="off">
                    </div>
                    <div class="col">
                        <label for="formGroupExampleInput">Origen</label>
                        <select id="origen" class="form-control" name="origen">
                            <option id="default">Seleccione...</option>
                            <?php
                                include '../db/db_connect.php';

                                $origen_stmt = $conn->query("SELECT * FROM `origen`");
                                while ($origen_row = $origen_stmt->fetch_assoc()) {
                            ?>
                            <option id="<?php echo $origen_row['id']; ?>"><?php echo $origen_row['name'] ?></option>
                            <?php
                                }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="formGroupExampleInput">Chofer</label> 
                        <select id="driver" class="form-control" name="driver">
                            <option id="default">Seleccione...</option>
                            <?php
                                include '../db/db_connect.php';

                                $driver_stmt = $conn->query("SELECT * FROM `drivers`");
                                while ($driver_row = $driver_stmt->fetch_assoc()) {
                            ?>
                            <option id="<?php echo $driver_row['id']; ?>"><?php echo $driver_row['name'] ?></option>
                            <?php
                                }
                            ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="formGroupExampleInput">Vehiculo</label> 
                        <select id="vehicule" class="form-control" name="vehicule">
                            <option id="default">Seleccione...</option>
                            <?php
                                include '../db/db_connect.php';

                                $vh_stmt = $conn->query("SELECT * FROM `vehicules`");
                                while ($vh_row = $vh_stmt->fetch_assoc()) {
                            ?>
                            <option id="<?php echo $vh_row['id']; ?>"><?php echo $vh_row['matriculate'] ?></option>
                            <?php
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
    </div>
    <div class="d-flex flex-column align-items-left">
        <div class="form-control">
            <input type="text" id="search_input" onkeyup="searchTable()" placeholder="Buscar por nombre..">
            <table class="table table-light" id="table">
                <thead>
                    <tr class="header">
                        <th scope="col"></th>
                        <th scope="col" onclick="sortTable(1)">Nombre</th>
                        <th scope="col" onclick="sortTable(2)">Bultos</th>
                        <th scope="col" onclick="sortTable(3)">Municipio</th>
                        <th scope="col" onclick="sortTable(4)">Provincia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        include '../db/db_connect.php';

                        $client_stmt = $conn->prepare("SELECT * FROM `clients` ORDER BY `state` ASC");
                        $client_stmt->execute();
                        $client_result = $client_stmt->get_result();

                        $warehouse_stmt = $conn->prepare("SELECT COUNT(`ci`) as count FROM `shipments` WHERE `ci` = ? AND `status` = 'warehouse'");

                        while ($client = $client_result->fetch_assoc()) {
                            $client_id = $client['ci'];

                            $warehouse_stmt->bind_param("s", $client_id);
                            $warehouse_stmt->execute();
                            $count_result = $warehouse_stmt->get_result();
                            $count_data = $count_result->fetch_assoc();
                            $shipment_count = $count_data['count'];
                                    
                            if ($shipment_count === 0) {
                                continue;
                            }
                    ?>
                        <tr>  
                            <td><input class="form-check-input" type="checkbox" value="<?php echo $client['ci']; ?>" name="clients[]"></td>
                            <td><?php echo $client['name'] ?></td>
                            <td><?php echo $shipment_count ?></td>
                            <td><?php echo $client['city'] ?></td>
                            <td><?php echo $client['state'] ?></td>
                        </tr>
                    <?php 
                        }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="container p-5 d-flex flex-column align-items-right">
        <button class="btn btn-primary mb-2"
            type="submit" style="font-weight:bolder;color:white;">
            Guardar</button>
        </div>
        </form>
    </div>
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <script src="../resources/js/custom.js"></script>
    <script src="../resources/js/delivery.js"></script>
</body>
</html>