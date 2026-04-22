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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <script src="../resources/js/custom.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"
  content="width=device-width, initial-scale=1.0">
    <title>Rutas</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div class="container p-3 d-flex flex-column align-items-center">  
        <form class="d-flex my-2 my-lg-0">
            <a href="manage_delivery.php?model=new_delivery" class="btn btn-light my-2 my-sm-0"
            type="submit" style="font-weight:bolder;color:green;">
            Adicionar Ruta</a>
        </form>
    </div>
    <div class="container p-5 d-flex flex-column align-items-left">  
        <input type="text" id="search_input" onkeyup="searchTable()" placeholder="Buscar por ID..">
        <table class="table table-light" id="table">
            <thead>
                <tr class="header">
                    <th scope="col" onclick="sortTable(0)">ID</th>
                    <th scope="col" onclick="sortTable(1)">Nombre</th>
                    <th scope="col" >Origen</th>
                    <th scope="col" onclick="sortTable(2)">Chofer</th>
                    <th scope="col" onclick="sortTable(2)">Arancel</th>
                    <th scope="col" onclick="sortTable(2)">Paquetes</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    include '../api/db_connect.php';

                    $stmt = $conn->query("SELECT * FROM `delivery` ORDER BY `id` desc");

                    while ($row = $stmt->fetch_assoc()) {
                        if ($row['status'] == 'finished') {
                            $update = "";
                            $delete = "";
                        } else {
                            $update = "fa fa-edit";
                            $delete = "fa fa-trash";
                        }
                ?>
                    <tr>  
                        <td><?php echo $row['id'] ?></td>
                        <td><?php echo $row['name'] ?></td>
                        <td><?php echo $row['origen'] ?></td>
                        <td><?php echo $row['driver'] ?></td>
                        <td><?php echo "$" . $row['total_tariff'] ?></td>
                        <td><?php echo $row['total_shipments'] ?></td>
                        <td><a href="update_shipment.php?id=<?php echo $row['id'] ?>" ><i class="<?php echo $update ?>"></a></td>
                        <td><a target="_blank" href="../pdf/template.php?id=<?php echo $row['id'] ?>"><i class="fa fa-print"></a></td>
                        <td><a href="../api/delete_route.php?id=<?php echo $row['id'] ?>"><i class="<?php echo $delete ?>"></a></td>
                    </tr>
                <?php 
                    }   
                ?>
            </tbody>
        </table>
    </div>
</body>

</html>