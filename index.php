<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="./resources/css/custom.css">
    <link rel="stylesheet" href="./resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="./resources/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="./resources/js/bootstrap.bundle.min.js"></script>
    <script src="./resources/js/custom.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="clients/clients.php">Clientes</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="delivery/deliveries.php">Rutas</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link active dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" 
                        data-bs-toggle="dropdown" aria-expanded="false">
                            Ajustes
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="settings/uploadManifest.php">Subir Manifiesto</a></li>
                            <li><a class="dropdown-item" href="settings/manageDriversVehicules.php">Choferes y Vehiculos</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav m-auto mt-2 mt-lg-0">
                </ul>
                <form class="d-flex my-2 my-lg-0">
                    <a href="../logout.php" class="btn btn-outline-dark my-2 my-sm-0"
                      type="submit">
                        Cerrar Sesion</a>
                </form>
            </div>
        </div>
    </nav>

    <div class="container p-5 d-flex">
        <h2 class="p-4 mt-5">Bienvenido/a <?php echo $_SESSION['first_name'] ?></h2>
    </div>
</body>

</html>