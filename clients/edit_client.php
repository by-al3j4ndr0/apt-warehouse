<?php 
function clientDetails($ci) {

    include '../db/db_connect.php';

    global $client_name, $client_ci, $client_phone, $client_address, $client_city, $client_state;

    try {

        $client_info_stmt = $conn->prepare("SELECT * FROM `clients` WHERE `ci` = ?");
        $client_info_stmt->bind_param("s", $ci);
        $client_info_stmt->execute();

        $client_info_result = $client_info_stmt->get_result();
        $client_info_data = $client_info_result->fetch_assoc();

        $client_info_stmt->close();

        $client_name = $client_info_data['name'];
        $client_ci = $client_info_data['ci'];
        $client_phone = $client_info_data['phone'];
        $client_address = $client_info_data['address'];
        $client_city = $client_info_data['city'];
        $client_state = $client_info_data['state'];

    } catch (Exception $e) {
         // Rollback on error
        $conn->rollback();
        
        // Log error (in production, use proper logging)
        error_log("Clients details getting error: " . $e->getMessage());
        
        $_SESSION['error_message'] = "Failed to create delivery: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../resources/css/custom.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="stylesheet" href="../resources/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <meta charset="UTF-8">
    <meta name="viewport"
  content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente</title>
</head>

<body>
    <?php include '../header.php' ?>
</body>