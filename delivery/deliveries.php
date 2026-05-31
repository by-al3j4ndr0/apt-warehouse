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
        <?php if (isset($_SESSION['error_message']) && !empty($_SESSION['error_message'])): ?>
            <div class="toast align-items-center text-white bg-danger border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php 
                            echo htmlspecialchars($_SESSION['error_message']);
                            unset($_SESSION['error_message']); // Limpiar después de mostrar
                        ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
            <div class="toast align-items-center text-white bg-success border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php 
                            echo htmlspecialchars($_SESSION['success_message']);
                            unset($_SESSION['success_message']);
                        ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="container p-3 d-flex flex-column align-items-center">
        <form class="d-flex my-2 my-lg-0">
            <a href="manage_delivery.php?model=new_delivery" class="btn btn-light my-2 my-sm-0"
            type="submit" style="font-weight:bolder;color:black;">
            Adicionar Ruta</a>
        </form>
    </div>
    <div class="p-5 d-flex flex-column align-items-left">  
        <input type="text" id="search_input" onkeyup="searchTable()" placeholder="Buscar por ID..">
        <table class="table table-light" id="table">
            <thead>
                <tr class="header">
                    <th scope="col">ID</th>
                    <th scope="col">Nombre</th>
                    <th scope="col">Estado</th>
                    <th scope="col">Origen</th>
                    <th scope="col">Chofer</th>
                    <th scope="col">Arancel</th>
                    <th scope="col">Paquetes</th>
                </tr>
            </thead>
            <tbody>
                <?php

                    include '../api/db_connect.php';
                    include '../api/getInfoById.php';

                    $status = "";

                    $deliveries_stmt = $conn->query("SELECT * FROM `delivery` WHERE `id` > '760' ORDER BY `id` desc");

                    while ($delivery = $deliveries_stmt->fetch_assoc()) {

                        if ($delivery['status'] == 'finished') {
                            $update = "";
                            $delete = "";
                            $status = '<td style="color: #198754">TERMINADA</td>';
                        } else if($delivery['status'] == 'delivering'){
                            $update = "fa fa-edit";
                            $delete = "";
                            $status = '<td style="color: #0d6efd">ENTREGANDO</td>';
                        } else if($delivery['status'] == 'draft') {
                            $update = "fa fa-edit";
                            $delete = "fa fa-trash";
                            $status = '<td style="color: #ffc107;">BORRADOR</td>';
                        }
                ?>
                    <tr>  
                        <td><?php echo $delivery['id'] ?></td>
                        <td><?php echo $delivery['name'] ?></td>
                        <?php echo $status ?>
                        <td><?php echo getInfoById($delivery['origen'], 'origen')['name'] ?></td>
                        <td><?php echo getInfoById($delivery['driver'], 'driver')['name']?></td>
                        <td><?php echo "$" . $delivery['total_tariff'] ?></td>
                        <td><?php echo $delivery['total_shipments'] ?></td>
                        <td><a href="manage_delivery.php?model=update_delivery&id=<?php echo $delivery['id'] ?>" ><i class="<?php echo $update ?>"></a></td>
                        <td><a target="_blank" href="../pdf/template.php?id=<?php echo $delivery['id'] ?>"><i class="fa fa-print"></a></td>
                        <td><a href="../api/deleteDelivery.php?id=<?php echo $delivery['id'] ?>"><i class="<?php echo $delete ?>"></a></td>
                        <td><a href="../api/exportRouteInfo.php?id=<?php echo $delivery['id'] ?>"><i class="fa fa-external-link"></a></td>
                    </tr>
                <?php 
                    }   
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>