<?php
    if (isset($_GET['id']))
        deleteShipment($_GET['id'])
?>
<?php
function deleteShipment($id) {
    include '../db/db_connect.php';
        
    $status = 'warehouse';
    
    try {
        $stmt = $conn->prepare("DELETE FROM `delivery` WHERE `id` = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE `shipments` SET `status` = ?, `route_id` = 0 WHERE `route_id` = ?");
        $stmt->bind_param("ss", $status, $id);
        $stmt->execute();

    } catch(Exception $e) {
        echo $e;
    }
        
    header("Location: ../shipments/shipments.php");
}

?>