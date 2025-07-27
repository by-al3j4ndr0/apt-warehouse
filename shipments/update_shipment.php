<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id']))
    updateShipment($_GET['id'])
?>

<?php function updateShipment($id) { 
                include '../db/db_connect.php';

                $name_stmt = $conn->query("SELECT `name` FROM `delivery` WHERE `id` = $id;");
                $name = $name_stmt->fetch_assoc();
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
            <a class="navbar-brand" href="../clients.php" style="font-weight:bold; color:white;">Clientes</a>
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
        <form action="db/update_route.php" method="post">
            <div class="form-control">
                <input type="hidden" value="<?php echo $id ?>" id="id" name="id">
                <label for="formGroupExampleInput">Nombre</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $name['name'] ?>" readonly>
                <div class="row">
                    <div class="col">
                        <label for="formGroupExampleInput">Chofer</label> 
                        <select id="driver" class="form-control" name="driver">
                            <?php
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
    <div class="container d-flex flex-column align-items-left">
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
                            include './db/db_connect.php';

                            $stmt = $conn->query("SELECT * FROM `clients`");
                            $clients_stmt = $conn->query("SELECT `shipments` FROM `delivery` WHERE `id` = $id;");
                            $clients_array = $clients_stmt->fetch_assoc();
                            $clients_ci = explode(", ", $clients_array['shipments']);
                            
                            while ($row = $stmt->fetch_assoc()) {   
                                $ci = $row['ci'];
                                if(in_array($ci, $clients_ci)) {
                                    $checkbox_status = 'checked';
                                } else {
                                    $checkbox_status = '';
                                }
                                $pkts_result = $conn->query("SELECT COUNT(`ci`) as count FROM `shipments` WHERE `ci` = $ci AND `status` != 'finished'");
                                $pkts_row = $pkts_result->fetch_assoc();
                                $pkts = $pkts_row['count'];
                                if($pkts == 0) {
                                    continue;
                                }
                    ?>
                                <tr>  
                                    <td><input class="form-check-input" type="checkbox" value="<?php echo $row['ci']; ?>" name="clients[]" <?php echo $checkbox_status ?>></td>
                                    <td><?php echo $row['name'] ?></td>
                                    <td><?php echo $pkts ?></td>
                                    <td><?php echo $row['city'] ?></td>
                                    <td><?php echo $row['state'] ?></td>
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
            <a class="btn btn-primary mb-2"
            href="db/finish_route.php?id=<?php echo $id ?>" style="font-weight:bolder;color:white;">
            Finalizar</a>
        </div>
        <?php } ?>
        </form>
    </div>
</body>
</html>