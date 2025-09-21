<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
} else if (isset($_GET['ci'])) {
    clientDetails($_GET['ci']);
}
?>
<?php
function clientDetails($ci) {
    include_once '../db/db_connect.php';

    $client_info_stmt = $conn->prepare("SELECT * FROM `clients` WHERE `ci` = ?");
    $client_info_stmt->bind_param("s", $ci);
    $client_info_stmt->execute();

    $client_info_row = $client_info_stmt->get_result();

    $client_name = $client_info_row['name'];
    $client_ci = $client_info_row['ci'];
    $client_phone = $client_info_row['phone'];
    $client_address = $client_info_row['address'];
    $client_city = $client_info_row['city'];
    $client_state = $client_info_row['state'];
}