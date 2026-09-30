<?php
require_once __DIR__ . '/../api/auth.php';
requireLogin(true);

// Validate CI input
if (isset($_GET['hbl'])) {
    $hbl = $_GET['hbl'];

    // Adjust pattern based on your CI format (e.g., numbers, letters, hyphens)
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $hbl)) {
        $_SESSION['error_message'] = "Invalid client ID format";
        header("Location: ../search/shipments.php");
        exit();
    }
    
    include '../api/getShipmentsDetails.php';
    include '../api/getInfoById.php';
    include '../api/sanitizeStatus.php';

    shipmentsDetails($hbl);
    
} else {
    header("Location: ../search/shipments.php");
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
                <h2><label class=""><?php echo htmlspecialchars($shipment_hbl, ENT_QUOTES, 'UTF-8'); ?></label></h2>
            </div>
            <div class="row p-2">
                <div class="col">
                    <h5><label class="">Cliente: <?php echo htmlspecialchars($shipment_owner_name, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
                <div class="col">
                    <h5><label class="">CI: <?php echo htmlspecialchars($shipment_owner_ci, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
                <div class="col">
                    <h5><label class="">Origen: <?php echo htmlspecialchars(getInfoById($shipment_origen, 'origen')['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
            </div>
            <div class="row p-2">
                <div class="col">
                    <h5><label class="">Peso: <?php echo htmlspecialchars($shipment_weight, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
                <div class="col">
                    <h5><label class="">Arancel: <?php echo htmlspecialchars($shipment_tariff, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
            </div>
            <div class="row p-2">
                <div class="col">
                    <h5><label class="">Manifiesto: <?php echo htmlspecialchars($shipment_manifest, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
                <div class="col">
                    <h5><label class="">Estado: <?php echo htmlspecialchars(getStatusList()[$shipment_status_raw] ?? $shipment_status_raw, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
                <div class="col">
                    <h5><label class="">Ruta: <?php echo htmlspecialchars($shipment_route_id, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
            </div>
            <div class="row p-2">
                <div class="col">
                    <h5><label class="">Descripcion: <?php echo htmlspecialchars($shipment_description, ENT_QUOTES, 'UTF-8'); ?></label></h5>
                </div>
            </div>
            <div class="row p-2">
                <a class="btn btn-dark" href="./editShipment.php?hbl=<?php echo urlencode($shipment_hbl) ?>">Editar Envio</a>
            </div>
            <div class="row p-2">
                <a class="btn btn-warning" href="../search/shipments.php">Atras</a>
            </div>
        </div>
    </div>

    <!-- Optional: Add Bootstrap JS for alert dismissal -->
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
</body>
</html>