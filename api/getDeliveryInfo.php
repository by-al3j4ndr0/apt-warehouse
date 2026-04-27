<?php

function getDeliveryInfo(int $delivery_id) {

    include '../api/db_connect.php';

    $delivery_stmt = $conn->prepare("SELECT * FROM `delivery` WHERE `id` = ?");
    $delivery_stmt->bind_param("i", $delivery_id);
    $delivery_stmt->execute();
    $delivery_result = $delivery_stmt->get_result();
    $delivery_data = $delivery_result->fetch_assoc();

    return $delivery_data;

}



?>