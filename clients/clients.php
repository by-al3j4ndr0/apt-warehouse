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

    <div class="container p-3 d-flex flex-column">
        <input type="text" id="search_input" onkeyup="searchTable()" placeholder="Buscar por nombre..">
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

                    include '../api/db_connect.php';

                    $stmt = $conn->query("SELECT * FROM `clients`");

                    while ($row = $stmt->fetch_assoc()) {

                ?>
                    <tr>  
                        <td style="font-size: 15px">
                            <a class="link-dark link-underline link-underline-opacity-0" href="./details.php?ci=<?php echo $row['ci'] ?>">
                                <?php echo $row['name'] ?>
                            </a>
                        </td>
                        <td style="font-size: 15px"><?php echo $row['ci'] ?></td>
                        <td style="font-size: 15px"><?php echo $row['phone'] ?></td>
                        <td style="font-size: 15px"><?php echo $row['address'] ?></td> 
                        <td style="font-size: 15px"><?php echo $row['city'] ?></td>
                        <td style="font-size: 15px"><?php echo $row['state'] ?></td>
                    </tr>
                <?php 
                    }
                ?>

                
            </tbody>
        </table>
    </div>
</body>

</html>