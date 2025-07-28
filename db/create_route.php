<?php
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = $_POST['name'];
    $driver = $_POST['driver'];
    $vehicule = $_POST['vehicule'];
    $status = 'delivering';
    
    if ($_POST['clients']) {
        $clients = '';
        $num_clients = count($_POST['clients']);
        foreach ($_POST['clients'] as $key => $value) {
            if ($num_clients > 1) {
                $clients .= $value.', ';
            } else {
                $clients .= $value;
            }
            $num_clients--;
        }
    }

    $stmt = $conn->query("INSERT INTO `delivery`(
                                        `name`, 
                                        `driver`, 
                                        `vehicule`, 
                                        `status`, 
                                        `shipments`) 
                        VALUES ('$name','$driver','$vehicule','$status','$clients')");

    

    foreach ($_POST['clients'] as $key => $client) {
        // Validate and sanitize the input
        $client_id = (string)$client; 
        $status = (string)$status;  
        
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("UPDATE `shipments` SET `status` = ? WHERE `ci` = ? AND `status` = 'warehouse'");
        $stmt->bind_param("ss", $status, $client_id);
        $stmt->execute();
    }

    header("Location: ../shipments/shipments.php");
}
?>