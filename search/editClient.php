<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}

// Validate CI input
if (isset($_GET['ci'])) {
    $ci = filter_input(INPUT_GET, 'ci', FILTER_SANITIZE_STRING);
    
    // Adjust pattern based on your CI format (e.g., numbers, letters, hyphens)
    if (!preg_match('/^[a-zA-Z0-9-]+$/', $ci)) {
        $_SESSION['error_message'] = "Invalid client ID format";
        header("Location: ./details.php");
        exit();
    }
    
    include '../api/getClientsDetails.php';
    
    clientDetails($ci);

} else {
    header("Location: ./details.php");
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
    <title>Editar Cliente</title>
</head>
<body>
    <?php include '../header.php' ?>

    <div class="container p-3 flex-column align-items-center">
        <div class="header">
            <h1>Editar Cliente</h1>
        </div>
        <form method="POST" action="../api/updateClient.php">
            <div class="form-control">
                <div class="row p-2">
                    <div class="col">
                        <label for="client_name" class="form-label">Nombre y Apellidos</label>  
                        <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo $client_name ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="client_ci" class="form-label">Carnet de Identidad</label>  
                        <input type="text" class="form-control" id="client_ci" name="client_ci" value="<?php echo $client_ci ?>" autocomplete="off" required>
                    </div>
                    <div class="col">
                        <label for="client_phone" class="form-label">Telefono(s)</label>  
                        <input type="text" class="form-control" id="client_phone" name="client_phone" value="<?php echo $client_phone ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="client_address" class="form-label">Direccion</label>  
                        <input type="textarea" class="form-control" id="client_address" name="client_address" value="<?php echo $client_address ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="row p-2">
                    <div class="col">
                        <label for="client_city" class="form-label">Municipio</label>  
                        <input type="text" class="form-control" id="client_city" name="client_city" value="<?php echo $client_city ?>" autocomplete="off" required>
                    </div>
                    <div class="col">
                        <label for="client_state" class="form-label">Provincia</label>  
                        <input type="text" class="form-control" id="client_state" name="client_state" value="<?php echo $client_state ?>" autocomplete="off" required>
                    </div>
                </div>
                <div class="container">
                    <div class="row p-2">
                        <a class="btn btn-warning" href="./details.php?ci=<?php echo urlencode($client_ci) ?>">Atras</a>
                    </div>
                    <div class="row p-2">
                        <input type="submit" class="btn btn-success" value="Guardar">
                    </div>
                </div>
            </div>
        </form>
    </div>
</body>