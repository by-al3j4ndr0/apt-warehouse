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
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <script src="../resources/js/custom.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"
  content="width=device-width, initial-scale=1.0">
    <title>Rutas</title>
</head>

<body>
    <nav class="navbar navbar-expand-sm navbar-light bg-success">
        <div class="container">
            <a class="navbar-brand" href="../index.php" style="font-weight:bold; color:white;">Inicio</a>
            <a class="navbar-brand" href="../clients/clients.php" style="font-weight:bold; color:white;">Clientes</a>
            <a class="navbar-brand" href="shipments.php" style="font-weight:bold; color:white;">Rutas</a>
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapsibleNavId" aria-controls="collapsibleNavId" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="collapsibleNavId">
                <ul class="navbar-nav m-auto mt-2 mt-lg-0">
                </ul>
                <form class="d-flex my-2 my-lg-0">
                    <a href="../logout.php" class="btn btn-light my-2 my-sm-0"
                      type="submit" style="font-weight:bolder;color:green;">
                        Cerrar Sesion</a>
                </form>
            </div>
        </div>
    </nav>

    <div class="container p-5 d-flex flex-column align-items-left">
        <form action="../db/create_route.php" method="post">
            <div class="form-control">
                    <label for="formGroupExampleInput">Nombre</label>  
                    <input type="text" class="form-control" id="name" name="name" placeholder="(XXX-00) 00/00" autocomple="nope">
                <div class="row">
                    <div class="col">
                        <label for="formGroupExampleInput">Chofer</label> 
                        <select id="driver" class="form-control" name="driver">
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

                        $client_stmt = $conn->prepare("SELECT * FROM `clients` ORDER BY `city` ASC");
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
                            <td><input class="form-check-input" type="checkbox" value="<?php echo $row['ci']; ?>" name="clients[]"></td>
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
</body>
</html>