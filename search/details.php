<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Validate CI input
if (isset($_GET['ci'])) {
    $ci = filter_input(INPUT_GET, 'ci', FILTER_SANITIZE_STRING);
    
    // Adjust pattern based on your CI format (e.g., numbers, letters, hyphens)
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $ci)) {
        $_SESSION['error_message'] = "Invalid client ID format";
        header("Location: ./clients.php");
        exit();
    }
    
    include '../api/getClientsDetails.php';
    
    clientDetails($ci);
    getClientShipments($ci);

} else {
    header("Location: ./clients.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <script src="../resources/js/custom.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title>Detalles</title>
</head>

<body>
    <?php include '../header.php' ?>

    <!-- Display error message if exists -->
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Display success message if exists -->
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8');
                unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col container p-5 align-items-left">
            <div class="row p-2">
                <h2><label class=""><?php echo $client_name ?></label></h2>
            </div>
            <div class="row p-2">
                <div class="col">
                    <h5><label class="">CI: <?php echo $client_ci ?></label></h5>
                </div>
                <div class="col">
                    <h5><label class="">Teléfono: <?php echo $client_phone ?></label></h5>
                </div>
            </div>
            <div class="row flex p-2">
                <h5><label class="">Dirección: <?php echo $client_address . ", " . $client_city . ", " . $client_state ?></label></h5>
            </div>
            <div class="row p-2">
                <a class="btn btn-dark" href="./editClient.php?ci=<?php echo urlencode($client_ci) ?>">Editar Cliente</a>
            </div>
            <div class="row p-2">
                <a class="btn btn-warning" href="../search/clients.php">Atras</a>
            </div>
        </div>
        <div class="col container p-5 align-items-left">
            <div class="row align-items-center">
                <h2><label class="">ENVIOS</label></h2>
            </div>
            <div class="row">
                <table id="table" class="table table-striped">
                    <thead>
                        <tr>
                            <th>HBL</th>
                            <th>Ruta</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    if ($shipment_info_result && $shipment_info_result->num_rows > 0) {
                        while($shipment_info_data = $shipment_info_result->fetch_assoc()) {
                            $shipment_hbl = htmlspecialchars($shipment_info_data['hbl'], ENT_QUOTES, 'UTF-8');
                            $shipment_route_id = htmlspecialchars($shipment_info_data['route_id'], ENT_QUOTES, 'UTF-8');
                            $shipment_status = htmlspecialchars($shipment_info_data['status'], ENT_QUOTES, 'UTF-8');

                            // Translate status
                            if($shipment_status == "warehouse") {
                                $shipment_status_display = "Almacen";
                            } else if($shipment_status == "draft") {
                                $shipment_status_display = "Borrador";
                            } else if($shipment_status == "delivering") {
                                $shipment_status_display = "Entregando";
                            } else if($shipment_status == "finished") {
                                $shipment_status_display = "Terminada";
                            } else {
                                $shipment_status_display = $shipment_status;
                            }
                    ?>
                        <tr>
                            <td><?php echo $shipment_hbl ?></td>
                            <td><?php echo $shipment_route_id ?></td>
                            <td>
                                <?php 
                                // Add status badge for better UX
                                $badge_class = '';
                                switch($shipment_status) {
                                    case 'warehouse':
                                        $badge_class = 'bg-info';
                                        break;
                                    case 'draft':
                                        $badge_class = 'bg-secondary';
                                        break;
                                    case 'delivering':
                                        $badge_class = 'bg-warning';
                                        break;
                                    case 'finished':
                                        $badge_class = 'bg-success';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $badge_class ?>"><?php echo $shipment_status_display ?></span>
                            </td>
                        </tr>
                    <?php 
                        }
                    } else {
                    ?>
                        <tr>
                            <td colspan="3" class="text-center">No hay envíos registrados para este cliente</td>
                        </tr>
                    <?php
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Optional: Add Bootstrap JS for alert dismissal -->
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
</body>
</html>
