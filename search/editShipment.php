<?php
session_start();

// Verificar sesión
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
} else {
    if (time() - $_SESSION["login_time_stamp"] > 600) {
        session_unset();
        session_destroy();
        header("Location: ../login.php");
    }
}

// Validate CI input
if (isset($_GET['hbl'])) {
    $hbl = $_GET['hbl'];
    
    // Adjust pattern based on your CI format (e.g., numbers, letters, hyphens)
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $hbl)) {
        $_SESSION['error_message'] = "Invalid client ID format";
        header("Location: ./details.php");
        exit();
    }
} else {
    header("Location: ./shipments.php");
    exit();
}

include '../api/getShipmentsDetails.php';
include '../api/getDeliveryInfo.php';
include '../api/sanitizeStatus.php';
    
shipmentsDetails($hbl);
getGlobalInfo();
$statusList = getStatusList();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title>Editar Envio</title>
</head>
<body>
    <?php include '../header.php' ?>

    <div class="container p-3 flex-column align-items-center">
        <div class="header">
            <h1>Editar Envio</h1>
        </div>
        <form method="POST" action="../api/updateShipment.php">
            <div class="form-control">
                <div class="row p-2">
                    <div class="col">
                        <label for="client_name" class="form-label">HBL</label>  
                        <input type="text" class="form-control" id="shipment_hbl" name="shipment_hbl" value="<?php echo $shipment_hbl ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="client_ci" class="form-label">Nombre y Apellidos</label>  
                        <input type="text" class="form-control" id="shipment_owner_name" name="shipment_owner_name" value="<?php echo $shipment_owner_name ?>" autocomplete="off" required>
                    </div>
                    <div class="col">
                        <label for="client_ci" class="form-label">Carnet de Identidad</label>  
                        <input type="text" class="form-control" id="shipment_owner_ci" name="shipment_owner_ci" value="<?php echo $shipment_owner_ci ?>" autocomplete="off" required>
                    </div>
                    <div class="col">
                        <label for="shipment_origen" class="form-label">Origen</label>
                        <select id="shipment_origen" class="form-control" name="shipment_origen" required>
                            <option name="default_origen" value="">Seleccione...</option>
                            <?php if (isset($origen_stmt) && $origen_stmt): ?>
                                <?php while($origen = $origen_stmt->fetch_assoc()): ?>
                                    <option value="<?php echo htmlspecialchars($origen['id']); ?>"
                                        <?php if($origen['id'] == $shipment_origen) {
                                                echo "selected";
                                            }
                                        ?>>
                                        <?php echo htmlspecialchars($origen['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="client_address" class="form-label">Peso</label>  
                        <input type="textarea" class="form-control" id="shipment_weight" name="shipment_weight" value="<?php echo $shipment_weight ?>" autocomplete="off" required>
                    </div>
                    <div class="col">
                        <label for="client_city" class="form-label">Arancel</label>  
                        <input type="text" class="form-control" id="shipment_tariff" name="shipment_tariff" value="<?php echo $shipment_tariff ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="client_state" class="form-label">Manifiesto</label>  
                        <input type="text" class="form-control" id="shipment_manifest" name="shipment_manifest" value="<?php echo $shipment_manifest ?>" autocomplete="off" required>
                    </div>
                    <div class="col">
                        <label for="shipment_status" class="form-label">Estado</label>
                        <select id="shipment_status" class="form-control" name="shipment_status" required>
                            <option name="default_status" value="">Seleccione...</option>
                            <?php if (isset($statusList) && $statusList): ?>
                                <?php foreach($statusList as $statusKey => $status): ?>
                                    <option value="<?php echo htmlspecialchars($statusKey); ?>"
                                        <?php if($statusKey == $shipment_status_raw) {
                                                echo "selected";
                                            }
                                        ?>>
                                        <?php echo htmlspecialchars($status); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col">
                        <label for="client_state" class="form-label">Ruta</label>  
                        <input type="text" class="form-control" id="shipment_route_id" name="shipment_route_id" value="<?php echo $shipment_route_id ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="client_name" class="form-label">Descripcion</label>  
                        <input type="text" class="form-control" id="shipment_description" name="shipment_description" value="<?php echo $shipment_description ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="container">
                    <div class="row p-2">
                        <a class="btn btn-warning" href="./shipmentsDetails.php?hbl=<?php echo urlencode($shipment_hbl) ?>">Atras</a>
                    </div>
                    <div class="row p-2">
                        <input type="submit" class="btn btn-success" value="Guardar">
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>