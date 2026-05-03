<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}
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
    <title>Clientes</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div class="p-3 d-flex flex-column">
        <input type="text" id="search_input" onkeyup="searchGClientsTable()" placeholder="Buscar por nombre..">
        <table class="table table-bordered table-hover" id="clientsTable">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Nombre</th>
                    <th scope="col">CI</th>
                    <th scope="col">Municipio</th>
                    <th scope="col">Provincia</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    include '../api/db_connect.php';

                    $clientsList_stmt = $conn->query("SELECT * FROM `clients` ORDER BY `name`");

                    while ($generalClient = $clientsList_stmt->fetch_assoc()) {

                ?>
                    <tr>  
                        <td style="font-size: 15px">
                            <a class="link-dark link-underline link-underline-opacity-0" href="./details.php?ci=<?php echo $generalClient['ci'] ?>">
                                <?php echo $generalClient['name'] ?>
                            </a>
                        </td>
                        <td style="font-size: 15px"><?php echo $generalClient['ci'] ?></td>
                        <td style="font-size: 15px"><?php echo $generalClient['city'] ?></td>
                        <td style="font-size: 15px"><?php echo $generalClient['state'] ?></td>
                    </tr>
                <?php 
                    }
                ?>

                
            </tbody>
        </table>
    </div>
</body>

</html>