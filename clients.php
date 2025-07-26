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
    <link rel="stylesheet" href="resources/css/custom.css">
    <link rel="stylesheet" href="resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="resources/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="resources/js/bootstrap.bundle.min.js"></script>
    <script src="resources/js/custom.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"
  content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
</head>

<body>
    <nav class="navbar navbar-expand-sm navbar-light bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php" style="font-weight:bold; color:white;">Inicio</a>
            <a class="navbar-brand" href="#" style="font-weight:bold; color:white;">Clientes</a>
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
                    <a href="./logout.php" class="btn btn-light my-2 my-sm-0"
                      type="submit" style="font-weight:bolder;color:green;">
                        Cerrar Sesion</a>
                </form>
            </div>
        </div>
    </nav>

    <div class="container p-5 d-flex flex-column align-items-left">
        <!-- <form class="d-flex my-2 my-lg-0" action="api/uploadmanifest.php" method="post"
        enctype="multipart/form-data">    
            <input class="btn btn-light" type="file" name="manifest" id="manifest">    
            <input class="btn btn-light" type="submit" name="upload"
            value="Subir Manifiesto" style="font-weight:bolder;color:green;">
        </form> -->
        <input type="text" id="search_input" onkeyup="myFunction()" placeholder="Buscar por nombre..">
        <table class="table table-light" id="table">
            <thead>
                <tr class="header">
                    <th scope="col" onclick="sortTable(0)">Nombre</th>
                    <th scope="col" onclick="sortTable(1)">CI</th>
                    <th scope="col" onclick="sortTable(2)">Telefono</th>
                    <th scope="col" onclick="sortTable(3)">Direccion</th>
                    <th scope="col" onclick="sortTable(4)">Municipio</th>
                    <th scope="col" onclick="sortTable(5)">Provincia</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    include './db/db_connect.php';

                    $stmt = $conn->query("SELECT * FROM `clients`");

                    while ($row = $stmt->fetch_assoc()) {

                ?>
                    <tr>  
                        <td><?php echo $row['name'] ?></td>
                        <td><?php echo $row['ci'] ?></td>
                        <td><?php echo $row['phone'] ?></td>
                        <td><?php echo $row['address'] ?></td> 
                        <td><?php echo $row['city'] ?></td>
                        <td><?php echo $row['state'] ?></td>
                    </tr>
                <?php 
                    }
                ?>

                
            </tbody>
        </table>
    </div>
</body>

</html>