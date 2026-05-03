<?php
    if (isset($_GET['id']))
        deleteShipment($_GET['id'])
?>
<?php
function deleteShipment($id) {
    include '../api/db_connect.php';
        
    $status = 'warehouse';
    
    try {
        $stmt = $conn->prepare("DELETE FROM `delivery` WHERE `id` = ?");
        $stmt->bind_param("s", $id);
        $stmt->execute();

        $stmt = $conn->prepare("UPDATE `shipments` SET `status` = ?, `route_id` = 0 WHERE `route_id` = ?");
        $stmt->bind_param("ss", $status, $id);
        $stmt->execute();

        $_SESSION['success_message'] = "Delivery route removed successfully!";
        header("Location: ../delivery/deliveries.php");
        exit();

    } catch(Exception $e) {
        $_SESSION['error_message'] = "Failed to remove delivery: " . $e->getMessage();
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

?>