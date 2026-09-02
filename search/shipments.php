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
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <script src="../resources/js/custom.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title>Envios</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div class="p-3 d-flex flex-column">
        <input type="text" id="search_input" onkeyup="searchGShipmentsTable()" placeholder="Buscar por nombre..">
        <table class="table table-bordered table-hover" id="shipmentsTable">
            <thead class="table-dark">
                <tr>
                    <th scope="col">Origen</th>
                    <th scope="col">HBL</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Manifiesto</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    include '../api/db_connect.php';
                    include '../api/getInfoById.php';

                    $shipmentList_stmt = $conn->query("SELECT * FROM `shipments` ORDER BY `hbl`");

                    while ($generalShipment = $shipmentList_stmt->fetch_assoc()) {

                ?>
                    <tr style="">
                        <td style="font-size: 15px"><?php echo getInfoById($generalShipment['origen'], "origen")['origen'] ?></td>  
                        <td style="font-size: 15px">
                            <a class="link-dark link-underline link-underline-opacity-0" href="">
                                <?php echo $generalShipment['hbl'] ?>
                            </a>
                        </td>
                        <td style="font-size: 15px"><?php echo $generalShipment['status'] ?></td>
                        <td style="font-size: 15px"><?php echo $generalShipment['manifest'] ?></td>
                    </tr>
                <?php 
                    }
                ?>

                
            </tbody>
        </table>
    </div>
</body>

</html>