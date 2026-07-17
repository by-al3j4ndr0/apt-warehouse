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

    include '../api/getDeliveryInfo.php';

    global $drivers_stmt, $vehicule_stmt;

    getGlobalInfo();

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
    <title>Choferes y Vehiculos</title>
</head>
<body>
    <?php include '../header.php' ?>

    <div class="container p-3 d-flex">
        <h2 class="p-4 mt-5">Choferes</h2>
    </div>
    <div class="container">
        <div class="row">
            <div class="col">
                <select id="driver" class="form-control" name="driver">
                    <option name="default_driver" value="">Seleccione...</option>
                    <?php if (isset($drivers_stmt) && $drivers_stmt): ?>
                        <?php while($drivers = $drivers_stmt->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($drivers['id']); ?>">
                                <?php echo htmlspecialchars($drivers['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col">
                <a href=""><i class="fa fa-edit"></i></a>
                <a href=""><i class="fa fa-trash"></i></a>
            </div>
        </div>
    </div>

    <div class="container p-3 d-flex">
        <h2 class="p-4 mt-5">Vehiculos</h2>
    </div>
    <div class="container">
        <div class="row">
            <div class="col">
                <select id="vehicule" class="form-control" name="vehicule">
                    <option name="default_driver" value="">Seleccione...</option>
                    <?php if (isset($vehicule_stmt) && $vehicule_stmt): ?>
                        <?php while($vehicule = $vehicule_stmt->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($vehicule['id']); ?>">
                                <?php echo htmlspecialchars($vehicule['matriculate']); ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col">
                <a href=""><i class="fa fa-edit"></i></a>
                <a href=""><i class="fa fa-trash"></i></a>
            </div>
        </div>
    </div>
</body>