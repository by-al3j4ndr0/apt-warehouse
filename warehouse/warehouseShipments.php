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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/css/warehouseShipments.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title>Almacen</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div id="app">
        <div class="loading">Cargando datos...</div>
    </div>

    <script src="../resources/js/warehouseShipments.js"></script>
</body>
</html>