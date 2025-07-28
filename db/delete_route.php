<?php
    if (isset($_GET['id']))
        deleteShipment($_GET['id'])
?>
<?php
function deleteShipment($id) {
    include '../db/db_connect.php';

        $id = $id;
        
        try {
            $clients_stmt = $conn->query("SELECT `shipments` FROM `delivery` WHERE `id` = $id;");
            $clients_array = $clients_stmt->fetch_assoc();
            $clients_ci = substr($clients_array['shipments'], 0, -1);
            $clients_ci = explode(", ", $clients_ci);

            $stmt = $conn->prepare("DELETE FROM `delivery` WHERE `id` = ?");
            $stmt->bind_param("s", $id);
            $stmt->execute();

            foreach ($clients_ci as $client) {
                // Validate and sanitize the input
                $client_id = (string)$client; 
                $status = (string)$status;  
                
                // Use prepared statement to prevent SQL injection
                $stmt = $conn->prepare("UPDATE `shipments` SET `status` = 'warehouse' WHERE `ci` = ? AND `status` = 'delivering'");
                $stmt->bind_param("s", $client_id);
                $stmt->execute();
            }

        } catch(Exception $e) {
            echo $e;
        }
        
        header("Location: ../shipments/shipments.php");
}

?>