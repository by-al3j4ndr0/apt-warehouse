<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
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
    <script src="../resources/js/bootstrap.min.js"></script>
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
        <form action="../db/update_route.php" method="post">
            <div class="form-control">
                <input type="hidden" value="<?php echo $id ?>" id="id" name="id">
                <div class="row">
                    <div class="col">
                        <label for="formGroupExampleInput">Nombre</label>  
                        <input type="text" class="form-control" id="name" name="name" placeholder="<?php echo $name['name'] ?>" autocomple="nope" readonly>
                    </div>
                    <div class="col">
                        <label for="formGroupExampleInput">Origen</label>  
                        <select id="origen" class="form-control" name="origen">
                            <?php
                                include '../db/db_connect.php';

                                $origen_stmt = $conn->query("SELECT `orgien` FROM `delivery` WHERE `id` = $id");
                                while ($origen_row = $origen_stmt->fetch_assoc()) {
                            ?>
                            <option id=""><?php echo $origen_row['orgien'] ?></option>
                            <?php
                                }
                            ?>
                        </select>
                    </div>
                </div>
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

                            // 1. Use prepared statements to prevent SQL injection
                            $delivery_stmt = $conn->prepare("SELECT `shipments` FROM `delivery` WHERE `id` = ?");
                            $delivery_stmt->bind_param("i", $id);
                            $delivery_stmt->execute();
                            $delivery_result = $delivery_stmt->get_result();
                            $delivery_data = $delivery_result->fetch_assoc();
                            $delivery_stmt->close();

                            // 2. Get the list of client IDs from shipments
                            $client_ids_in_shipments = [];
                            if (!empty($delivery_data['shipments'])) {
                                $client_ids_in_shipments = array_map('trim', explode(",", $delivery_data['shipments']));
                            }

                            // 3. Get all clients ordered by city
                            $client_stmt = $conn->prepare("SELECT * FROM `clients` ORDER BY `city` ASC");
                            $client_stmt->execute();
                            $client_result = $client_stmt->get_result();

                            // 4. Prepare queries for shipment counts to reuse them
                            $warehouse_stmt = $conn->prepare("SELECT COUNT(`ci`) as count FROM `shipments` WHERE `ci` = ? AND `status` = 'warehouse'");
                            $delivering_stmt = $conn->prepare("SELECT COUNT(`ci`) as count FROM `shipments` WHERE `ci` = ? AND `route_id` = ?");

                            // 5. Process each client
                            while ($client = $client_result->fetch_assoc()) {
                                $client_id = $client['ci'];
                                $is_in_shipments = in_array($client_id, $client_ids_in_shipments);
                                
                                // Get the appropriate count based on shipment status
                                if ($is_in_shipments) {
                                    $delivering_stmt->bind_param("ss", $client_id, $id);
                                    $delivering_stmt->execute();
                                    $count_result = $delivering_stmt->get_result();
                                    $count_data = $count_result->fetch_assoc();
                                    $shipment_count = $count_data['count'];
                                    
                                    if ($shipment_count === 0) {
                                        continue;
                                    }
                                } else {
                                    $warehouse_stmt->bind_param("s", $client_id);
                                    $warehouse_stmt->execute();
                                    $count_result = $warehouse_stmt->get_result();
                                    $count_data = $count_result->fetch_assoc();
                                    $shipment_count = $count_data['count'];
                                    
                                    if ($shipment_count === 0) {
                                        continue;
                                    }
                                }
                                
                                // Output or process the client data
                                $checkbox_status = $is_in_shipments ? 'checked' : '';                         
                                
                    ?>
                                <tr>  
                                    <td><input class="form-check-input" type="checkbox" value="<?php echo $client['ci']; ?>" name="clients[]" <?php echo $checkbox_status ?>></td>
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
            <a class="btn btn-primary mb-2"
            href="../db/finish_route.php?id=<?php echo $id ?>" style="font-weight:bolder;color:white;">
            Finalizar</a>
        </div>
        <?php } ?>
        </form>
    </div>
</body>
</html>