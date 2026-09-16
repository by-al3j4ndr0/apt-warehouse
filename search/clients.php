<?php
session_start();

// Check if the user is logged in, if
// not then redirect them to the login page
if (!isset($_SESSION['username'])) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../resources/css/search.css">
    <link rel="stylesheet" href="../resources/css/font-awesome-all.css">
    <link rel="stylesheet" href="../resources/css/bootstrap.min.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="../resources/js/bootstrap.bundle.min.js"></script>
    <meta charset="UTF-8">
    <meta name="viewport"content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
</head>

<body>
    <?php include '../header.php' ?>

    <div class="p-3 d-flex flex-column">
        <div id="clients-search-app"></div>
    </div>

    <script src="../resources/js/searchClients.js"></script>
</body>

</html>