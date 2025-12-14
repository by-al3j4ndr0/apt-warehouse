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
    <link rel="stylesheet" href="resources/css/dashboard.css">
    <link rel="stylesheet" href="resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="resources/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"
  content="width=device-width, initial-scale=1.0">
    <title>Inicio</title>
</head>

<body>
    <nav class="navbar navbar-expand-sm navbar-light bg-success">
        <div class="container">
            <a class="navbar-brand" href="#" style="font-weight:bold; color:white;">Inicio</a>
            <a class="navbar-brand" href="clients/clients.php" style="font-weight:bold; color:white;">Clientes</a>
            <a class="navbar-brand" href="shipments/shipments.php" style="font-weight:bold; color:white;">Rutas</a>
            <button class="navbar-toggler d-lg-none" type="button" data-bs-toggle="collapse"
                data-bs-target="#collapsibleNavId" aria-controls="collapsibleNavId" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="collapsibleNavId">
                <ul class="navbar-nav m-auto mt-2 mt-lg-0">
                </ul>
                <form class="d-flex my-2 my-lg-0">
                    <a href="./logout.php" class="btn btn-light my-2 my-sm-0"
                      type="submit" style="font-weight:bolder;color:green;">
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