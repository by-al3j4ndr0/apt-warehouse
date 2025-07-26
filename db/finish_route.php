<?php
    if (isset($_GET['id']))
        finishShipment($_GET['id'])
?>
<?php
function finishShipment($id) {
    include '../db/db_connect.php';

        $id = $id;
        $status = 'finished';
        
        try {
            $clients_stmt = $conn->query("SELECT `shipments` FROM `delivery` WHERE `id` = $id;");
            $clients_array = $clients_stmt->fetch_assoc();
            $clients_ci = substr($clients_array['shipments'], 0, -1);
            $clients_ci = explode(", ", $clients_ci);

            $stmt = $conn->prepare("UPDATE `delivery` SET `status` = ? WHERE `id` = ?");
            $stmt->bind_param("ss", $status, $id);
            $stmt->execute();

            foreach ($clients_ci as $client) {
                // Validate and sanitize the input
                $client_id = (string)$client; 
                $status = (string)$status;  
                
                // Use prepared statement to prevent SQL injection
                $stmt = $conn->prepare("UPDATE `shipments` SET `status` = ? WHERE `ci` = ? AND `status` = 'delivering'");
                $stmt->bind_param("ss", $status, $client_id);
                $stmt->execute();
            }

        } catch(Exception $e) {
            echo $e;
        }
        
        header("Location: ../shipments.php");
}

?>