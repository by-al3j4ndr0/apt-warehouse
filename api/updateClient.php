<?php 
    session_start();
    include './db_connect.php';

    $clientCI = $_POST['client_ci'];
    $clientName = $_POST['client_name'];
    $clientPhone = $_POST['client_phone'];
    $clientAddress = $_POST['client_address'];
    $clientCity = $_POST['client_city'];
    $clientState = $_POST['client_state'];

    try {
        $updateClient_stmt = $conn->prepare("UPDATE `clients` SET `ci` = ?, `name` = ?, `phone` = ?, `address` = ?, `city` = ?, `state` = ? WHERE `ci` = ?");
        $updateClient_stmt->bind_param("sssssss", $clientCI, $clientName, $clientPhone, $clientAddress, $clientCity, $clientState, $clientCI);
        $updateClient_stmt->execute();

        header("Location: ../search/details.php?ci=" . $clientCI);
        exit();
    } catch (Exception $e) {
        error_log("Error en updateClient.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Error al actualizar el cliente']);
    }
    
    $conn->close();

    

?>